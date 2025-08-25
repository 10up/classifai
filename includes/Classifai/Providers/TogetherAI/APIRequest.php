<?php

namespace Classifai\Providers\TogetherAI;

use Classifai\Providers\HTTPClient;

/**
 * The APIRequest class is a low level class to make TogetherAI API
 * requests.
 *
 * The returned response is parsed into JSON and returned as an
 * associative array.
 *
 * Usage:
 *
 * $request = new Classifai\Providers\TogetherAI\APIRequest();
 * $request->post( $togetherai_url, $options );
 */
class APIRequest extends HTTPClient {

	/**
	 * Get the filter prefix for this provider.
	 *
	 * @return string
	 */
	protected function get_filter_prefix(): string {
		return 'classifai_togetherai';
	}

	/**
	 * Add authentication header.
	 *
	 * @param array $options The header options, passed by reference.
	 */
	protected function add_auth_header( array &$options ) {
		$options['headers']['Authorization'] = $this->get_auth_header();
	}

	/**
	 * Get the auth header.
	 *
	 * @return string
	 */
	public function get_auth_header(): string {
		return 'Bearer ' . $this->get_api_key();
	}

	/**
	 * Get the TogetherAI API key.
	 *
	 * @return string
	 */
	public function get_api_key(): string {
		return $this->api_key;
	}
}
