<?php
/**
 * Tests for structured key takeaways output from language providers.
 */

namespace Classifai\Tests\Providers;

use Classifai\Features\KeyTakeaways;
use Classifai\Providers\Azure\OpenAI as AzureOpenAI;
use Classifai\Providers\Localhost\Ollama;
use Classifai\Providers\OpenAI\ChatGPT;
use WP_UnitTestCase;

/**
 * Class KeyTakeawaysStructuredOutputTest
 *
 * @package Classifai\Tests\Providers;
 */
class KeyTakeawaysStructuredOutputTest extends WP_UnitTestCase {
	/**
	 * Tear down method.
	 */
	public function tear_down() {
		remove_all_filters( 'classifai_feature_key_takeaways_is_feature_enabled' );
		remove_all_filters( 'pre_option_classifai_feature_key_takeaways' );
		remove_all_filters( 'pre_http_request' );

		parent::tear_down();
	}

	/**
	 * Test that OpenAI uses bounded structured output for key takeaways.
	 */
	public function test_openai_generates_key_takeaways_from_structured_output() {
		$post_id      = $this->factory->post->create();
		$request_body = [];

		$this->mock_key_takeaways_settings( ChatGPT::ID );
		$this->mock_openai_response( $request_body );

		$provider = new ChatGPT( new KeyTakeaways() );
		$result   = $provider->generate_key_takeaways(
			$post_id,
			[
				'content' => 'This article explains structured outputs and why they help parsing.',
				'title'   => 'Structured Outputs',
			]
		);
		$schema   = $request_body['response_format']['json_schema']['schema']['properties']['takeaways'] ?? [];

		$this->assertSame(
			[
				'Structured outputs make provider responses easier to parse.',
				'Schemas reduce formatting instructions in prompts.',
			],
			$result
		);
		$this->assertSame( 'json_schema', $request_body['response_format']['type'] ?? '' );
		$this->assertSame( 2, $schema['minItems'] ?? 0 );
		$this->assertSame( 4, $schema['maxItems'] ?? 0 );
	}

	/**
	 * Test that Ollama uses bounded structured output for key takeaways.
	 */
	public function test_ollama_generates_key_takeaways_from_structured_output() {
		$post_id      = $this->factory->post->create();
		$request_body = [];

		$this->mock_key_takeaways_settings( Ollama::ID );
		$this->mock_ollama_response( $request_body );

		$provider = new Ollama( new KeyTakeaways() );
		$result   = $provider->generate_key_takeaways(
			$post_id,
			[
				'content' => 'This article explains structured outputs and why they help parsing.',
				'title'   => 'Structured Outputs',
			]
		);
		$schema   = $request_body['format']['properties']['takeaways'] ?? [];

		$this->assertSame(
			[
				'Structured outputs make provider responses easier to parse.',
				'Schemas reduce formatting instructions in prompts.',
			],
			$result
		);
		$this->assertSame( [ 'takeaways' ], $request_body['format']['required'] ?? [] );
		$this->assertSame( 2, $schema['minItems'] ?? 0 );
		$this->assertSame( 4, $schema['maxItems'] ?? 0 );
	}

	/**
	 * Test that Azure OpenAI uses bounded structured output for key takeaways.
	 */
	public function test_azure_openai_generates_key_takeaways_from_structured_output() {
		$post_id      = $this->factory->post->create();
		$request_body = [];

		$this->mock_key_takeaways_settings( AzureOpenAI::ID );
		$this->mock_azure_openai_response( $request_body );

		$provider = new AzureOpenAI( new KeyTakeaways() );
		$result   = $provider->generate_key_takeaways(
			$post_id,
			[
				'content' => 'This article explains structured outputs and why they help parsing.',
				'title'   => 'Structured Outputs',
			]
		);
		$schema   = $request_body['response_format']['json_schema']['schema']['properties']['takeaways'] ?? [];

		$this->assertSame(
			[
				'Structured outputs make provider responses easier to parse.',
				'Schemas reduce formatting instructions in prompts.',
			],
			$result
		);
		$this->assertSame( 'json_schema', $request_body['response_format']['type'] ?? '' );
		$this->assertSame( 2, $schema['minItems'] ?? 0 );
		$this->assertSame( 4, $schema['maxItems'] ?? 0 );
	}

