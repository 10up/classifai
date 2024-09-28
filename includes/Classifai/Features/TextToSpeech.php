<?php

namespace Classifai\Features;

use Classifai\Services\LanguageProcessing;
use Classifai\Providers\Azure\Speech;
use Classifai\Providers\AWS\AmazonPolly;
use Classifai\Providers\OpenAI\TextToSpeech as OpenAITTS;
use Classifai\Normalizer;
use WP_REST_Server;
use WP_REST_Request;
use WP_Error;

use function Classifai\get_asset_info;

/**
 * Class TextToSpeech
 */
class TextToSpeech extends Feature {
	/**
	 * ID of the current feature.
	 *
	 * @var string
	 */
	const ID = 'feature_text_to_speech_generation';

	/**
	 * Meta key to get/set the ID of the speech audio file.
	 *
	 * @var string
	 */
	const AUDIO_ID_KEY = '_classifai_post_audio_id';

	/**
	 * Meta key to get/set the timestamp indicating when the speech was generated.
	 * Used for cache-busting as the audio filename remains static for a given post.
	 *
	 * @var string
	 */
	const AUDIO_TIMESTAMP_KEY = '_classifai_post_audio_timestamp';

	/**
	 * Meta key to hide/unhide already generated audio file.
	 *
	 * @var string
	 */
	const DISPLAY_GENERATED_AUDIO = '_classifai_display_generated_audio';

	/**
	 * Meta key to get/set the audio hash that helps to indicate if there is any need
	 * for the audio file to be regenerated or not.
	 *
	 * @var string
	 */
	const AUDIO_HASH_KEY = '_classifai_post_audio_hash';

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->label = __( 'Text to Speech', 'classifai' );

		// Contains all providers that are registered to the service.
		$this->provider_instances = $this->get_provider_instances( LanguageProcessing::get_service_providers() );

		// Contains just the providers this feature supports.
		$this->supported_providers = [
			AmazonPolly::ID => __( 'Amazon Polly', 'classifai' ),
			Speech::ID      => __( 'Microsoft Azure AI Speech', 'classifai' ),
			OpenAITTS::ID   => __( 'OpenAI Text to Speech', 'classifai' ),
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

		if ( $this->is_enabled() ) {
			add_filter( 'the_content', [ $this, 'render_post_audio_controls' ] );
		}
	}

	/**
	 * Set up necessary hooks.
	 */
	public function feature_setup() {
		add_action( 'enqueue_block_editor_assets', [ $this, 'enqueue_editor_assets' ] );
		add_action( 'rest_api_init', [ $this, 'add_meta_to_rest_api' ] );

		foreach ( $this->get_supported_post_types() as $post_type ) {
			add_action( 'rest_insert_' . $post_type, [ $this, 'rest_handle_audio' ], 10, 2 );
		}

		add_action( 'add_meta_boxes', [ $this, 'add_meta_box' ] );
		add_action( 'admin_notices', [ $this, 'show_error_if' ] );
		add_action( 'save_post', [ $this, 'save_post_metadata' ], 5 );
	}

	/**
	 * Enqueue the editor scripts.
	 *
	 * @since 2.4.0 Use get_asset_info to get the asset version and dependencies.
	 */
	public function enqueue_editor_assets() {
		if ( ! $this->is_feature_enabled() ) {
			return;
		}

		$post = get_post();

		if ( empty( $post ) ) {
			return;
		}

		wp_enqueue_script(
			'classifai-plugin-text-to-speech',
			CLASSIFAI_PLUGIN_URL . 'dist/classifai-plugin-text-to-speech.js',
			array_merge(
				get_asset_info( 'classifai-plugin-text-to-speech', 'dependencies' ),
				array( 'lodash' ),
				array( Feature::PLUGIN_AREA_SCRIPT )
			),
			get_asset_info( 'classifai-plugin-text-to-speech', 'version' ),
			true
		);
	}

