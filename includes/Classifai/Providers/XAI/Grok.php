<?php
/**
 * xAI Grok integration
 */

namespace Classifai\Providers\XAI;

use Classifai\Features\ContentResizing;
use Classifai\Features\DescriptiveTextGenerator;
use Classifai\Features\ExcerptGeneration;
use Classifai\Features\TitleGeneration;
use Classifai\Providers\OpenAI\Tokenizer;
use Classifai\Providers\Provider;
use Classifai\Normalizer;
use WP_Error;

use function Classifai\get_default_prompt;
use function Classifai\sanitize_number_of_responses_field;
use function Classifai\get_modified_image_source_url;
use function Classifai\get_largest_size_and_dimensions_image_url;

class Grok extends Provider {
	/**
	 * Provider ID
	 *
	 * @var string
	 */
	const ID = 'xai_grok';

	/**
	 * xAI Grok URL
	 *
	 * @var string
	 */
	protected $completions_url = 'https://api.x.ai/v1/chat/completions';

	/**
	 * xAI Models URL
	 *
	 * @var string
	 */
	protected $models_url = 'https://api.x.ai/v1/models';

	/**
	 * xAI Grok model
	 *
	 * @var string
	 */
	protected $model = 'grok-2-1212';

	/**
	 * xAI Grok vision model
	 *
	 * @var string
	 */
	protected $vision_model = 'grok-2-vision-1212';

	/**
	 * Maximum number of tokens our model supports
	 *
	 * @var int
	 */
	protected $max_tokens = 131072;

	/**
	 * xAI Grok constructor.
	 *
	 * @param \Classifai\Features\Feature $feature_instance The feature instance.
	 */
	public function __construct( $feature_instance = null ) {
		$this->feature_instance = $feature_instance;
	}

	/**
	 * Get the model name.
	 *
	 * @return string
	 */
	public function get_model(): string {
		/**
		 * Filter the model name.
		 *
		 * Useful if you want to use a different model, like
		 * grok-beta
		 *
		 * @since x.x.x
		 * @hook classifai_xai_grok_model
		 *
		 * @param {string} $model The default model to use.
		 *
		 * @return {string} The model to use.
		 */
		return apply_filters( 'classifai_xai_grok_model', $this->model );
	}

	/**
	 * Get the vision model (model for images).
	 *
	 * @return string
	 */
	public function get_vision_model(): string {
		/**
		 * Filter the vision model name (The model with image and text capabilities).
		 *
		 * Useful if you want to use a different model, like
		 * grok-vision-beta
		 *
		 * @since x.x.x
		 * @hook classifai_xai_grok_vision_model
		 *
		 * @param {string} $model The default model to use.
		 *
		 * @return {string} The model to use.
		 */
		return apply_filters( 'classifai_xai_grok_vision_model', $this->vision_model );
	}

