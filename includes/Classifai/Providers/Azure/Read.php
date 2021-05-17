<?php
/**
 * Provides OCR detection with the Computer Vision service.
 *
 * @since 1.6.0
 * @package Classifai
 */

namespace Classifai\Providers\Azure;

use WP_Error;
use function Classifai\computer_vision_max_filesize;

/**
 * Read class
 *
 * Connects to Computer Vision's Read endpoint to detect text.
 *
 * @see https://docs.microsoft.com/en-us/rest/api/cognitiveservices/computervision/recognizeprintedtext/
 * @since 1.6.0
 */
class Read {

	/**
	 * The Computer Vision API path to the Read service.
	 *
	 * @since 1.6.0
	 *
	 * @var string
	 */
	const API_PATH = 'vision/v3.2/read/';

	/**
	 * ComputerVision settings.
	 *
	 * @since 1.6.0
	 *
	 * @var array
	 */
	private $settings;

	/**
	 * Attachment ID to process.
	 *
	 * @since 1.6.0
	 *
	 * @var boolean
	 */
	private $attachment_id;

	/**
	 * Force processing
	 *
	 * @since 1.6.0
	 *
	 * @var boolean
	 */
	private $force;

	/**
	 * Constructor
	 *
	 * @since 1.6.0
	 *
	 * @param array       $settings Computer Vision settings.
	 * @param boolean     $force    Whether to force processing or not.
	 */
	public function __construct( array $settings, bool $force = false ) {
		$this->settings = $settings;
		$this->force    = $force;
	}

	/**
	 * Builds the API url.
	 *
	 * @since 1.6.0
	 *
	 * @return string
	 */
	public function get_api_url( $path = '' ) {
		return sprintf( '%s%s%s', trailingslashit( $this->settings['url'] ), static::API_PATH, $path );
	}

	/**
	 * Returns whether Read processing should be applied to the attachment
	 *
	 * @since 1.6.0
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return boolean
	 */
	public function should_process( int $attachment_id ) {
		// Bypass check if this is a force request
		if ( $this->force ) {
			return true;
		}

		$mime_type          = get_post_mime_type( $attachment_id );
		$matched_extensions = explode( '|', array_search( $mime_type, wp_get_mime_types(), true ) );
		$process            = false;

		$approved_media_types = [ 'pdf' ];

		foreach ( $matched_extensions as $ext ) {
			if ( in_array( $ext, $approved_media_types, true ) ) {
				$process = true;
			}
		}

		/**
		 * Filters whether to run Read processing on this attachment item
		 *
		 * @since 1.6.0
		 * @hook classifai_read_should_process
		 *
		 * @param bool        $process       Whether to run OCR processing or not.
		 * @param int         $attachment_id The attachment ID.
		 *
		 * @return bool Whether this attachment should have OCR processing.
		 */
		return apply_filters( 'classifai_read_should_process', $process, $attachment_id );
	}

	/**
	 * Run OCR processing using the Azure API
	 *
	 * @since 1.6.0
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return object|WP_Error
	 */
	public function scan_document( $attachment_id ) {
		if ( ! $this->should_process( $attachment_id ) ) {
			return $this->log( new WP_Error( 'processError', esc_html__( 'Document does not meet processing requirements.', 'classifai' ), $attachment_id ), $attachment_id );
		}

		$filesize = filesize( get_attached_file( $attachment_id ) );
		if ( ! $filesize || $filesize > computer_vision_max_filesize() ) {
			return $this->log( new WP_Error( 'sizeError', esc_html__( 'Document does not meet size requirements. Please ensure it is smaller than the maximum threshold (default to 4MB).', 'classifai' ), $metadata ), $attachment_id );
		}

		$request_args = apply_filters( 'classifai_read_request_args', [], $attachment_id );

		$url = add_query_arg(
			$request_args,
			$this->get_api_url( 'analyze' )
		);

		$document_url = wp_get_attachment_url( $attachment_id );

		if ( ! $document_url ) {
			return $this->log( new WP_Error( 'invalid_attachment', esc_html__( 'Document does not exist.', 'classifai' ), $attachment_id ), $attachment_id );
		}

		$response = wp_remote_post(
			$url,
			[
				'body'    => wp_json_encode(
					[
						'url' => $document_url,
					]
				),
				'headers' => [
					'Content-Type'              => 'application/json',
					'Ocp-Apim-Subscription-Key' => $this->settings['api_key'],
				],
			]
		);

		/**
		 * Fires after the request to the read endpoint has run.
		 *
		 * @since 1.5.0
		 * @hook classifai_read_after_request
		 *
		 * @param array|WP_Error Response data or a WP_Error if the request failed.
		 * @param string The request URL with query args added.
		 * @param int The document ID.
		 * @param string The document URL.
		 */
		do_action( 'classifai_read_after_request', $response, $url, $attachment_id, $document_url );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( 202 === wp_remote_retrieve_response_code( $response ) ) {
			$operation_url = wp_remote_retrieve_header( $response, 'Operation-Location' );
			if ( ! filter_var( $operation_url, FILTER_VALIDATE_URL ) ) {
				return $this->log( new WP_Error( 'invalid_read_operation_url', esc_html__( 'Operation URL is invalid.', 'classifai' ), $attachment_id ), $attachment_id );
			}
			return $this->check_read_result( $operation_url, $attachment_id );
		}

	}

