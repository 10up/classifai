<?php

namespace Classifai\Providers\OpenAI;

use Classifai\Providers\HTTPClient;
use WP_Error;
use function Classifai\safe_wp_remote_post;
use function Classifai\safe_wp_remote_get;

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
class APIRequest extends HTTPClient {

	/**
	 * OpenAI APIRequest constructor.
	 *
	 * @param string $api_key API key.
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
		return 'classifai_openai';
	}

	/**
	 * Makes an authorized POST request with form data.
	 *
	 * @param string $url The OpenAI API URL.
	 * @param array  $body The body of the request.
	 * @return array|WP_Error
	 */
	public function post_form( $url = '', $body = [] ) {
		/**
		 * Filter the URL for the post form request.
		 *
		 * @since 2.4.0
		 * @hook classifai_openai_api_request_post_form_url
		 *
		 * @param string $url           The URL for the request.
		 * @param string $this->feature The feature name.
		 *
		 * @return string The URL for the request.
		 */
		$url = apply_filters( 'classifai_openai_api_request_post_form_url', $url, $this->feature );

		$boundary = wp_generate_password( 24, false );
		$payload  = '';

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

		/**
		 * Filter the options for the post form request.
		 *
		 * @since 2.4.0
		 * @hook classifai_openai_api_request_post_form_options
		 *
		 * @param array  $options       The options for the request.
		 * @param string $url           The URL for the request.
		 * @param array  $body          The body of the request.
		 * @param string $this->feature The feature name.
		 *
		 * @return array The options for the request.
		 */
		$options = apply_filters(
			'classifai_openai_api_request_post_form_options',
			[
				'body'    => $payload,
				'headers' => [
					'Content-Type' => 'multipart/form-data; boundary=' . $boundary,
				],
				'timeout' => 60, // phpcs:ignore WordPressVIPMinimum.Performance.RemoteRequestTimeout.timeout_timeout
			],
			$url,
			$body,
			$this->feature
		);

		$this->add_headers( $options );

		/**
		 * Filter the response from OpenAI for a post form request.
		 *
		 * @since 2.4.0
		 * @hook classifai_openai_api_response_post_form
		 *
		 * @param array|\WP_Error $response      The API response.
		 * @param string          $url           Request URL.
		 * @param array           $options       Request body options.
		 * @param string          $this->feature Feature name.
		 *
		 * @return array API response.
		 */
		return apply_filters(
			'classifai_openai_api_response_post_form',
			$this->get_result( safe_wp_remote_post( $url, $options ) ),
			$url,
			$options,
			$this->feature
		);
	}

	/**
	 * Parse the response from the API with special handling for audio content.
	 *
	 * @param array $response The API response.
	 * @return array|WP_Error
	 */
	protected function parse_response( array $response ) {
		$headers      = wp_remote_retrieve_headers( $response );
		$content_type = false;

		if ( ! empty( $headers ) ) {
			$content_type = isset( $headers['content-type'] ) ? $headers['content-type'] : false;
		}

		// Special handling for OpenAI audio so it doesn't try to parse as JSON.
		if ( $content_type && false !== strpos( $content_type, 'audio/mpeg' ) ) {
			return $response; // Raw response
		}

		return parent::parse_response( $response );
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
	 * Get the OpenAI API key.
	 *
	 * @return string
	 */
	public function get_api_key(): string {
		return $this->api_key;
	}
}
