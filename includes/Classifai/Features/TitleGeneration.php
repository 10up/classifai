<?php

namespace Classifai\Features;

use Classifai\Providers\Azure\OpenAI;
use Classifai\Providers\GoogleAI\GeminiAPI;
use Classifai\Providers\OpenAI\ChatGPT;
use Classifai\Providers\Browser\ChromeAI;
use Classifai\Providers\XAI\Grok;
use Classifai\Services\LanguageProcessing;
use WP_REST_Server;
use WP_REST_Request;
use WP_Error;

use function Classifai\sanitize_prompts;
use function Classifai\get_asset_info;

/**
 * Class TitleGeneration
 */
class TitleGeneration extends Feature {
	/**
	 * ID of the current feature.
	 *
	 * @var string
	 */
	const ID = 'feature_title_generation';

	/**
	 * Prompt for generating titles.
	 *
	 * @var string
	 */
	public $prompt = 'Write an SEO-friendly title for the following content that will encourage readers to clickthrough, staying within a range of 40 to 60 characters.';

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->label = __( 'Title Generation', 'classifai' );

		// Contains all providers that are registered to the service.
		$this->provider_instances = $this->get_provider_instances( LanguageProcessing::get_service_providers() );

		// Contains just the providers this feature supports.
		$this->supported_providers = [
			ChatGPT::ID   => __( 'OpenAI ChatGPT', 'classifai' ),
			GeminiAPI::ID => __( 'Google AI (Gemini API)', 'classifai' ),
			OpenAI::ID    => __( 'Azure OpenAI', 'classifai' ),
			Grok::ID      => __( 'xAI Grok', 'classifai' ),
			ChromeAI::ID  => __( 'Chrome AI (experimental)', 'classifai' ),
		];
	}

	/**
	 * Set up necessary hooks.
	 *
	 * We utilize this so we can register the REST route.
	 */
	public function setup() {
		parent::setup();
		add_action( 'rest_api_init', [ $this, 'register_endpoints' ] );
		add_action(
			'admin_footer',
			static function () {
				if (
					( isset( $_GET['tab'], $_GET['feature'] ) ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					&& 'language_processing' === sanitize_text_field( wp_unslash( $_GET['tab'] ) ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					&& 'feature_title_generation' === sanitize_text_field( wp_unslash( $_GET['feature'] ) ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				) {
					printf(
						'<div id="js-classifai--delete-prompt-modal" style="display:none;"><p>%1$s</p></div>',
						esc_html__( 'Are you sure you want to delete the prompt?', 'classifai' ),
					);
				}
			}
		);
	}

	/**
	 * Set up necessary hooks.
	 */
	public function feature_setup() {
		add_action( 'enqueue_block_assets', [ $this, 'enqueue_editor_assets' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
		add_action( 'edit_form_before_permalink', [ $this, 'register_generated_titles_template' ] );
	}

	/**
	 * Register any needed endpoints.
	 */
	public function register_endpoints() {
		register_rest_route(
			'classifai/v1',
			'generate-title(?:/(?P<id>\d+))?',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'rest_endpoint_callback' ],
					'args'                => [
						'id' => [
							'required'          => true,
							'type'              => 'integer',
							'sanitize_callback' => 'absint',
							'description'       => esc_html__( 'Post ID to generate title for.', 'classifai' ),
						],
						'n'  => [
							'type'              => 'integer',
							'minimum'           => 1,
							'maximum'           => 10,
							'sanitize_callback' => 'absint',
							'validate_callback' => 'rest_validate_request_arg',
							'description'       => esc_html__( 'Number of titles to generate', 'classifai' ),
						],
					],
					'permission_callback' => [ $this, 'generate_title_permissions_check' ],
				],
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'rest_endpoint_callback' ],
					'args'                => [
						'content' => [
							'required'          => true,
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
							'validate_callback' => 'rest_validate_request_arg',
							'description'       => esc_html__( 'Content to generate a title for', 'classifai' ),
						],
					],
					'permission_callback' => [ $this, 'generate_title_permissions_check' ],
				],
			]
		);
	}

	/**
	 * Check if a given request has access to generate a title.
	 *
	 * This check ensures we have a proper post ID, the current user
	 * making the request has access to that post, that we are
	 * properly authenticated with OpenAI and that title generation
	 * is turned on.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|bool
	 */
	public function generate_title_permissions_check( WP_REST_Request $request ) {
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
			return new WP_Error( 'not_enabled', esc_html__( 'Title generation not currently enabled.', 'classifai' ) );
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

		if ( strpos( $route, '/classifai/v1/generate-title' ) === 0 ) {
			return rest_ensure_response(
				$this->run(
					$request->get_param( 'id' ),
					'title',
					[
						'num'     => $request->get_param( 'n' ),
						'content' => $request->get_param( 'content' ),
					]
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

		wp_enqueue_script(
			'classifai-plugin-title-generation-js',
			CLASSIFAI_PLUGIN_URL . 'dist/classifai-plugin-title-generation.js',
			array_merge( get_asset_info( 'classifai-plugin-title-generation', 'dependencies' ), [ 'lodash' ] ),
			get_asset_info( 'classifai-plugin-title-generation', 'version' ),
			true
		);

		wp_add_inline_script(
			'classifai-plugin-title-generation-js',
			sprintf(
				'var classifaiChatGPTData = %s;',
				wp_json_encode( $this->get_localised_vars() )
			),
			'before'
		);
	}

	/**
	 * Enqueue the admin scripts.
	 *
	 * @param string $hook_suffix The current admin page.
	 */
	public function enqueue_admin_assets( string $hook_suffix ) {
		// Load asset in new post and edit post screens.
		if ( 'post.php' === $hook_suffix || 'post-new.php' === $hook_suffix ) {
			$screen = get_current_screen();

			// Load the assets for the classic editor.
			if ( $screen && ! $screen->is_block_editor() ) {
				if ( post_type_supports( $screen->post_type, 'title' ) ) {
					wp_enqueue_style(
						'classifai-plugin-classic-title-generation-css',
						CLASSIFAI_PLUGIN_URL . 'dist/classifai-plugin-classic-title-generation.css',
						[],
						get_asset_info( 'classifai-plugin-classic-title-generation', 'version' ),
						'all'
					);

					wp_enqueue_script(
						'classifai-plugin-classic-title-generation-js',
						CLASSIFAI_PLUGIN_URL . 'dist/classifai-plugin-classic-title-generation.js',
						array_merge( get_asset_info( 'classifai-plugin-classic-title-generation', 'dependencies' ), array( 'wp-api' ) ),
						get_asset_info( 'generate-title-classic', 'version' ),
						true
					);

					wp_add_inline_script(
						'classifai-plugin-classic-title-generation-js',
						sprintf(
							'var classifaiChatGPTData = %s;',
							wp_json_encode( $this->get_localised_vars() )
						),
						'before'
					);
				}
			}
		}
	}

	/**
	 * HTML template for title generation result popup.
	 */
	public function register_generated_titles_template() {
		?>
		<div id="classifai-title-generation__results" style="display: none;">
			<div id="classifai-title-generation__overlay" style="opacity: 0;"></div>
			<div id="classifai-title-generation__modal" style="opacity: 0;">
				<h2 id="classifai-title-generation__results-title"></h2>
				<div id="classifai-title-generation__close-modal-button"></div>
				<div id="classifai-title-generation__results-content">
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Returns localised data for title generation.
	 */
	public function get_localised_vars() {
		global $post;

		return [
			'enabledFeatures' => [
				0 => [
					'feature'       => 'title',
					'path'          => '/classifai/v1/generate-title/',
					'buttonText'    => __( 'Generate titles', 'classifai' ),
					'modalTitle'    => __( 'Select a title', 'classifai' ),
					'selectBtnText' => __( 'Select', 'classifai' ),
				],
			],
			'noPermissions'   => ! is_user_logged_in() || ! current_user_can( 'edit_post', $post->ID ),
		];
	}

	/**
	 * Get the description for the enable field.
	 *
	 * @return string
	 */
	public function get_enable_description(): string {
		return esc_html__( 'A button will be added to the status panel that can be used to generate titles.', 'classifai' );
	}

	/**
	 * Add any needed custom fields.
	 */
	public function add_custom_settings_fields() {
		$settings = $this->get_settings();

		add_settings_field(
			'generate_title_prompt',
			esc_html__( 'Prompt', 'classifai' ),
			[ $this, 'render_prompt_repeater_field' ],
			$this->get_option_name(),
			$this->get_option_name() . '_section',
			[
				'label_for'     => 'generate_title_prompt',
				'placeholder'   => $this->prompt,
				'default_value' => $settings['generate_title_prompt'],
				'description'   => esc_html__( 'Add a custom prompt, if desired.', 'classifai' ),
			]
		);
	}

	/**
	 * Returns the default settings for the feature.
	 *
	 * @return array
	 */
	public function get_feature_default_settings(): array {
		return [
			'generate_title_prompt' => [
				[
					'title'    => esc_html__( 'ClassifAI default', 'classifai' ),
					'prompt'   => $this->prompt,
					'original' => 1,
				],
			],
			'provider'              => ChatGPT::ID,
		];
	}

	/**
	 * Returns the settings for the feature.
	 *
	 * @param string $index The index of the setting to return.
	 * @return array|mixed
	 */
	public function get_settings( $index = false ) {
		$settings = parent::get_settings( $index );

		// Keep using the original prompt from the codebase to allow updates.
		if ( $settings && ! empty( $settings['generate_title_prompt'] ) ) {
			foreach ( $settings['generate_title_prompt'] as $key => $prompt ) {
				if ( 1 === intval( $prompt['original'] ) ) {
					$settings['generate_title_prompt'][ $key ]['prompt'] = $this->prompt;
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
		$new_settings['generate_title_prompt'] = sanitize_prompts( 'generate_title_prompt', $new_settings );

		return $new_settings;
	}

	/**
	 * Generates feature setting data required for migration from
	 * ClassifAI < 3.0.0 to 3.0.0
	 *
	 * @return array
	 */
	public function migrate_settings() {
		$old_settings = get_option( 'classifai_openai_chatgpt', array() );
		$new_settings = $this->get_default_settings();

		if ( isset( $old_settings['enable_titles'] ) ) {
			$new_settings['status'] = $old_settings['enable_titles'];
		}

		$new_settings['provider'] = 'openai_chatgpt';

		if ( isset( $old_settings['api_key'] ) ) {
			$new_settings['openai_chatgpt']['api_key'] = $old_settings['api_key'];
		}

		if ( isset( $old_settings['authenticated'] ) ) {
			$new_settings['openai_chatgpt']['authenticated'] = $old_settings['authenticated'];
		}

		if ( isset( $old_settings['number_titles'] ) ) {
			$new_settings['openai_chatgpt']['number_of_suggestions'] = $old_settings['number_titles'];
		}

		if ( isset( $old_settings['generate_title_prompt'] ) ) {
			$new_settings['generate_title_prompt'] = $old_settings['generate_title_prompt'];
		}

		if ( isset( $old_settings['title_generation_roles'] ) ) {
			$new_settings['roles'] = $old_settings['title_generation_roles'];
		}

		if ( isset( $old_settings['title_generation_users'] ) ) {
			$new_settings['users'] = $old_settings['title_generation_users'];
		}

		if ( isset( $old_settings['title_generation_user_based_opt_out'] ) ) {
			$new_settings['user_based_opt_out'] = $old_settings['title_generation_user_based_opt_out'];
		}

		return $new_settings;
	}
}