	/**
	 * Returns the default settings for this provider.
	 *
	 * @return array
	 */
	public function get_default_provider_settings(): array {
		$common_settings = [
			'api_key'       => '',
			'authenticated' => false,
		];

		/**
		 * Default values for feature specific settings.
		 */
		switch ( $this->feature_instance::ID ) {
			case ContentResizing::ID:
			case TitleGeneration::ID:
				$common_settings['number_of_suggestions'] = 1;
				break;

			case DescriptiveTextGenerator::ID:
				$common_settings['prompt'] = [
					[
						'title'    => esc_html__( 'ClassifAI default', 'classifai' ),
						'prompt'   => $this->feature_instance->prompt,
						'original' => 1,
						'default'  => 1,
					],
				];
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
		$settings         = $this->feature_instance->get_settings();
		$api_key_settings = $this->sanitize_api_key_settings( $new_settings, $settings );

		$new_settings[ static::ID ]['api_key']       = $api_key_settings[ static::ID ]['api_key'];
		$new_settings[ static::ID ]['authenticated'] = $api_key_settings[ static::ID ]['authenticated'];

		switch ( $this->feature_instance::ID ) {
			case ContentResizing::ID:
			case TitleGeneration::ID:
				$new_settings[ static::ID ]['number_of_suggestions'] = sanitize_number_of_responses_field( 'number_of_suggestions', $new_settings[ static::ID ], $settings[ static::ID ] );
				break;
		}

		return $new_settings;
	}

	/**
	 * Sanitize the API key, showing an error message if needed.
	 *
	 * @param array $new_settings Incoming settings, if any.
	 * @param array $settings     Current settings, if any.
	 * @return array
	 */
	public function sanitize_api_key_settings( array $new_settings = [], array $settings = [] ): array {
		$authenticated = $this->authenticate_credentials( $new_settings[ static::ID ]['api_key'] ?? '' );

		$new_settings[ static::ID ]['authenticated'] = $settings[ static::ID ]['authenticated'];

		if ( is_wp_error( $authenticated ) ) {
			$new_settings[ static::ID ]['authenticated'] = false;
			$error_message                               = $authenticated->get_error_message();

			// For response code 429, credentials are valid but rate limit is reached.
			if ( 429 === (int) $authenticated->get_error_code() ) {
				$new_settings[ static::ID ]['authenticated'] = true;
			}

			add_settings_error(
				'api_key',
				'classifai-auth',
				$error_message,
				'error'
			);
		} else {
			$new_settings[ static::ID ]['authenticated'] = true;
		}

		$new_settings[ static::ID ]['api_key'] = sanitize_text_field( $new_settings[ static::ID ]['api_key'] ?? $settings[ static::ID ]['api_key'] );

		return $new_settings;
	}

	/**
	 * Authenticate our credentials.
	 *
	 * @param string $api_key Api Key.
	 * @return bool|WP_Error
	 */
	protected function authenticate_credentials( string $api_key = '' ) {
		// Check that we have credentials before hitting the API.
		if ( empty( $api_key ) ) {
			return new WP_Error( 'auth', esc_html__( 'Please enter your xAI API key.', 'classifai' ) );
		}

		// Make request to ensure credentials work.
		$request  = new APIRequest( $api_key );
		$response = $request->get( $this->models_url );

		return ! is_wp_error( $response ) ? true : $response;
	}

	/**
	 * Common entry point for all REST endpoints for this provider.
	 *
	 * @param int    $post_id The Post ID we're processing.
	 * @param string $route_to_call The route we are processing.
	 * @param array  $args Optional arguments to pass to the route.
	 * @return string|WP_Error
	 */
	public function rest_endpoint_callback( $post_id = 0, string $route_to_call = '', array $args = [] ) {
		if ( ! $post_id || ! get_post( $post_id ) ) {
			return new WP_Error( 'post_id_required', esc_html__( 'A valid ID is required.', 'classifai' ) );
		}

		$route_to_call = strtolower( $route_to_call );
		$return        = '';

		// Handle all of our routes.
		switch ( $route_to_call ) {
			case 'descriptive_text':
				$return = $this->generate_descriptive_text( $post_id, $args );
				break;
			case 'excerpt':
				$return = $this->generate_excerpt( $post_id, $args );
				break;
			case 'title':
				$return = $this->generate_titles( $post_id, $args );
				break;
			case 'resize_content':
				$return = $this->resize_content( $post_id, $args );
				break;
		}

		return $return;
	}

	/**
	 * Generate descriptive text of an image.
	 *
	 * @param int   $post_id The attachment ID we're processing.
	 * @param array $args Optional arguments.
	 * @return string|WP_Error
	 */
	public function generate_descriptive_text( int $post_id = 0, array $args = [] ) {
		// Check to be sure the attachment exists and is an image.
		if ( ! wp_attachment_is_image( $post_id ) ) {
			return new WP_Error( 'invalid', esc_html__( 'This attachment can\'t be processed.', 'classifai' ) );
		}

		$metadata = wp_get_attachment_metadata( $post_id );

		if ( ! $metadata || ! is_array( $metadata ) ) {
			return new WP_Error( 'invalid', esc_html__( 'No valid metadata found.', 'classifai' ) );
		}

		$image_url = get_modified_image_source_url( $post_id );

		if ( empty( $image_url ) || ! filter_var( $image_url, FILTER_VALIDATE_URL ) ) {
			if ( isset( $metadata['sizes'] ) && is_array( $metadata['sizes'] ) ) {
				$image_url = get_largest_size_and_dimensions_image_url(
					get_attached_file( $post_id ),
					wp_get_attachment_url( $post_id ),
					$metadata,
					[ 28, 16000 ],
					[ 28, 16000 ],
					10 * MB_IN_BYTES
				);
			} else {
				$image_url = wp_get_attachment_url( $post_id );
			}
		}

		if ( empty( $image_url ) ) {
			return new WP_Error( 'error', esc_html__( 'Valid image size not found. Make sure the image is bigger than 512x512px.', 'classifai' ) );
		}

		$feature  = new DescriptiveTextGenerator();
		$settings = $feature->get_settings();

		// These checks (and the one above) happen in the REST permission_callback,
		// but we run them again here in case this method is called directly.
		if ( empty( $settings ) || ( isset( $settings[ static::ID ]['authenticated'] ) && false === $settings[ static::ID ]['authenticated'] ) || ( ! $feature->is_feature_enabled() && ( ! defined( 'WP_CLI' ) || ! WP_CLI ) ) ) {
			return new WP_Error( 'not_enabled', esc_html__( 'Descriptive text generation is disabled or xAI authentication failed. Please check your settings.', 'classifai' ) );
		}

		$request = new APIRequest( $settings[ static::ID ]['api_key'] ?? '', $feature->get_option_name() );

		/**
		 * Filter the prompt we will send to xAI Grok.
		 *
		 * @since x.x.x
		 * @hook classifai_xai_grok_descriptive_text_prompt
		 *
		 * @param {string} $prompt Prompt we are sending to xAI Grok.
		 * @param {int} $post_id ID of attachment we are describing.
		 *
		 * @return {string} Prompt.
		 */
		$prompt = apply_filters( 'classifai_xai_grok_descriptive_text_prompt', get_default_prompt( $settings[ static::ID ]['prompt'] ?? [] ) ?? $feature->prompt, $post_id );

		/**
		 * Filter the request body before sending to xAI Grok.
		 *
		 * @since x.x.x
		 * @hook classifai_xai_grok_descriptive_text_request_body
		 *
		 * @param {array} $body Request body that will be sent to xAI Grok.
		 * @param {int} $post_id ID of attachment we are describing.
		 *
		 * @return {array} Request body.
		 */
		$body = apply_filters(
			'classifai_xai_grok_descriptive_text_request_body',
			[
				'model'       => $this->get_vision_model(),
				'messages'    => [
					[
						'role'    => 'system',
						'content' => $prompt,
					],
					[
						'role'    => 'user',
						'content' => [
							[
								'type'      => 'image_url',
								'image_url' => [
									'url'    => $image_url,
									'detail' => 'auto',
								],
							],
						],
					],
				],
				'temperature' => 0.2,
				'max_tokens'  => 300,
			],
			$post_id
		);

		// Make our API request.
		$response = $request->post(
			$this->completions_url,
			[
				'body' => wp_json_encode( $body ),
			]
		);

		// Extract out the text response, if it exists.
		if ( ! is_wp_error( $response ) && ! empty( $response['choices'] ) ) {
			foreach ( $response['choices'] as $choice ) {
				if ( isset( $choice['message'], $choice['message']['content'] ) ) {
					$response = sanitize_text_field( trim( $choice['message']['content'], ' "\'' ) );
				}
			}
		}

		return $response;
	}

	/**
	 * Generate an excerpt using Grok.
	 *
	 * @param int   $post_id The Post ID we're processing
	 * @param array $args Arguments passed in.
	 * @return string|WP_Error
	 */
	public function generate_excerpt( int $post_id = 0, array $args = [] ) {
		if ( ! $post_id || ! get_post( $post_id ) ) {
			return new WP_Error( 'post_id_required', esc_html__( 'A valid post ID is required to generate an excerpt.', 'classifai' ) );
		}

		$feature  = new ExcerptGeneration();
		$settings = $feature->get_settings();
		$args     = wp_parse_args(
			array_filter( $args ),
			[
				'content' => '',
				'title'   => get_the_title( $post_id ),
			]
		);

		// These checks (and the one above) happen in the REST permission_callback,
		// but we run them again here in case this method is called directly.
		if ( empty( $settings ) || ( isset( $settings[ static::ID ]['authenticated'] ) && false === $settings[ static::ID ]['authenticated'] ) || ( ! $feature->is_feature_enabled() && ( ! defined( 'WP_CLI' ) || ! WP_CLI ) ) ) {
			return new WP_Error( 'not_enabled', esc_html__( 'Excerpt generation is disabled or xAI authentication failed. Please check your settings.', 'classifai' ) );
		}

		$excerpt_length = absint( $settings['length'] ?? 55 );

		$request = new APIRequest( $settings[ static::ID ]['api_key'] ?? '', $feature->get_option_name() );

		$excerpt_prompt = esc_textarea( get_default_prompt( $settings['generate_excerpt_prompt'] ) ?? $feature->prompt );

		// Replace our variables in the prompt.
		$prompt_search  = array( '{{WORDS}}', '{{TITLE}}' );
		$prompt_replace = array( $excerpt_length, $args['title'] );
		$prompt         = str_replace( $prompt_search, $prompt_replace, $excerpt_prompt );

		/**
		 * Filter the prompt we will send to xAI Grok.
		 *
		 * @since x.x.x
		 * @hook classifai_xai_grok_excerpt_prompt
		 *
		 * @param {string} $prompt Prompt we are sending to xAI Grok. Gets added before post content.
		 * @param {int} $post_id ID of post we are summarizing.
		 * @param {int} $excerpt_length Length of final excerpt.
		 *
		 * @return {string} Prompt.
		 */
		$prompt = apply_filters( 'classifai_xai_grok_excerpt_prompt', $prompt, $post_id, $excerpt_length );

		/**
		 * Filter the request body before sending to xAI Grok.
		 *
		 * @since x.x.x
		 * @hook classifai_xai_grok_excerpt_request_body
		 *
		 * @param {array} $body Request body that will be sent to xAI Grok.
		 * @param {int} $post_id ID of post we are summarizing.
		 *
		 * @return {array} Request body.
		 */
		$body = apply_filters(
			'classifai_xai_grok_excerpt_request_body',
			[
				'model'       => $this->get_model(),
				'messages'    => [
					[
						'role'    => 'system',
						'content' => 'You will be provided with content delimited by triple quotes. ' . $prompt,
					],
					[
						'role'    => 'user',
						'content' => '"""' . $this->get_content( $post_id, $excerpt_length, false, $args['content'] ) . '"""',
					],
				],
				'stream'      => false,
				'temperature' => 0.9,
			],
			$post_id
		);

		// Make our API request.
		$response = $request->post(
			$this->completions_url,
			[
				'body' => wp_json_encode( $body ),
			]
		);

		set_transient( 'classifai_xai_grok_excerpt_generation_latest_response', $response, DAY_IN_SECONDS * 30 );

		// Extract out the text response, if it exists.
		if ( ! is_wp_error( $response ) && ! empty( $response['choices'] ) ) {
			foreach ( $response['choices'] as $choice ) {
				if ( isset( $choice['message'], $choice['message']['content'] ) ) {
					$response = sanitize_text_field( trim( $choice['message']['content'], ' "\'' ) );
				}
			}
		}

		return $response;
	}

	/**
	 * Generate titles using xAI Grok.
	 *
	 * @param int   $post_id The Post Id we're processing
	 * @param array $args Arguments passed in.
	 * @return string|WP_Error
	 */
	public function generate_titles( int $post_id = 0, array $args = [] ) {
		if ( ! $post_id || ! get_post( $post_id ) ) {
			return new WP_Error( 'post_id_required', esc_html__( 'Post ID is required to generate titles.', 'classifai' ) );
		}

		$feature  = new TitleGeneration();
		$settings = $feature->get_settings();
		$args     = wp_parse_args(
			array_filter( $args ),
			[
				'num'     => $settings[ static::ID ]['number_of_suggestions'] ?? 1,
				'content' => '',
			]
		);

		// These checks happen in the REST permission_callback,
		// but we run them again here in case this method is called directly.
		if ( empty( $settings ) || ( isset( $settings[ static::ID ]['authenticated'] ) && false === $settings[ static::ID ]['authenticated'] ) || ! $feature->is_feature_enabled() ) {
			return new WP_Error( 'not_enabled', esc_html__( 'Title generation is disabled or xAI authentication failed. Please check your settings.', 'classifai' ) );
		}

		$request = new APIRequest( $settings[ static::ID ]['api_key'] ?? '', $feature->get_option_name() );

		$prompt = esc_textarea( get_default_prompt( $settings['generate_title_prompt'] ) ?? $feature->prompt );

		/**
		 * Filter the prompt we will send to xAI Grok.
		 *
		 * @since x.x.x
		 * @hook classifai_xai_grok_title_prompt
		 *
		 * @param {string} $prompt Prompt we are sending to xAI Grok. Gets added before post content.
		 * @param {int} $post_id ID of post we are summarizing.
		 * @param {array} $args Arguments passed to endpoint.
		 *
		 * @return {string} Prompt.
		 */
		$prompt = apply_filters( 'classifai_xai_grok_title_prompt', $prompt, $post_id, $args );

		/**
		 * Filter the request body before sending to xAI Grok.
		 *
		 * @since x.x.x
		 * @hook classifai_xai_grok_title_request_body
		 *
		 * @param {array} $body Request body that will be sent to xAI Grok.
		 * @param {int} $post_id ID of post we are summarizing.
		 *
		 * @return {array} Request body.
		 */
		$body = apply_filters(
			'classifai_xai_grok_title_request_body',
			[
				'model'       => $this->get_model(),
				'messages'    => [
					[
						'role'    => 'system',
						'content' => 'You will be provided with content delimited by triple quotes. ' . $prompt,
					],
					[
						'role'    => 'user',
						'content' => '"""' . $this->get_content( $post_id, absint( $args['num'] ) * 15, false, $args['content'] ) . '"""',
					],
				],
				'temperature' => 0.9,
				'stream'      => false,
				'n'           => absint( $args['num'] ),
			],
			$post_id
		);

		// Make our API request.
		$response = $request->post(
			$this->completions_url,
			[
				'body' => wp_json_encode( $body ),
			]
		);

		set_transient( 'classifai_xai_grok_title_generation_latest_response', $response, DAY_IN_SECONDS * 30 );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( empty( $response['choices'] ) ) {
			return new WP_Error( 'no_choices', esc_html__( 'No choices were returned from xAI.', 'classifai' ) );
		}

		// Extract out the text response.
		$return = [];
		foreach ( $response['choices'] as $choice ) {
			if ( isset( $choice['message'], $choice['message']['content'] ) ) {
				$return[] = sanitize_text_field( trim( $choice['message']['content'], ' "\'' ) );
			}
		}

		return $return;
	}

	/**
	 * Resizes content.
	 *
	 * @param int   $post_id The Post Id we're processing
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
				'num' => $settings[ static::ID ]['number_of_suggestions'] ?? 1,
			]
		);

		$request = new APIRequest( $settings[ static::ID ]['api_key'] ?? '', $feature->get_option_name() );

		if ( 'shrink' === $args['resize_type'] ) {
			$prompt = esc_textarea( get_default_prompt( $settings['condense_text_prompt'] ) ?? $feature->condense_prompt );
		} else {
			$prompt = esc_textarea( get_default_prompt( $settings['expand_text_prompt'] ) ?? $feature->expand_prompt );
		}

		/**
		 * Filter the resize prompt we will send to xAI Grok.
		 *
		 * @since x.x.x
		 * @hook classifai_xai_grok_' . $args['resize_type'] . '_content_prompt
		 *
		 * @param {string} $prompt Resize prompt we are sending to xAI Grok. Gets added as a system prompt.
		 * @param {int} $post_id ID of post.
		 * @param {array} $args Arguments passed to endpoint.
		 *
		 * @return {string} Prompt.
		 */
		$prompt = apply_filters( 'classifai_xai_grok_' . $args['resize_type'] . '_content_prompt', $prompt, $post_id, $args );

		/**
		 * Filter the resize request body before sending to xAI Grok.
		 *
		 * @since x.x.x
		 * @hook classifai_xai_grok_resize_content_request_body
		 *
		 * @param {array} $body Request body that will be sent to xAI Grok.
		 * @param {int}   $post_id ID of post.
		 *
		 * @return {array} Request body.
		 */
		$body = apply_filters(
			'classifai_xai_grok_resize_content_request_body',
			[
				'model'       => $this->get_model(),
				'messages'    => [
					[
						'role'    => 'system',
						'content' => 'You will be provided with content delimited by triple quotes. ' . $prompt,
					],
					[
						'role'    => 'user',
						'content' => '"""' . esc_html( $args['content'] ) . '"""',
					],
				],
				'temperature' => 0.9,
				'stream'      => false,
				'n'           => absint( $args['num'] ),
			],
			$post_id
		);

		// Make our API request.
		$response = $request->post(
			$this->completions_url,
			[
				'body' => wp_json_encode( $body ),
			]
		);

		set_transient( 'classifai_xai_grok_content_resizing_latest_response', $response, DAY_IN_SECONDS * 30 );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( empty( $response['choices'] ) ) {
			return new WP_Error( 'no_choices', esc_html__( 'No choices were returned from xAI.', 'classifai' ) );
		}

		// Extract out the text response.
		$return = [];

		foreach ( $response['choices'] as $choice ) {
			if ( isset( $choice['message'], $choice['message']['content'] ) ) {
				// xAI Grok often adds quotes to strings, so remove those as well as extra spaces.
				$return[] = sanitize_text_field( trim( $choice['message']['content'], ' "\'' ) );
			}
		}

		return $return;
	}

