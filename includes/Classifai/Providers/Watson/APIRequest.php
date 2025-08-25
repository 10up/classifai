<?php

namespace Classifai\Providers\Watson;

use Classifai\Providers\HTTPClient;
use function Classifai\Providers\Watson\get_username;
use function Classifai\Providers\Watson\get_password;

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
class APIRequest extends HTTPClient {

	/**
	 * The Watson API username.
	 *
	 * @var string The Watson API username.
	 */
	public $username;

	/**
	 * The Watson API password.
	 *
	 * @var string The Watson API password.
	 */
	public $password;

	/**
	 * Watson APIRequest constructor.
	 *
	 * @param string $api_key Not used for Watson, kept for compatibility.
	 * @param string $feature Feature name.
	 */
	public function __construct( string $api_key = '', string $feature = '' ) {
		parent::__construct( $api_key, $feature );
		$this->username = get_username();
		$this->password = get_password();
	}

	/**
	 * Get the filter prefix for this provider.
	 *
	 * @return string
	 */
	protected function get_filter_prefix(): string {
		return 'classifai_watson';
	}

	/**
	 * Get the Watson username.
	 *
	 * @return string $username.
	 */
	public function get_username(): string {
		if ( empty( $this->username ) ) {
			$this->username = get_username();
		}

		return $this->username;
	}

	/**
	 * Get the Watson API password.
	 *
	 * @return string
	 */
	public function get_password(): string {
		if ( empty( $this->password ) ) {
			$this->password = get_password();
		}

		return $this->password;
	}

	/**
	 * Get the auth header.
	 *
	 * @return string The header.
	 */
	public function get_auth_header(): string {
		return 'Basic ' . $this->get_auth_hash();
	}

	/**
	 * Get the auth hash.
	 *
	 * @return string The auth hash.
	 */
	public function get_auth_hash(): string {
		$username = $this->get_username();
		$password = $this->get_password();

		return base64_encode( $username . ':' . $password ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
	}

	/**
	 * Add authentication header.
	 *
	 * @param array $options The header options, passed by reference.
	 */
	protected function add_auth_header( array &$options ) {
		$options['headers']['Authorization'] = $this->get_auth_header();
	}
}
