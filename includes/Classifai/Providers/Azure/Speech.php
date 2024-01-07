<?php
/**
 * Provides Text to Speech synthesis feature using Microsoft Azure Text to Speech.
 */

namespace Classifai\Providers\Azure;

use \Classifai\Admin\SavePostHandler;
use \Classifai\Providers\Provider;
use \Classifai\Watson\Normalizer;
use \Classifai\Features\TextToSpeech;
use stdClass;
use WP_Http;
use WP_REST_Server;
use WP_REST_Request;
use WP_Error;

use function Classifai\get_post_types_for_language_settings;
use function Classifai\get_tts_supported_post_types;
use function Classifai\get_asset_info;

class Speech extends Provider {

	const ID = 'ms_azure_text_to_speech';

	/**
	 * Name of the feature that is displayed to the end user.
	 *
	 * @var string
	 */
	const FEATURE_NAME = 'Text to Speech';

	/**
	 * Azure's Text to Speech endpoint path.
	 *
	 * @var string
	 */
	const API_PATH = 'cognitiveservices/v1';

	/**
	 * Meta key to hide/unhide already generated audio file.
	 *
	 * @var string
	 */
	const DISPLAY_GENERATED_AUDIO = '_classifai_display_generated_audio';

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
	 * Meta key to get/set the audio hash that helps to indicate if there is any need
	 * for the audio file to be regenerated or not.
	 *
	 * @var string
	 */
	const AUDIO_HASH_KEY = '_classifai_post_audio_hash';

	/**
	 * Azure Text to Speech constructor.
	 *
	 * @param \Classifai\Features\Feature $feature_instance The feature instance.
	 */
	public function __construct( $feature_instance = null ) {
		parent::__construct(
			'Microsoft Azure',
			self::FEATURE_NAME,
			'azure_text_to_speech'
		);

		$this->feature_instance = $feature_instance;

		add_action( 'rest_api_init', [ $this, 'register_endpoints' ] );
		do_action( 'classifai_' . static::ID . '_init', $this );
	}

	/**
	 * Enqueue the editor scripts.
	 *
	 * @since 2.4.0 Use get_asset_info to get the asset version and dependencies.
	 */
	public function enqueue_editor_assets() {
		$post = get_post();

		if ( empty( $post ) ) {
			return;
		}

		wp_enqueue_script(
			'classifai-gutenberg-plugin',
			CLASSIFAI_PLUGIN_URL . 'dist/gutenberg-plugin.js',
			array_merge( get_asset_info( 'gutenberg-plugin', 'dependencies' ), array( 'lodash' ) ),
			get_asset_info( 'gutenberg-plugin', 'version' ),
			true
		);

		wp_add_inline_script(
			'classifai-gutenberg-plugin',
			sprintf(
				'var classifaiTTSEnabled = %d;',
				true
			),
			'before'
		);
	}

	/**
	 * Register the actions needed.
	 */
	public function register() {
		$tts = new TextToSpeech();
		if ( $tts->is_feature_enabled() ) {
			add_action( 'enqueue_block_editor_assets', [ $this, 'enqueue_editor_assets' ] );
			add_action( 'rest_api_init', [ $this, 'add_synthesize_speech_meta_to_rest_api' ] );
			add_action( 'add_meta_boxes', [ $this, 'add_meta_box' ] );
			add_action( 'save_post', [ $this, 'save_post_metadata' ], 5 );

			foreach ( $tts->get_tts_supported_post_types() as $post_type ) {
				add_action( 'rest_insert_' . $post_type, [ $this, 'rest_handle_audio' ], 10, 2 );
			}
		}

		if ( $tts->is_enabled() ) {
			add_filter( 'the_content', [ $this, 'render_post_audio_controls' ] );
		}
	}