	/**
	 * Mock Key Takeaways feature settings.
	 *
	 * @param string $provider_id Provider ID.
	 */
	private function mock_key_takeaways_settings( string $provider_id ): void {
		add_filter( 'classifai_feature_key_takeaways_is_feature_enabled', '__return_true' );
		add_filter(
			'pre_option_classifai_feature_key_takeaways',
			function () use ( $provider_id ) {
				return [
					'status'               => '1',
					'provider'             => $provider_id,
					'key_takeaways_prompt' => [],
					ChatGPT::ID            => [
						'authenticated' => true,
					],
					Ollama::ID             => [
						'authenticated' => true,
						'endpoint_url'   => 'http://localhost:11434',
						'model'          => 'llama3',
					],
					AzureOpenAI::ID        => [
						'authenticated' => true,
						'endpoint_url'   => 'https://example.openai.azure.com',
						'api_key'        => 'test-api-key',
						'deployment'     => 'test-deployment',
					],
				];
			}
		);
	}

	/**
	 * Mock OpenAI response.
	 *
	 * @param array $request_body Captured request body.
	 */
	private function mock_openai_response( array &$request_body ): void {
		add_filter(
			'pre_http_request',
			function ( $preempt, $parsed_args, $url ) use ( &$request_body ) {
				if ( false === strpos( $url, 'api.openai.com/v1/chat/completions' ) ) {
					return $preempt;
				}

				$request_body = json_decode( $parsed_args['body'], true );

				return $this->get_chat_completion_response();
			},
			10,
			3
		);
	}

	/**
	 * Mock Ollama response.
	 *
	 * @param array $request_body Captured request body.
	 */
	private function mock_ollama_response( array &$request_body ): void {
		add_filter(
			'pre_http_request',
			function ( $preempt, $parsed_args, $url ) use ( &$request_body ) {
				if ( false === strpos( $url, 'localhost:11434' ) ) {
					return $preempt;
				}

				$request_body = json_decode( $parsed_args['body'], true );

				return [
					'headers'  => [
						'content-type' => 'application/json',
					],
					'response' => [
						'code'    => 200,
						'message' => 'OK',
					],
					'body'     => wp_json_encode(
						[
							'message' => [
								'content' => $this->get_takeaways_json(),
							],
						]
					),
				];
			},
			10,
			3
		);
	}

	/**
	 * Mock Azure OpenAI response.
	 *
	 * @param array $request_body Captured request body.
	 */
	private function mock_azure_openai_response( array &$request_body ): void {
		add_filter(
			'pre_http_request',
			function ( $preempt, $parsed_args, $url ) use ( &$request_body ) {
				if ( false === strpos( $url, 'example.openai.azure.com' ) ) {
					return $preempt;
				}

				$request_body = json_decode( $parsed_args['body'], true );

				return $this->get_chat_completion_response();
			},
			10,
			3
		);
	}

	/**
	 * Get a chat completion response.
	 *
	 * @return array
	 */
	private function get_chat_completion_response(): array {
		return [
			'headers'  => [
				'content-type' => 'application/json',
			],
			'response' => [
				'code'    => 200,
				'message' => 'OK',
			],
			'body'     => wp_json_encode(
				[
					'choices' => [
						[
							'message' => [
								'content' => $this->get_takeaways_json(),
							],
						],
					],
				]
			),
		];
	}

	/**
	 * Get the structured takeaways JSON response.
	 *
	 * @return string
	 */
	private function get_takeaways_json(): string {
		return wp_json_encode(
			[
				'takeaways' => [
					'Structured outputs make provider responses easier to parse.',
					'Schemas reduce formatting instructions in prompts.',
				],
			]
		);
	}
}
