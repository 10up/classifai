<?php
/**
 * Tests for the ChatGPT provider (title generation path).
 */

namespace Classifai\Tests\Providers\OpenAI;

use Classifai\Tests\TestCase;
use Classifai\Features\TitleGeneration;
use Classifai\Providers\OpenAI\ChatGPT;

/**
 * @group providers
 * @coversDefaultClass \Classifai\Providers\OpenAI\ChatGPT
 */
class ChatGPTTest extends TestCase {

	const OPTION = 'classifai_feature_title_generation';

	public function tear_down() {
		delete_option( self::OPTION );
		parent::tear_down();
	}

	/**
	 * Enable TitleGeneration backed by ChatGPT for the current admin user.
	 *
	 * @param int $suggestions Number of suggestions to configure.
	 */
	private function enable_feature( int $suggestions = 1 ) {
		$this->as_user_with_role( 'administrator' );
		update_option(
			self::OPTION,
			[
				'status'         => '1',
				'provider'       => 'openai_chatgpt',
				'roles'          => [ 'administrator' => 'administrator' ],
				'openai_chatgpt' => [
					'api_key'               => 'sk-test',
					'authenticated'         => true,
					'number_of_suggestions' => $suggestions,
				],
			]
		);
	}

	/**
	 * @covers ::generate_titles
	 * @covers ::rest_endpoint_callback
	 */
	public function test_generate_titles_parses_fixture() {
		$this->enable_feature();
		$this->load_e2e_fixtures();

		$post_id  = self::factory()->post->create( [ 'post_content' => 'Some article body.' ] );
		$provider = new ChatGPT( new TitleGeneration() );

		$titles = $provider->rest_endpoint_callback( $post_id, 'title', [ 'num' => 1 ] );

		$this->assertSame( [ 'Hello there, how may I assist you today?' ], $titles );
	}

	/**
	 * Disabled feature returns an error and never fires an HTTP request.
	 *
	 * @covers ::generate_titles
	 */
	public function test_disabled_feature_returns_error_without_http() {
		update_option( self::OPTION, [ 'status' => '0' ] );

		$http_called = false;
		$guard       = function () use ( &$http_called ) {
			$http_called = true;
			return [
				'response' => [ 'code' => 200 ],
				'body'     => '{}',
			];
		};
		add_filter( 'pre_http_request', $guard, 10, 3 );

		$result = ( new ChatGPT( new TitleGeneration() ) )->rest_endpoint_callback( self::factory()->post->create(), 'title' );

		remove_filter( 'pre_http_request', $guard, 10 );

		$this->assertWPErrorCode( 'not_enabled', $result );
		$this->assertFalse( $http_called, 'No HTTP request should be made when the feature is disabled.' );
	}

	/**
	 * The configured number_of_suggestions is sent as `n` in the request body.
	 *
	 * @covers ::generate_titles
	 */
	public function test_number_of_suggestions_honored() {
		$this->enable_feature( 3 );

		$body   = null;
		$filter = function ( $preempt, $parsed_args ) use ( &$body ) {
			$body = json_decode( $parsed_args['body'], true );
			return [
				'response' => [ 'code' => 200 ],
				'headers'  => [ 'content-type' => 'application/json' ],
				'body'     => wp_json_encode( [ 'choices' => [ [ 'message' => [ 'content' => 'A title' ] ] ] ] ),
			];
		};
		add_filter( 'pre_http_request', $filter, 10, 3 );

		( new ChatGPT( new TitleGeneration() ) )->rest_endpoint_callback( self::factory()->post->create(), 'title' );

		remove_filter( 'pre_http_request', $filter, 10 );

		$this->assertSame( 3, $body['n'] );
	}

	/**
	 * Content that exceeds the token budget is trimmed by get_content() before
	 * it is placed in the request. The real budget is the model's full context
	 * window, so we shrink the chars-per-token ratio to force trimming
	 * deterministically.
	 *
	 * @covers ::get_content
	 */
	public function test_long_content_is_trimmed() {
		$this->enable_feature();

		// 0.01 chars/token makes a 10k-char string ~1,000,000 tokens, well over budget.
		add_filter( 'classifai_openai_characters_in_token', fn() => 0.01 );

		$long_content = str_repeat( 'word ', 2000 ); // 10,000 characters.
		$post_id      = self::factory()->post->create( [ 'post_content' => $long_content ] );

		$content = ( new ChatGPT( new TitleGeneration() ) )->get_content( $post_id, 0, false, $long_content );

		$this->assertLessThan(
			strlen( $long_content ),
			strlen( $content ),
			'Content should be trimmed when it exceeds the token budget.'
		);

		remove_all_filters( 'classifai_openai_characters_in_token' );
	}
}