	/**
	 * Get our content, trimming if needed.
	 *
	 * @param int    $post_id Post ID to get content from.
	 * @param int    $return_length Word length of returned content.
	 * @param bool   $use_title Whether to use the title or not.
	 * @param string $post_content The post content.
	 * @return string
	 */
	public function get_content( int $post_id = 0, int $return_length = 0, bool $use_title = true, string $post_content = '' ): string {
		$tokenizer  = new Tokenizer( $this->max_tokens );
		$normalizer = new Normalizer();

		/**
		 * We first determine how many tokens, roughly, our returned content will require.
		 * This is determined by the number of words we expect to be returned and how
		 * many tokens are in an average word.
		 */
		$return_tokens = $tokenizer->tokens_in_words( $return_length );

		/**
		 * We then subtract those tokens from the max number of tokens xAI Grok allows
		 * in a single request, as well as subtracting out the number of tokens in our
		 * prompt (~50). xAI Grok counts both the tokens in the request and in
		 * the response towards the max.
		 */
		$max_content_tokens = $this->max_tokens - $return_tokens - 50;

		if ( empty( $post_content ) ) {
			$post         = get_post( $post_id );
			$post_content = apply_filters( 'the_content', $post->post_content );
		}

		$post_content = preg_replace( '#\[.+\](.+)\[/.+\]#', '$1', $post_content );

		// Then trim our content, if needed, to stay under the max.
		if ( $use_title ) {
			$content = $tokenizer->trim_content(
				$normalizer->normalize( $post_id, $post_content ),
				(int) $max_content_tokens
			);
		} else {
			$content = $tokenizer->trim_content(
				$normalizer->normalize_content( $post_content, '', $post_id ),
				(int) $max_content_tokens
			);
		}

		/**
		 * Filter content that will get sent to xAI Grok.
		 *
		 * @since x.x.x
		 * @hook classifai_xai_grok_content
		 *
		 * @param {string} $content Content that will be sent to xAI Grok.
		 * @param {int} $post_id ID of post we are summarizing.
		 *
		 * @return {string} Content.
		 */
		return apply_filters( 'classifai_xai_grok_content', $content, $post_id );
	}