	/**
	 * Render the provider fields.
	 *
	 * @return void
	 */
	public function render_provider_fields() {
		$settings = $this->feature_instance->get_settings( static::ID );

		add_settings_field(
			'endpoint_url',
			esc_html__( 'Endpoint URL', 'classifai' ),
			[ $this->feature_instance, 'render_input' ],
			$this->feature_instance->get_option_name(),
			$this->feature_instance->get_option_name() . '_section',
			[
				'option_index'  => static::ID,
				'label_for'     => 'endpoint_url',
				'input_type'    => 'text',
				'default_value' => $settings['endpoint_url'],
				'description'   => __( 'Text to Speech region endpoint, e.g., <code>https://LOCATION.tts.speech.microsoft.com/</code>. Replace <code>LOCATION</code> with the Location/Region you selected for the resource in Azure.', 'classifai' ),
				'class'         => 'large-text classifai-provider-field hidden provider-scope-' . static::ID, // Important to add this.
			]
		);

		add_settings_field(
			'api_key',
			esc_html__( 'API Key', 'classifai' ),
			[ $this->feature_instance, 'render_input' ],
			$this->feature_instance->get_option_name(),
			$this->feature_instance->get_option_name() . '_section',
			[
				'option_index'  => static::ID,
				'label_for'     => 'api_key',
				'input_type'    => 'password',
				'default_value' => $settings['api_key'],
				'class'         => 'classifai-provider-field hidden provider-scope-' . static::ID, // Important to add this.
			]
		);

		$voices_options = $this->get_voices_select_options();

		if ( ! empty( $voices_options ) ) {
			add_settings_field(
				'voice',
				esc_html__( 'Voice', 'classifai' ),
				[ $this->feature_instance, 'render_select' ],
				$this->feature_instance->get_option_name(),
				$this->feature_instance->get_option_name() . '_section',
				[
					'option_index'  => static::ID,
					'label_for'     => 'voice',
					'options'       => $voices_options,
					'default_value' => $settings['voice'],
					'class'         => 'classifai-provider-field hidden provider-scope-' . static::ID, // Important to add this.
				]
			);
		}

		do_action( 'classifai_' . static::ID . '_render_provider_fields', $this );
	}

	/**
	 * Returns the default settings for this provider.
	 *
	 * @return array
	 */
	public function get_default_provider_settings() {
		$common_settings = [
			'api_key'       => '',
			'endpoint_url'  => '',
			'authenticated' => false,
			'voices'        => [],
			'voice'         => '',
		];

		switch ( $this->feature_instance::ID ) {
			case TextToSpeech::ID:
				return $common_settings;
		}

		return [];
	}

	/**
	 * Sanitization callback for settings.
	 *
	 * @param array $new_settings The settings being saved.
	 * @return array
	 */
	public function sanitize_settings( $new_settings ) {
		$settings               = $this->feature_instance->get_settings();
		$is_credentials_changed = false;

		$new_settings[ static::ID ]['authenticated'] = $settings[ static::ID ]['authenticated'];
		$new_settings[ static::ID ]['voices']        = $settings[ static::ID ]['voices'];

		if ( ! empty( $new_settings[ static::ID ]['endpoint_url'] ) && ! empty( $new_settings[ static::ID ]['api_key'] ) ) {
			$new_url = trailingslashit( esc_url_raw( $new_settings[ static::ID ]['endpoint_url'] ) );
			$new_key = sanitize_text_field( $new_settings[ static::ID ]['api_key'] );

			if ( $new_url !== $settings[ static::ID ]['endpoint_url'] || $new_key !== $settings[ static::ID ]['api_key'] ) {
				$is_credentials_changed = true;
			}

			if ( $is_credentials_changed ) {
				$new_settings[ static::ID ]['endpoint_url'] = $new_url;
				$new_settings[ static::ID ]['api_key']      = $new_key;
				$new_settings[ static::ID ]['voices']       = $this->connect_to_service(
					array(
						'endpoint_url' => $new_url,
						'api_key'      => $new_key,
					)
				);

				if ( ! empty( $new_settings[ static::ID ]['voices'] ) ) {
					$new_settings[ static::ID ]['authenticated'] = true;
				} else {
					$new_settings[ static::ID ]['voices']        = [];
					$new_settings[ static::ID ]['authenticated'] = false;
				}
			}
		} else {
			$new_settings[ static::ID ]['endpoint_url'] = $settings[ static::ID ]['endpoint_url'];
			$new_settings[ static::ID ]['api_key']      = $settings[ static::ID ]['api_key'];

			add_settings_error(
				$this->feature_instance->get_option_name(),
				'classifai-azure-text-to-speech-auth-empty',
				esc_html__( 'One or more credentials required to connect to the Azure Text to Speech service is empty.', 'classifai' ),
				'error'
			);
		}

		// Sanitize the post type checkboxes
		$post_types = get_post_types_for_language_settings();

		foreach ( $post_types as $post_type ) {
			if ( isset( $new_settings['post_types'][ $post_type->name ] ) ) {
				$new_settings['post_types'][ $post_type->name ] = sanitize_text_field( $new_settings['post_types'][ $post_type->name ] );
			} else {
				$new_settings['post_types'][ $post_type->name ] = $settings['post_types'];
			}
		}

		$new_settings[ static::ID ]['voice'] = sanitize_text_field( $new_settings[ static::ID ]['voice'] ?? $settings[ static::ID ]['voice'] );

		return $new_settings;
	}

