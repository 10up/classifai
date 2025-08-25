<?php

namespace Classifai\Providers\Localhost;

use Classifai\Providers\HTTPClient;

/**
 * The APIRequest class is a low level class to make Localhost API
 * requests.
 *
 * The returned response is parsed into JSON and returned as an
 * associative array.
 *
 * Usage:
 *
 * $request = new Classifai\Providers\Localhost\APIRequest();
 * $request->post( $localhost_url, $options );
 */
class APIRequest extends HTTPClient {

	/**
	 * Localhost APIRequest constructor.
	 *
	 * @param string $api_key API key (not used for localhost, kept for compatibility).
	 * @param string $feature Feature name.
	 */
	public function __construct( string $api_key = '', string $feature = '' ) {
		parent::__construct( $api_key, $feature );
	}

	/**
	 * Get the filter prefix for this provider.
	 *
	 * @return string
	 */
	protected function get_filter_prefix(): string {
		return 'classifai_localhost';
	}

	/**
	 * Add authentication header.
	 *
	 * @param array $options The header options, passed by reference.
	 */
	protected function add_auth_header( array &$options ) {
		// Not needed for localhost providers.
	}

	/**
	 * Get the auth header.
	 *
	 * @return string
	 */
	public function get_auth_header() {
		// Not needed for localhost providers.
		return '';
	}

	/**
	 * Get the Localhost API key.
	 *
	 * @return string
	 */
	public function get_api_key(): string {
		return $this->api_key;
	}
}
