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
					return $this->transcribe_from_attachment( $audio_resource, $args );
				} elseif ( is_remote_url( $audio_resource ) ) {
					return $this->transcribe_from_path( $audio_resource );
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
	 * Generates a transcript from a given attachment ID.
	 *
	 * Validates that the current user can edit the attachment,
	 * ensures the feature is enabled, and checks whether the attachment
	 * meets the processing criteria (e.g., correct file type and size).
	 *
	 * @param int   $attachment_id Attachment post ID.
	 * @param array $args          Optional arguments to pass to the route.
	 * @return string|WP_Error Transcription result on success, or WP_Error on failure.
	 */
	private function transcribe_from_attachment( int $attachment_id = 0, array $args = [] ) {
		if ( $attachment_id && ! current_user_can( 'edit_post', $attachment_id ) && ( ! defined( 'WP_CLI' ) || ! WP_CLI ) ) {
			return new \WP_Error( 'no_permission', esc_html__( 'User does not have permission to edit this attachment.', 'classifai' ) );
		}

		$feature = new AudioTranscriptsGeneration();

		if ( ! $feature->is_feature_enabled() ) {
			return new WP_Error( 'not_enabled', esc_html__( 'Transcript generation is disabled. Please check your settings.', 'classifai' ) );
		}

		if ( ! $feature->should_process( $attachment_id ) ) {
			return new WP_Error( 'process_error', esc_html__( 'Attachment does not meet processing requirements. Ensure the file type and size meet requirements.', 'classifai' ) );
		}

		return $this->transcribe_audio(
			get_attached_file( $attachment_id ),
			array_merge( $args, array( 'attachment_id' => $attachment_id ) )
		);
	}

	/**
	 * Generates a transcript from a file path or remote URL.
	 *
	 * If the path is a remote URL, it is downloaded to a temporary
	 * location and deleted after processing. If it's a local path
	 * and the file exists, it is processed directly.
	 *
	 * @param string $path Absolute local path or remote URL to an audio file.
	 * @param array  $args  Optional arguments to pass to the route.
	 * @return string|WP_Error Transcription result on success, or WP_Error on failure.
	 */
	private function transcribe_from_path( string $path, array $args = [] ) {
		$result = '';

		if ( \Classifai\is_remote_url( $path ) ) {
			$temp_file_path = AudioTranscriptsGeneration::remote_url_to_path( $path );

			if ( is_wp_error( $temp_file_path ) ) {
				return $temp_file_path;
			}

			$result = $this->transcribe_audio( $temp_file_path, $args );
			wp_delete_file( $temp_file_path );
		} elseif ( \Classifai\is_local_path( $path ) ) {
			if ( file_exists( $path ) ) {
				return $this->transcribe_audio( $path, $args );

			} else {
				return $result;
			}
		}

		return $result;
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
			return new WP_Error( 'not_enabled', esc_html__( 'Audio Transcripts Generation is disabled or OpenAI authentication failed. Please check your settings.', 'classifai' ) );
		}

		$request = new APIRequest( $settings['api_key'] ?? '', $feature->get_option_name() );

		/**
		 * Filter the request body before sending to OpenAI.
		 *
		 * @since 2.2.0
		 * @hook classifai_whisper_transcribe_request_body
		 *
		 * @param array  $body      Request body that will be sent to OpenAI.
		 * @param string $file_path Path of the attachment we are transcribing.
		 * @param array  $args      Additional args.
		 *
		 * @return array Request body.
		 */
		$body = apply_filters(
			'classifai_whisper_transcribe_request_body',
			[
				'file'            => $file_path,
				'model'           => $this->get_model(),
				'response_format' => 'json',
				'temperature'     => 0,
			],
			$file_path,
			$args
		);

		// Make our API request.
		$response = $request->post_form(
			$this->get_api_url( 'transcriptions' ),
			$body
		);

		set_transient( 'classifai_openai_whisper_latest_response', $response, DAY_IN_SECONDS * 30 );

		// Extract out the text response, if it exists.
		if ( ! is_wp_error( $response ) && isset( $response['text'] ) ) {
			$response = $response['text'];
		}

		return $response;
	}
}
