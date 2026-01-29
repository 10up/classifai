<?php
/**
 * Ollama integration
 */

namespace Classifai\Providers\Localhost;

use Classifai\Providers\Provider;
use Classifai\Providers\OpenAI\APIRequest;
use Classifai\Features\ContentResizing;
use Classifai\Features\ExcerptGeneration;
use Classifai\Features\TitleGeneration;
use Classifai\Features\ContentGeneration;
use Classifai\Features\KeyTakeaways;
use Classifai\Normalizer;
use WP_Error;

use function Classifai\get_default_prompt;
use function Classifai\sanitize_number_of_responses_field;

/**
 * Ollama class
 */
class Ollama extends Provider {

	/**
	 * The Provider ID.
	 */
	const ID = 'ollama';

	/**
	 * Ollama constructor.
	 *
	 * @param \Classifai\Features\Feature $feature_instance The feature instance.
	 */
	public function __construct( $feature_instance = null ) {
		$this->feature_instance = $feature_instance;
	}

	/**
	 * Returns the default settings for this provider.
	 *
	 * @return array
	 */
	public function get_default_provider_settings(): array {
		$common_settings = [
			'endpoint_url'  => 'http://localhost:11434/',
			'authenticated' => false,
			'model'         => '',
			'models'        => [],
		];

		/**
		 * Default values for feature specific settings.
		 */
		switch ( $this->feature_instance::ID ) {
			case ContentResizing::ID:
			case TitleGeneration::ID:
				$common_settings['number_of_suggestions'] = 1;
				break;
		}

		return $common_settings;
	}

	/**
	 * Sanitize the settings for this provider.
	 *
	 * @param array $new_settings The settings array.
	 * @return array
	 */
	public function sanitize_settings( array $new_settings ): array {
		$settings            = $this->feature_instance->get_settings();
		$credentials_changed = false;

		$new_settings[ static::ID ]['authenticated'] = $settings[ static::ID ]['authenticated'];

		if ( ! empty( $new_settings[ static::ID ]['endpoint_url'] ) ) {
			$new_url = trailingslashit( esc_url_raw( $new_settings[ static::ID ]['endpoint_url'] ) );

			$new_settings[ static::ID ]['models'] = $this->get_models(
				[
					'endpoint_url' => $new_url,
				]
			);

			// Check to see if credentials have changed.
			if ( $new_url !== $settings[ static::ID ]['endpoint_url'] || ! $new_settings[ static::ID ]['authenticated'] ) {
				$credentials_changed = true;
			}

			// If they have changed, make a request to get models and ensure the connection works.
			if ( $credentials_changed ) {
				$new_settings[ static::ID ]['endpoint_url'] = $new_url;

				if ( ! empty( $new_settings[ static::ID ]['models'] ) ) {
					$new_settings[ static::ID ]['authenticated'] = true;
				} else {
					$new_settings[ static::ID ]['models']        = [];
					$new_settings[ static::ID ]['authenticated'] = false;
				}
			}
		} else {
			$new_settings[ static::ID ]['endpoint_url'] = $settings[ static::ID ]['endpoint_url'];

			add_settings_error(
				$this->feature_instance->get_option_name(),
				'classifai-auth-empty',
				esc_html__( 'Please enter a valid endpoint URL in order to connect.', 'classifai' ),
				'error'
			);
		}

		$new_settings[ static::ID ]['model'] = sanitize_text_field( $new_settings[ static::ID ]['model'] ?? $settings[ static::ID ]['model'] );

		switch ( $this->feature_instance::ID ) {
			case ContentResizing::ID:
			case TitleGeneration::ID:
				$new_settings[ static::ID ]['number_of_suggestions'] = sanitize_number_of_responses_field( 'number_of_suggestions', $new_settings[ static::ID ], $settings[ static::ID ] );
				break;
		}

		return $new_settings;
	}

