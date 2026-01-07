<?php
/**
 * ElevenLabs shared functionality
 */

namespace Classifai\Providers\ElevenLabs;

use Classifai\Features\TextToSpeech;
use WP_Error;

use function Classifai\safe_wp_remote_get;
use function Classifai\safe_wp_remote_post;

trait ElevenLabs {

	/**
	 * ElevenLabs base API URL
	 *
	 * @var string
	 */
	protected $api_url = 'https://api.elevenlabs.io/v1';

	/**
	 * ElevenLabs model path
	 *
	 * @var string
	 */
	protected $model_path = 'models';

	/**
	 * Build the API URL.
	 *
	 * @param string $path The path to the API endpoint.
	 * @return string
	 */
	public function get_api_url( string $path = '' ): string {
		/**
		 * Filter the ElevenLabs API URL.
		 *
		 * @since 3.7.0
		 * @hook classifai_elevenlabs_api_url
		 *
		 * @param string $url The default API URL.
		 * @param string $path The path to the API endpoint.
		 *
		 * @return string The API URL.
		 */
		return apply_filters( 'classifai_elevenlabs_api_url', trailingslashit( $this->api_url ) . $path, $path );
	}

	/**
	 * Make a request to the ElevenLabs API.
	 *
	 * Note instead of adding a new APIRequest class like we do elsewhere,
	 * doing a lightweight version of that here instead. The goal is to
	 * replace this with a more robust APIRequest class in the future,
	 * based on the PHP AI SDK.
	 *
	 * @param string $url The URL for the request.
	 * @param string $api_key The API key.
	 * @param string $type The type of request.
	 * @param array  $options The options for the request.
	 * @return array|WP_Error
	 */
	public function request( string $url, string $api_key = '', string $type = 'post', array $options = [] ) {
		/**
		 * Filter the URL for the request.
		 *
		 * @since 3.7.0
		 * @hook classifai_elevenlabs_api_request_url
		 *
		 * @param string $url The URL for the request.
		 * @param array  $options The options for the request.
		 *
		 * @return string The URL for the request.
		 */
		$url = apply_filters( 'classifai_elevenlabs_api_request_url', $url, $options );

		// Set our default options.
		$options = wp_parse_args(
			$options,
			[
				'timeout' => 90, // phpcs:ignore WordPressVIPMinimum.Performance.RemoteRequestTimeout.timeout_timeout
			]
		);

		/**
		 * Filter the options for the request.
		 *
		 * @since 3.7.0
		 * @hook classifai_elevenlabs_api_request_options
		 *
		 * @param array  $options The options for the request.
		 * @param string $url The URL for the request.
		 *
		 * @return array The options for the request.
		 */
		$options = apply_filters( 'classifai_elevenlabs_api_request_options', $options, $url );

		// Set our default headers.
		if ( empty( $options['headers'] ) ) {
			$options['headers'] = [];
		}

		if ( ! isset( $options['headers']['xi-api-key'] ) ) {
			$options['headers']['xi-api-key'] = $api_key;
		}

		if ( ! isset( $options['headers']['Content-Type'] ) ) {
			$options['headers']['Content-Type'] = 'application/json';
		}

		// Make the request.
		if ( 'post' === $type ) {
			$response = safe_wp_remote_post( $url, $options );
		} else {
			$response = safe_wp_remote_get( $url, $options );
		}

		// Parse out the response.
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code         = wp_remote_retrieve_response_code( $response );
		$content_type = wp_remote_retrieve_header( $response, 'content-type' );

		// Return the body if the request was successful and the content type is audio.
		if ( 200 === $code && false !== strpos( $content_type, 'audio' ) ) {
			return $response;
		}

		$body = wp_remote_retrieve_body( $response );
		$json = json_decode( $body, true );

		if ( 200 !== $code ) {
			if ( isset( $json['detail']['message'] ) ) {
				return new WP_Error( $json['detail']['status'] ?? $code, $json['detail']['message'] ?? esc_html__( 'An error occurred', 'classifai' ) );
			} else {
				return new WP_Error( $code, esc_html__( 'An error occurred', 'classifai' ) );
			}
		}

		if ( json_last_error() === JSON_ERROR_NONE ) {
			if ( empty( $json['error'] ) ) {
				return $json;
			} else {
				$message = $json['error']['message'] ?? esc_html__( 'An error occurred', 'classifai' );
				return new WP_Error( $code, $message );
			}
		} elseif ( ! empty( wp_remote_retrieve_response_message( $response ) ) ) {
			return new WP_Error( $code, wp_remote_retrieve_response_message( $response ) );
		} else {
			return new WP_Error( 'Invalid JSON: ' . json_last_error_msg(), $body );
		}
	}

