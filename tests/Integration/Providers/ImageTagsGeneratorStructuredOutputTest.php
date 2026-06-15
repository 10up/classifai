<?php
/**
 * Tests for structured image tag output from vision providers.
 */

namespace Classifai\Tests\Providers;

use Classifai\Features\ImageTagsGenerator;
use Classifai\Providers\Localhost\OllamaMultimodal;
use Classifai\Providers\OpenAI\ChatGPT;
use WP_UnitTestCase;

/**
 * Class ImageTagsGeneratorStructuredOutputTest
 *
 * @package Classifai\Tests\Providers;
 */
class ImageTagsGeneratorStructuredOutputTest extends WP_UnitTestCase {
	/**
	 * Tear down method.
	 */
	public function tear_down() {
		$this->remove_added_uploads();
		remove_all_filters( 'classifai_feature_image_tags_generator_is_feature_enabled' );
		remove_all_filters( 'pre_option_classifai_feature_image_tags_generator' );
		remove_all_filters( 'pre_http_request' );

		parent::tear_down();
	}

	/**
	 * Test that OpenAI uses structured output for image tags.
	 */
	public function test_openai_generates_image_tags_from_structured_output() {
		$attachment_id = $this->factory->attachment->create_upload_object( DIR_TESTDATA . '/images/33772.jpg' );
		$request_body  = [];

		add_filter( 'classifai_feature_image_tags_generator_is_feature_enabled', '__return_true' );
		add_filter(
			'pre_option_classifai_feature_image_tags_generator',
			function () {
				return [
					'status'             => '1',
					'provider'           => ChatGPT::ID,
					'tag_taxonomy'       => 'post_tag',
					ChatGPT::ID          => [
						'authenticated' => true,
					],
					OllamaMultimodal::ID => [
						'authenticated' => true,
					],
				];
			}
		);
		add_filter(
			'pre_http_request',
			function ( $preempt, $parsed_args, $url ) use ( &$request_body ) {
				if ( false === strpos( $url, 'api.openai.com/v1/chat/completions' ) ) {
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
							'choices' => [
								[
									'message' => [
										'content' => wp_json_encode(
											[
												'tags' => [
													'cat',
													'window',
													'sunlight',
												],
											]
										),
									],
								],
							],
						]
					),
				];
			},
			10,
			3
		);

		$provider = new ChatGPT( new ImageTagsGenerator() );
		$result   = $provider->generate_image_tags( $attachment_id );
		$schema   = $request_body['response_format']['json_schema'] ?? [];

		$this->assertSame( [ 'cat', 'window', 'sunlight' ], $result );
		$this->assertSame( 'json_schema', $request_body['response_format']['type'] ?? '' );
		$this->assertSame( 'image_tags', $schema['name'] ?? '' );
		$this->assertSame( [ 'tags' ], $schema['schema']['required'] ?? [] );
		$this->assertTrue( $schema['strict'] ?? false );
	}

	/**
	 * Test that Ollama uses structured output for image tags.
	 */
	public function test_ollama_generates_image_tags_from_structured_output() {
		$attachment_id = $this->factory->attachment->create_upload_object( DIR_TESTDATA . '/images/33772.jpg' );
		$image_path    = get_attached_file( $attachment_id );
		$request_body  = [];

		add_filter( 'classifai_feature_image_tags_generator_is_feature_enabled', '__return_true' );
		add_filter(
			'pre_option_classifai_feature_image_tags_generator',
			function () {
				return [
					'status'             => '1',
					'provider'           => OllamaMultimodal::ID,
					'tag_taxonomy'       => 'post_tag',
					ChatGPT::ID          => [
						'authenticated' => true,
					],
					OllamaMultimodal::ID => [
						'authenticated' => true,
						'endpoint_url'   => 'http://localhost:11434',
						'model'          => 'llava',
					],
				];
			}
		);
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
							'response' => wp_json_encode(
								[
									'tags' => [
										'cat',
										'window',
										'sunlight',
									],
								]
							),
						]
					),
				];
			},
			10,
			3
		);

		$provider = new OllamaMultimodal( new ImageTagsGenerator() );
		$result   = $provider->generate_image_tags( $image_path, $attachment_id );

		$this->assertSame( [ 'cat', 'window', 'sunlight' ], $result );
		$this->assertSame( [ 'tags' ], $request_body['format']['required'] ?? [] );
		$this->assertSame( 'array', $request_body['format']['properties']['tags']['type'] ?? '' );
		$this->assertSame( 3, $request_body['format']['properties']['tags']['minItems'] ?? 0 );
		$this->assertSame( 5, $request_body['format']['properties']['tags']['maxItems'] ?? 0 );
	}
}