	/**
	 * Connects to Ollama and retrieves supported models.
	 *
	 * @param array $args Overridable args.
	 * @return array
	 */
	public function get_models( array $args = [] ): array {
		$settings = $this->feature_instance->get_settings( static::ID );

		$default = [
			'endpoint_url' => $settings[ static::ID ]['endpoint_url'] ?? '',
		];

		$default = wp_parse_args( $args, $default );

		// Return if credentials don't exist.
		if ( empty( $default['endpoint_url'] ) ) {
			return [];
		}

		// Make our request.
		$request  = new APIRequest( 'test' );
		$response = $request->get(
			$this->get_api_model_url( $default['endpoint_url'] ),
			[
				'timeout' => 30, // phpcs:ignore WordPressVIPMinimum.Performance.RemoteRequestTimeout.timeout_timeout
				'use_vip' => true,
			]
		);

		if ( is_wp_error( $response ) ) {
			add_settings_error(
				$this->feature_instance->get_option_name(),
				'ollama-request-failed',
				sprintf(
					/* translators: %s is replaced with the error message */
					esc_html__( 'Error making request, please ensure the Ollama service is running: %s', 'classifai' ),
					$response->get_error_message()
				),
				'error'
			);

			return [];
		}

		$sanitized_models = [];

		if ( is_array( $response['models'] ) ) {
			foreach ( $response['models'] as $model ) {
				$sanitized_models[ $model['model'] ] = $model['name'];
			}
		}

		return $sanitized_models;
	}

