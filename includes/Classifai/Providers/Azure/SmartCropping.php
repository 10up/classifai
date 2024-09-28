<?php
/**
 * Provides smart cropping with the Computer Vision service.
 *
 * @since 1.5.0
 * @package Classifai
 */

namespace Classifai\Providers\Azure;

use function Classifai\computer_vision_max_filesize;
use function Classifai\get_largest_acceptable_image_url;

/**
 * SmartCropping class.
 * Connects to Computer Vision's generateThumbnail endpoint to crop images to a region of interest.
 *
 * @see https://docs.microsoft.com/en-us/rest/api/cognitiveservices/computervision/generatethumbnail/
 * @since 1.5.0
 */
class SmartCropping {
	/**
	 * The Computer Vision API path to the thumbnail generation service.
	 *
	 * @since 1.5.0
	 *
	 * @var string
	 */
	const API_PATH = 'vision/v3.2/generateThumbnail/';

	/**
	 * ComputerVisition settings.
	 *
	 * @since 1.5.0
	 *
	 * @var array
	 */
	private $settings;

	/**
	 * SmartCropping constructor
	 *
	 * @since 1.5.0
	 *
	 * @param array $settings Computer Vision settings.
	 */
	public function __construct( array $settings ) {
		$this->settings = $settings;
	}

	/**
	 * Provides the maximum allowable width or height in pixels accepted by the generateThumbnail endpoint.
	 *
	 * @since 1.5.0
	 * @see https://docs.microsoft.com/en-us/rest/api/cognitiveservices/computervision/generatethumbnail/generatethumbnail#uri-parameters
	 *
	 * @return int
	 */
	public function get_max_pixel_dimension(): int {
		/**
		 * Filters the maximum allowable width or height of an image to be cropped. Default 1024.
		 *
		 * @since 1.5.0
		 * @hook classifai_smart_crop_max_pixel_dimension
		 *
		 * @param {int} $max The max width/height in pixels. Default 1024.
		 *
		 * @return {int} Filtered max dimension in pixels.
		 */
		return apply_filters( 'classifai_smart_crop_max_pixel_dimension', 1024 );
	}

	/**
	 * Returns whether smart cropping should be applied to images of a given size.
	 *
	 * @since 1.5.0
	 *
	 * @param string $size An image size.
	 * @return bool
	 */
	public function should_crop( string $size ): bool {
		if ( 'thumbnail' === $size ) {
			return boolval( get_option( 'thumbnail_crop', false ) );
		}

		$image_sizes = wp_get_additional_image_sizes();
		if ( ! isset( $image_sizes[ $size ] )
			|| ! isset( $image_sizes[ $size ]['height'] )
			|| ! isset( $image_sizes[ $size ]['width'] ) ) {
			return false;
		}

		// If positions are specified in the add_image_size crop argument, as indicated by the crop field being an
		// array, then that should take priority and smart cropping should not run.
		if ( is_array( $image_sizes[ $size ]['crop'] ) ) {
			$return = false;
		} else {
			$return = boolval( $image_sizes[ $size ]['crop'] );
		}

		$max_pixels = $this->get_max_pixel_dimension();
		if ( $max_pixels < $image_sizes[ $size ]['height'] || $max_pixels < $image_sizes[ $size ]['width'] ) {
			$return = false;
		}

		/**
		 * Filters whether to smart crop images of a given size.
		 *
		 * @since 1.5.0
		 * @hook classifai_should_crop_size
		 *
		 * @param {bool}   $return Whether non-position-based cropping was opted into when registering the image size.
		 * @param {string} $size   The image size.
		 *
		 * @return {bool} Whether this image size should be smart cropped.
		 */
		return apply_filters( 'classifai_should_crop_size', $return, $size );
	}

	/**
	 * Generate cropped image sizes.
	 *
	 * @param array $metadata Image attachment metadata.
	 * @param int   $attachment_id Attachment ID
	 * @return array|\WP_Error
	 */
	public function generate_cropped_images( array $metadata, int $attachment_id ) {
		$cropped_images = [];

		if ( ! isset( $metadata['sizes'] ) || empty( $metadata['sizes'] ) ) {
			return $cropped_images;
		}

		foreach ( $metadata['sizes'] as $size => $size_data ) {
			if ( ! $this->should_crop( $size ) ) {
				continue;
			}

			$data = [
				'width'  => $size_data['width'],
				'height' => $size_data['height'],
			];

			$data = $this->get_cropped_thumbnail( $attachment_id, $size_data );

			if ( is_wp_error( $data ) ) {
				return $data;
			}

			$cropped_images[ $size ] = [
				'width'  => $size_data['width'],
				'height' => $size_data['height'],
				'data'   => $data,
			];
		}

		return $cropped_images;
	}