	/**
	 * Use WP Cron to preodically check the status of the read operation.
	 * 
	 * @param string $operation_url Operation URL for checking the read status.
	 * @param int    $attachment_id Attachment ID.
	 * 
	 * @return WP_Error|null|array
	 */
	public function check_read_result( $operation_url, $attachment_id ) {
		if ( function_exists( 'vip_safe_wp_remote_get' ) ) {
			$response = vip_safe_wp_remote_get( $operation_url );
		} else {
			$response = wp_remote_get(
				$operation_url,
				[
					'headers' => [
						'Ocp-Apim-Subscription-Key' => $this->settings['api_key'],
					],
				]
			);
		}

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( 200 === wp_remote_retrieve_response_code( $response ) ) {
			$body = json_decode( wp_remote_retrieve_body( $response ), true );

			if ( empty( $body['status'] ) ) {
				return $this->log( new WP_Error( 'invalid_read_result', esc_html__( 'Invalid read result.', 'classifai' ), $attachment_id ), $attachment_id );
			}

			switch( $body['status'] ) {
				case 'notStarted':
				case 'running':
					$retry_interval = apply_filters( 'classifai_read_retry_interval', MINUTE_IN_SECONDS );
					wp_schedule_single_event( time() + $retry_interval, 'classifai_retry_get_read_result', [ $operation_url, $attachment_id ] );
					break;
				case 'failed':
					return $this->log( new WP_Error( 'failed_read_request', esc_html__( 'The read operation has failed.', 'classifai' ), $attachment_id ), $attachment_id );
					break;
				case 'succeeded':
					return $this->update_document_description( $attachment_id, $body );
					break;
				default:
					return $this->log( new WP_Error( 'invalid_read_result_status', esc_html__( 'Invalid result status.', 'classifai' ), $attachment_id ), $attachment_id );
					break;
			}
		}
	}

	/**
	 * Update document desctiption using text received from Read API.
	 * 
	 * @param int   $attachment_id Attachment ID.
	 * @param array $data          Read result.
	 * 
	 * @return WP_Error|array
	 */
	public function update_document_description( $attachment_id, $data ) {
		if ( empty( $data['analyzeResult'] ) || empty( $data['analyzeResult']['readResults'] ) ) {
			return $this->log( new WP_Error( 'invalid_read_result', esc_html__( 'The Read result is invalid.', 'classifai' ), $attachment_id ), $attachment_id );
		}

		$max_page = min( apply_filters( 'classifai_read_result_max_page', 2 ), count( $data['analyzeResult']['readResults'] ) );

		$lines_of_text = [];

		for ( $page = 0; $page < $max_page; $page++ ) {
			foreach( $data['analyzeResult']['readResults'][$page]['lines'] as $line ) {
				$lines_of_text[] = $line['text'];
			}
		}

		$lines_of_text = apply_filters( 'classifai_read_text_result', $lines_of_text, $attachment_id, $data );

		$update = wp_update_post(
			[
				'ID'           => $attachment_id,
				'post_content' => implode( ' ', $lines_of_text ),
			]
		);

		if ( is_wp_error( $update ) ) {
			return $update;
		}

		return [ 'success' => true ];
	}

	/**
	 * Log error to metadata for troubleshooting.
	 * 
	 * @param WP_Error $error         WP_Error object.
	 * @param int      $attachment_id Attachment ID.
	 */
	private function log( $error, $attachment_id ) {
		update_post_meta( $attachment_id, '_classifai_read_pdf_log', $error->get_error_message() );
	}
}