	/**
	 * Generate an excerpt.
	 *
	 * @param int   $post_id The Post ID we're processing
	 * @param array $args Arguments passed in.
	 * @return string|WP_Error
	 */
	public function generate_excerpt( int $post_id = 0, array $args = [] ) {
		if ( ! $post_id || ! get_post( $post_id ) ) {
			return new WP_Error( 'post_id_required', esc_html__( 'A valid post ID is required to generate an excerpt.', 'classifai' ) );
		}

		$feature   = new ExcerptGeneration();
		$settings  = $feature->get_settings();
		$args      = wp_parse_args(
			array_filter( $args ),
			[
				'content' => '',
				'title'   => get_the_title( $post_id ),
				'author'  => '',
			]
		);
		$post_type = get_post_type( $post_id );

		// These checks (and the one above) happen in the REST permission_callback,
		// but we run them again here in case this method is called directly.
		if ( empty( $settings ) || ( isset( $settings[ static::ID ]['authenticated'] ) && false === $settings[ static::ID ]['authenticated'] ) || ( ! $feature->is_feature_enabled() && ( ! defined( 'WP_CLI' ) || ! WP_CLI ) ) ) {
			return new WP_Error( 'not_enabled', esc_html__( 'Excerpt generation is disabled or Ollama authentication failed. Please check your settings.', 'classifai' ) );
		}

		$excerpt_length = absint( $settings['length'] ?? 55 );

		// Overwrite the prompt if we are generating an excerpt for a product.
		if ( 'product' === $post_type ) {
			$excerpt_prompt = $feature->woo_prompt;
		} else {
			$excerpt_prompt = esc_textarea( get_default_prompt( $settings['generate_excerpt_prompt'] ) ?? $feature->prompt );
		}

		// Replace our variables in the prompt.
		$prompt_search  = array( '{{WORDS}}', '{{TITLE}}', '{{AUTHOR}}' );
		$prompt_replace = array( $excerpt_length, $args['title'], $args['author'] );
		$prompt         = str_replace( $prompt_search, $prompt_replace, $excerpt_prompt );

		/**
		 * Filter the prompt we will send to Ollama.
		 *
		 * @since 3.3.0
		 * @hook classifai_ollama_excerpt_prompt
		 *
		 * @param string $prompt         Prompt we are sending to Ollama. Gets added before post content.
		 * @param int    $post_id        ID of post.
		 * @param int    $excerpt_length Length of final excerpt.
		 *
		 * @return string Prompt.
		 */
		$prompt = apply_filters( 'classifai_ollama_excerpt_prompt', $prompt, $post_id, $excerpt_length );

		// Check if we are generating an excerpt for a product.
		if ( 'product' === $post_type && function_exists( 'wc_get_product' ) && \wc_get_product( $post_id ) ) {
			$args['content'] = $this->get_product_content( $post_id );
		}

		// Get the filtered content for request.
		$message_content = $this->get_content( $post_id, false, $args['content'] );

		/**
		 * Filter the request body before sending to Ollama.
		 *
		 * @since 3.3.0
		 * @hook classifai_ollama_excerpt_request_body
		 *
		 * @param array $body    Request body that will be sent to Ollama.
		 * @param int   $post_id ID of post.
		 *
		 * @return array Request body.
		 */
		$body = apply_filters(
			'classifai_ollama_excerpt_request_body',
			[
				'model'    => $settings[ static::ID ]['model'] ?? '',
				'messages' => $this->get_request_messages( $post_id, $prompt, $message_content ),
				'stream'   => false,
			],
			$post_id
		);

		// Make our API request.
		$request  = new APIRequest( 'test' );
		$response = $request->post(
			$this->get_api_chat_url( $settings[ static::ID ]['endpoint_url'] ?? '' ),
			[
				'body' => wp_json_encode( $body ),
			]
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		// If we have a message, return it.
		$return = '';
		if ( isset( $response['message'], $response['message']['content'] ) ) {
			$return = sanitize_text_field( trim( $response['message']['content'], ' "\'' ) );
		}

		return $return;
	}

	/**
	 * Generate a title.
	 *
	 * @param int   $post_id The Post ID we're processing
	 * @param array $args Arguments passed in.
	 * @return string|WP_Error
	 */
	public function generate_title( int $post_id = 0, array $args = [] ) {
		if ( ! $post_id || ! get_post( $post_id ) ) {
			return new WP_Error( 'post_id_required', esc_html__( 'Post ID is required to generate titles.', 'classifai' ) );
		}

		$feature   = new TitleGeneration();
		$settings  = $feature->get_settings();
		$args      = wp_parse_args(
			array_filter( $args ),
			[
				'num'     => $settings[ static::ID ]['number_of_suggestions'] ?? 1,
				'content' => '',
			]
		);
		$post_type = get_post_type( $post_id );

		// These checks happen in the REST permission_callback,
		// but we run them again here in case this method is called directly.
		if ( empty( $settings ) || ( isset( $settings[ static::ID ]['authenticated'] ) && false === $settings[ static::ID ]['authenticated'] ) || ! $feature->is_feature_enabled() ) {
			return new WP_Error( 'not_enabled', esc_html__( 'Title generation is disabled or Ollama authentication failed. Please check your settings.', 'classifai' ) );
		}

		// Overwrite the prompt if we are generating titles for a product.
		if ( 'product' === $post_type ) {
			$prompt = $feature->woo_prompt;
		} else {
			$prompt = esc_textarea( get_default_prompt( $settings['generate_title_prompt'] ) ?? $feature->prompt );
		}

		/**
		 * Filter the prompt we will send to Ollama.
		 *
		 * @since 3.3.0
		 * @hook classifai_ollama_title_prompt
		 *
		 * @param string $prompt  Prompt we are sending to Ollama. Gets added before post content.
		 * @param int    $post_id ID of post.
		 * @param array  $args    Arguments passed to endpoint.
		 *
		 * @return string Prompt.
		 */
		$prompt = apply_filters( 'classifai_ollama_title_prompt', $prompt, $post_id, $args );

		// Check if we are generating titles for a product.
		if ( 'product' === $post_type && function_exists( 'wc_get_product' ) && \wc_get_product( $post_id ) ) {
			$args['content'] = $this->get_product_content( $post_id );
		}

		// Get the filtered content for request.
		$message_content = $this->get_content( $post_id, false, $args['content'] );

		/**
		 * Filter the request body before sending to Ollama.
		 *
		 * @since 3.3.0
		 * @hook classifai_ollama_title_request_body
		 *
		 * @param array $body    Request body that will be sent to Ollama.
		 * @param int   $post_id ID of post.
		 *
		 * @return array Request body.
		 */
		$body = apply_filters(
			'classifai_ollama_title_request_body',
			[
				'model'    => $settings[ static::ID ]['model'] ?? '',
				'messages' => $this->get_request_messages( $post_id, $prompt, $message_content ),
				'stream'   => false,
				'format'   => [
					'type'       => 'object',
					'properties' => [
						'title' => [
							'type' => 'string',
						],
					],
					'required'   => [ 'title' ],
				],
			],
			$post_id
		);

		// Make our API requests.
		$request = new APIRequest( 'test', $feature->get_option_name() );

		$responses = [];
		for ( $i = 0; $i < $args['num']; $i++ ) {
			$responses[] = $request->post(
				$this->get_api_chat_url( $settings[ static::ID ]['endpoint_url'] ?? '' ),
				[
					'body' => wp_json_encode( $body ),
				]
			);
		}

		$cleaned_responses = [];

		foreach ( $responses as $response ) {
			// Extract out the response, if it exists.
			if ( ! is_wp_error( $response ) && isset( $response['message'], $response['message']['content'] ) ) {
				// We expect the response to be valid json since we requested that schema.
				$title = json_decode( $response['message']['content'], true );

				if ( isset( $title['title'] ) ) {
					$cleaned_responses[] = sanitize_text_field( trim( $title['title'], ' "\'' ) );
				} else {
					return new WP_Error( 'refusal', esc_html__( 'Request failed', 'classifai' ) );
				}
			} elseif ( is_wp_error( $response ) ) {
				return $response;
			}
		}

		return $cleaned_responses;
	}

	/**
	 * Resizes content.
	 *
	 * @param int   $post_id The Post ID we're processing
	 * @param array $args Arguments passed in.
	 * @return string|WP_Error
	 */
	public function resize_content( int $post_id, array $args = array() ) {
		if ( ! $post_id || ! get_post( $post_id ) ) {
			return new WP_Error( 'post_id_required', esc_html__( 'Post ID is required to resize content.', 'classifai' ) );
		}

		$feature  = new ContentResizing();
		$settings = $feature->get_settings();

		$args = wp_parse_args(
			array_filter( $args ),
			[
				'num'     => $settings[ static::ID ]['number_of_suggestions'] ?? 1,
				'content' => '',
			]
		);

		if ( 'shrink' === $args['resize_type'] ) {
			$prompt = esc_textarea( get_default_prompt( $settings['condense_text_prompt'] ) ?? $feature->condense_prompt );
		} else {
			$prompt = esc_textarea( get_default_prompt( $settings['expand_text_prompt'] ) ?? $feature->expand_prompt );
		}

		/**
		 * Filter the resize prompt we will send to Ollama.
		 *
		 * @since 3.3.0
		 * @hook classifai_ollama_' . $args['resize_type'] . '_content_prompt
		 *
		 * @param string $prompt  Resize prompt we are sending to Ollama. Gets added as a system prompt.
		 * @param int    $post_id ID of post.
		 * @param array  $args    Arguments passed to endpoint.
		 *
		 * @return string Prompt.
		 */
		$prompt = apply_filters( 'classifai_ollama_' . $args['resize_type'] . '_content_prompt', $prompt, $post_id, $args );

		/**
		 * Filter the resize request body before sending to Ollama.
		 *
		 * @since 3.3.0
		 * @hook classifai_ollama_resize_content_request_body
		 *
		 * @param array $body    Request body that will be sent to Ollama.
		 * @param int   $post_id ID of post.
		 *
		 * @return array Request body.
		 */
		$body = apply_filters(
			'classifai_ollama_resize_content_request_body',
			[
				'model'    => $settings[ static::ID ]['model'] ?? '',
				'messages' => [
					[
						'role'    => 'system',
						'content' => 'You will be provided with content delimited by triple quotes. ' . $prompt,
					],
					[
						'role'    => 'user',
						'content' => '"""' . esc_html( $args['content'] ) . '"""',
					],
				],
				'stream'   => false,
				'format'   => [
					'type'       => 'object',
					'properties' => [
						'content' => [
							'type' => 'string',
						],
					],
					'required'   => [ 'content' ],
				],
			],
			$post_id
		);

		// Make our API requests.
		$request = new APIRequest( 'test', $feature->get_option_name() );

		$responses = [];
		for ( $i = 0; $i < $args['num']; $i++ ) {
			$responses[] = $request->post(
				$this->get_api_chat_url( $settings[ static::ID ]['endpoint_url'] ?? '' ),
				[
					'body' => wp_json_encode( $body ),
				]
			);
		}

		$cleaned_responses = [];

		foreach ( $responses as $response ) {
			// Extract out the response, if it exists.
			if ( ! is_wp_error( $response ) && isset( $response['message'], $response['message']['content'] ) ) {
				// We expect the response to be valid json since we requested that schema.
				$content = json_decode( $response['message']['content'], true );

				if ( isset( $content['content'] ) ) {
					$cleaned_responses[] = sanitize_text_field( trim( $content['content'], ' "\'' ) );
				} else {
					return new WP_Error( 'refusal', esc_html__( 'Request failed', 'classifai' ) );
				}
			} elseif ( is_wp_error( $response ) ) {
				return $response;
			}
		}

		return $cleaned_responses;
	}

	/**
	 * Generate key takeaways from content.
	 *
	 * @param int   $post_id The Post ID we're processing
	 * @param array $args Arguments passed in.
	 * @return string|WP_Error
	 */
	public function generate_key_takeaways( int $post_id = 0, array $args = [] ) {
		if ( ! $post_id || ! get_post( $post_id ) ) {
			return new WP_Error( 'post_id_required', esc_html__( 'A valid post ID is required to generate key takeaways.', 'classifai' ) );
		}

		$feature  = new KeyTakeaways();
		$settings = $feature->get_settings();
		$args     = wp_parse_args(
			array_filter( $args ),
			[
				'content' => '',
				'title'   => get_the_title( $post_id ),
				'render'  => 'list',
				'run'     => 'auto',
			]
		);

		// These checks (and the one above) happen in the REST permission_callback,
		// but we run them again here in case this method is called directly.
		if ( empty( $settings ) || ( isset( $settings[ static::ID ]['authenticated'] ) && false === $settings[ static::ID ]['authenticated'] ) || ( ! $feature->is_feature_enabled() && ( ! defined( 'WP_CLI' ) || ! WP_CLI ) ) ) {
			return new WP_Error( 'not_enabled', esc_html__( 'Key Takeaways generation is disabled or authentication failed. Please check your settings.', 'classifai' ) );
		}

		/**
		 * Decide if we should automatically run the key takeaways generation.
		 *
		 * By default, we will always run the generation. If you
		 * only want to run when triggered manually, you can
		 * filter the return value to false.
		 *
		 * @since 3.5.0
		 * @hook classifai_ollama_key_takeaways_auto_run
		 *
		 * @param bool $run     Whether to run the key takeaways generation.
		 * @param int  $post_id ID of post we are summarizing.
		 *
		 * @return bool Whether to run the key takeaways generation.
		 */
		$run = apply_filters( 'classifai_ollama_key_takeaways_auto_run', true, $post_id );

		if ( 'auto' === $args['run'] && ! (bool) $run ) {
			return new WP_Error( 'not_run', esc_html__( 'Automatic generation is disabled. Please click the "Generate results" button when ready.', 'classifai' ) );
		}

		// Ensure we have content before making a request.
		$content = $this->get_content( $post_id, false, $args['content'] );
		if ( empty( $content ) ) {
			return new WP_Error( 'no_content', esc_html__( 'No content found. Please add content then click the "Generate results" button.', 'classifai' ) );
		}

		$prompt = esc_textarea( get_default_prompt( $settings['key_takeaways_prompt'] ) ?? $feature->prompt );

		// Replace our variables in the prompt.
		$prompt_search  = array( '{{TITLE}}' );
		$prompt_replace = array( $args['title'] );
		$prompt         = str_replace( $prompt_search, $prompt_replace, $prompt );

		/**
		 * Filter the prompt we will send to Ollama.
		 *
		 * @since 3.3.0
		 * @hook classifai_ollama_key_takeaways_prompt
		 *
		 * @param string $prompt  Prompt we are sending to Ollama. Gets added before post content.
		 * @param int    $post_id ID of post we are summarizing.
		 *
		 * @return string Prompt.
		 */
		$prompt = apply_filters( 'classifai_ollama_key_takeaways_prompt', $prompt, $post_id );

		/**
		 * Filter the request body before sending to Ollama.
		 *
		 * @since 3.3.0
		 * @hook classifai_ollama_key_takeaways_request_body
		 *
		 * @param array $body    Request body that will be sent to Ollama.
		 * @param int   $post_id ID of post we are summarizing.
		 *
		 * @return array Request body.
		 */
		$body = apply_filters(
			'classifai_ollama_key_takeaways_request_body',
			[
				'model'    => $settings[ static::ID ]['model'] ?? '',
				'messages' => [
					[
						'role'    => 'system',
						'content' => 'You will be provided with content delimited by triple quotes. Ensure the response you return is valid JSON, in the structure {"takeaways":["first","second"]}. ' . $prompt,
					],
					[
						'role'    => 'user',
						'content' => '"""' . $content . '"""',
					],
				],
				'format'   => 'json',
				'stream'   => false,
			],
			$post_id
		);

		// Make our API request.
		$request  = new APIRequest( 'test' );
		$response = $request->post(
			$this->get_api_chat_url( $settings[ static::ID ]['endpoint_url'] ?? '' ),
			[
				'body' => wp_json_encode( $body ),
			]
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		// Parse out the response and return it.
		if ( isset( $response['message'], $response['message']['content'] ) ) {
			$takeaways = json_decode( $response['message']['content'], true );

			if ( isset( $takeaways['takeaways'] ) && is_array( $takeaways['takeaways'] ) ) {
				$response = array_map(
					function ( $takeaway ) {
						return sanitize_text_field( trim( $takeaway, ' "\'' ) );
					},
					$takeaways['takeaways']
				);
			} else {
				return new WP_Error( 'refusal', esc_html__( 'Ollama request failed', 'classifai' ) );
			}
		} else {
			return new WP_Error( 'refusal', esc_html__( 'Ollama request failed', 'classifai' ) );
		}

		return $response;
	}

	/**
	 * Generate content.
	 *
	 * @param int   $post_id The Post ID we're processing
	 * @param array $args Arguments passed in.
	 * @return string|WP_Error
	 */
	public function generate_content( int $post_id = 0, array $args = [] ) {
		if ( ! $post_id || ! get_post( $post_id ) ) {
			return new WP_Error( 'post_id_required', esc_html__( 'Post ID is required to generate content.', 'classifai' ) );
		}

		$feature  = new ContentGeneration();
		$settings = $feature->get_settings();
		$args     = wp_parse_args(
			array_filter( $args ),
			[
				'title'        => '',
				'summary'      => '',
				'conversation' => [],
			]
		);

		// These checks happen in the REST permission_callback,
		// but we run them again here in case this method is called directly.
		if ( empty( $settings ) || ( isset( $settings[ static::ID ]['authenticated'] ) && false === $settings[ static::ID ]['authenticated'] ) || ! $feature->is_feature_enabled() ) {
			return new WP_Error( 'not_enabled', esc_html__( 'Content generation is disabled or Ollama authentication failed. Please check your settings.', 'classifai' ) );
		}

		/**
		 * Filter the prompt we will send to Ollama.
		 *
		 * @since 3.4.0
		 * @hook classifai_ollama_content_prompt
		 *
		 * @param string $prompt  Prompt we are sending to Ollama. Gets added before summary.
		 * @param int    $post_id ID of post.
		 * @param array  $args    Arguments passed to endpoint.
		 *
		 * @return string Prompt.
		 */
		$prompt = apply_filters( 'classifai_ollama_content_prompt', esc_textarea( get_default_prompt( $settings['prompt'] ) ?? $feature->prompt ), $post_id, $args );

		// Set up the content we are sending to the LLM.
		if ( ! empty( $args['conversation'] ) ) {
			$content = 'Summary: ' . $args['conversation'][0]['prompt'];
		} else {
			$content = 'Summary: ' . $args['summary'];
		}

		if ( ! empty( $args['title'] ) ) {
			$content = 'Title: ' . $args['title'] . "\n" . $content;
		}

		// Set up our messages.
		$messages = [
			[
				'role'    => 'system',
				'content' => $prompt . "\n" . $feature->return_format,
			],
			[
				'role'    => 'user',
				'content' => $content,
			],
		];

		// If we have an existing conversation, add it to the messages.
		if ( ! empty( $args['conversation'] ) ) {
			foreach ( $args['conversation'] as $i => $conversation ) {
				if ( $i > 0 ) {
					$messages[] = [
						'role'    => 'user',
						'content' => $conversation['prompt'],
					];
				}

				$messages[] = [
					'role'    => 'assistant',
					'content' => $conversation['completion'],
				];
			}

			$messages[] = [
				'role'    => 'user',
				'content' => $args['summary'],
			];
		}

		/**
		 * Filter the request body before sending to Ollama.
		 *
		 * @since 3.4.0
		 * @hook classifai_ollama_content_request_body
		 *
		 * @param array $body    Request body that will be sent to Ollama.
		 * @param int   $post_id ID of post.
		 *
		 * @return array Request body.
		 */
		$body = apply_filters(
			'classifai_ollama_content_request_body',
			[
				'model'    => $settings[ static::ID ]['model'] ?? '',
				'messages' => $messages,
				'stream'   => false,
			],
			$post_id
		);

		// Make our API request.
		$request  = new APIRequest( 'test' );
		$response = $request->post(
			$this->get_api_chat_url( $settings[ static::ID ]['endpoint_url'] ?? '' ),
			[
				'body' => wp_json_encode( $body ),
			]
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		// If we have a message, return it.
		$return = '';
		if ( isset( $response['message'], $response['message']['content'] ) ) {
			$return = wp_kses_post( trim( $response['message']['content'], ' "\'' ) );
		}

		return $return;
	}

	/**
	 * Common entry point for all REST endpoints for this provider.
	 *
	 * @param int    $post_id       The post ID we're processing.
	 * @param string $route_to_call The name of the route we're going to be processing.
	 * @param array  $args          Optional arguments to pass to the route.
	 * @return array|string|WP_Error
	 */
	public function rest_endpoint_callback( $post_id, string $route_to_call = '', array $args = [] ) {
		if ( ! $post_id || ! get_post( $post_id ) ) {
			return new WP_Error( 'post_id_required', esc_html__( 'A valid post ID is required.', 'classifai' ) );
		}

		$route_to_call = strtolower( $route_to_call );
		$return        = '';

		// Handle all of our routes.
		switch ( $route_to_call ) {
			case 'excerpt':
				$return = $this->generate_excerpt( $post_id, $args );
				break;
			case 'title':
				$return = $this->generate_title( $post_id, $args );
				break;
			case 'resize_content':
				$return = $this->resize_content( $post_id, $args );
				break;
			case 'key_takeaways':
				$return = $this->generate_key_takeaways( $post_id, $args );
				break;
			case 'create_content':
				$return = $this->generate_content( $post_id, $args );
				break;
		}

		return $return;
	}

	/**
	 * Get our content.
	 *
	 * @param int    $post_id Post ID to get content from.
	 * @param bool   $use_title Whether to use the title or not.
	 * @param string $post_content The post content.
	 * @return string
	 */
	public function get_content( int $post_id = 0, bool $use_title = true, string $post_content = '' ): string {
		$normalizer = new Normalizer();

		if ( empty( $post_content ) ) {
			$post         = get_post( $post_id );
			$post_content = apply_filters( 'the_content', $post->post_content );
		}

		$post_content = preg_replace( '#\[.+\](.+)\[/.+\]#', '$1', $post_content );

		if ( $use_title ) {
			$content = $normalizer->normalize( $post_id, $post_content );
		} else {
			$content = $normalizer->normalize_content( $post_content, '', $post_id );
		}

		/**
		 * Filter content that will get sent to Ollama.
		 *
		 * @since 3.3.0
		 * @hook classifai_ollama_content
		 *
		 * @param string $content Content that will be sent to Ollama.
		 * @param int    $post_id ID of post.
		 *
		 * @return string Content.
		 */
		return apply_filters( 'classifai_ollama_content', $content, $post_id );
	}

	/**
	 * Chunk content into smaller pieces with an overlap.
	 *
	 * @param string $content Content to chunk.
	 * @param int    $chunk_size Size of each chunk, in words.
	 * @param int    $overlap_size Overlap size for each chunk, in words.
	 * @return array
	 */
	public function chunk_content( string $content = '', int $chunk_size = 150, $overlap_size = 25 ): array {
		// Remove multiple whitespaces.
		$content = preg_replace( '/\s+/', ' ', $content );

		// Split text by single whitespace.
		$words = explode( ' ', $content );

		$chunks     = [];
		$text_count = count( $words );

		// Iterate through and chunk data with an overlap.
		for ( $i = 0; $i < $text_count; $i += $chunk_size ) {
			// Join a set of words into a string.
			$chunk = implode(
				' ',
				array_slice(
					$words,
					max( $i - $overlap_size, 0 ),
					$chunk_size + $overlap_size
				)
			);

			array_push( $chunks, $chunk );
		}

		return $chunks;
	}

	/**
	 * Builds the API Model URL.
	 *
	 * @param string $endpoint_url The endpoint URL.
	 * @return string
	 */
	public function get_api_model_url( string $endpoint_url ): string {
		return sprintf( '%s%s', trailingslashit( $endpoint_url ), 'api/tags' );
	}

	/**
	 * Builds the API Chat URL.
	 *
	 * @param string $endpoint_url The endpoint URL.
	 * @return string
	 */
	public function get_api_chat_url( string $endpoint_url ): string {
		return sprintf( '%s%s', trailingslashit( $endpoint_url ), 'api/chat' );
	}

	/**
	 * Builds the API Embeddings URL.
	 *
	 * @param string $endpoint_url The endpoint URL.
	 * @return string
	 */
	public function get_api_embeddings_url( string $endpoint_url ): string {
		return sprintf( '%s%s', trailingslashit( $endpoint_url ), 'api/embed' );
	}

	/**
	 * Builds the API Generate URL.
	 *
	 * @param string $endpoint_url The endpoint URL.
	 * @return string
	 */
	public function get_api_generate_url( string $endpoint_url ): string {
		return sprintf( '%s%s', trailingslashit( $endpoint_url ), 'api/generate' );
	}
}
