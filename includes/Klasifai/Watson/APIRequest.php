<?php

namespace Klasifai\Watson;

/**
 * APIRequest class is the low level class to make IBM Watson API
 * Requests. It uses constants declared in the config file for
 * authentication.
 *
 * The returned response is parsed into JSON and returned as an
 * associative array.
 *
 * Usage:
 *
 * $request = new APIRequest();
 * $request->post( $nlu_url, $options );
 */
class APIRequest {

	/**
	 * The Watson API username
	 */
	public $username;

	/**
	 * The Watson API password
	 */
	public $password;

	/**
	 * Adds authorization headers to the request options and makes an
	 * HTTP request. The result is parsed and returned if valid JSON.
	 *
	 * @param string $url The Watson API url
	 * @param array $options Additional query params
	 * @return array|WP_Error
	 */
	public function request( $url, $options = [] ) {
		$this->add_headers( $options );
		return $this->get_result( wp_remote_request( $url, $options ) );
	}

	/**
	 * Makes an authorized GET request and returns the parsed JSON
	 * response if valid.
	 *
	 * @param string $url The Watson API url
	 * @param array $options Additional query params
	 * @return array|WP_Error
	 */
	public function get( $url, $options = [] ) {
		$this->add_headers( $options );
		return $this->get_result( wp_remote_get( $url, $options ) );
	}

	/**
	 * Makes an authorized POST request and returns the parsed JSON
	 * response if valid.
	 *
	 * @param string $url The Watson API url
	 * @param array $options Additional query params
	 * @return array|WP_Error
	 */
	public function post( $url, $options = [] ) {
		$this->add_headers( $options );
		return $this->get_result( wp_remote_post( $url, $options ) );
	}

	function get_result( $response ) {
		if ( ! is_wp_error( $response ) ) {
			$body = wp_remote_retrieve_body( $response );
			$json = json_decode( $body, true );

			if ( json_last_error() === JSON_ERROR_NONE ) {
				return $json;
			} else {
				return new \WP_Error( 'Invalid JSON: ' . json_last_error_msg(), $body );
			}
		} else {
			return $response;
		}
	}

	function get_username() {
		if ( empty( $this->username ) ) {
			$this->username = \Klasifai\get_watson_username();
		}

		return $this->username;
	}

	function get_password() {
		if ( empty( $this->password ) ) {
			$this->password = \Klasifai\get_watson_password();
		}

		return $this->password;
	}

	function get_auth_header() {
		return 'Basic ' . $this->get_auth_hash();
	}

	function get_auth_hash() {
		$username = $this->get_username();
		$password = $this->get_password();

		return base64_encode( $username . ':' . $password );
	}

	function add_headers( &$options ) {
		if ( empty( $options['headers'] ) ) {
			$options['headers'] = [];
		}

		$options['headers']['Authorization'] = $this->get_auth_header();
		$options['headers']['Accept']        = 'application/json';
		$options['headers']['Content-Type']  = 'application/json';
	}

}