	/**
	 * Sanitize the API key, showing an error message if needed.
	 *
	 * @param array $new_settings Incoming settings, if any.
	 * @param array $settings     Current settings, if any.
	 * @return array
	 */
	public function sanitize_api_key_settings( array $new_settings = [], array $settings = [] ): array {
		$models = $this->get_models( $new_settings[ static::ID ]['api_key'] ?? '' );

		$new_settings[ static::ID ]['authenticated'] = $settings[ static::ID ]['authenticated'];
		$new_settings[ static::ID ]['models']        = $settings[ static::ID ]['models'];

		if ( is_wp_error( $models ) ) {
			$new_settings[ static::ID ]['authenticated'] = false;
			$new_settings[ static::ID ]['models']        = [];
			$error_message                               = $models->get_error_message();

			add_settings_error(
				'api_key',
				'classifai-auth',
				$error_message,
				'error'
			);
		} else {
			$new_settings[ static::ID ]['authenticated'] = true;
			$new_settings[ static::ID ]['models']        = $models;

			if ( $this->feature_instance instanceof TextToSpeech ) {
				// Get the available voices.
				$voices = $this->get_voices( $new_settings[ static::ID ]['api_key'] ?? '' );

				if ( is_wp_error( $voices ) ) {
					$new_settings[ static::ID ]['authenticated'] = false;
					$new_settings[ static::ID ]['voices']        = [];
					add_settings_error(
						'api_key',
						'classifai-elevenlabs-voices-error',
						$voices->get_error_message(),
						'error'
					);
				} else {
					$new_settings[ static::ID ]['voices'] = $voices;
				}
			}
		}

		$new_settings[ static::ID ]['api_key'] = sanitize_text_field( $new_settings[ static::ID ]['api_key'] ?? $settings[ static::ID ]['api_key'] );

		return $new_settings;
	}

	/**
	 * Get the available models.
	 *
	 * @param string $api_key The API key.
	 * @return array|WP_Error
	 */
	protected function get_models( string $api_key = '' ) {
		// Check that we have credentials before hitting the API.
		if ( empty( $api_key ) ) {
			return new WP_Error( 'auth', esc_html__( 'Please enter your ElevenLabs API key.', 'classifai' ) );
		}

		$response = $this->request( $this->get_api_url( $this->model_path ), $api_key, 'get', [ 'use_vip' => true ] );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		// Filter the models based on the current feature.
		if ( $this->feature_instance instanceof TextToSpeech ) {
			$response = array_filter(
				$response,
				function ( $model ) {
					return true === $model['can_do_text_to_speech'];
				}
			);
		}

		// Get the model data we need.
		$models = array_map(
			fn( $model ) => [
				'id'              => $model['model_id'] ?? '',
				'display_name'    => $model['name'] ?? '',
				'max_text_length' => $model['maximum_text_length_per_request'] ?? '',
			],
			$response
		);

		return $models;
	}

	/**
	 * Get the available voices.
	 *
	 * @param string $api_key The API key.
	 * @return array|WP_Error
	 */
	protected function get_voices( string $api_key = '' ) {
		// Check that we have credentials before hitting the API.
		if ( empty( $api_key ) ) {
			return new WP_Error( 'auth', esc_html__( 'Please enter your ElevenLabs API key.', 'classifai' ) );
		}

		$response = $this->request( $this->get_api_url( 'voices?per_page=100' ), $api_key, 'get', [ 'use_vip' => true ] );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		// Get the voice data we need.
		$voices = array_map(
			function ( $voice ) {
				$labels     = $voice['labels'] ?? array();
				$name       = $voice['name'] ?? '';
				$gender     = $labels['gender'] ?? '';
				$language   = $labels['language'] ?? '';
				$accent     = $labels['accent'] ?? '';
				$voice_name = sprintf( '%s (%s) - %s', $name, ucfirst( $gender ), strtoupper( $language ) );
				if ( ! empty( $accent ) ) {
					$voice_name = sprintf( '%s (%s)', $voice_name, ucfirst( $accent ) );
				}
				return [
					'id'   => $voice['voice_id'] ?? '',
					'name' => $voice_name,
				];
			},
			$response['voices'] ?? []
		);

		return $voices;
	}
}
