<?php

namespace Classifai\Providers\OpenAI;

use WP_Error;

/**
 * The APIRequest class is a low level class to make OpenAI API
 * requests.
 *
 * The returned response is parsed into JSON and returned as an
 * associative array.
 *
 * Usage:
 *
 * $request = new Classifai\Providers\OpenAI\APIRequest();
 * $request->post( $openai_url, $options );
 */
class APIRequest {

	/**
	 * The OpenAI API key.
	 *
	 * @var string
	 */
	public $api_key;

	/**
	 * OpenAI APIRequest constructor.
	 *
	 * @param string $api_key OpenAI API key.
	 */
	public function __construct( $api_key = '' ) {
		$this->api_key = $api_key;
	}

	/**
	 * Makes an authorized POST request.
	 *
	 * @param string $url The OpenAI API URL.
	 * @param array  $options Additional query params.
	 * @return array|WP_Error
	 */
	public function post( $url = '', $options = [] ) {
		$this->add_headers( $options );
		return $this->get_result( wp_remote_post( $url, $options ) ); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.wp_remote_get_wp_remote_get
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
	public function add_headers( &$options = [] ) {
		if ( empty( $options['headers'] ) ) {
			$options['headers'] = [];
		}

		$options['headers']['Authorization'] = $this->get_auth_header();
		$options['headers']['Content-Type']  = 'application/json';
	}

	/**
	 * Get the auth header.
	 *
	 * @return string
	 */
	public function get_auth_header() {
		return 'Bearer ' . $this->get_api_key();
	}

	/**
	 * Get the OpenAI API key.
	 *
	 * @return string
	 */
	public function get_api_key() {
		return $this->api_key;
	}

}
