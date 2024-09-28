<?php

namespace Classifai\Providers\GoogleAI;

use WP_Error;

/**
 * The APIRequest class is a low level class to make Google AI API
 * requests.
 *
 * The returned response is parsed into JSON and returned as an
 * associative array.
 *
 * Usage:
 *
 * $request = new Classifai\Providers\GoogleAI\APIRequest();
 * $request->post( $googleai_url, $options );
 */
class APIRequest {

	/**
	 * The Google AI API key.
	 *
	 * @var string
	 */
	public $api_key;

	/**
	 * The feature name.
	 *
	 * @var string
	 */
	public $feature;

	/**
	 * Google AI APIRequest constructor.
	 *
	 * @param string $api_key Google AI API key.
	 * @param string $feature Feature name.
	 */
	public function __construct( string $api_key = '', string $feature = '' ) {
		$this->api_key = $api_key;
		$this->feature = $feature;
	}

	/**
	 * Makes an authorized GET request.
	 *
	 * @param string $url     The Google AI API url
	 * @param array  $options Additional query params
	 * @return array|WP_Error
	 */
	public function get( string $url, array $options = [] ) {
		/**
		 * Filter the URL for the get request.
		 *
		 * @since 3.0.0
		 * @hook classifai_googleai_api_request_get_url
		 *
		 * @param {string} $url The URL for the request.
		 * @param {array}  $options The options for the request.
		 * @param {string} $this->feature The feature name.
		 *
		 * @return {string} The URL for the request.
		 */
		$url = apply_filters( 'classifai_googleai_api_request_get_url', $url, $options, $this->feature );

		/**
		 * Filter the options for the get request.
		 *
		 * @since 3.0.0
		 * @hook classifai_googleai_api_request_get_options
		 *
		 * @param {array}  $options The options for the request.
		 * @param {string} $url The URL for the request.
		 * @param {string} $this->feature The feature name.
		 *
		 * @return {array} The options for the request.
		 */
		$options = apply_filters( 'classifai_googleai_api_request_get_options', $options, $url, $this->feature );

		$this->add_headers( $options );

		/**
		 * Filter the response from Google AI for a get request.
		 *
		 * @since 3.0.0
		 * @hook classifai_googleai_api_response_get
		 *
		 * @param {array|WP_Error} $response API response.
		 * @param {string} $url Request URL.
		 * @param {array} $options Request body options.
		 * @param {string} $this->feature Feature name.
		 *
		 * @return {array} API response.
		 */
		return apply_filters(
			'classifai_googleai_api_response_get',
			$this->get_result( wp_remote_get( $url, $options ) ), // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.wp_remote_get_wp_remote_get
			$url,
			$options,
			$this->feature
		);
	}

	/**
	 * Makes an authorized POST request.
	 *
	 * @param string $url     The Google AI API URL.
	 * @param array  $options Additional query params.
	 * @return array|WP_Error
	 */
	public function post( string $url = '', array $options = [] ) {
		$options = wp_parse_args(
			$options,
			[
				'timeout' => 60, // phpcs:ignore WordPressVIPMinimum.Performance.RemoteRequestTimeout.timeout_timeout
			]
		);

		/**
		 * Filter the URL for the post request.
		 *
		 * @since 3.0.0
		 * @hook classifai_googleai_api_request_post_url
		 *
		 * @param {string} $url The URL for the request.
		 * @param {array} $options The options for the request.
		 * @param {string} $this->feature The feature name.
		 *
		 * @return {string} The URL for the request.
		 */
		$url = apply_filters( 'classifai_googleai_api_request_post_url', $url, $options, $this->feature );

		/**
		 * Filter the options for the post request.
		 *
		 * @since 3.0.0
		 * @hook classifai_googleai_api_request_post_options
		 *
		 * @param {array} $options The options for the request.
		 * @param {string} $url The URL for the request.
		 * @param {string} $this->feature The feature name.
		 *
		 * @return {array} The options for the request.
		 */
		$options = apply_filters( 'classifai_googleai_api_request_post_options', $options, $url, $this->feature );

		$this->add_headers( $options );

		/**
		 * Filter the response from Google AI for a post request.
		 *
		 * @since 3.0.0
		 * @hook classifai_googleai_api_response_post
		 *
		 * @param {array|WP_Error} $response API response.
		 * @param {string} $url Request URL.
		 * @param {array} $options Request body options.
		 * @param {string} $this->feature Feature name.
		 *
		 * @return {array} API response.
		 */
		return apply_filters(
			'classifai_googleai_api_response_post',
			$this->get_result( wp_remote_post( $url, $options ) ), // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.wp_remote_get_wp_remote_get
			$url,
			$options,
			$this->feature
		);
	}

	/**
	 * Get results from the response.
	 *
	 * @param object $response The API response.
	 * @return array|WP_Error
	 */
	public function get_result( $response ) {
		if ( ! is_wp_error( $response ) ) {
			$body = wp_remote_retrieve_body( $response );
			$code = wp_remote_retrieve_response_code( $response );
			$json = json_decode( $body, true );

			if ( json_last_error() === JSON_ERROR_NONE ) {
				if ( empty( $json['error'] ) ) {
					return $json;
				} else {
					$message = $json['error']['message'] ?? esc_html__( 'An error occured', 'classifai' );
					return new WP_Error( $code, $message );
				}
			} elseif ( ! empty( wp_remote_retrieve_response_message( $response ) ) ) {
				return new WP_Error( $code, wp_remote_retrieve_response_message( $response ) );
			} else {
				return new WP_Error( 'Invalid JSON: ' . json_last_error_msg(), $body );
			}
		} else {
			return $response;
		}
	}

	/**
	 * Add the headers.
	 *
	 * @param array $options The header options, passed by reference.
	 */
	public function add_headers( array &$options = [] ) {
		if ( empty( $options['headers'] ) ) {
			$options['headers'] = [];
		}

		if ( ! isset( $options['headers']['x-goog-api-key'] ) ) {
			$options['headers']['x-goog-api-key'] = $this->get_auth_header();
		}

		if ( ! isset( $options['headers']['Content-Type'] ) ) {
			$options['headers']['Content-Type'] = 'application/json';
		}
	}

	/**
	 * Get the auth header.
	 *
	 * @return string
	 */
	public function get_auth_header() {
		return $this->get_api_key();
	}

	/**
	 * Get the Google AI API key.
	 *
	 * @return string
	 */
	public function get_api_key() {
		return $this->api_key;
	}
}