	/**
	 * Add audio related fields to rest API for view/edit.
	 */
	public function add_meta_to_rest_api() {
		if ( ! $this->is_feature_enabled() ) {
			return;
		}

		$supported_post_types = $this->get_supported_post_types();

		register_rest_field(
			$supported_post_types,
			'classifai_synthesize_speech',
			array(
				'get_callback' => function ( $data ) {
					$audio_id = get_post_meta( $data['id'], self::AUDIO_ID_KEY, true );
					if (
						( $this->get_audio_generation_initial_state( $data['id'] ) && ! $audio_id ) ||
						( $this->get_audio_generation_subsequent_state( $data['id'] ) && $audio_id )
					) {
						return true;
					} else {
						return false;
					}
				},
				'schema'       => [
					'type'    => 'boolean',
					'context' => [ 'view', 'edit' ],
				],
			)
		);

		register_rest_field(
			$supported_post_types,
			'classifai_display_generated_audio',
			array(
				'get_callback'    => function ( $data ) {
					// Default to display the audio if available.
					if ( metadata_exists( 'post', $data['id'], self::DISPLAY_GENERATED_AUDIO ) ) {
						return (bool) get_post_meta( $data['id'], self::DISPLAY_GENERATED_AUDIO, true );
					}
					return true;
				},
				'update_callback' => function ( $value, $data ) {
					if ( $value ) {
						delete_post_meta( $data->ID, self::DISPLAY_GENERATED_AUDIO );
					} else {
						update_post_meta( $data->ID, self::DISPLAY_GENERATED_AUDIO, false );
					}
				},
				'schema'          => [
					'type'    => 'boolean',
					'context' => [ 'view', 'edit' ],
				],
			)
		);

		register_rest_field(
			$supported_post_types,
			'classifai_post_audio_id',
			array(
				'get_callback' => function ( $data ) {
					$post_audio_id = get_post_meta( $data['id'], self::AUDIO_ID_KEY, true );
					return (int) $post_audio_id;
				},
				'schema'       => [
					'type'    => 'integer',
					'context' => [ 'view', 'edit' ],
				],
			)
		);
	}

	/**
	 * Handles audio generation on REST updates / inserts.
	 *
	 * @param \WP_Post        $post     Inserted or updated post object.
	 * @param WP_REST_Request $request  Request object.
	 */
	public function rest_handle_audio( \WP_Post $post, WP_REST_Request $request ) {
		if ( ! $this->is_feature_enabled() ) {
			return;
		}

		$audio_id = get_post_meta( $request->get_param( 'id' ), self::AUDIO_ID_KEY, true );

		// Since we have dynamic generation option agnostic to meta saves we need a flag to differentiate audio generation accurately
		$process_content = false;
		if (
			( $this->get_audio_generation_initial_state( $post ) && ! $audio_id ) ||
			( $this->get_audio_generation_subsequent_state( $post ) && $audio_id )
		) {
			$process_content = true;
		}

		// Add/update audio if it was requested.
		if (
			( $process_content && null === $request->get_param( 'classifai_synthesize_speech' ) ) ||
			true === $request->get_param( 'classifai_synthesize_speech' )
		) {
			$results = $this->run( $request->get_param( 'id' ), 'synthesize' );

			if ( $results && ! is_wp_error( $results ) ) {
				$this->save( $results, $request->get_param( 'id' ) );
				delete_post_meta( $post->ID, '_classifai_text_to_speech_error' );
			} elseif ( is_wp_error( $results ) ) {
				update_post_meta(
					$post->ID,
					'_classifai_text_to_speech_error',
					wp_json_encode(
						[
							'code'    => $results->get_error_code(),
							'message' => $results->get_error_message(),
						]
					)
				);
			}
		}
	}

