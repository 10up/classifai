<?php

namespace Classifai\Providers;

use WP_Error;

/**
 * Abstract class that provides common functionality for all provider API requests.
 *
 * @since x.x.x
 */
abstract class HTTPClient {

	/**
	 * The API key.
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
	 * HTTPClient constructor.
	 *
	 * @since x.x.x
	 *
	 * @param string $api_key API key.
	 * @param string $feature Feature name.
	 */
	public function __construct( string $api_key = '', string $feature = '' ) {
		$this->api_key = $api_key;
		$this->feature = $feature;
	}

	/**
	 * Makes an authorized GET request.
	 *
	 * @since x.x.x
	 *
	 * @param string $url     The API URL.
	 * @param array  $options Additional query params.
	 * @return array|WP_Error
	 */
	public function get( string $url, array $options = [] ) {
		/**
		 * Filter the URL for the get request.
		 *
		 * @since x.x.x
		 * @hook classifai_{provider}_api_request_get_url
		 *
		 * @param {string} $url The URL for the request.
		 * @param {array}  $options The options for the request.
		 * @param {string} $this->feature The feature name.
		 *
		 * @return {string} The URL for the request.
		 */
		$url = apply_filters( $this->get_filter_prefix() . '_api_request_get_url', $url, $options, $this->feature );

		/**
		 * Filter the options for the get request.
		 *
		 * @since x.x.x
		 * @hook classifai_{provider}_api_request_get_options
		 *
		 * @param {array}  $options The options for the request.
		 * @param {string} $url The URL for the request.
		 * @param {string} $this->feature The feature name.
		 *
		 * @return {array} The options for the request.
		 */
		$options = apply_filters( $this->get_filter_prefix() . '_api_request_get_options', $options, $url, $this->feature );

		$this->add_headers( $options );

		/**
		 * Filter the response from the provider for a get request.
		 *
		 * @since x.x.x
		 * @hook classifai_{provider}_api_response_get
		 *
		 * @param {array|WP_Error} $response The API response.
		 * @param {string} $url Request URL.
		 * @param {array} $options Request body options.
		 * @param {string} $this->feature Feature name.
		 *
		 * @return {array} API response.
		 */
		return apply_filters(
			$this->get_filter_prefix() . '_api_response_get',
			$this->get_result( wp_remote_get( $url, $options ) ), // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.wp_remote_get_wp_remote_get
			$url,
			$options,
			$this->feature
		);
	}

	/**
	 * Makes an authorized POST request.
	 *
	 * @since x.x.x
	 *
	 * @param string $url     The API URL.
	 * @param array  $options Additional query params.
	 * @return array|WP_Error
	 */
	public function post( string $url = '', array $options = [] ) {
		$options = wp_parse_args(
			$options,
			[
				'timeout' => $this->get_default_timeout(),
			]
		);

		/**
		 * Filter the URL for the post request.
		 *
		 * @since x.x.x
		 * @hook classifai_{provider}_api_request_post_url
		 *
		 * @param {string} $url The URL for the request.
		 * @param {array} $options The options for the request.
		 * @param {string} $this->feature The feature name.
		 *
		 * @return {string} The URL for the request.
		 */
		$url = apply_filters( $this->get_filter_prefix() . '_api_request_post_url', $url, $options, $this->feature );

		/**
		 * Filter the options for the post request.
		 *
		 * @since x.x.x
		 * @hook classifai_{provider}_api_request_post_options
		 *
		 * @param {array} $options The options for the request.
		 * @param {string} $url The URL for the request.
		 * @param {string} $this->feature The feature name.
		 *
		 * @return {array} The options for the request.
		 */
		$options = apply_filters( $this->get_filter_prefix() . '_api_request_post_options', $options, $url, $this->feature );

		$this->add_headers( $options );

		/**
		 * Filter the response from the provider for a post request.
		 *
		 * @since x.x.x
		 * @hook classifai_{provider}_api_response_post
		 *
		 * @param {array|WP_Error} $response The API response.
		 * @param {string} $url Request URL.
		 * @param {array} $options Request body options.
		 * @param {string} $this->feature Feature name.
		 *
		 * @return {array} API response.
		 */
		return apply_filters(
			$this->get_filter_prefix() . '_api_response_post',
			$this->get_result( wp_remote_post( $url, $options ) ), // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.wp_remote_get_wp_remote_get
			$url,
			$options,
			$this->feature
		);
	}

	/**
	 * Get results from the response.
	 *
	 * @since x.x.x
	 *
	 * @param array|WP_Error $response The API response.
	 * @return array|WP_Error
	 */
	public function get_result( $response ) {
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		return $this->parse_response( $response );
	}

	/**
	 * Parse the response from the API.
	 *
	 * @since x.x.x
	 *
	 * @param array $response The API response
	 * @return array|WP_Error
	 */
	protected function parse_response( array $response ) {
		$code = wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );

