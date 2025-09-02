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

	/**
	 * Makes an authorized POST request with form data.
	 *
	 * @param string $url The OpenAI API URL.
	 * @param array  $body The body of the request.
	 * @return array|WP_Error
	 */
	public function post_form( string $url = '', array $body = [] ) {
		$url = $this->filter_url( 'post_form', $url, [] );

		$boundary = wp_generate_password( 24, false );
		$payload  = $this->build_form_data( $body, $boundary );

		$options = $this->filter_options(
			'post_form',
			[
				'body'    => $payload,
				'headers' => [
					'Content-Type' => 'multipart/form-data; boundary=' . $boundary,
				],
				'timeout' => 60, // phpcs:ignore WordPressVIPMinimum.Performance.RemoteRequestTimeout.timeout_timeout
			],
			$url
		);

		$this->add_headers( $options );

		$response = safe_wp_remote_post( $url, $options );
		$result   = $this->get_result( $response );

		return $this->filter_response( 'post_form', $result, $url, $options );
	}

	/**
	 * Build the form data for the request.
	 *
	 * @param array  $body     The body of the request.
	 * @param string $boundary The boundary of the request.
	 * @return string The form data.
	 */
	protected function build_form_data( array $body, string $boundary ): string {
		$payload = '';

		// Take all our POST fields and transform them to work with form-data.
		foreach ( $body as $name => $value ) {
			$payload .= '--' . $boundary;
			$payload .= "\r\n";

			if ( 'file' === $name ) {
				$payload .= 'Content-Disposition: form-data; name="file"; filename="' . basename( $value ) . '"' . "\r\n";
				$payload .= "\r\n";
				$payload .= file_get_contents( $value ); // phpcs:ignore
			} else {
				$payload .= 'Content-Disposition: form-data; name="' . esc_attr( $name ) .
					'"' . "\r\n\r\n";
				$payload .= esc_attr( $value );
			}

			$payload .= "\r\n";
		}

		$payload .= '--' . $boundary . '--';

		return $payload;
	}
}