	/**
	 * Connects to Azure's Text to Speech service.
	 *
	 * @param array $args Overridable args.
	 * @return array
	 */
	public function connect_to_service( array $args = array() ) {
		$settings = $this->feature_instance->get_settings( static::ID );

		$default = array(
			'endpoint_url' => isset( $settings[ static::ID ]['url'] ) ? $settings[ static::ID ]['url'] : '',
			'api_key'      => isset( $settings[ static::ID ]['api_key'] ) ? $settings[ static::ID ]['api_key'] : '',
		);

		$default = wp_parse_args( $args, $default );

		// Return if credentials don't exist.
		if ( empty( $default['endpoint_url'] ) || empty( $default['api_key'] ) ) {
			return array();
		}

		// Create request arguments.
		$request_params = array(
			'headers' => array(
				'Ocp-Apim-Subscription-Key' => $default['api_key'],
				'Content-Type'              => 'application/json',
			),
		);

		// Create request URL.
		$request_url = sprintf(
			'%1$scognitiveservices/voices/list',
			$default['endpoint_url']
		);

		if ( function_exists( 'vip_safe_wp_remote_get' ) ) {
			$response = vip_safe_wp_remote_get(
				$request_url,
				'',
				3,
				1,
				20,
				$request_params
			);
		} else {
			$request_params['timeout'] = 20; // phpcs:ignore WordPressVIPMinimum.Performance.RemoteRequestTimeout.timeout_timeout
			// phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.wp_remote_get_wp_remote_get -- use of `vip_safe_wp_remote_get` is done when available.
			$response = wp_remote_get(
				$request_url,
				$request_params
			);
		}

		if ( is_wp_error( $response ) ) {
			add_settings_error(
				$this->get_option_name(),
				'azure-text-to-request-failed',
				esc_html__( 'Azure Speech to Text: HTTP request failed.', 'classifai' ),
				'error'
			);

			return array();
		}

		$http_code = wp_remote_retrieve_response_code( $response );

		// Return and render error if HTTP response status code is other than 200.
		if ( WP_Http::OK !== $http_code ) {
			add_settings_error(
				$this->get_option_name(),
				'azure-text-to-speech-auth-failed',
				esc_html__( 'Connection to Azure Text to Speech failed.', 'classifai' ),
				'error'
			);

			return array();
		}

		$response_body    = wp_remote_retrieve_body( $response );
		$voices           = json_decode( $response_body );
		$sanitized_voices = array();

		if ( is_array( $voices ) ) {
			foreach ( $voices as $voice ) {
				$voice_object = new stdClass();

				foreach ( $voice as $key => $value ) {
					$voice_object->$key = sanitize_text_field( $value );
				}

				$sanitized_voices[] = $voice_object;
			}
		}

		return $sanitized_voices;
	}

