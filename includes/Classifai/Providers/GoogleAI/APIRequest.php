<?php

namespace Classifai\Providers\GoogleAI;

use Classifai\Providers\HTTPClient;

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
class APIRequest extends HTTPClient {

	/**
	 * Get the filter prefix for this provider.
	 *
	 * @return string
	 */
	protected function get_filter_prefix(): string {
		return 'classifai_googleai';
	}

	/**
	 * Add authentication header.
	 *
	 * @param array $options The header options, passed by reference.
	 */
	protected function add_auth_header( array &$options ) {
		$options['headers']['x-goog-api-key'] = $this->get_auth_header();
	}

	/**
	 * Get the auth header.
	 *
	 * @return string
	 */
	public function get_auth_header(): string {
		return $this->get_api_key();
	}

	/**
	 * Get the Google AI API key.
	 *
	 * @return string
	 */
	public function get_api_key(): string {
		return $this->api_key;
	}
}
