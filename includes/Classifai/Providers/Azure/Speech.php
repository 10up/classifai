<?php
/**
 * Provides Text to Speech synthesis feature using Microsoft Azure Text to Speech.
 */

namespace Classifai\Providers\Azure;

use Classifai\Providers\Provider;
use Classifai\Normalizer;
use Classifai\Features\TextToSpeech;
use stdClass;
use WP_Http;
use WP_Error;

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

		do_action( 'classifai_' . static::ID . '_init', $this );
	}

	/**
	 * Render the provider fields.
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
	public function get_default_provider_settings(): array {
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
	public function sanitize_settings( array $new_settings ): array {
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

		$new_settings[ static::ID ]['voice'] = sanitize_text_field( $new_settings[ static::ID ]['voice'] ?? $settings[ static::ID ]['voice'] );

		return $new_settings;
	}

	/**
	 * Connects to Azure's Text to Speech service.
	 *
	 * @param array $args Overridable args.
	 * @return array
	 */
	public function connect_to_service( array $args = array() ): array {
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
	public function get_voices_select_options(): array {
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
	 * Synthesizes speech from a post item.
	 *
	 * @param int $post_id Post ID.
	 * @return string|WP_Error
	 */
	public function synthesize_speech( int $post_id ) {
		if ( empty( $post_id ) ) {
			return new WP_Error(
				'azure_text_to_speech_post_id_missing',
				esc_html__( 'Post ID missing.', 'classifai' )
			);
		}

		// We skip the user cap check if running under WP-CLI.
		if ( ! current_user_can( 'edit_post', $post_id ) && ( ! defined( 'WP_CLI' ) || ! WP_CLI ) ) {
			return new WP_Error(
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
		$saved_attachment_id = (int) get_post_meta( $post_id, $feature::AUDIO_ID_KEY, true );

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
			return new WP_Error(
				'azure_text_to_speech_voice_information_missing',
				esc_html__( 'Voice data not set.', 'classifai' )
			);
		}

		// Create the request body to synthesize speech from text.
		$request_body = sprintf(
			"<speak version='1.0' xml:lang='en-US'><voice xml:lang='en-US' xml:gender='%s' name='%s'>%s</voice></speak>",
			esc_attr( $voice_gender ),
			esc_attr( $voice_name ),
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
			return new WP_Error(
				'azure_text_to_speech_http_error',
				esc_html( $response->get_error_message() )
			);
		}

		$code          = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );

		// return error if HTTP status code is not 200.
		if ( \WP_Http::OK !== $code ) {
			return new WP_Error(
				'azure_text_to_speech_unsuccessful_request',
				esc_html__( 'HTTP request unsuccessful.', 'classifai' )
			);
		}

		update_post_meta( $post_id, self::AUDIO_HASH_KEY, md5( $post_content ) );

		return $response_body;
	}

	/**
	 * Common entry point for all REST endpoints for this provider.
	 *
	 * @param int    $post_id       The post ID we're processing.
	 * @param string $route_to_call The name of the route we're going to be processing.
	 * @param array  $args          Optional arguments to pass to the route.
	 * @return array|string|WP_Error
	 */
	public function rest_endpoint_callback( $post_id, string $route_to_call = '', array $args = [] ) {
		if ( ! $post_id || ! get_post( $post_id ) ) {
			return new WP_Error( 'post_id_required', esc_html__( 'A valid post ID is required.', 'classifai' ) );
		}

		$route_to_call = strtolower( $route_to_call );
		$return        = '';

		// Handle all of our routes.
		switch ( $route_to_call ) {
			case 'synthesize':
				$return = $this->synthesize_speech( $post_id, $args );
				break;
		}

		return $return;
	}

	/**
	 * Returns the debug information for the provider settings.
	 *
	 * @return array
	 */
	public function get_debug_information(): array {
		$settings          = $this->feature_instance->get_settings();
		$provider_settings = $settings[ static::ID ];
		$debug_info        = [];

		if ( $this->feature_instance instanceof TextToSpeech ) {
			$post_types = array_filter(
				$settings['post_types'],
				function ( $value ) {
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