	/**
	 * Gets a cropped thumbnail from the Azure API.
	 *
	 * @since 1.5.0.
	 *
	 * @param int   $attachment_id Attachment ID.
	 * @param array $size_data Attachment metadata size data.
	 * @return string|\WP_Error
	 */
	public function get_cropped_thumbnail( int $attachment_id, array $size_data ) {
		/**
		 * Filters the image URL to send to AI Vision for smart cropping.
		 *
		 * A non-null value will override default plugin behavior.
		 *
		 * @since 1.5.0
		 * @hook classifai_smart_cropping_source_url
		 *
		 * @param {null|string} url            `null` to use default plugin behavior; `string` to override.
		 * @param {int}         $attachment_id The attachment image ID.
		 *
		 * @return {null|string} URL to be sent to Computer Vision for smart cropping.
		 */
		$url = apply_filters( 'classifai_smart_cropping_source_url', null, $attachment_id );

		if ( empty( $url ) ) {
			$url = get_largest_acceptable_image_url(
				get_attached_file( $attachment_id ),
				wp_get_attachment_url( $attachment_id ),
				$size_data,
				computer_vision_max_filesize()
			);
		}

		if ( empty( $url ) || empty( $size_data ) || ! is_array( $size_data ) ) {
			return new \WP_Error( 'classifai_smart_cropping_invalid_args', 'Invalid arguments for API request.' );
		}

		$data = [
			'width'  => $size_data['width'],
			'height' => $size_data['height'],
			'url'    => $url,
		];

		$new_thumb_image = $this->request_cropped_thumbnail( $data );

		set_transient( 'classifai_azure_computer_vision_image_cropping_latest_response', $new_thumb_image, DAY_IN_SECONDS * 30 );

		return $new_thumb_image;
	}

	/**
	 * Builds the API url.
	 *
	 * @since 1.5.0
	 *
	 * @return string
	 */
	public function get_api_url(): string {
		return sprintf( '%s%s', trailingslashit( $this->settings['endpoint_url'] ), static::API_PATH );
	}

	/**
	 * Fetch thumbnail using Azure API.
	 *
	 * @since 1.5.0
	 *
	 * @param array $data Data for an attachment image size.
	 * @return string|\WP_Error
	 */
	public function request_cropped_thumbnail( array $data ) {
		$url = add_query_arg(
			[
				'height'        => $data['height'],
				'width'         => $data['width'],
				'smartCropping' => 'true',
			],
			$this->get_api_url()
		);

		$response = wp_remote_post(
			$url,
			[
				'body'    => wp_json_encode(
					[
						'url' => $data['url'],
					]
				),
				'headers' => [
					'Content-Type'              => 'application/json',
					'Ocp-Apim-Subscription-Key' => $this->settings['api_key'],
				],
			]
		);

		/**
		 * Fires after the request to the generateThumbnail smart-cropping endpoint has run.
		 *
		 * @since 1.5.0
		 * @hook classifai_smart_cropping_after_request
		 *
		 * @param {array|WP_Error} Response data or a WP_Error if the request failed.
		 * @param {string} The request URL with query args added.
		 * @param {array}  Array containing the image height and width.
		 */
		do_action( 'classifai_smart_cropping_after_request', $response, $url, $data );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$response_body = wp_remote_retrieve_body( $response );

		if ( 200 === wp_remote_retrieve_response_code( $response ) ) {
			return $response_body;
		}

		$response_json = json_decode( $response_body );

		/**
		 * Fires when the generateThumbnail smart-cropping API response did not have a 200 status code.
		 *
		 * @since 1.5.0
		 * @hook classifai_smart_cropping_unsuccessful_response
		 *
		 * @param {array|WP_Error} Response data or a WP_Error if the request failed.
		 * @param {string} The request URL with query args added.
		 * @param {array}  Array containing the image height and width.
		 */
		do_action( 'classifai_smart_cropping_unsuccessful_response', $response, $url, $data );

		if ( ! empty( $response_json->code ) ) {
			return new \WP_Error( $response_json->code, $response_json->message );
		}

		if ( ! empty( $response_json->error ) ) {
			return new \WP_Error( $response_json->error->code, $response_json->error->message );
		}

		if ( ! empty( $response_json->errors ) ) {
			return new \WP_Error( 'classifai_smart_cropping_api_validation_errors', implode( ' ', $response_json->errors->smartCropping ) );
		}

		return new \WP_Error( 'classifai_smart_cropping_failed', 'A Smart Cropping error occurred.' );
	}
}
