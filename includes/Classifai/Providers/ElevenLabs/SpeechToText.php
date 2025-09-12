<?php
/**
 * ElevenLabs Speech to Text integration
 */

namespace Classifai\Providers\ElevenLabs;

use Classifai\Features\AudioTranscriptsGeneration;
use Classifai\Providers\Provider;
use WP_Error;

use function Classifai\is_attachment;
use function Classifai\is_remote_url;
use function Classifai\is_local_path;

class SpeechToText extends Provider {

	use \Classifai\Providers\ElevenLabs\ElevenLabs;

	/**
	 * ID of the current provider.
	 *
	 * @var string
	 */
	const ID = 'elevenlabs_speech_to_text';

	/**
	 * ElevenLabs Speech to Text API path.
	 *
	 * @var string
	 */
	protected $api_path = 'speech-to-text';

	/**
	 * Supported file formats.
	 *
	 * @var array
	 */
	public $file_formats = [
		'mp3',
		'mp4',
		'mpeg',
		'wav',
		'ogg',
	];

	/**
	 * Maximum file size our model supports.
	 *
	 * Note that ElevenLabs supports larger files, up to 3GB.
	 * We enforce a smaller limit to avoid performance issues
	 * since we don't process asynchronously.
	 *
	 * @var int
	 */
	public $max_file_size = 100 * MB_IN_BYTES;

	/**
	 * ElevenLabs Speech to Text constructor.
	 *
	 * @param \Classifai\Features\Feature $feature_instance The feature instance.
	 */
	public function __construct( $feature_instance = null ) {
		$this->feature_instance = $feature_instance;
	}

	/**
	 * Get the default settings for this provider.
	 *
	 * @return array
	 */
	public function get_default_provider_settings(): array {
		return [
			'api_key'       => '',
			'authenticated' => false,
			'model'         => '',
			'models'        => [],
		];
	}

	/**
	 * Sanitize the settings for this provider.
	 *
	 * @param array $new_settings New settings.
	 * @return array
	 */
	public function sanitize_settings( array $new_settings ): array {
		$settings         = $this->feature_instance->get_settings();
		$api_key_settings = $this->sanitize_api_key_settings( $new_settings, $settings );

		$new_settings[ static::ID ]['api_key']       = $api_key_settings[ static::ID ]['api_key'];
		$new_settings[ static::ID ]['authenticated'] = $api_key_settings[ static::ID ]['authenticated'];

		// Speech To Text only supports two models and they don't seem to be
		// in the models endpoint, so we hardcode them here.
		$new_settings[ static::ID ]['models'] = [
			[
				'id'           => 'scribe_v1',
				'display_name' => 'Scribe v1',
			],
			[
				'id'           => 'scribe_v1_experimental',
				'display_name' => 'Scribe v1 Experimental',
			],
		];

		$new_settings[ static::ID ]['model'] = sanitize_text_field( $new_settings[ static::ID ]['model'] ?? $settings[ static::ID ]['model'] );

		// Ensure the model being saved is valid. If not valid or we don't have one, use the first model.
		if ( ! in_array( $new_settings[ static::ID ]['model'], array_column( $new_settings[ static::ID ]['models'], 'id' ), true ) ) {
			$new_settings[ static::ID ]['model'] = array_column( $new_settings[ static::ID ]['models'], 'id' )[0];
		}

		return $new_settings;
	}

	/**
	 * Common entry point for all REST endpoints for this provider.
	 *
	 * @param string $audio_resource Attachment ID, URL or system file path to the audio resource.
	 * @param string $route_to_call The route we are processing.
	 * @param array  $args Optional arguments to pass to the route.
	 * @return string|WP_Error
	 */
	public function rest_endpoint_callback( string $audio_resource, string $route_to_call = '', array $args = [] ) {
		switch ( $route_to_call ) {
			case 'transcript':
				if ( is_attachment( $audio_resource ) ) {
					return $this->feature_instance->transcribe_from_attachment( $audio_resource, $args );
				} elseif ( is_remote_url( $audio_resource ) ) {
					return $this->feature_instance->transcribe_from_path( $audio_resource );
				} elseif ( is_local_path( $audio_resource ) ) {
					return $this->transcribe_audio( $audio_resource, $args );
				}

				break;
			default:
				break;
		}

		return new WP_Error( 'invalid_route', esc_html__( 'Invalid route.', 'classifai' ) );
	}

	/**
	 * Run the audio transcription process.
	 *
	 * @param string $file_path File system path.
	 * @param array  $args      Optional arguments passed in.
	 * @return WP_Error|bool
	 */
	public function transcribe_audio( string $file_path = '', array $args = [] ) {
		$feature  = new AudioTranscriptsGeneration();
		$settings = $feature->get_settings( static::ID );

		if ( ! $feature->is_feature_enabled() ) {
			return new WP_Error( 'not_enabled', esc_html__( 'Audio Transcripts Generation is disabled or ElevenLabs authentication failed. Please check your settings.', 'classifai' ) );
		}

		/**
		 * Filter the request body before sending to ElevenLabs.
		 *
		 * @since 3.7.0
		 * @hook classifai_elevenlabs_transcribe_request_body
		 *
		 * @param array  $body      Request body that will be sent to ElevenLabs.
		 * @param string $file_path Path of the attachment we are transcribing.
		 * @param array  $args      Additional args.
		 *
		 * @return array Request body.
		 */
		$body = apply_filters(
			'classifai_elevenlabs_transcribe_request_body',
			[
				'file'     => $file_path,
				'model_id' => sanitize_text_field( $settings['model'] ),
			],
			$file_path,
			$args
		);

		$boundary = wp_generate_password( 24, false );
		$payload  = '';

		// Take all our fields and transform them to work with form-data.
		foreach ( $body as $name => $value ) {
			$payload .= '--' . $boundary;
			$payload .= "\r\n";

			if ( 'file' === $name ) {
				$payload .= 'Content-Disposition: form-data; name="file"; filename="' . basename( $value ) . '"' . "\r\n";
				$payload .= "\r\n";
				$payload .= file_get_contents( $value ); // phpcs:ignore
			} else {
				$payload .= 'Content-Disposition: form-data; name="' . esc_attr( $name ) .
					'"' . "\r\n\r\n";
				$payload .= esc_attr( $value );
			}

			$payload .= "\r\n";
		}

		$payload .= '--' . $boundary . '--';

		// Make our API request.
		$response = $this->request(
			$this->get_api_url( $this->api_path ),
			$settings['api_key'] ?? '',
			'post',
			[
				'body'    => $payload,
				'headers' => [
					'Content-Type' => 'multipart/form-data; boundary=' . $boundary,
				],
			]
		);

		$return = '';

		// Extract out the text response, if it exists.
		if ( ! is_wp_error( $response ) && isset( $response['text'] ) ) {
			$return = $response['text'];
		} elseif ( is_wp_error( $response ) ) {
			return $response;
		}

		return $return;
	}
}
