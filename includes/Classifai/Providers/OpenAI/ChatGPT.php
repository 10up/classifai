<?php
/**
 * OpenAI ChatGPT integration
 */

namespace Classifai\Providers\OpenAI;

use Classifai\Features\ContentResizing;
use Classifai\Features\DescriptiveTextGenerator;
use Classifai\Features\ExcerptGeneration;
use Classifai\Features\TitleGeneration;
use Classifai\Providers\Provider;
use Classifai\Normalizer;
use WP_Error;

use function Classifai\get_default_prompt;
use function Classifai\sanitize_number_of_responses_field;
use function Classifai\get_modified_image_source_url;
use function Classifai\get_largest_size_and_dimensions_image_url;

class ChatGPT extends Provider {

	use OpenAI;

	const ID = 'openai_chatgpt';

	/**
	 * OpenAI ChatGPT URL
	 *
	 * @var string
	 */
	protected $chatgpt_url = 'https://api.openai.com/v1/chat/completions';

	/**
	 * OpenAI ChatGPT model
	 *
	 * @var string
	 */
	protected $chatgpt_model = 'gpt-4o-mini';

	/**
	 * Maximum number of tokens our model supports
	 *
	 * @var int
	 */
	protected $max_tokens = 128000;

	/**
	 * OpenAI ChatGPT constructor.
	 *
	 * @param \Classifai\Features\Feature $feature_instance The feature instance.
	 */
	public function __construct( $feature_instance = null ) {
		$this->feature_instance = $feature_instance;
	}

