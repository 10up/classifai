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
use function Classifai\get_largest_size_and_dimensions_image_url;

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
	const API_PATH = 'vision/v3.2/ocr/';

	/**
	 * ComputerVision settings.
	 *
	 * @since 1.6.0
	 *
	 * @var array
	 */
	private $settings;

	/**
	 * Force processing
	 *
	 * @since 1.6.0
	 *
	 * @var bool|object
	 */
	private $scan;

	/**
	 * Force processing
	 *
	 * @since 1.6.0
	 *
	 * @var boolean
	 */
	private $force;

	/**
	 * Media types to process.
	 *
	 * @since 1.6.0
	 *
	 * @var array
	 */
	private $media_to_process = [
		'bmp',
		'gif',
		'jpeg',
		'png',
	];

	/**
	 * OCR constructor
	 *
	 * @since 1.6.0
	 *
	 * @param array       $settings Computer Vision settings.
	 * @param bool|object $scan     Previously run image scan.
	 * @param boolean     $force    Whether to force processing or not.
	 */
	public function __construct( array $settings, $scan, bool $force ) {
		$this->settings = $settings;
		$this->scan     = $scan;
		$this->force    = $force;
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
		// Bypass check if this is a force request
		if ( $this->force ) {
			return true;
		}

		$mime_type          = get_post_mime_type( $attachment_id );
		$matched_extensions = explode( '|', array_search( $mime_type, wp_get_mime_types(), true ) );
		$process            = false;

		/**
		 * Filters the media types that should be processed
		 *
		 * @since 1.6.0
		 * @hook classifai_ocr_approved_media_types
		 *
		 * @param {array} $media_types   The media types to process.
		 * @param {int}   $attachment_id The attachment ID.
		 *
		 * @return {array} Filtered media types.
		 */
		$approved_media_types = apply_filters( 'classifai_ocr_approved_media_types', $this->media_to_process, $attachment_id );

		foreach ( $matched_extensions as $ext ) {
			if ( in_array( $ext, $approved_media_types, true ) ) {
				$process = true;
			}
		}

		// If we have a proper image and a previous image scan, check
		// to see if we have proper tags set, with a high confidence
		if ( $process && $this->scan && ! empty( $this->scan->tags ) && is_array( $this->scan->tags ) ) {

			/**
			 * Filters the tags we check for OCR processing
			 *
			 * @since 1.6.0
			 * @hook classifai_ocr_tags
			 *
			 * @param {array}       $tags          Tags to look for. Default handwriting and text.
			 * @param {int}         $attachment_id The attachment ID.
			 * @param {bool|object} $scan          Previously run scan.
			 *
			 * @return {array} Filtered tags.
			 */
			$tags = apply_filters( 'classifai_ocr_tags', [ 'handwriting', 'text' ], $attachment_id, $this->scan );

			/**
			 * Filters the tag confidence level for OCR processing
			 *
			 * @since 1.6.0
			 * @hook classifai_ocr_tag_confidence
			 *
			 * @param {int}         $confidence    The minimum confidence level. Default 90.
			 * @param {int}         $attachment_id The attachment ID.
			 * @param {bool|object} $scan          Previously run scan.
			 *
			 * @return {int} Confidence level.
			 */
			$tag_confidence = apply_filters( 'classifai_ocr_tag_confidence', 90, $attachment_id, $this->scan );

			foreach ( $this->scan->tags as $tag ) {
				if ( in_array( $tag->name, $tags, true ) && $tag->confidence * 100 >= $tag_confidence ) {
					$process = true;
					break;
				}
			}
		}

		/**
		 * Filters whether to run OCR processing on this media item
		 *
		 * @since 1.6.0
		 * @hook classifai_ocr_should_process
		 *
		 * @param {bool}        $process       Whether to run OCR processing or not.
		 * @param {int}         $attachment_id The attachment ID.
		 * @param {bool|object} $scan          Previously run scan.
		 *
		 * @return {bool} Whether this attachment should have OCR processing.
		 */
		return apply_filters( 'classifai_ocr_should_process', $process, $attachment_id, $this->scan );
	}

	/**
	 * Get and save the OCR data
	 *
	 * @since 1.6.0
	 *
	 * @param array   $metadata      Attachment metadata.
	 * @param integer $attachment_id Attachment ID.
	 * @return string|WP_Error
	 */
	public function generate_ocr_data( array $metadata, int $attachment_id ) {
		$rtn = '';

		if ( ! $this->should_process( $attachment_id ) ) {
			return new WP_Error( 'process_error', esc_html__( 'Image does not meet processing requirements.', 'classifai' ), $metadata );
		}

		$url = get_largest_size_and_dimensions_image_url(
			get_attached_file( $attachment_id ),
			wp_get_attachment_url( $attachment_id, 'full' ),
			$metadata,
			[ 50, 4200 ],
			[ 50, 4200 ],
			computer_vision_max_filesize()
		);

		// If a properly sized image isn't found, return
		if ( ! $url ) {
			return new WP_Error( 'size_error', esc_html__( 'Image does not meet size requirements. Please ensure it is at least 50x50 but less than 4200x4200 and smaller than 4MB.', 'classifai' ), $metadata );
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
				 * @param {string} $text The returned text data.
				 * @param {object} $scan The full scan results from the API.
				 *
				 * @return {string} The filtered text data.
				 */
				$text = apply_filters( 'classifai_ocr_text', implode( ' ', $text ), $scan );

				$post_args = [
					'ID'           => $attachment_id,
					'post_content' => sanitize_text_field( $text ),
				];

				/**
				 * Filter the post arguments before saving the text to post_content.
				 *
				 * This enables text to be stored in a different post or post meta field,
				 * or do other post data setting based on scan results.
				 *
				 * @since 1.6.0
				 * @hook classifai_ocr_text_post_args
				 *
				 * @param {string} $post_args     Array of post data for the attachment post update. Defaults to `ID` and `post_content`.
				 * @param {int}    $attachment_id ID of the attachment post.
				 * @param {object} $scan          The full scan results from the API.
				 * @param {string} $text          The text data to be saved.
				 * @param {object} $scan          The full scan results from the API.
				 *
				 * @return {string} The filtered text data.
				 */
				$post_args = apply_filters( 'classifai_ocr_text_post_args', $post_args, $attachment_id, $text, $scan );

				wp_update_post( $post_args );

				$rtn = $text;

				// Save all the results for later
				update_post_meta( $attachment_id, 'classifai_computer_vision_ocr', $scan );
			}
		} else {
			$rtn = $scan;
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
		// Check if valid authentication is in place.
		if ( empty( $this->settings ) || ( isset( $this->settings['authenticated'] ) && false === $this->settings['authenticated'] ) ) {
			return new WP_Error( 'auth', esc_html__( 'Please set up valid authentication with Azure.', 'classifai' ) );
		}

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
		 * @param {array|WP_Error} Response data or a WP_Error if the request failed.
		 * @param {string} The attachment URL.
		 */
		do_action( 'classifai_ocr_after_request', $response, $url );

		if ( ! is_wp_error( $response ) ) {
			$body = json_decode( wp_remote_retrieve_body( $response ) );

			if ( isset( $body->message ) ) {
				$error_message = $body->message;
			} elseif ( isset( $body->error->message ) ) {
				$error_message = $body->error->message;
			} else {
				$error_message = false;
			}

			if ( 200 !== wp_remote_retrieve_response_code( $response ) && $error_message ) {
				/**
				 * Fires when the ocr API response did not succeed.
				 *
				 * @since 1.6.0
				 * @hook classifai_ocr_unsuccessful_response
				 *
				 * @param {array|WP_Error} Response data or a WP_Error if the request failed.
				 * @param {string} The attachment URL.
				 */
				do_action( 'classifai_ocr_unsuccessful_response', $response, $url );

				$rtn = new WP_Error( $body->code ?? 'error', $error_message, $body );
			} else {
				$rtn = $body;
			}
		} else {
			$rtn = $response;
		}

		return $rtn;
	}

}
