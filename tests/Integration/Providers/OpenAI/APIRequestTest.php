<?php
/**
 * Tests for the OpenAI APIRequest low-level client.
 */

namespace Classifai\Tests\Providers\OpenAI;

use Classifai\Tests\TestCase;
use Classifai\Providers\OpenAI\APIRequest;
use WP_Error;

/**
 * @group providers
 * @coversDefaultClass \Classifai\Providers\OpenAI\APIRequest
 */
class APIRequestTest extends TestCase {

	/**
	 * @covers ::get_result
	 */
	public function test_get_result_decodes_valid_json() {
		$request  = new APIRequest( 'sk-test' );
		$response = [
			'response' => [ 'code' => 200 ],
			'headers'  => [ 'content-type' => 'application/json' ],
			'body'     => wp_json_encode( [ 'choices' => [ 'one', 'two' ] ] ),
		];

		$this->assertSame( [ 'choices' => [ 'one', 'two' ] ], $request->get_result( $response ) );
	}

	/**
	 * @covers ::get_result
	 */
	public function test_get_result_invalid_json_returns_error() {
		$request  = new APIRequest( 'sk-test' );
		$response = [
			'response' => [ 'code' => 200 ],
			'headers'  => [ 'content-type' => 'application/json' ],
			'body'     => '{ not valid json',
		];

		$result = $request->get_result( $response );
		$this->assertWPError( $result );
		$this->assertStringStartsWith( 'Invalid JSON', $result->get_error_code() );
	}

	/**
	 * @covers ::get_result
	 */
	public function test_get_result_non_200_carries_api_message() {
		$request  = new APIRequest( 'sk-test' );
		$response = [
			'response' => [ 'code' => 401 ],
			'headers'  => [ 'content-type' => 'application/json' ],
			'body'     => wp_json_encode( [ 'error' => [ 'message' => 'Incorrect API key provided' ] ] ),
		];

		$result = $request->get_result( $response );
		$this->assertWPError( $result );
		$this->assertSame( 401, $result->get_error_code() );
		$this->assertSame( 'Incorrect API key provided', $result->get_error_message() );
	}

	/**
	 * @covers ::get_result
	 */
	public function test_get_result_passes_through_transport_error() {
		$request = new APIRequest( 'sk-test' );
		$error   = new WP_Error( 'http_request_failed', 'Connection timed out' );

		$this->assertSame( $error, $request->get_result( $error ) );
	}

	/**
	 * @covers ::add_headers
	 * @covers ::get_auth_header
	 */
	public function test_add_headers_sets_bearer_and_content_type() {
		$request = new APIRequest( 'sk-test-key' );
		$options = [];
		$request->add_headers( $options );

		$this->assertSame( 'Bearer sk-test-key', $options['headers']['Authorization'] );
		$this->assertSame( 'application/json', $options['headers']['Content-Type'] );
	}

	/**
	 * @covers ::post_form
	 */
	public function test_post_form_builds_multipart_body() {
		$captured = [];
		$filter   = function ( $preempt, $parsed_args ) use ( &$captured ) {
			$captured = $parsed_args;
			return [
				'response' => [ 'code' => 200 ],
				'headers'  => [ 'content-type' => 'application/json' ],
				'body'     => wp_json_encode( [ 'ok' => true ] ),
			];
		};
		add_filter( 'pre_http_request', $filter, 10, 3 );

		$request = new APIRequest( 'sk-test-key' );
		$request->post_form( 'https://api.openai.com/v1/audio/transcriptions', [ 'model' => 'whisper-1' ] );

		remove_filter( 'pre_http_request', $filter, 10 );

		$this->assertMatchesRegularExpression(
			'#^multipart/form-data; boundary=#',
			$captured['headers']['Content-Type']
		);

		// The boundary in the header must appear in the payload.
		$boundary = substr( $captured['headers']['Content-Type'], strlen( 'multipart/form-data; boundary=' ) );
		$this->assertStringContainsString( '--' . $boundary, $captured['body'] );
		$this->assertStringContainsString( 'name="model"', $captured['body'] );
		$this->assertStringContainsString( 'whisper-1', $captured['body'] );
	}
}