		// Error responses
		if ( $code >= 400 ) {
			$response_message = wp_remote_retrieve_response_message( $response );
			$status_text      = $response_message ? $response_message : __( 'Unknown error', 'classifai' );

			// Try to extract specific error message from the server response
			$server_response = $this->extract_error_message( $response );

			if ( ! empty( $server_response ) ) {
				$error_message = sprintf(
					/* translators: %1$d is the HTTP status code, %2$s is the HTTP status message, %3$s is the server response */
					__( 'An error occurred when performing an HTTP request: %1$d (%2$s). Server response: [ %3$s ]', 'classifai' ),
					$code,
					$status_text,
					$server_response
				);
			} else {
				$error_message = sprintf(
					/* translators: %1$d is the HTTP status code, %2$s is the HTTP status message */
					__( 'An error occurred when performing an HTTP request: %1$d (%2$s).', 'classifai' ),
					$code,
					$status_text
				);
			}

			return new WP_Error( $code, $error_message );
		}

		// Successful responses
		$headers      = wp_remote_retrieve_headers( $response );
		$content_type = false;

		if ( ! empty( $headers ) ) {
			$content_type = isset( $headers['content-type'] ) ? $headers['content-type'] : false;
		}

		// JSON responses
		if ( false === $content_type || false !== strpos( $content_type, 'application/json' ) ) {
			$json = json_decode( $body, true );

			if ( json_last_error() === JSON_ERROR_NONE ) {
				if ( isset( $json['error'] ) && $json['error'] ) {
					$error_message = $this->extract_error_message( $response );
					return new WP_Error( 'api_error', $error_message ? $error_message : __( 'API returned an error', 'classifai' ) );
				}

				return $json;
			} else {
				$error_msg = __( 'Invalid JSON response: ', 'classifai' ) . json_last_error_msg();
				return new WP_Error(
					'invalid_json',
					$error_msg,
					[
						'body' => wp_is_stream( $body ) ? null : wp_html_excerpt( (string) $body, 1000, '…' ),
					]
				);
			}
		} else {
			$error_msg = __( 'Unsupported response content type', 'classifai' );
			return new WP_Error(
				'invalid_content_type',
				$error_msg,
				[
					'content_type' => $content_type,
					'body'         => wp_is_stream( $body ) ? null : wp_html_excerpt( (string) $body, 1000, '…' ),
				]
			);
		}
	}

	/**
	 * Add the headers.
	 *
	 * @since x.x.x
	 *
	 * @param array $options The header options, passed by reference.
	 */
	public function add_headers( array &$options = [] ) {
		if ( empty( $options['headers'] ) ) {
			$options['headers'] = [];
		}

		// Get provider-specific default headers
		$default_headers = $this->get_default_headers();

		// Merge default headers (only if not already set)
		foreach ( $default_headers as $header => $value ) {
			if ( ! isset( $options['headers'][ $header ] ) ) {
				$options['headers'][ $header ] = $value;
			}
		}

		// Add authentication header (this may override default)
		$this->add_auth_header( $options );
	}

	/**
	 * Get the API key.
	 *
	 * @since x.x.x
	 *
	 * @return string
	 */
	public function get_api_key(): string {
		return $this->api_key;
	}

	/**
	 * Add authentication header.
	 *
	 * @since x.x.x
	 * @param array $options The header options, passed by reference.
	 */
	protected function add_auth_header( array &$options ) {
		// Child override
	}

	/**
	 * Get default headers for this provider.
	 *
	 * @since x.x.x
	 * @return array Default headers to include in requests.
	 */
	protected function get_default_headers(): array {
		return [
			'Content-Type' => 'application/json',
			'Accept'       => 'application/json',
		];
	}

	/**
	 * Get the filter prefix for this provider.
	 *
	 * @since x.x.x
	 * @return string
	 */
	abstract protected function get_filter_prefix(): string;

	/**
	 * Extract error message from response body.
	 *
	 * @since x.x.x
	 * @param array $response The API response.
	 * @return string
	 */
	protected function extract_error_message( array $response ): string {
		$body         = wp_remote_retrieve_body( $response );
		$headers      = wp_remote_retrieve_headers( $response );
		$content_type = isset( $headers['content-type'] ) ? $headers['content-type'] : false;

		if ( $content_type && false !== strpos( $content_type, 'application/json' ) ) {
			$json = json_decode( $body, true );
			if ( json_last_error() === JSON_ERROR_NONE && ! empty( $json['error'] ) ) {
				$provider_error = is_string( $json['error'] )
					? $json['error']
					: ( $json['error']['message'] ?? '' );

				if ( ! empty( $provider_error ) ) {
					return $provider_error;
				}
			}
		}

		return '';
	}

	/**
	 * Get the default timeout for requests.
	 *
	 * @since x.x.x
	 * @return int
	 */
	protected function get_default_timeout(): int {
		return 90;
	}
}
