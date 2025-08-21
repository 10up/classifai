<?php
/**
 * Google AI Gemini API integration
 */

namespace Classifai\Providers\GoogleAI;

use Classifai\Features\ContentResizing;
use Classifai\Features\ExcerptGeneration;
use Classifai\Features\TitleGeneration;
use Classifai\Providers\Provider;
use Classifai\Normalizer;
use WP_Error;

use function Classifai\get_default_prompt;

class GeminiAPI extends Provider {

	use \Classifai\Providers\GoogleAI\GoogleAI;

	/**
	 * Provider ID
	 *
	 * @var string
	 */
	const ID = 'googleai_gemini_api';

	/**
	 * Gemini API URL
	 *
	 * @var string
	 */
	protected $googleai_url = 'https://generativelanguage.googleapis.com/v1beta';

	/**
	 * Gemini API default model
	 *
	 * @var string
	 */
	protected $default_model = 'models/gemini-2.5-flash-preview-05-20';

	/**
	 * GeminiAPI constructor.
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
		$settings = $this->feature_instance->get_settings();
		$model    = ! empty( $settings[ static::ID ]['model'] ) ? $settings[ static::ID ]['model'] : $this->default_model;

		/**
		 * Filter the model name.
		 *
		 * Useful if you want to use a different model, like
		 * gemini-2.5-pro-preview-05-06.
		 *
		 * @since 3.4.0
		 * @hook classifai_googleai_gemini_api_model
		 *
		 * @param string $model The default model to use.
		 *
		 * @return string The model to use.
		 */
		return apply_filters( 'classifai_googleai_gemini_api_model', $model );
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
							__( 'Don\'t have an Google AI (Gemini API) key? <a title="Get an API key" href="%1$s">Get an API key</a> now.', 'classifai' ),
							[
								'a' => [
									'href'  => [],
									'title' => [],
								],
							]
						),
						esc_url( 'https://makersuite.google.com/app/apikey' )
					),
			]
		);

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
			'model'         => $this->default_model,
			'models'        => [],
		];

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
		$model            = ! empty( $new_settings[ static::ID ]['model'] ) ? sanitize_text_field( $new_settings[ static::ID ]['model'] ) : $this->default_model;

		$new_settings[ static::ID ]['api_key']       = $api_key_settings[ static::ID ]['api_key'];
		$new_settings[ static::ID ]['authenticated'] = $api_key_settings[ static::ID ]['authenticated'];
		$new_settings[ static::ID ]['model']         = $model;
		$new_settings[ static::ID ]['models']        = $api_key_settings[ static::ID ]['models'];

		return $new_settings;
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
			return new WP_Error( 'post_id_required', esc_html__( 'A valid post ID is required to generate titles.', 'classifai' ) );
		}

		$route_to_call = strtolower( $route_to_call );
		$return        = '';

		// Handle all of our routes.
		switch ( $route_to_call ) {
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
	 * Generate an excerpt using Google AI (Gemini API).
	 *
	 * @param int   $post_id The Post ID we're processing
	 * @param array $args    Arguments passed in.
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
			return new WP_Error( 'not_enabled', esc_html__( 'Excerpt generation is disabled or Google AI authentication failed. Please check your settings.', 'classifai' ) );
		}

		$excerpt_length = absint( $settings['length'] ?? 55 );

		$request = new APIRequest( $settings[ static::ID ]['api_key'] ?? '', $feature->get_option_name() );

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
		 * Filter the prompt we will send to Gemini API.
		 *
		 * @since 3.0.0
		 * @hook classifai_googleai_gemini_api_excerpt_prompt
		 *
		 * @param string $prompt         Prompt we are sending to Gemini API. Gets added before post content.
		 * @param int    $post_id        ID of post we are summarizing.
		 * @param int    $excerpt_length Length of final excerpt.
		 *
		 * @return string Prompt.
		 */
		$prompt = apply_filters( 'classifai_googleai_gemini_api_excerpt_prompt', $prompt, $post_id, $excerpt_length );

		// Check if we are generating an excerpt for a product.
		$system_prompt = $this->system_prompt;
		if ( 'product' === $post_type && function_exists( 'wc_get_product' ) && \wc_get_product( $post_id ) ) {
			$args['content'] = $this->get_product_content( $post_id );
			$system_prompt   = $this->system_prompt_woo;
		}

		// Get the filtered content for request.
		$message_content = $this->get_content( $post_id, false, $args['content'] );

		/**
		 * Filter the request body before sending to Gemini API.
		 *
		 * @since 3.0.0
		 * @hook classifai_googleai_gemini_api_excerpt_request_body
		 *
		 * @param array $body    Request body that will be sent to Gemini API.
		 * @param int   $post_id ID of post we are summarizing.
		 *
		 * @return array Request body.
		 */
		$body = apply_filters(
			'classifai_googleai_gemini_api_excerpt_request_body',
			[
				'contents'         => [
					[
						'parts' => [
							'text' => $system_prompt . ' ' . $prompt . ' \n """' . $message_content . '"""',
						],
					],
				],
				'generationConfig' => [
					'temperature'     => 0.9,
					'topK'            => 1,
					'topP'            => 1,
					'maxOutputTokens' => 2048,
				],
			],
			$post_id
		);

		// Make our API request.
		$response = $request->post(
			$this->googleai_url . '/' . $this->get_model() . ':generateContent',
			[
				'body' => wp_json_encode( $body ),
			]
		);

		set_transient( 'classifai_googleai_gemini_api_excerpt_generation_latest_response', $response, DAY_IN_SECONDS * 30 );

		// Extract out the text response, if it exists.
		if ( ! is_wp_error( $response ) && ! empty( $response['candidates'] ) ) {
			foreach ( $response['candidates'] as $candidate ) {
				if ( isset( $candidate['content'], $candidate['content']['parts'] ) ) {
					$parts    = $candidate['content']['parts'];
					$response = sanitize_text_field( trim( $parts[0]['text'], ' "\'' ) );
				}
			}
		}

		return $response;
	}

	/**
	 * Generate titles using Google AI (Gemini API).
	 *
	 * @param int   $post_id The Post Id we're processing
	 * @param array $args Arguments passed in.
	 * @return string|WP_Error
	 */
	public function generate_titles( int $post_id = 0, array $args = [] ) {
		if ( ! $post_id || ! get_post( $post_id ) ) {
			return new WP_Error( 'post_id_required', esc_html__( 'Post ID is required to generate titles.', 'classifai' ) );
		}

		$feature   = new TitleGeneration();
		$settings  = $feature->get_settings();
		$args      = wp_parse_args(
			array_filter( $args ),
			[
				'num'     => 1, // Gemini API only returns 1 title.
				'content' => '',
			]
		);
		$post_type = get_post_type( $post_id );

		// These checks happen in the REST permission_callback,
		// but we run them again here in case this method is called directly.
		if ( empty( $settings ) || ( isset( $settings[ static::ID ]['authenticated'] ) && false === $settings[ static::ID ]['authenticated'] ) || ! $feature->is_feature_enabled() ) {
			return new WP_Error( 'not_enabled', esc_html__( 'Title generation is disabled or Google AI authentication failed. Please check your settings.', 'classifai' ) );
		}

		$request = new APIRequest( $settings[ static::ID ]['api_key'] ?? '', $feature->get_option_name() );

		// Overwrite the prompt if we are generating titles for a product.
		if ( 'product' === $post_type ) {
			$prompt = $feature->woo_prompt;
		} else {
			$prompt = esc_textarea( get_default_prompt( $settings['generate_title_prompt'] ) ?? $feature->prompt );
		}

		/**
		 * Filter the prompt we will send to Gemini API.
		 *
		 * @since 3.0.0
		 * @hook classifai_googleai_gemini_api_title_prompt
		 *
		 * @param string $prompt  Prompt we are sending to Gemini API. Gets added before post content.
		 * @param int    $post_id ID of post we are summarizing.
		 * @param array  $args    Arguments passed to endpoint.
		 *
		 * @return string Prompt.
		 */
		$prompt = apply_filters( 'classifai_googleai_gemini_api_title_prompt', $prompt, $post_id, $args );

		// Check if we are generating titles for a product.
		$system_prompt = $this->system_prompt;
		if ( 'product' === $post_type && function_exists( 'wc_get_product' ) && \wc_get_product( $post_id ) ) {
			$args['content'] = $this->get_product_content( $post_id );
			$system_prompt   = $this->system_prompt;
		}

		// Get the filtered content for request.
		$message_content = $this->get_content( $post_id, false, $args['content'] );

		/**
		 * Filter the request body before sending to Gemini API.
		 *
		 * @since 3.0.0
		 * @hook classifai_googleai_gemini_api_title_request_body
		 *
		 * @param array $body    Request body that will be sent to Gemini API.
		 * @param int   $post_id ID of post we are summarizing.
		 *
		 * @return array Request body.
		 */
		$body = apply_filters(
			'classifai_googleai_gemini_api_title_request_body',
			[
				'contents'         => [
					[
						'parts' => [
							'text' => $system_prompt . ' ' . $prompt . '\n"""' . $message_content . '"""',
						],
					],
				],
				'generationConfig' => [
					'temperature'     => 0.9,
					'topK'            => 1,
					'topP'            => 1,
					'maxOutputTokens' => 2048,
				],
			],
			$post_id
		);

		// Make our API request.
		$response = $request->post(
			$this->googleai_url . '/' . $this->get_model() . ':generateContent',
			[
				'body' => wp_json_encode( $body ),
			]
		);

		set_transient( 'classifai_googleai_gemini_api_title_generation_latest_response', $response, DAY_IN_SECONDS * 30 );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( empty( $response['candidates'] ) ) {
			return new WP_Error( 'no_choices', esc_html__( 'No choices were returned from Google AI.', 'classifai' ) );
		}

		// Extract out the text response.
		$return = [];
		foreach ( $response['candidates'] as $candidate ) {
			if ( isset( $candidate['content'], $candidate['content']['parts'] ) ) {
				$parts    = $candidate['content']['parts'];
				$return[] = sanitize_text_field( trim( $parts[0]['text'], ' "\'' ) );
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
				'num' => 1, // Gemini API only returns 1 variation as of now.
			]
		);

		$request = new APIRequest( $settings[ static::ID ]['api_key'] ?? '', $feature->get_option_name() );

		if ( 'shrink' === $args['resize_type'] ) {
			$prompt = esc_textarea( get_default_prompt( $settings['condense_text_prompt'] ) ?? $feature->condense_prompt );
		} else {
			$prompt = esc_textarea( get_default_prompt( $settings['expand_text_prompt'] ) ?? $feature->expand_prompt );
		}

		/**
		 * Filter the resize prompt we will send to Gemini API.
		 *
		 * @since 3.0.0
		 * @hook classifai_googleai_gemini_api_' . $args['resize_type'] . '_content_prompt
		 *
		 * @param string $prompt  Resize prompt we are sending to Gemini API. Gets added as a system prompt.
		 * @param int    $post_id ID of post.
		 * @param array  $args    Arguments passed to endpoint.
		 *
		 * @return string Prompt.
		 */
		$prompt = apply_filters( 'classifai_googleai_gemini_api_' . $args['resize_type'] . '_content_prompt', $prompt, $post_id, $args );

		/**
		 * Filter the resize request body before sending to Gemini API.
		 *
		 * @since 2.3.0
		 * @hook classifai_googleai_gemini_api_resize_content_request_body
		 *
		 * @param array $body Request body that will be sent to Gemini API.
		 * @param int   $post_id ID of post.
		 *
		 * @return array Request body.
		 */
		$body = apply_filters(
			'classifai_googleai_gemini_api_resize_content_request_body',
			[
				'contents'         => [
					[
						'parts' => [
							'text' => 'You will be provided with content delimited by triple quotes. ' . $prompt . '\n"""' . esc_html( $args['content'] ) . '"""',
						],
					],
				],
				'generationConfig' => [
					'temperature'     => 0.9,
					'topK'            => 1,
					'topP'            => 1,
					'maxOutputTokens' => 2048,
				],
			],
			$post_id
		);

		// Make our API request.
		$response = $request->post(
			$this->googleai_url . '/' . $this->get_model() . ':generateContent',
			[
				'body' => wp_json_encode( $body ),
			]
		);

		set_transient( 'classifai_googleai_gemini_api_content_resizing_latest_response', $response, DAY_IN_SECONDS * 30 );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( empty( $response['candidates'] ) ) {
			return new WP_Error( 'no_choices', esc_html__( 'No choices were returned from Google AI.', 'classifai' ) );
		}

		// Extract out the text response.
		$return = [];
		foreach ( $response['candidates'] as $candidate ) {
			if ( isset( $candidate['content'], $candidate['content']['parts'] ) ) {
				$parts    = $candidate['content']['parts'];
				$return[] = sanitize_text_field( trim( $parts[0]['text'], ' "\'' ) );
			}
		}

		return $return;
	}

	/**
	 * Get our content, trimming if needed.
	 *
	 * ### Important Note:
	 * The content length is not limited in this implementation.
	 * The Gemini 2.5 Flash model can process up to 1,048,576 input tokens:
	 * (https://ai.google.dev/gemini-api/docs/models#gemini-2.5-flash-preview).
	 * This limit should be more than sufficient for our use case.
	 *
	 * @param int    $post_id      Post ID to get content from.
	 * @param bool   $use_title    Whether to use the title or not.
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

		// Then trim our content, if needed, to stay under the max.
		if ( $use_title ) {
			$content = $normalizer->normalize( $post_id, $post_content );
		} else {
			$content = $normalizer->normalize_content( $post_content, '', $post_id );
		}

		/**
		 * Filter content that will get sent to GoogleAI.
		 *
		 * @since 3.0.0
		 * @hook classifai_googleai_content
		 *
		 * @param string $content Content that will be sent to GoogleAI.
		 * @param int    $post_id ID of post we are summarizing.
		 *
		 * @return string Content.
		 */
		return apply_filters( 'classifai_googleai_gemini_api_content', $content, $post_id );
	}

	/**
	 * Returns the debug information for the provider settings.
	 *
	 * @return array
	 */
	public function get_debug_information(): array {
		$settings   = $this->feature_instance->get_settings();
		$debug_info = [];

		$debug_info[ __( 'Model', 'classifai' ) ] = $this->get_model();
		if ( $this->feature_instance instanceof TitleGeneration ) {
			$debug_info[ __( 'No. of titles', 'classifai' ) ]         = 1;
			$debug_info[ __( 'Generate title prompt', 'classifai' ) ] = wp_json_encode( $settings['generate_title_prompt'] ?? [] );
			$debug_info[ __( 'Latest response', 'classifai' ) ]       = $this->get_formatted_latest_response( get_transient( 'classifai_googleai_gemini_api_title_generation_latest_response' ) );
		} elseif ( $this->feature_instance instanceof ExcerptGeneration ) {
			$debug_info[ __( 'Excerpt length', 'classifai' ) ]          = $settings['length'] ?? 55;
			$debug_info[ __( 'Generate excerpt prompt', 'classifai' ) ] = wp_json_encode( $settings['generate_excerpt_prompt'] ?? [] );
			$debug_info[ __( 'Latest response', 'classifai' ) ]         = $this->get_formatted_latest_response( get_transient( 'classifai_googleai_gemini_api_excerpt_generation_latest_response' ) );
		} elseif ( $this->feature_instance instanceof ContentResizing ) {
			$debug_info[ __( 'No. of suggestions', 'classifai' ) ]   = 1;
			$debug_info[ __( 'Expand text prompt', 'classifai' ) ]   = wp_json_encode( $settings['expand_text_prompt'] ?? [] );
			$debug_info[ __( 'Condense text prompt', 'classifai' ) ] = wp_json_encode( $settings['condense_text_prompt'] ?? [] );
			$debug_info[ __( 'Latest response', 'classifai' ) ]      = $this->get_formatted_latest_response( get_transient( 'classifai_googleai_gemini_api_content_resizing_latest_response' ) );
		}

		return apply_filters(
			'classifai_' . self::ID . '_debug_information',
			$debug_info,
			$settings,
			$this->feature_instance
		);
	}
}