	/**
	 * Render the provider fields.
	 */
	public function render_provider_fields() {
		$settings = $this->feature_instance->get_settings( static::ID );

		add_settings_field(
			static::ID . '_api_key',
			esc_html__( 'API Key', 'classifai' ),
			[ $this->feature_instance, 'render_input' ],
			$this->feature_instance->get_option_name(),
			$this->feature_instance->get_option_name() . '_section',
			[
				'option_index'  => static::ID,
				'label_for'     => 'api_key',
				'input_type'    => 'password',
				'default_value' => $settings['api_key'],
				'class'         => 'classifai-provider-field hidden provider-scope-' . static::ID, // Important to add this.
				'description'   => $this->feature_instance->is_configured_with_provider( static::ID ) ?
					'' :
					sprintf(
						wp_kses(
							/* translators: %1$s is replaced with the OpenAI sign up URL */
							__( 'Don\'t have an OpenAI account yet? <a title="Sign up for an OpenAI account" href="%1$s">Sign up for one</a> in order to get your API key.', 'classifai' ),
							[
								'a' => [
									'href'  => [],
									'title' => [],
								],
							]
						),
						esc_url( 'https://platform.openai.com/signup' )
					),
			]
		);

		switch ( $this->feature_instance::ID ) {
			case ContentResizing::ID:
			case TitleGeneration::ID:
				add_settings_field(
					static::ID . '_number_of_suggestions',
					esc_html__( 'Number of suggestions', 'classifai' ),
					[ $this->feature_instance, 'render_input' ],
					$this->feature_instance->get_option_name(),
					$this->feature_instance->get_option_name() . '_section',
					[
						'option_index'  => static::ID,
						'label_for'     => 'number_of_suggestions',
						'input_type'    => 'number',
						'min'           => 1,
						'step'          => 1,
						'default_value' => $settings['number_of_suggestions'],
						'class'         => 'classifai-provider-field hidden provider-scope-' . static::ID, // Important to add this.
						'description'   => esc_html__( 'Number of suggestions that will be generated in one request.', 'classifai' ),
					]
				);
				break;
		}

		do_action( 'classifai_' . static::ID . '_render_provider_fields', $this );
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
	 * Sanitize the API key.
	 *
	 * @param array $new_settings The settings array.
	 * @return string
	 */
	public function sanitize_api_key( array $new_settings ): string {
		$settings = $this->feature_instance->get_settings();
		return sanitize_text_field( $new_settings[ static::ID ]['api_key'] ?? $settings[ static::ID ]['api_key'] ?? '' );
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
			return new WP_Error( 'post_id_required', esc_html__( 'A valid post ID is required to generate an excerpt.', 'classifai' ) );
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
					[ 512, 2000 ],
					[ 512, 2000 ],
					100 * MB_IN_BYTES
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
			return new WP_Error( 'not_enabled', esc_html__( 'Descriptive text generation is disabled or OpenAI authentication failed. Please check your settings.', 'classifai' ) );
		}

		$request = new APIRequest( $settings[ static::ID ]['api_key'] ?? '', $feature->get_option_name() );

		/**
		 * Filter the prompt we will send to ChatGPT.
		 *
		 * @since 3.2.0
		 * @hook classifai_chatgpt_descriptive_text_prompt
		 *
		 * @param {string} $prompt Prompt we are sending to ChatGPT.
		 * @param {int} $post_id ID of attachment we are describing.
		 *
		 * @return {string} Prompt.
		 */
		$prompt = apply_filters( 'classifai_chatgpt_descriptive_text_prompt', get_default_prompt( $settings[ static::ID ]['prompt'] ?? [] ) ?? $feature->prompt, $post_id );

		/**
		 * Filter the request body before sending to ChatGPT.
		 *
		 * @since 3.2.0
		 * @hook classifai_chatgpt_descriptive_text_request_body
		 *
		 * @param {array} $body Request body that will be sent to ChatGPT.
		 * @param {int} $post_id ID of attachment we are describing.
		 *
		 * @return {array} Request body.
		 */
		$body = apply_filters(
			'classifai_chatgpt_descriptive_text_request_body',
			[
				'model'       => $this->chatgpt_model,
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
			$this->chatgpt_url,
			[
				'body' => wp_json_encode( $body ),
			]
		);

		// Extract out the text response, if it exists.
		if ( ! is_wp_error( $response ) && ! empty( $response['choices'] ) ) {
			foreach ( $response['choices'] as $choice ) {
				if ( isset( $choice['message'], $choice['message']['content'] ) ) {
					// ChatGPT often adds quotes to strings, so remove those as well as extra spaces.
					$response = sanitize_text_field( trim( $choice['message']['content'], ' "\'' ) );
				}
			}
		}

		return $response;
	}

	/**
	 * Generate an excerpt using ChatGPT.
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
			return new WP_Error( 'not_enabled', esc_html__( 'Excerpt generation is disabled or OpenAI authentication failed. Please check your settings.', 'classifai' ) );
		}

		$excerpt_length = absint( $settings['length'] ?? 55 );

		$request = new APIRequest( $settings[ static::ID ]['api_key'] ?? '', $feature->get_option_name() );

		$excerpt_prompt = esc_textarea( get_default_prompt( $settings['generate_excerpt_prompt'] ) ?? $feature->prompt );

		// Replace our variables in the prompt.
		$prompt_search  = array( '{{WORDS}}', '{{TITLE}}' );
		$prompt_replace = array( $excerpt_length, $args['title'] );
		$prompt         = str_replace( $prompt_search, $prompt_replace, $excerpt_prompt );

		/**
		 * Filter the prompt we will send to ChatGPT.
		 *
		 * @since 2.0.0
		 * @hook classifai_chatgpt_excerpt_prompt
		 *
		 * @param {string} $prompt Prompt we are sending to ChatGPT. Gets added before post content.
		 * @param {int} $post_id ID of post we are summarizing.
		 * @param {int} $excerpt_length Length of final excerpt.
		 *
		 * @return {string} Prompt.
		 */
		$prompt = apply_filters( 'classifai_chatgpt_excerpt_prompt', $prompt, $post_id, $excerpt_length );

		/**
		 * Filter the request body before sending to ChatGPT.
		 *
		 * @since 2.0.0
		 * @hook classifai_chatgpt_excerpt_request_body
		 *
		 * @param {array} $body Request body that will be sent to ChatGPT.
		 * @param {int} $post_id ID of post we are summarizing.
		 *
		 * @return {array} Request body.
		 */
		$body = apply_filters(
			'classifai_chatgpt_excerpt_request_body',
			[
				'model'       => $this->chatgpt_model,
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
				'temperature' => 0.9,
			],
			$post_id
		);

		// Make our API request.
		$response = $request->post(
			$this->chatgpt_url,
			[
				'body' => wp_json_encode( $body ),
			]
		);

		set_transient( 'classifai_openai_chatgpt_excerpt_generation_latest_response', $response, DAY_IN_SECONDS * 30 );

		// Extract out the text response, if it exists.
		if ( ! is_wp_error( $response ) && ! empty( $response['choices'] ) ) {
			foreach ( $response['choices'] as $choice ) {
				if ( isset( $choice['message'], $choice['message']['content'] ) ) {
					// ChatGPT often adds quotes to strings, so remove those as well as extra spaces.
					$response = sanitize_text_field( trim( $choice['message']['content'], ' "\'' ) );
				}
			}
		}

		return $response;
	}

	/**
	 * Generate titles using ChatGPT.
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
			return new WP_Error( 'not_enabled', esc_html__( 'Title generation is disabled or OpenAI authentication failed. Please check your settings.', 'classifai' ) );
		}

		$request = new APIRequest( $settings[ static::ID ]['api_key'] ?? '', $feature->get_option_name() );

		$prompt = esc_textarea( get_default_prompt( $settings['generate_title_prompt'] ) ?? $feature->prompt );

		/**
		 * Filter the prompt we will send to ChatGPT.
		 *
		 * @since 2.2.0
		 * @hook classifai_chatgpt_title_prompt
		 *
		 * @param {string} $prompt Prompt we are sending to ChatGPT. Gets added before post content.
		 * @param {int} $post_id ID of post we are summarizing.
		 * @param {array} $args Arguments passed to endpoint.
		 *
		 * @return {string} Prompt.
		 */
		$prompt = apply_filters( 'classifai_chatgpt_title_prompt', $prompt, $post_id, $args );

