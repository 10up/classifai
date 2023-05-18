<?php
/**
 * Transcribe audio files using the OpenAI Whisper API.
 *
 * @since 2.2.0
 */

namespace Classifai\Providers\OpenAI\Whisper;

use Classifai\Providers\OpenAI\APIRequest;
use WP_Error;

/**
 * Transcribe class
 *
 * Uses OpenAI's Whisper API.
 *
 * @see https://platform.openai.com/docs/guides/speech-to-text
 */
class Transcribe {

	use \Classifai\Providers\OpenAI\Whisper\Whisper;

	/**
	 * Attachment ID to process.
	 *
	 * @var boolean
	 */
	private $attachment_id;

	/**
	 * OpenAI Whisper settings.
	 *
	 * @var array
	 */
	private $settings;

	/**
	 * OpenAI Whisper path.
	 *
	 * @var string
	 */
	protected $path = 'transcriptions';

	/**
	 * Constructor
	 *
	 * @param int   $attachment_id Attachment ID to process.
	 * @param array $settings OpenAI Whisper settings.
	 */
	public function __construct( int $attachment_id, array $settings = [] ) {
		$this->attachment_id = $attachment_id;
		$this->settings      = $settings;
	}

	/**
	 * Transcribe the audio file.
	 *
	 * @return string|WP_Error
	 */
	public function process() {
		if ( ! $this->should_process( $this->attachment_id ) ) {
			return new WP_Error( 'process_error', esc_html__( 'Attachment does not meet processing requirements. Ensure the file type and size meet requirements.', 'classifai' ) );
		}

		$request = new APIRequest( $this->settings['api_key'] ?? '' );

		/**
		 * Filter the request body before sending to Whisper.
		 *
		 * @since 2.2.0
		 * @hook classifai_whisper_transcribe_request_body
		 *
		 * @param {array} $body Request body that will be sent to Whisper.
		 * @param {int} $attachment_id ID of attachment we are transcribing.
		 *
		 * @return {array} Request body.
		 */
		$body = apply_filters(
			'classifai_whisper_transcribe_request_body',
			[
				'file'            => get_attached_file( $this->attachment_id ) ?? '',
				'model'           => $this->whisper_model,
				'response_format' => 'json',
				'temperature'     => 0,
			],
			$this->attachment_id
		);

		// Make our API request.
		$response = $request->post_form(
			$this->get_api_url( $this->path ),
			$body
		);

		set_transient( 'classifai_openai_whisper_latest_response', $response, DAY_IN_SECONDS * 30 );

		// Extract out the text response, if it exists.
		if ( ! is_wp_error( $response ) && isset( $response['text'] ) ) {
			$response = $this->add_transcription( $response['text'] );
		}

		return $response;
	}

	/**
	 * Add the transcribed text to the attachment.
	 *
	 * @param string $text Transcription result.
	 * @return string|WP_Error
	 */
	public function add_transcription( string $text = '' ) {
		if ( empty( $text ) ) {
			return new WP_Error( 'invalid_result', esc_html__( 'The transcription result is invalid.', 'classifai' ) );
		}

		/**
		 * Filter the text result returned from Whisper API.
		 *
		 * @since 2.2.0
		 * @hook classifai_whisper_transcribe_result
		 *
		 * @param {string} $text Text extracted from the response.
		 * @param {int}    $attachment_id The attachment ID.
		 *
		 * @return {string}
		 */
		$text = apply_filters( 'classifai_whisper_transcribe_result', $text, $this->attachment_id );

		$update = wp_update_post(
			[
				'ID'           => (int) $this->attachment_id,
				'post_content' => wp_kses_post( $text ),
			],
			true
		);

		if ( is_wp_error( $update ) ) {
			return $update;
		} else {
			return $text;
		}
	}

}
