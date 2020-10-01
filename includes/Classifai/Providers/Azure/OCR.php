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
use function Classifai\get_largest_acceptable_image_url;

/**
 * OCR class
 *
 * Connects to Computer Vision's ocr endpoint to detect text.
 *
 * @see https://docs.microsoft.com/en-us/rest/api/cognitiveservices/computervision/recognizeprintedtext/
 * @since 1.6.0
 */
class OCR {

	/**
	 * The Computer Vision API path to the OCR service.
	 *
	 * @since 1.6.0
	 *
	 * @var string
	 */
	const API_PATH = 'vision/v3.0/ocr/';

	/**
	 * ComputerVisition settings.
	 *
	 * @since 1.6.0
	 *
	 * @var array
	 */
	private $settings;

	/**
	 * Media types to process.
	 *
	 * @since 1.6.0
	 *
	 * @var array
	 */
	private $media_to_process = [
		'png',
	];

	/**
	 * OCR constructor
	 *
	 * @since 1.6.0
	 *
	 * @param array $settings Computer Vision settings.
	 */
	public function __construct( array $settings ) {
		$this->settings = $settings;
	}

	/**
	 * Builds the API url.
	 *
	 * @since 1.6.0
	 *
	 * @return string
	 */
	public function get_api_url() {
		return sprintf( '%s%s', trailingslashit( $this->settings['url'] ), static::API_PATH );
	}

	/**
	 * Returns whether OCR processing should be applied to the attachment
	 *
	 * @since 1.6.0
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return boolean
	 */
	public function should_process( int $attachment_id ) {
		$mime_type          = get_post_mime_type( $attachment_id );
		$matched_extensions = explode( '|', array_search( $mime_type, wp_get_mime_types(), true ) );
		$process            = false;

		foreach ( $matched_extensions as $ext ) {
			if ( in_array( $ext, $this->media_to_process, true ) ) {
				$process = true;
			}
		}

		/**
		 * Filters whether to run OCR processing on this media item
		 *
		 * @since 1.6.0
		 * @hook classifai_ocr_should_process
		 *
		 * @param bool $process       Whether to run OCR processing or not.
		 * @param int  $attachment_id The attachment ID.
		 *
		 * @return bool Whether this attachment should have OCR processing.
		 */
		return apply_filters( 'classifai_ocr_should_process', $process, $attachment_id );
	}

	/**
	 * Get and save the OCR data
	 *
	 * @since 1.6.0
	 *
	 * @param array   $metadata      Attachment metadata.
	 * @param integer $attachment_id Attachment ID.
	 * @return string
	 */
	public function generate_ocr_data( array $metadata, int $attachment_id ) {
		$rtn = '';

		if ( ! $this->should_process( $attachment_id ) ) {
			return $rtn;
		}

		$url = wp_get_attachment_url( $attachment_id, 'full' );

		if ( isset( $metadata['sizes'] ) && is_array( $metadata['sizes'] ) ) {
			$url = get_largest_acceptable_image_url(
				get_attached_file( $attachment_id ),
				wp_get_attachment_url( $attachment_id, 'full' ),
				$metadata['sizes'],
				computer_vision_max_filesize()
			);
		}

		$scan = $this->process( $url );

		set_transient( 'classifai_azure_computer_vision_ocr_latest_response', $scan, DAY_IN_SECONDS * 30 );

		if ( ! is_wp_error( $scan ) && isset( $scan->regions ) ) {
			$text = [];

			// Iterate down the chain to find the text we want
			foreach ( $scan->regions as $region ) {
				foreach ( $region->lines as $lines ) {
					foreach ( $lines->words as $word ) {
						if ( isset( $word->text ) ) {
							$text[] = $word->text;
						}
					}
				}
			}

			if ( ! empty( $text ) ) {

				/**
				 * Filter the text returned from the API.
				 *
				 * @since 1.6.0
				 * @hook classifai_ocr_text
				 *
				 * @param {array} $captions The returned caption data.
				 *
				 * @return {array} The filtered caption data.
				 */
				$text = apply_filters( 'classifai_ocr_text', implode( ' ', $text ) );

				wp_update_post(
					[
						'ID'           => $attachment_id,
						'post_content' => sanitize_text_field( $text ),
					]
				);

				$rtn = $text;
			}

			// Save all the results for later
			update_post_meta( $attachment_id, 'classifai_computer_vision_ocr', $scan );
		}

		return $rtn;
	}

	/**
	 * Run OCR processing using the Azure API
	 *
	 * @since 1.6.0
	 *
	 * @param string $url Media URL.
	 * @return object|WP_Error
	 */
	public function process( string $url ) {
		$response = wp_remote_post(
			$this->get_api_url(),
			[
				'body'    => wp_json_encode(
					[
						'url' => $url,
					]
				),
				'headers' => [
					'Content-Type'              => 'application/json',
					'Ocp-Apim-Subscription-Key' => $this->settings['api_key'],
				],
			]
		);

		/**
		 * Fires after the request to the ocr endpoint has run.
		 *
		 * @since 1.6.0
		 * @hook classifai_ocr_after_request
		 *
		 * @param array|WP_Error Response data or a WP_Error if the request failed.
		 * @param string The attachment URL.
		 */
		do_action( 'classifai_ocr_after_request', $response, $url );

		if ( ! is_wp_error( $response ) ) {
			$body = json_decode( wp_remote_retrieve_body( $response ) );

			if ( 200 !== wp_remote_retrieve_response_code( $response ) && isset( $body->message ) ) {

				/**
				 * Fires when the ocr API response did not succeed.
				 *
				 * @since 1.6.0
				 * @hook classifai_ocr_unsuccessful_response
				 *
				 * @param array|WP_Error Response data or a WP_Error if the request failed.
				 * @param string The attachment URL.
				 */
				do_action( 'classifai_ocr_unsuccessful_response', $response, $url );

				$rtn = new WP_Error( $body->code ?? 'error', $body->message, $body );
			} else {
				$rtn = $body;
			}
		} else {
			$rtn = $response;
		}

		return $rtn;
	}

}