	/**
	 * Register any needed endpoints.
	 */
	public function register_endpoints() {
		$post_types = $this->get_supported_post_types();
		foreach ( $post_types as $post_type ) {
			register_meta(
				$post_type,
				'_classifai_text_to_speech_error',
				[
					'show_in_rest'  => true,
					'single'        => true,
					'auth_callback' => '__return_true',
				]
			);
		}

		register_rest_route(
			'classifai/v1',
			'synthesize-speech/(?P<id>\d+)',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'rest_endpoint_callback' ),
				'args'                => array(
					'id' => array(
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
						'description'       => esc_html__( 'ID of post to run text to speech conversion on.', 'classifai' ),
					),
				),
				'permission_callback' => [ $this, 'speech_synthesis_permissions_check' ],
			]
		);
	}

	/**
	 * Check if a given request has access to generate audio for the post.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|bool
	 */
	public function speech_synthesis_permissions_check( WP_REST_Request $request ) {
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

		// Ensure the post type is supported by this feature.
		$supported = $this->get_supported_post_types();
		if ( ! in_array( $post_type, $supported, true ) ) {
			return new WP_Error( 'not_enabled', esc_html__( 'Speech synthesis is not enabled for current item.', 'classifai' ) );
		}

		// Ensure the feature is enabled. Also runs a user check.
		if ( ! $this->is_feature_enabled() ) {
			return new WP_Error( 'not_enabled', esc_html__( 'Speech synthesis is not currently enabled.', 'classifai' ) );
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

		if ( strpos( $route, '/classifai/v1/synthesize-speech' ) === 0 ) {
			$results = $this->run( $request->get_param( 'id' ), 'synthesize' );

			if ( $results && ! is_wp_error( $results ) ) {
				$attachment_id = $this->save( $results, $request->get_param( 'id' ) );

				if ( ! is_wp_error( $attachment_id ) ) {
					return rest_ensure_response(
						array(
							'success'  => true,
							'audio_id' => $attachment_id,
						)
					);
				}
			}

			return rest_ensure_response(
				array(
					'success' => false,
					'code'    => $results->get_error_code(),
					'message' => $results->get_error_message(),
				)
			);
		}

		return parent::rest_endpoint_callback( $request );
	}

	/**
	 * Adds a meta box for Classic content to trigger Text to Speech.
	 *
	 * @param string $post_type The post type.
	 */
	public function add_meta_box( string $post_type ) {
		if (
			! in_array( $post_type, $this->get_supported_post_types(), true ) ||
			! $this->is_feature_enabled()
		) {
			return;
		}

		\add_meta_box(
			'classifai-text-to-speech-meta-box',
			__( 'ClassifAI Text to Speech Processing', 'classifai' ),
			[ $this, 'render_meta_box' ],
			null,
			'side',
			'high',
			array( '__back_compat_meta_box' => true )
		);
	}

	/**
	 * Render meta box content.
	 *
	 * @param \WP_Post $post WP_Post object.
	 */
	public function render_meta_box( \WP_Post $post ) {
		wp_nonce_field( 'classifai_text_to_speech_meta_action', 'classifai_text_to_speech_meta' );

		$source_url = false;
		$audio_id   = get_post_meta( $post->ID, self::AUDIO_ID_KEY, true );

		if ( $audio_id ) {
			$source_url = wp_get_attachment_url( $audio_id );
		}

		$process_content = false;
		if (
			( $this->get_audio_generation_initial_state( $post ) && ! $audio_id ) ||
			( $this->get_audio_generation_subsequent_state( $post ) && $audio_id )
		) {
			$process_content = true;
		}

		$display_audio = true;
		if ( metadata_exists( 'post', $post->ID, self::DISPLAY_GENERATED_AUDIO ) &&
			! (bool) get_post_meta( $post->ID, self::DISPLAY_GENERATED_AUDIO, true ) ) {
			$display_audio = false;
		}

		$post_type_label = esc_html__( 'Post', 'classifai' );
		$post_type       = get_post_type_object( get_post_type( $post ) );
		if ( $post_type ) {
			$post_type_label = $post_type->labels->singular_name;
		}
		?>

		<p>
			<label for="classifai_synthesize_speech">
				<input type="checkbox" value="1" id="classifai_synthesize_speech" name="classifai_synthesize_speech" <?php checked( $process_content ); ?> />
				<?php esc_html_e( 'Enable audio generation', 'classifai' ); ?>
			</label>
			<span class="description">
				<?php
				/* translators: %s Post type label */
				printf( esc_html__( 'ClassifAI will generate audio for this %s when it is published or updated.', 'classifai' ), esc_html( $post_type_label ) );
				?>
			</span>
		</p>

		<p <?php echo $source_url ? '' : 'class="hidden"'; ?>>
			<label for="classifai_display_generated_audio">
				<input type="checkbox" value="1" id="classifai_display_generated_audio" name="classifai_display_generated_audio" <?php checked( $display_audio ); ?> />
				<?php esc_html_e( 'Display audio controls', 'classifai' ); ?>
			</label>
			<span class="description">
				<?php
				esc_html__( 'Controls the display of the audio player on the front-end.', 'classifai' );
				?>
			</span>
		</p>

		<?php
		if ( $source_url ) {
			$cache_busting_url = add_query_arg(
				[
					'ver' => time(),
				],
				$source_url
			);
			?>

			<p>
				<audio id="classifai-audio-preview" controls controlslist="nodownload" src="<?php echo esc_url( $cache_busting_url ); ?>"></audio>
			</p>

			<?php
		}
	}

	/**
	 * Process the meta box save.
	 *
	 * @param int $post_id Post ID.
	 */
	public function save_post_metadata( int $post_id ) {
		if (
			! in_array( get_post_type( $post_id ), $this->get_supported_post_types(), true ) ||
			! $this->is_feature_enabled()
		) {
			return;
		}

		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) || 'revision' === get_post_type( $post_id ) ) {
			return;
		}

		if ( empty( $_POST['classifai_text_to_speech_meta'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['classifai_text_to_speech_meta'] ) ), 'classifai_text_to_speech_meta_action' ) ) {
			return;
		}

		if ( ! isset( $_POST['classifai_display_generated_audio'] ) ) {
			update_post_meta( $post_id, self::DISPLAY_GENERATED_AUDIO, false );
		} else {
			delete_post_meta( $post_id, self::DISPLAY_GENERATED_AUDIO );
		}

		if ( isset( $_POST['classifai_synthesize_speech'] ) ) {
			$results = $this->run( $post_id, 'synthesize' );

			if ( $results && ! is_wp_error( $results ) ) {
				$this->save( $results, $post_id );
				delete_post_meta( $post_id, '_classifai_text_to_speech_error' );
			} elseif ( is_wp_error( $results ) ) {
				update_post_meta(
					$post_id,
					'_classifai_text_to_speech_error',
					wp_json_encode(
						[
							'code'    => $results->get_error_code(),
							'message' => $results->get_error_message(),
						]
					)
				);
			}
		}
	}

	/**
	 * Save the returned result.
	 *
	 * @param string $result The results to save.
	 * @param int    $post_id The post ID.
	 * @return int|WP_Error
	 */
	public function save( string $result, int $post_id ) {
		$saved_attachment_id = (int) get_post_meta( $post_id, self::AUDIO_ID_KEY, true );

		// The audio file name.
		$audio_file_name = sprintf(
			'post-as-audio-%1$s.mp3',
			$post_id
		);

		// Upload the audio stream as an .mp3 file.
		$file_data = wp_upload_bits(
			$audio_file_name,
			null,
			$result
		);

		if ( isset( $file_data['error'] ) && ! empty( $file_data['error'] ) ) {
			return new WP_Error(
				'text_to_speech_upload_bits_failure',
				esc_html( $file_data['error'] )
			);
		}

		// Insert the audio file as attachment.
		$attachment_id = wp_insert_attachment(
			array(
				'guid'           => $file_data['file'],
				'post_title'     => $audio_file_name,
				'post_mime_type' => $file_data['type'],
			),
			$file_data['file'],
			$post_id
		);

		// Return error if creation of attachment fails.
		if ( ! $attachment_id ) {
			return new WP_Error(
				'text_to_speech_resource_creation_failure',
				esc_html__( 'Audio creation failed.', 'classifai' )
			);
		}

		// If audio already exists for this post, delete it.
		if ( $saved_attachment_id ) {
			wp_delete_attachment( $saved_attachment_id, true );
			delete_post_meta( $post_id, self::AUDIO_ID_KEY );
			delete_post_meta( $post_id, self::AUDIO_TIMESTAMP_KEY );
		}

		update_post_meta( $post_id, self::AUDIO_ID_KEY, absint( $attachment_id ) );
		update_post_meta( $post_id, self::AUDIO_TIMESTAMP_KEY, time() );

		return $attachment_id;
	}

	/**
	 * Adds audio controls to the post that has speech synthesis enabled.
	 *
	 * @param string $content Post content.
	 * @return string
	 */
	public function render_post_audio_controls( string $content ): string {
		$_post = get_post();

		if (
			! $_post instanceof \WP_Post ||
			! is_singular( $_post->post_type ) ||
			! in_array( $_post->post_type, $this->get_supported_post_types(), true )
		) {
			return $content;
		}

		/**
		 * Filter to disable the rendering of the Text to Speech block.
		 *
		 * @since 2.2.0
		 * @hook classifai_disable_post_to_audio_block
		 *
		 * @param  {bool}    $is_disabled Whether to disable the display or not. By default - false.
		 * @param  {WP_Post} $_post       The Post object.
		 *
		 * @return {bool} Whether the audio block should be shown.
		 */
		if ( apply_filters( 'classifai_disable_post_to_audio_block', false, $_post ) ) {
			return $content;
		}

		// Respect the audio display settings of the post.
		if ( metadata_exists( 'post', $_post->ID, self::DISPLAY_GENERATED_AUDIO ) &&
			! (bool) get_post_meta( $_post->ID, self::DISPLAY_GENERATED_AUDIO, true ) ) {
			return $content;
		}

		$audio_attachment_id = (int) get_post_meta( $_post->ID, self::AUDIO_ID_KEY, true );

		if ( ! $audio_attachment_id ) {
			return $content;
		}

		$audio_attachment_url = wp_get_attachment_url( $audio_attachment_id );

		if ( ! $audio_attachment_url ) {
			return $content;
		}

		$audio_timestamp = (int) get_post_meta( $_post->ID, self::AUDIO_TIMESTAMP_KEY, true );

		if ( $audio_timestamp ) {
			$audio_attachment_url = add_query_arg( 'ver', filter_var( $audio_timestamp, FILTER_SANITIZE_NUMBER_INT ), $audio_attachment_url );
		}

		/**
		 * Filters the audio player markup before display.
		 *
		 * Returning a non-false value from this filter will short-circuit building
		 * the block markup and instead will return your custom markup prepended to
		 * the post_content.
		 *
		 * Note that by using this filter, the custom CSS and JS files will no longer
		 * be enqueued, so you'll be responsible for either loading them yourself or
		 * loading custom ones.
		 *
		 * @hook classifai_pre_render_post_audio_controls
		 * @since 2.2.3
		 *
		 * @param {bool|string} $markup               Audio markup to use. Defaults to false.
		 * @param {string}      $content              Content of the current post.
		 * @param {WP_Post}     $_post                The Post object.
		 * @param {int}         $audio_attachment_id  The audio attachment ID.
		 * @param {string}      $audio_attachment_url The URL to the audio attachment file.
		 *
		 * @return {bool|string} Custom audio block markup. Will be prepended to the post content.
		 */
		$markup = apply_filters( 'classifai_pre_render_post_audio_controls', false, $content, $_post, $audio_attachment_id, $audio_attachment_url );

		if ( false !== $markup ) {
			return (string) $markup . $content;
		}

		wp_enqueue_script(
			'classifai-plugin-text-to-speech-frontend-js',
			CLASSIFAI_PLUGIN_URL . 'dist/classifai-plugin-text-to-speech-frontend.js',
			get_asset_info( 'classifai-plugin-text-to-speech-frontend', 'dependencies' ),
			get_asset_info( 'classifai-plugin-text-to-speech-frontend', 'version' ),
			true
		);

		wp_enqueue_style(
			'classifai-plugin-text-to-speech-frontend-css',
			CLASSIFAI_PLUGIN_URL . 'dist/classifai-plugin-text-to-speech-frontend.css',
			array( 'dashicons' ),
			get_asset_info( 'classifai-plugin-text-to-speech-frontend', 'version' ),
			'all'
		);

		ob_start();

		?>
			<div>
				<div class='classifai-listen-to-post-wrapper'>
					<div class="class-post-audio-controls" tabindex="0" role="button" aria-label="<?php esc_attr_e( 'Play audio', 'classifai' ); ?>" data-aria-pause-audio="<?php esc_attr_e( 'Pause audio', 'classifai' ); ?>">
						<span class="dashicons dashicons-controls-play"></span>
						<span class="dashicons dashicons-controls-pause"></span>
					</div>
					<div class='classifai-post-audio-heading'>
						<?php
							$listen_to_post_text = sprintf(
								/**
								 * Hook to filter the text next to the audio controls on the frontend.
								 *
								 * @since 2.2.0
								 * @hook classifai_listen_to_this_post_text
								 *
								 * @param {string} The text to filter.
								 * @param {int}    Post ID.
								 *
								 * @return {string} Filtered text.
								 */
								apply_filters( 'classifai_listen_to_this_post_text', '%s %s', $_post->ID ),
								esc_html__( 'Listen to this', 'classifai' ),
								esc_html( $_post->post_type )
							);

							echo wp_kses_post( $listen_to_post_text );
						?>
					</div>
				</div>
				<audio id="classifai-post-audio-player" src="<?php echo esc_url( $audio_attachment_url ); ?>"></audio>
			</div>
		<?php

		return ob_get_clean() . $content;
	}

	/**
	 * Get the description for the enable field.
	 *
	 * @return string
	 */
	public function get_enable_description(): string {
		return esc_html__( 'Enables speech generation for post content.', 'classifai' );
	}

	/**
	 * Add any needed custom fields.
	 */
	public function add_custom_settings_fields() {
		$settings          = $this->get_settings();
		$post_types        = \Classifai\get_post_types_for_language_settings();
		$post_type_options = array();

		foreach ( $post_types as $post_type ) {
			$post_type_options[ $post_type->name ] = $post_type->label;
		}

		add_settings_field(
			'post_types',
			esc_html__( 'Allowed post types', 'classifai' ),
			[ $this, 'render_checkbox_group' ],
			$this->get_option_name(),
			$this->get_option_name() . '_section',
			[
				'label_for'      => 'post_types',
				'options'        => $post_type_options,
				'default_values' => $settings['post_types'],
				'description'    => __( 'Choose which post types support this feature.', 'classifai' ),
			]
		);
	}

	/**
	 * Returns the select options for post types.
	 *
	 * @return array
	 */
	protected function get_post_types_select_options(): array {
		$post_types = \Classifai\get_post_types_for_language_settings();
		$options    = array();

		foreach ( $post_types as $post_type ) {
			$options[ $post_type->name ] = $post_type->label;
		}

		return $options;
	}

	/**
	 * Returns the default settings for the feature.
	 *
	 * @return array
	 */
	public function get_feature_default_settings(): array {
		return [
			'post_types' => [
				'post' => 'post',
			],
			'provider'   => Speech::ID,
		];
	}

	/**
	 * Sanitizes the default feature settings.
	 *
	 * @param array $new_settings Settings being saved.
	 * @return array
	 */
	public function sanitize_default_feature_settings( array $new_settings ): array {
		$settings   = $this->get_settings();
		$post_types = \Classifai\get_post_types_for_language_settings();

		foreach ( $post_types as $post_type ) {
			if ( ! isset( $new_settings['post_types'][ $post_type->name ] ) ) {
				$new_settings['post_types'][ $post_type->name ] = $settings['post_types'];
			} else {
				$new_settings['post_types'][ $post_type->name ] = sanitize_text_field( $new_settings['post_types'][ $post_type->name ] );
			}
		}

		return $new_settings;
	}

	/**
	 * Initial audio generation state.
	 *
	 * Fetch the initial state of audio generation prior to the audio existing for the post.
	 *
	 * @param  int|WP_Post|null $post   Optional. Post ID or post object. `null`, `false`, `0` and other PHP falsey values
	 *                                    return the current global post inside the loop. A numerically valid post ID that
	 *                                    points to a non-existent post returns `null`. Defaults to global $post.
	 * @return bool                     The initial state of audio generation. Default true.
	 */
	public function get_audio_generation_initial_state( $post = null ): bool {
		/**
		 * Initial state of the audio generation toggle when no audio already exists for the post.
		 *
		 * @since 2.3.0
		 * @hook classifai_audio_generation_initial_state
		 *
		 * @param  {bool}    $state Initial state of audio generation toggle on a post. Default true.
		 * @param  {WP_Post} $post  The current Post object.
		 *
		 * @return {bool}           Initial state the audio generation toggle should be set to when no audio exists.
		 */
		return apply_filters( 'classifai_audio_generation_initial_state', true, get_post( $post ) );
	}

	/**
	 * Subsequent audio generation state.
	 *
	 * Fetch the subsequent state of audio generation once audio is generated for the post.
	 *
	 * @param int|WP_Post|null $post   Optional. Post ID or post object. `null`, `false`, `0` and other PHP falsey values
	 *                                   return the current global post inside the loop. A numerically valid post ID that
	 *                                   points to a non-existent post returns `null`. Defaults to global $post.
	 * @return bool                    The subsequent state of audio generation. Default false.
	 */
	public function get_audio_generation_subsequent_state( $post = null ): bool {
		/**
		 * Subsequent state of the audio generation toggle when audio exists for the post.
		 *
		 * @since 2.3.0
		 * @hook classifai_audio_generation_subsequent_state
		 *
		 * @param  {bool}    $state Subsequent state of audio generation toggle on a post. Default false.
		 * @param  {WP_Post} $post  The current Post object.
		 *
		 * @return {bool}           Subsequent state the audio generation toggle should be set to when audio exists.
		 */
		return apply_filters( 'classifai_audio_generation_subsequent_state', false, get_post( $post ) );
	}

	/**
	 * Normalizes the post content for text to speech generation.
	 *
	 * @param int $post_id The post ID.
	 *
	 * @return string The normalized post content.
	 */
	public function normalize_post_content( int $post_id ): string {
		add_filter( 'classifai_pre_normalize', [ $this, 'strip_sub_sup_tags' ] );
		$normalizer   = new Normalizer();
		$post         = get_post( $post_id );
		$post_content = $normalizer->normalize_content( $post->post_content, $post->post_title, $post_id );
		remove_filter( 'classifai_pre_normalize', [ $this, 'strip_sub_sup_tags' ] );

		return $post_content;
	}

	/**
	 * Filters the post content by stripping off HTML subscript and superscript tags
	 * with its content for text to speech generation.
	 *
	 * @param string $post_content The post content.
	 *
	 * @return string The filtered post content.
	 */
	public function strip_sub_sup_tags( string $post_content ): string {
		$post_content = preg_replace( '/<sub>.*?<\/sub>|<sup>.*?<\/sup>/', '', $post_content );
		return $post_content;
	}

	/**
	 * Generates feature setting data required for migration from
	 * ClassifAI < 3.0.0 to 3.0.0
	 *
	 * @return array
	 */
	public function migrate_settings() {
		$old_settings = get_option( 'classifai_azure_text_to_speech', array() );
		$new_settings = $this->get_default_settings();

		if ( isset( $old_settings['enable_text_to_speech'] ) ) {
			$new_settings['status'] = $old_settings['enable_text_to_speech'];
		}

		$new_settings['provider'] = 'ms_azure_text_to_speech';

		if ( isset( $old_settings['credentials']['url'] ) ) {
			$new_settings['ms_azure_text_to_speech']['endpoint_url'] = $old_settings['credentials']['url'];
		}

		if ( isset( $old_settings['credentials']['api_key'] ) ) {
			$new_settings['ms_azure_text_to_speech']['api_key'] = $old_settings['credentials']['api_key'];
		}

		if ( isset( $old_settings['authenticated'] ) ) {
			$new_settings['ms_azure_text_to_speech']['authenticated'] = $old_settings['authenticated'];
		}

		if ( isset( $old_settings['voices'] ) ) {
			$new_settings['ms_azure_text_to_speech']['voices'] = $old_settings['voices'];
		}

		if ( isset( $old_settings['voice'] ) ) {
			$new_settings['ms_azure_text_to_speech']['voice'] = $old_settings['voice'];
		}

		if ( isset( $old_settings['text_to_speech_users'] ) ) {
			$new_settings['users'] = $old_settings['text_to_speech_users'];
		}

		if ( isset( $old_settings['text_to_speech_roles'] ) ) {
			$new_settings['roles'] = $old_settings['text_to_speech_roles'];
		}

		if ( isset( $old_settings['text_to_speech_user_based_opt_out'] ) ) {
			$new_settings['user_based_opt_out'] = $old_settings['text_to_speech_user_based_opt_out'];
		}

		if ( isset( $old_settings['post_types'] ) ) {
			$new_settings['post_types'] = $old_settings['post_types'];
		}

		return $new_settings;
	}

	/**
	 * Outputs an admin notice with the error message if needed.
	 */
	public function show_error_if() {
		global $post;

		if ( empty( $post ) ) {
			return;
		}

		$post_id = $post->ID;

		if ( empty( $post_id ) ) {
			return;
		}

		$error = get_post_meta( $post_id, '_classifai_text_to_speech_error', true );

		if ( ! empty( $error ) ) {
			delete_post_meta( $post_id, '_classifai_text_to_speech_error' );
			$error   = (array) json_decode( $error );
			$code    = ! empty( $error['code'] ) ? $error['code'] : 500;
			$message = ! empty( $error['message'] ) ? $error['message'] : 'Unknown API error';

			?>
			<div class="notice notice-error is-dismissible">
				<p>
					<?php esc_html_e( 'Error: Audio generation failed.', 'classifai' ); ?>
				</p>
				<p>
					<?php echo esc_html( $code ); ?>
					-
					<?php echo esc_html( $message ); ?>
				</p>
			</div>
			<?php
		}
	}
}
