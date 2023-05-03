<?php
/**
 * OpenAI Whisper shared functionality
 */

namespace Classifai\Providers\OpenAI\Whisper;

trait Whisper {

	/**
	 * OpenAI Whisper URL
	 *
	 * @var string
	 */
	protected $whisper_url = 'https://api.openai.com/v1/audio/';

	/**
	 * OpenAI Whisper model
	 *
	 * @var string
	 */
	protected $whisper_model = 'whisper-1';

	/**
	 * Supported file formats
	 *
	 * @var array
	 */
	protected $file_formats = [
		'mp3',
		'mp4',
		'mpeg',
		'mpga',
		'm4a',
		'wav',
		'webm',
	];

	/**
	 * Maximum file size our model supports
	 *
	 * @var int
	 */
	protected $max_file_size = 25 * MB_IN_BYTES;

	/**
	 * Builds the API url.
	 *
	 * @param string $path Path to append to API URL.
	 * @return string
	 */
	public function get_api_url( $path = '' ) {
		return sprintf( '%s%s', trailingslashit( $this->whisper_url ), $path );
	}

	/**
	 * Should this attachment be processed.
	 *
	 * Ensure the file is a supported format and is under the maximum file size.
	 *
	 * @param int $attachment_id Attachment ID to process.
	 * @return boolean
	 */
	public function should_process( int $attachment_id ) {
		$mime_type          = get_post_mime_type( $attachment_id );
		$matched_extensions = explode( '|', array_search( $mime_type, wp_get_mime_types(), true ) );
		$process            = false;

		foreach ( $matched_extensions as $ext ) {
			if ( in_array( $ext, $this->file_formats, true ) ) {
				$process = true;
			}
		}

		// If we have a proper file format, check the file size.
		if ( $process ) {
			$filesize = filesize( get_attached_file( $attachment_id ) );
			if ( ! $filesize || $filesize > $this->max_file_size ) {
				$process = false;
			}
		}

		return $process;
	}

}
