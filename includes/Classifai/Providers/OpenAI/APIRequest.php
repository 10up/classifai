<?php

namespace Classifai\Providers\OpenAI;

use Classifai\Providers\APIRequest as APIRequestBase;
use function Classifai\safe_wp_remote_post;

/**
 * OpenAI API Request implementation.
 *
 * The returned response is parsed into JSON and returned as an
 * associative array.
 *
 * Usage:
 *
 * $request = new Classifai\Providers\OpenAI\APIRequest();
 * $request->post( $openai_url, $options );
 */
class APIRequest extends APIRequestBase {

	protected const PROVIDER_ID = 'openai';

	/**
	 * Get the authentication header value.
	 *
	 * @return string
	 */
	protected function get_auth_header(): string {
		return 'Bearer ' . $this->api_key;
	}

	/**
	 * Get the authentication header name.
	 *
	 * @return string
	 */
	protected function get_auth_header_name(): string {
		return 'Authorization';
	}
}