	/**
	 * Returns the debug information for the provider settings.
	 *
	 * @return array
	 */
	public function get_debug_information(): array {
		$settings          = $this->feature_instance->get_settings();
		$provider_settings = $settings[ static::ID ];
		$debug_info        = [];

		if ( $this->feature_instance instanceof TitleGeneration ) {
			$debug_info[ __( 'No. of titles', 'classifai' ) ]         = $provider_settings['number_of_suggestions'] ?? 1;
			$debug_info[ __( 'Generate title prompt', 'classifai' ) ] = wp_json_encode( $settings['generate_title_prompt'] ?? [] );
			$debug_info[ __( 'Latest response', 'classifai' ) ]       = $this->get_formatted_latest_response( get_transient( 'classifai_xai_grok_title_generation_latest_response' ) );
		} elseif ( $this->feature_instance instanceof ExcerptGeneration ) {
			$debug_info[ __( 'Excerpt length', 'classifai' ) ]          = $settings['length'] ?? 55;
			$debug_info[ __( 'Generate excerpt prompt', 'classifai' ) ] = wp_json_encode( $settings['generate_excerpt_prompt'] ?? [] );
			$debug_info[ __( 'Latest response', 'classifai' ) ]         = $this->get_formatted_latest_response( get_transient( 'classifai_xai_grok_excerpt_generation_latest_response' ) );
		} elseif ( $this->feature_instance instanceof ContentResizing ) {
			$debug_info[ __( 'No. of suggestions', 'classifai' ) ]   = $provider_settings['number_of_suggestions'] ?? 1;
			$debug_info[ __( 'Expand text prompt', 'classifai' ) ]   = wp_json_encode( $settings['expand_text_prompt'] ?? [] );
			$debug_info[ __( 'Condense text prompt', 'classifai' ) ] = wp_json_encode( $settings['condense_text_prompt'] ?? [] );
			$debug_info[ __( 'Latest response', 'classifai' ) ]      = $this->get_formatted_latest_response( get_transient( 'classifai_xai_grok_content_resizing_latest_response' ) );
		}

		return apply_filters(
			'classifai_' . self::ID . '_debug_information',
			$debug_info,
			$settings,
			$this->feature_instance
		);
	}
}
