<?php

namespace Classifai\Features;

use Classifai\Services\LanguageProcessing;
use Classifai\Providers\OpenAI\ChatGPT;
use Classifai\Providers\Azure\OpenAI;
use Classifai\Providers\Localhost\Ollama;
use WP_REST_Server;
use WP_REST_Request;
use WP_Error;

use function Classifai\get_asset_info;
use function Classifai\sanitize_prompts;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class KeyTakeaways
 */
class KeyTakeaways extends Feature {

	/**
	 * ID of the current feature.
	 *
	 * @var string
	 */
	const ID = 'feature_key_takeaways';

	/**
	 * Meta key storing the generated takeaways.
	 *
	 * @var string
	 */
	const TAKEAWAYS_META_KEY = '_classifai_key_takeaways';

	/**
	 * Meta key storing the content hash at the time takeaways were generated.
	 *
	 * Used to invalidate stored takeaways when a post is edited.
	 *
	 * @var string
	 */
	const TAKEAWAYS_HASH_KEY = '_classifai_key_takeaways_hash';

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->label = __( 'Key Takeaways', 'classifai' );

		// Contains all providers that are registered to the service.
		$this->provider_instances = $this->get_provider_instances( LanguageProcessing::get_service_providers() );

		// Contains just the providers this feature supports.
		$this->supported_providers = array(
			ChatGPT::ID => __( 'OpenAI ChatGPT', 'classifai' ),
			OpenAI::ID  => __( 'Azure OpenAI', 'classifai' ),
			Ollama::ID  => __( 'Ollama', 'classifai' ),
		);
	}

	/**
	 * Set up necessary hooks.
	 *
	 * This is always run so useful if we need to register
	 * things even if the feature is not enabled, not configured
	 * or the user does not have access.
	 */
	public function setup() {
		parent::setup();
		add_action( 'rest_api_init', array( $this, 'register_endpoints' ) );

		if ( $this->is_configured() && $this->is_enabled() ) {
			add_action( 'enqueue_block_assets', array( $this, 'enqueue_editor_assets' ) );
			$this->register_block();

			if ( 'on_demand' === $this->get_generation_timing() ) {
				add_filter( 'the_content', array( $this, 'render_takeaways_button' ) );
				add_action( 'save_post', array( $this, 'maybe_invalidate_on_demand_takeaways' ), 20 );
			}
		}
	}

	/**
	 * Set up necessary hooks.
	 *
	 * Only fires if the feature is enabled, configured and user has access.
	 */
	public function feature_setup() {
	}

	/**
	 * Register the block used for this feature.
	 */
	public function register_block() {
		register_block_type_from_metadata(
			CLASSIFAI_PLUGIN_DIR . '/includes/Classifai/Blocks/key-takeaways', // this is the directory where the block.json is found.
		);
	}

	/**
	 * Register any needed endpoints.
	 */
	public function register_endpoints() {
		register_rest_route(
			'classifai/v1',
			'key-takeaways(?:/(?P<id>\d+))?',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'rest_endpoint_callback' ),
					'args'                => array(
						'id'     => array(
							'required'          => true,
							'type'              => 'integer',
							'sanitize_callback' => 'absint',
							'description'       => esc_html__( 'Post ID to generate key takeaways for.', 'classifai' ),
						),
						'render' => array(
							'type'              => 'string',
							'enum'              => array(
								'list',
								'paragraph',
							),
							'sanitize_callback' => 'sanitize_text_field',
							'validate_callback' => 'rest_validate_request_arg',
							'description'       => esc_html__( 'How the key takeaways should be rendered.', 'classifai' ),
						),
						'run'    => array(
							'type'              => 'string',
							'enum'              => array(
								'auto',
								'manual',
							),
							'default'           => 'auto',
							'sanitize_callback' => 'sanitize_text_field',
							'validate_callback' => 'rest_validate_request_arg',
							'description'       => esc_html__( 'Whether the key takeaways were generated automatically or manually.', 'classifai' ),
						),
					),
					'permission_callback' => array( $this, 'generate_key_takeaways_permissions_check' ),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'rest_endpoint_callback' ),
					'args'                => array(
						'content' => array(
							'required'          => true,
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
							'validate_callback' => 'rest_validate_request_arg',
							'description'       => esc_html__( 'Content to generate key takeaways from.', 'classifai' ),
						),
						'title'   => array(
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
							'validate_callback' => 'rest_validate_request_arg',
							'description'       => esc_html__( 'Title of content to generate key takeaways from.', 'classifai' ),
						),
						'render'  => array(
							'type'              => 'string',
							'enum'              => array(
								'list',
								'paragraph',
							),
							'sanitize_callback' => 'sanitize_text_field',
							'validate_callback' => 'rest_validate_request_arg',
							'description'       => esc_html__( 'How the key takeaways should be rendered.', 'classifai' ),
						),
						'run'     => array(
							'type'              => 'string',
							'enum'              => array(
								'auto',
								'manual',
							),
							'default'           => 'auto',
							'sanitize_callback' => 'sanitize_text_field',
							'validate_callback' => 'rest_validate_request_arg',
							'description'       => esc_html__( 'Whether the key takeaways were generated automatically or manually.', 'classifai' ),
						),
					),
					'permission_callback' => array( $this, 'generate_key_takeaways_permissions_check' ),
				),
			)
		);

		// Public, front-end on-demand generation route.
		register_rest_route(
			'classifai/v1',
			'key-takeaways-on-demand/(?P<id>\d+)',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'generate_key_takeaways_on_demand' ),
					'args'                => array(
						'id' => array(
							'required'          => true,
							'type'              => 'integer',
							'sanitize_callback' => 'absint',
							'description'       => esc_html__( 'ID of the published post to generate key takeaways for.', 'classifai' ),
						),
					),
					'permission_callback' => array( $this, 'on_demand_takeaways_permissions_check' ),
				),
			)
		);
	}

	/**
	 * Permission check for the public, front-end on-demand generation route.
	 *
	 * Unlike {@see generate_key_takeaways_permissions_check()}, this route is
	 * reachable by anonymous visitors, so it is gated on the feature being
	 * enabled and in on-demand mode, the target being a published supported
	 * post, and a valid REST nonce.
	 *
	 * Because takeaways are generated at most once per post, the cost ceiling is
	 * one generation per published post. Site owners can tighten or loosen this
	 * via the `classifai_key_takeaways_on_demand_permission` filter.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return bool|WP_Error
	 */
	public function on_demand_takeaways_permissions_check( WP_REST_Request $request ) {
		$post_id = (int) $request->get_param( 'id' );
		$post    = $post_id ? get_post( $post_id ) : null;

		$allowed = (
			$post instanceof \WP_Post &&
			'publish' === $post->post_status &&
			in_array( $post->post_type, $this->get_supported_post_types(), true ) &&
			$this->is_enabled() &&
			'on_demand' === $this->get_generation_timing() &&
			false !== wp_verify_nonce( (string) $request->get_header( 'X-WP-Nonce' ), 'wp_rest' )
		);

		/**
		 * Filter the permission check for on-demand front-end key takeaways generation.
		 *
		 * Return true to allow, or false / a WP_Error to deny. Use this to, for
		 * example, restrict generation to logged-in users only.
		 *
		 * @since x.x.x
		 * @hook classifai_key_takeaways_on_demand_permission
		 *
		 * @param bool            $allowed Whether the request is allowed.
		 * @param int             $post_id The post ID takeaways are being generated for.
		 * @param WP_REST_Request $request The REST request.
		 *
		 * @return bool|WP_Error Whether the request is allowed.
		 */
		return apply_filters( 'classifai_key_takeaways_on_demand_permission', $allowed, $post_id, $request );
	}

	/**
	 * Handle a front-end on-demand key takeaways generation request.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return \WP_REST_Response
	 */
	public function generate_key_takeaways_on_demand( WP_REST_Request $request ) {
		$post_id = (int) $request->get_param( 'id' );
		$render  = $this->get_settings( 'render' );
		$render  = in_array( $render, array( 'list', 'paragraph' ), true ) ? $render : 'list';

		// Takeaways may already exist, serve them without regenerating.
		$stored = get_post_meta( $post_id, self::TAKEAWAYS_META_KEY, true );
		if ( ! empty( $stored ) && is_array( $stored ) ) {
			return rest_ensure_response(
				array(
					'success'   => true,
					'takeaways' => $stored,
					'html'      => $this->render_takeaways_html( $stored, $render ),
				)
			);
		}

		$lock_key = 'classifai_key_takeaways_on_demand_lock_' . $post_id;

		// Only allow one request to generate at a time.
		if ( get_transient( $lock_key ) ) {
			return rest_ensure_response(
				array(
					'success'    => false,
					'inProgress' => true,
					'code'       => 'generation_in_progress',
					'message'    => esc_html__( 'Key takeaways are already being generated. Please try again in a moment.', 'classifai' ),
				)
			);
		}

		set_transient( $lock_key, 1, 5 * MINUTE_IN_SECONDS );

		// Generate as the post author so the feature's per-user access
		// check passes for anonymous front-end visitors.
		$post             = get_post( $post_id );
		$original_user_id = get_current_user_id();
		wp_set_current_user( $post ? (int) $post->post_author : $original_user_id );

		$result = $this->run(
			$post_id,
			'key_takeaways',
			array(
				'render' => $render,
				'run'    => 'manual',
			)
		);

		wp_set_current_user( $original_user_id );

		delete_transient( $lock_key );

		if ( is_wp_error( $result ) ) {
			return rest_ensure_response(
				array(
					'success' => false,
					'code'    => 'generation_failed',
					'message' => $result->get_error_message(),
				)
			);
		}

		$takeaways = (array) $result;

		if ( empty( $takeaways ) ) {
			return rest_ensure_response(
				array(
					'success' => false,
					'code'    => 'generation_failed',
					'message' => esc_html__( 'Key takeaways could not be generated.', 'classifai' ),
				)
			);
		}

		update_post_meta( $post_id, self::TAKEAWAYS_META_KEY, $takeaways );
		update_post_meta( $post_id, self::TAKEAWAYS_HASH_KEY, $this->get_content_hash( $post_id ) );

		return rest_ensure_response(
			array(
				'success'   => true,
				'takeaways' => $takeaways,
				'html'      => $this->render_takeaways_html( $takeaways, $render ),
			)
		);
	}

	/**
	 * Clear stored takeaways when an on-demand post's content changes.
	 *
	 * @param int $post_id The post ID being saved.
	 */
	public function maybe_invalidate_on_demand_takeaways( int $post_id ) {
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || 'revision' === get_post_type( $post_id ) ) {
			return;
		}

		if (
			'on_demand' !== $this->get_generation_timing() ||
			! in_array( get_post_type( $post_id ), $this->get_supported_post_types(), true ) ||
			! $this->is_enabled()
		) {
			return;
		}

		$stored = get_post_meta( $post_id, self::TAKEAWAYS_META_KEY, true );

		if ( empty( $stored ) ) {
			return;
		}

		$stored_hash  = get_post_meta( $post_id, self::TAKEAWAYS_HASH_KEY, true );
		$current_hash = $this->get_content_hash( $post_id );

		// Content unchanged, keep the existing takeaways.
		if ( ! empty( $stored_hash ) && $stored_hash === $current_hash ) {
			return;
		}

		delete_post_meta( $post_id, self::TAKEAWAYS_META_KEY );
		delete_post_meta( $post_id, self::TAKEAWAYS_HASH_KEY );
	}

	/**
	 * Returns a hash of the post content and title, used to detect edits.
	 *
	 * @param int $post_id The post ID.
	 * @return string
	 */
	public function get_content_hash( int $post_id ): string {
		$post = get_post( $post_id );

		if ( ! $post instanceof \WP_Post ) {
			return '';
		}

		return md5( $post->post_content . '|' . $post->post_title );
	}

	/**
	 * Render the inner markup for a set of takeaways.
	 *
	 * @param array  $takeaways Array of takeaway strings.
	 * @param string $render    Either `list` or `paragraph`.
	 * @return string
	 */
	public function render_takeaways_html( array $takeaways, string $render = 'list' ): string {
		if ( empty( $takeaways ) ) {
			return '';
		}

		ob_start();

		if ( 'list' === $render ) {
			echo '<ul>';
			foreach ( $takeaways as $takeaway ) {
				printf( '<li>%s</li>', esc_html( $takeaway ) );
			}
			echo '</ul>';
		} else {
			foreach ( $takeaways as $takeaway ) {
				printf( '<p>%s</p>', esc_html( $takeaway ) );
			}
		}

		return (string) ob_get_clean();
	}

	/**
	 * Render the on-demand "Key Takeaways" button on the front-end of singular posts.
	 *
	 * @param string $content The post content.
	 * @return string
	 */
	public function render_takeaways_button( string $content ): string {
		$post = get_post();

		if (
			! $post instanceof \WP_Post ||
			! is_singular( $post->post_type ) ||
			! in_array( $post->post_type, $this->get_supported_post_types(), true ) ||
			! in_the_loop() ||
			! is_main_query()
		) {
			return $content;
		}

		// If the post already includes the Key Takeaways block, it renders its
		// own takeaways inline, so the on-demand button would be redundant.
		if ( has_block( 'classifai/key-takeaways', $post ) ) {
			return $content;
		}

		$render = $this->get_settings( 'render' );
		$render = in_array( $render, array( 'list', 'paragraph' ), true ) ? $render : 'list';

		$label = $this->get_settings( 'button_label' );
		$label = is_string( $label ) && '' !== $label ? $label : esc_html__( 'Key Takeaways', 'classifai' );

		$stored      = get_post_meta( $post->ID, self::TAKEAWAYS_META_KEY, true );
		$has_results = ! empty( $stored ) && is_array( $stored );
		$panel_html  = $has_results ? $this->render_takeaways_html( $stored, $render ) : '';

		$this->enqueue_frontend_assets();

		$panel_id = 'classifai-key-takeaways-panel-' . $post->ID;

		ob_start();
		?>
		<div class="classifai-key-takeaways-wrapper">
			<button
				type="button"
				class="classifai-key-takeaways-toggle"
				aria-expanded="false"
				aria-controls="<?php echo esc_attr( $panel_id ); ?>"
				data-has-takeaways="<?php echo $has_results ? '1' : '0'; ?>"
				data-post-id="<?php echo esc_attr( (string) $post->ID ); ?>"
				data-rest-url="<?php echo esc_url( rest_url( 'classifai/v1/key-takeaways-on-demand/' . $post->ID ) ); ?>"
				data-nonce="<?php echo esc_attr( wp_create_nonce( 'wp_rest' ) ); ?>"
				data-generating-label="<?php esc_attr_e( 'Generating…', 'classifai' ); ?>"
				data-error-label="<?php esc_attr_e( 'Key takeaways could not be generated. Please try again.', 'classifai' ); ?>"
			>
				<span class="classifai-key-takeaways-toggle__label"><?php echo esc_html( $label ); ?></span>
				<span class="classifai-key-takeaways-toggle__chevron" aria-hidden="true"></span>
				<span class="classifai-key-takeaways-spinner" aria-hidden="true"></span>
			</button>
			<div id="<?php echo esc_attr( $panel_id ); ?>" class="classifai-key-takeaways-panel" hidden>
				<?php echo wp_kses_post( $panel_html ); ?>
			</div>
		</div>
		<?php
		$button = (string) ob_get_clean();

		/**
		 * Filter where the on-demand button is placed relative to the content.
		 *
		 * @since x.x.x
		 * @hook classifai_key_takeaways_button_position
		 *
		 * @param string $position One of `top` or `bottom`.
		 * @param int    $post_id  The post ID.
		 *
		 * @return string Either `top` or `bottom`.
		 */
		$position = apply_filters( 'classifai_key_takeaways_button_position', 'top', $post->ID );

		return 'bottom' === $position ? $content . $button : $button . $content;
	}

	/**
	 * Enqueue the front-end script and style for the on-demand button.
	 */
	public function enqueue_frontend_assets() {
		wp_enqueue_script(
			'classifai-plugin-key-takeaways-frontend-js',
			CLASSIFAI_PLUGIN_URL . 'dist/classifai-plugin-key-takeaways-frontend.js',
			get_asset_info( 'classifai-plugin-key-takeaways-frontend', 'dependencies' ),
			get_asset_info( 'classifai-plugin-key-takeaways-frontend', 'version' ),
			true
		);

		wp_enqueue_style(
			'classifai-plugin-key-takeaways-frontend-css',
			CLASSIFAI_PLUGIN_URL . 'dist/classifai-plugin-key-takeaways-frontend.css',
			array(),
			get_asset_info( 'classifai-plugin-key-takeaways-frontend', 'version' ),
			'all'
		);
	}

	/**
	 * Check if a given request has access to generate key takeaways.
	 *
	 * This check ensures we have a proper post ID, the current user
	 * making the request has access to that post, that we are
	 * properly authenticated and that the feature is turned on.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|bool
	 */
	public function generate_key_takeaways_permissions_check( WP_REST_Request $request ) {
		$post_id = $request->get_param( 'id' );

		// Ensure we have a logged in user that can edit the item.
		if ( empty( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return false;
		}

		$post_type     = get_post_type( $post_id );
		$post_type_obj = get_post_type_object( $post_type );

		// Ensure the post type is allowed in REST endpoints.
		if ( ! $post_type || empty( $post_type_obj ) || empty( $post_type_obj->show_in_rest ) ) {
			return false;
		}

		// Ensure the feature is enabled. Also runs a user check.
		if ( ! $this->is_feature_enabled() ) {
			return new WP_Error( 'not_enabled', esc_html__( 'Key takeaways not currently enabled.', 'classifai' ) );
		}

		return true;
	}

	/**
	 * Generic request handler for all our custom routes.
	 *
	 * @param WP_REST_Request $request The full request object.
	 * @return \WP_REST_Response
	 */
	public function rest_endpoint_callback( WP_REST_Request $request ) {
		$route = $request->get_route();

		if ( strpos( $route, '/classifai/v1/key-takeaways' ) === 0 ) {
			return rest_ensure_response(
				$this->run(
					$request->get_param( 'id' ),
					'key_takeaways',
					array(
						'content' => $request->get_param( 'content' ),
						'title'   => $request->get_param( 'title' ),
						'render'  => $request->get_param( 'render' ),
						'run'     => $request->get_param( 'run' ),
					)
				)
			);
		}

		return parent::rest_endpoint_callback( $request );
	}

	/**
	 * Enqueue the editor scripts.
	 */
	public function enqueue_editor_assets() {
		global $post;

		if ( empty( $post ) || ! is_admin() ) {
			return;
		}

		wp_register_script(
			'key-takeaways-editor-script',
			CLASSIFAI_PLUGIN_URL . 'dist/key-takeaways-block.js',
			get_asset_info( 'key-takeaways', 'dependencies' ),
			get_asset_info( 'key-takeaways', 'version' ),
			true
		);
	}

	/**
	 * Get the description for the enable field.
	 *
	 * @return string
	 */
	public function get_enable_description(): string {
		return esc_html__( 'A new block will be registered that when added to an item, will generate key takeaways from the content.', 'classifai' );
	}

	/**
	 * Returns the default settings for the feature.
	 *
	 * @return array
	 */
	public function get_feature_default_settings(): array {
		return array(
			'key_takeaways_prompt' => array(
				array(
					'title'    => esc_html__( 'ClassifAI default', 'classifai' ),
					'prompt'   => $this->get_prompt( 'default' ),
					'original' => 1,
				),
			),
			'generation_timing'    => 'manual',
			'post_types'           => array(
				'post' => 'post',
			),
			'render'               => 'list',
			'button_label'         => esc_html__( 'Key Takeaways', 'classifai' ),
			'provider'             => ChatGPT::ID,
		);
	}

	/**
	 * Returns the configured generation timing mode.
	 *
	 * @return string One of `manual`, `on_demand`.
	 */
	public function get_generation_timing(): string {
		$timing = $this->get_settings( 'generation_timing' );

		return array_key_exists( $timing, $this->get_generation_timing_options() ) ? $timing : 'manual';
	}

	/**
	 * Returns the supported generation timing modes.
	 *
	 * - `manual`: editors add the Key Takeaways block to generate takeaways.
	 * - `on_demand`: a front-end button generates takeaways the first time a
	 *   visitor requests them, then stores the result for reuse.
	 *
	 * @return array
	 */
	public function get_generation_timing_options(): array {
		return array(
			'manual'    => __( 'Manual (add the Key Takeaways block to a post)', 'classifai' ),
			'on_demand' => __( 'On demand (generate on first front-end request)', 'classifai' ),
		);
	}

	/**
	 * Returns the settings for the feature.
	 *
	 * @param string|false $index The index of the setting to return.
	 * @return array|mixed
	 */
	public function get_settings( $index = false ) {
		$settings = parent::get_settings( $index );

		// Keep using the original prompt from the codebase to allow updates.
		if ( $settings && ! empty( $settings['key_takeaways_prompt'] ) ) {
			foreach ( $settings['key_takeaways_prompt'] as $key => $prompt ) {
				if ( 1 === intval( $prompt['original'] ) ) {
					$settings['key_takeaways_prompt'][ $key ]['prompt'] = $this->get_prompt( 'default' );
					break;
				}
			}
		}

		return $settings;
	}

	/**
	 * Sanitizes the default feature settings.
	 *
	 * @param array $new_settings Settings being saved.
	 * @return array
	 */
	public function sanitize_default_feature_settings( array $new_settings ): array {
		$new_settings['key_takeaways_prompt'] = sanitize_prompts( 'key_takeaways_prompt', $new_settings );

		$timing                            = $new_settings['generation_timing'] ?? 'manual';
		$new_settings['generation_timing'] = array_key_exists( $timing, $this->get_generation_timing_options() ) ? $timing : 'manual';

		$render                 = $new_settings['render'] ?? 'list';
		$new_settings['render'] = in_array( $render, array( 'list', 'paragraph' ), true ) ? $render : 'list';

		$label                        = isset( $new_settings['button_label'] ) ? sanitize_text_field( $new_settings['button_label'] ) : '';
		$new_settings['button_label'] = '' !== $label ? $label : esc_html__( 'Key Takeaways', 'classifai' );

		$post_types = \Classifai\get_post_types_for_language_settings();
		foreach ( $post_types as $post_type ) {
			if ( ! isset( $new_settings['post_types'][ $post_type->name ] ) ) {
				$new_settings['post_types'][ $post_type->name ] = '';
			} else {
				$new_settings['post_types'][ $post_type->name ] = sanitize_text_field( $new_settings['post_types'][ $post_type->name ] );
			}
		}

		return $new_settings;
	}
}