	/**
	 * Returns HTML select dropdown options for voices.
	 *
	 * @return array
	 */
	public function get_voices_select_options() {
		$settings = $this->feature_instance->get_settings( static::ID );
		$voices   = $settings['voices'];
		$options  = array();

		if ( false === $voices ) {
			return $options;
		}

		foreach ( $voices as $voice ) {
			if ( ! is_object( $voice ) ) {
				continue;
			}

			// phpcs is disabled because it throws error for camel case.
			// phpcs:disable
			$options[ "{$voice->ShortName}|{$voice->Gender}" ] = sprintf(
				'%1$s (%2$s/%3$s)',
				esc_html( $voice->LocaleName ),
				esc_html( $voice->DisplayName ),
				esc_html( $voice->Gender )
			);
			// phpcs:enable
		}

		return $options;
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
	public function get_audio_generation_initial_state( $post = null ) {
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
	public function get_audio_generation_subsequent_state( $post = null ) {
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
	 * Add audio related fields to rest API for view/edit.
	 */
	public function add_synthesize_speech_meta_to_rest_api() {
		$supported_post_types = get_tts_supported_post_types();

		register_rest_field(
			$supported_post_types,
			'classifai_synthesize_speech',
			array(
				'get_callback' => function( $object ) {
					$audio_id = get_post_meta( $object['id'], self::AUDIO_ID_KEY, true );
					if (
						( $this->get_audio_generation_initial_state( $object['id'] ) && ! $audio_id ) ||
						( $this->get_audio_generation_subsequent_state( $object['id'] ) && $audio_id )
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
				'get_callback'    => function( $object ) {
					// Default to display the audio if available.
					if ( metadata_exists( 'post', $object['id'], self::DISPLAY_GENERATED_AUDIO ) ) {
						return (bool) get_post_meta( $object['id'], self::DISPLAY_GENERATED_AUDIO, true );
					}
					return true;
				},
				'update_callback' => function( $value, $object ) {
					if ( $value ) {
						delete_post_meta( $object->ID, self::DISPLAY_GENERATED_AUDIO );
					} else {
						update_post_meta( $object->ID, self::DISPLAY_GENERATED_AUDIO, false );
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
				'get_callback' => function( $object ) {
					$post_audio_id = get_post_meta( $object['id'], self::AUDIO_ID_KEY, true );
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
	 * Synthesizes speech from the post title and content.
	 *
	 * @param int $post_id Post ID.
	 * @return bool|int|WP_Error
	 */
	public function synthesize_speech( $post_id ) {
		if ( empty( $post_id ) ) {
			return new \WP_Error(
				'azure_text_to_speech_post_id_missing',
				esc_html__( 'Post ID missing.', 'classifai' )
			);
		}

		// We skip the user cap check if running under WP-CLI.
		if ( ! current_user_can( 'edit_post', $post_id ) && ( ! defined( 'WP_CLI' ) || ! WP_CLI ) ) {
			return new \WP_Error(
				'azure_text_to_speech_user_not_authorized',
				esc_html__( 'Unauthorized user.', 'classifai' )
			);
		}

		$normalizer          = new Normalizer();
		$feature             = new TextToSpeech();
		$settings            = $feature->get_settings();
		$post                = get_post( $post_id );
		$post_content        = $normalizer->normalize_content( $post->post_content, $post->post_title, $post_id );
		$content_hash        = get_post_meta( $post_id, self::AUDIO_HASH_KEY, true );
		$saved_attachment_id = (int) get_post_meta( $post_id, self::AUDIO_ID_KEY, true );

		// Don't regenerate the audio file it it already exists and the content hasn't changed.
		if ( $saved_attachment_id ) {

			// Check if the audio file exists.
			$audio_attachment_url = wp_get_attachment_url( $saved_attachment_id );

			if ( $audio_attachment_url && ! empty( $content_hash ) && ( md5( $post_content ) === $content_hash ) ) {
				return $saved_attachment_id;
			}
		}

		$voice        = $settings[ static::ID ]['voice'] ?? '';
		$voice_data   = explode( '|', $voice );
		$voice_name   = '';
		$voice_gender = '';

		// Extract the voice name and gender from the option value.
		if ( 2 === count( $voice_data ) ) {
			$voice_name   = $voice_data[0];
			$voice_gender = $voice_data[1];

			// Return error if voice is not set in settings.
		} else {
			return new \WP_Error(
				'azure_text_to_speech_voice_information_missing',
				esc_html__( 'Voice data not set.', 'classifai' )
			);
		}

		// Create the request body to synthesize speech from text.
		$request_body = sprintf(
			"<speak version='1.0' xml:lang='en-US'><voice xml:lang='en-US' xml:gender='%s' name='%s'>%s</voice></speak>",
			$voice_gender,
			$voice_name,
			$post_content
		);

		// Request parameters.
		$request_params = array(
			'method'  => 'POST',
			'body'    => $request_body,
			'timeout' => 60, // phpcs:ignore WordPressVIPMinimum.Performance.RemoteRequestTimeout.timeout_timeout
			'headers' => array(
				'Ocp-Apim-Subscription-Key' => $settings[ static::ID ]['api_key'],
				'Content-Type'              => 'application/ssml+xml',
				'X-Microsoft-OutputFormat'  => 'audio-16khz-128kbitrate-mono-mp3',
			),
		);

		$remote_url = sprintf( '%s%s', $settings[ static::ID ]['endpoint_url'], self::API_PATH );
		$response   = wp_remote_post( $remote_url, $request_params );

		if ( is_wp_error( $response ) ) {
			return new \WP_Error(
				'azure_text_to_speech_http_error',
				esc_html( $response->get_error_message() )
			);
		}

		$code          = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );

		// return error if HTTP status code is not 200.
		if ( \WP_Http::OK !== $code ) {
			return new \WP_Error(
				'azure_text_to_speech_unsuccessful_request',
				esc_html__( 'HTTP request unsuccessful.', 'classifai' )
			);
		}

		// If audio already exists for this post, delete it.
		if ( $saved_attachment_id ) {
			wp_delete_attachment( $saved_attachment_id, true );
			delete_post_meta( $post_id, self::AUDIO_ID_KEY );
			delete_post_meta( $post_id, self::AUDIO_TIMESTAMP_KEY );
		}

		// The audio file name.
		$audio_file_name = sprintf(
			'post-as-audio-%1$s.mp3',
			$post_id
		);

		// Upload the audio stream as an .mp3 file.
		$file_data = wp_upload_bits(
			$audio_file_name,
			null,
			$response_body
		);

		if ( isset( $file_data['error'] ) && ! empty( $file_data['error'] ) ) {
			return new \WP_Error(
				'azure_text_to_speech_upload_bits_failure',
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
			return new \WP_Error(
				'azure_text_to_speech_resource_creation_failure',
				esc_html__( 'Audio creation failed.', 'classifai' )
			);
		}

		update_post_meta( $post_id, self::AUDIO_ID_KEY, absint( $attachment_id ) );
		update_post_meta( $post_id, self::AUDIO_TIMESTAMP_KEY, time() );
		update_post_meta( $post_id, self::AUDIO_HASH_KEY, md5( $post_content ) );

		return $attachment_id;
	}

	/**
	 * Handles audio generation on rest updates / inserts.
	 *
	 * @param WP_Post         $post     Inserted or updated post object.
	 * @param WP_REST_Request $request  Request object.
	 */
	public function rest_handle_audio( $post, $request ) {
		$audio_id = get_post_meta( $request->get_param( 'id' ), self::AUDIO_ID_KEY, true );

		// Since we have dynamic generation option agnostic to meta saves we need a flag to differentiate audio generation accurately
		$process_content = false;
		if (
			( $this->get_audio_generation_initial_state( $post ) && ! $audio_id ) ||
			( $this->get_audio_generation_subsequent_state( $post ) && $audio_id )
		) {
			$process_content = true;
		}

		// Add/Update audio if it was requested.
		if (
			( $process_content && null === $request->get_param( 'classifai_synthesize_speech' ) ) ||
			true === $request->get_param( 'classifai_synthesize_speech' )
		) {
			( new TextToSpeech() )->run( $request->get_param( 'id' ) );
		}
	}

	/**
	 * Add meta box to post types that support speech synthesis.
	 *
	 * @param string $post_type Post type.
	 */
	public function add_meta_box( $post_type ) {
		if ( ! in_array( $post_type, get_tts_supported_post_types(), true ) ) {
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
	public function render_meta_box( $post ) {
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
	public function save_post_metadata( $post_id ) {

		if ( ! in_array( get_post_type( $post_id ), get_tts_supported_post_types(), true ) ) {
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
			$this->synthesize_speech( $post_id );
		}
	}

	/**
	 * Adds audio controls to the post that has speech synthesis enabled.
	 *
	 * @param string $content Post content.
	 * @return string
	 */
	public function render_post_audio_controls( $content ) {

		$_post = get_post();

		if ( ! $_post instanceof \WP_Post ) {
			return $content;
		}

		if ( ! is_singular( $_post->post_type ) ) {
			return $content;
		}

		if ( ! in_array( $_post->post_type, get_tts_supported_post_types(), true ) ) {
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
			'classifai-post-audio-player-js',
			CLASSIFAI_PLUGIN_URL . 'dist/post-audio-controls.js',
			get_asset_info( 'post-audio-controls', 'dependencies' ),
			get_asset_info( 'post-audio-controls', 'version' ),
			true
		);

		wp_enqueue_style(
			'classifai-post-audio-player-css',
			CLASSIFAI_PLUGIN_URL . 'dist/post-audio-controls.css',
			array(),
			get_asset_info( 'post-audio-controls', 'version' ),
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
	 * Returns post type array data for select dropdown options.
	 *
	 * @return array
	 */
	protected function get_post_types_select_options() {
		$post_types = get_post_types_for_language_settings();
		$options    = array();

		foreach ( $post_types as $post_type ) {
			$options[ $post_type->name ] = $post_type->label;
		}

		return $options;
	}

	/**
	 * Registers REST endpoints for this provider.
	 *
	 * @return void
	 */
	public function register_endpoints() {
		register_rest_route(
			'classifai/v1',
			'synthesize-speech/(?P<id>\d+)',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'synthesize_speech_from_text' ),
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
	 * Generates text to speech for a post using REST.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return \WP_REST_Response|WP_Error
	 */
	public function synthesize_speech_from_text( WP_REST_Request $request ) {
		$post_id       = $request->get_param( 'id' );
		$attachment_id = $this->synthesize_speech( $post_id );

		if ( is_wp_error( $attachment_id ) ) {
			return rest_ensure_response(
				array(
					'success' => false,
					'code'    => $attachment_id->get_error_code(),
					'message' => $attachment_id->get_error_message(),
				)
			);
		}

		return rest_ensure_response(
			array(
				'success'  => true,
				'audio_id' => $attachment_id,
			)
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

		if ( ! empty( $post_id ) && current_user_can( 'edit_post', $post_id ) ) {
			$post_type = get_post_type( $post_id );
			$supported = \Classifai\get_tts_supported_post_types();

			// Check if processing allowed.
			if ( ! in_array( $post_type, $supported, true ) ) {
				return new WP_Error( 'not_enabled', esc_html__( 'Azure Speech synthesis is not enabled for current post.', 'classifai' ) );
			}

			return true;
		}

		return false;
	}

	/**
	 * Returns the debug information for the provider settings.
	 *
	 * @return array
	 */
	public function get_debug_information() {
		$settings          = $this->feature_instance->get_settings();
		$provider_settings = $settings[ static::ID ];
		$debug_info        = [];

		if ( $this->feature_instance instanceof TextToSpeech ) {
			$post_types        = array_filter(
				$settings['post_types'],
				function( $value ) {
					return '0' !== $value;
				}
			);

			$debug_info[ __( 'Allowed post types', 'classifai' ) ]       = implode( ', ', $post_types );
			$debug_info[ __( 'Voice', 'classifai' ) ]                    = $provider_settings['voice'];
			$debug_info[ __( 'Latest response - Voices', 'classifai' ) ] = $this->get_formatted_latest_response( $provider_settings['voices'] );
		}

		return apply_filters(
			'classifai_' . self::ID . '_debug_information',
			$debug_info,
			$settings,
			$this->feature_instance
		);
	}
}