		/**
		 * Filter the request body before sending to ChatGPT.
		 *
		 * @since 2.2.0
		 * @hook classifai_chatgpt_title_request_body
		 *
		 * @param {array} $body Request body that will be sent to ChatGPT.
		 * @param {int} $post_id ID of post we are summarizing.
		 *
		 * @return {array} Request body.
		 */
		$body = apply_filters(
			'classifai_chatgpt_title_request_body',
			[
				'model'       => $this->chatgpt_model,
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
				'n'           => absint( $args['num'] ),
			],
			$post_id
		);

		// Make our API request.
		$response = $request->post(
			$this->chatgpt_url,
			[
				'body' => wp_json_encode( $body ),
			]
		);

		set_transient( 'classifai_openai_chatgpt_title_generation_latest_response', $response, DAY_IN_SECONDS * 30 );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( empty( $response['choices'] ) ) {
			return new WP_Error( 'no_choices', esc_html__( 'No choices were returned from OpenAI.', 'classifai' ) );
		}

		// Extract out the text response.
		$return = [];
		foreach ( $response['choices'] as $choice ) {
			if ( isset( $choice['message'], $choice['message']['content'] ) ) {
				// ChatGPT often adds quotes to strings, so remove those as well as extra spaces.
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
		 * Filter the resize prompt we will send to ChatGPT.
		 *
		 * @since 2.3.0
		 * @hook classifai_chatgpt_' . $args['resize_type'] . '_content_prompt
		 *
		 * @param {string} $prompt Resize prompt we are sending to ChatGPT. Gets added as a system prompt.
		 * @param {int} $post_id ID of post.
		 * @param {array} $args Arguments passed to endpoint.
		 *
		 * @return {string} Prompt.
		 */
		$prompt = apply_filters( 'classifai_chatgpt_' . $args['resize_type'] . '_content_prompt', $prompt, $post_id, $args );

		/**
		 * Filter the resize request body before sending to ChatGPT.
		 *
		 * @since 2.3.0
		 * @hook classifai_chatgpt_resize_content_request_body
		 *
		 * @param {array} $body Request body that will be sent to ChatGPT.
		 * @param {int}   $post_id ID of post.
		 *
		 * @return {array} Request body.
		 */
		$body = apply_filters(
			'classifai_chatgpt_resize_content_request_body',
			[
				'model'       => $this->chatgpt_model,
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
				'n'           => absint( $args['num'] ),
			],
			$post_id
		);

		// Make our API request.
		$response = $request->post(
			$this->chatgpt_url,
			[
				'body' => wp_json_encode( $body ),
			]
		);

		set_transient( 'classifai_openai_chatgpt_content_resizing_latest_response', $response, DAY_IN_SECONDS * 30 );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( empty( $response['choices'] ) ) {
			return new WP_Error( 'no_choices', esc_html__( 'No choices were returned from OpenAI.', 'classifai' ) );
		}

		// Extract out the text response.
		$return = [];

		foreach ( $response['choices'] as $choice ) {
			if ( isset( $choice['message'], $choice['message']['content'] ) ) {
				// ChatGPT often adds quotes to strings, so remove those as well as extra spaces.
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
		 * We then subtract those tokens from the max number of tokens ChatGPT allows
		 * in a single request, as well as subtracting out the number of tokens in our
		 * prompt (~50). ChatGPT counts both the tokens in the request and in
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
		 * Filter content that will get sent to ChatGPT.
		 *
		 * @since 2.0.0
		 * @hook classifai_chatgpt_content
		 *
		 * @param {string} $content Content that will be sent to ChatGPT.
		 * @param {int} $post_id ID of post we are summarizing.
		 *
		 * @return {string} Content.
		 */
		return apply_filters( 'classifai_chatgpt_content', $content, $post_id );
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
			$debug_info[ __( 'Latest response', 'classifai' ) ]       = $this->get_formatted_latest_response( get_transient( 'classifai_openai_chatgpt_title_generation_latest_response' ) );
		} elseif ( $this->feature_instance instanceof ExcerptGeneration ) {
			$debug_info[ __( 'Excerpt length', 'classifai' ) ]          = $settings['length'] ?? 55;
			$debug_info[ __( 'Generate excerpt prompt', 'classifai' ) ] = wp_json_encode( $settings['generate_excerpt_prompt'] ?? [] );
			$debug_info[ __( 'Latest response', 'classifai' ) ]         = $this->get_formatted_latest_response( get_transient( 'classifai_openai_chatgpt_excerpt_generation_latest_response' ) );
		} elseif ( $this->feature_instance instanceof ContentResizing ) {
			$debug_info[ __( 'No. of suggestions', 'classifai' ) ]   = $provider_settings['number_of_suggestions'] ?? 1;
			$debug_info[ __( 'Expand text prompt', 'classifai' ) ]   = wp_json_encode( $settings['expand_text_prompt'] ?? [] );
			$debug_info[ __( 'Condense text prompt', 'classifai' ) ] = wp_json_encode( $settings['condense_text_prompt'] ?? [] );
			$debug_info[ __( 'Latest response', 'classifai' ) ]      = $this->get_formatted_latest_response( get_transient( 'classifai_openai_chatgpt_content_resizing_latest_response' ) );
		}

		return apply_filters(
			'classifai_' . self::ID . '_debug_information',
			$debug_info,
			$settings,
			$this->feature_instance
		);
	}
}
