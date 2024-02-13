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
	 * GeminiAPI model
	 *
	 * @var string
	 */
	protected $googleai_model = 'models/gemini-pro';

	/**
	 * GeminiAPI constructor.
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
				'description'   => sprintf(
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

		$new_settings[ static::ID ]['api_key']       = $api_key_settings[ static::ID ]['api_key'];
		$new_settings[ static::ID ]['authenticated'] = $api_key_settings[ static::ID ]['authenticated'];

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

			// Add an error message.
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
			return new WP_Error( 'auth', esc_html__( 'Please enter your Google AI (Gemini API) key.', 'classifai' ) );
		}

		// Make request to ensure credentials work.
		$request  = new APIRequest( $api_key );
		$response = $request->get( $this->googleai_url . '/models' );

		return ! is_wp_error( $response ) ? true : $response;
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
			return new WP_Error( 'not_enabled', esc_html__( 'Excerpt generation is disabled or Google AI authentication failed. Please check your settings.', 'classifai' ) );
		}

		$excerpt_length = absint( $settings['length'] ?? 55 );

		$request = new APIRequest( $settings[ static::ID ]['api_key'] ?? '', $feature->get_option_name() );

		$excerpt_prompt = esc_textarea( get_default_prompt( $settings['generate_excerpt_prompt'] ) ?? $feature->prompt );

		// Replace our variables in the prompt.
		$prompt_search  = array( '{{WORDS}}', '{{TITLE}}' );
		$prompt_replace = array( $excerpt_length, $args['title'] );
		$prompt         = str_replace( $prompt_search, $prompt_replace, $excerpt_prompt );

		/**
		 * Filter the prompt we will send to Gemini API.
		 *
		 * @since 3.0.0
		 * @hook classifai_googleai_gemini_api_excerpt_prompt
		 *
		 * @param {string} $prompt Prompt we are sending to Gemini API. Gets added before post content.
		 * @param {int} $post_id ID of post we are summarizing.
		 * @param {int} $excerpt_length Length of final excerpt.
		 *
		 * @return {string} Prompt.
		 */
		$prompt = apply_filters( 'classifai_googleai_gemini_api_excerpt_prompt', $prompt, $post_id, $excerpt_length );

		/**
		 * Filter the request body before sending to Gemini API.
		 *
		 * @since 3.0.0
		 * @hook classifai_googleai_gemini_api_excerpt_request_body
		 *
		 * @param {array} $body Request body that will be sent to Gemini API.
		 * @param {int} $post_id ID of post we are summarizing.
		 *
		 * @return {array} Request body.
		 */
		$body = apply_filters(
			'classifai_googleai_gemini_api_excerpt_request_body',
			[
				'contents'         => [
					[
						'parts' => [
							'text' => 'You will be provided with content delimited by triple quotes. ' . $prompt . ' \n """' . $this->get_content( $post_id, false, $args['content'] ) . '"""',
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
			$this->googleai_url . '/' . $this->googleai_model . ':generateContent',
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

		$feature  = new TitleGeneration();
		$settings = $feature->get_settings();
		$args     = wp_parse_args(
			array_filter( $args ),
			[
				'num'     => 1, // Gemini API only returns 1 title.
				'content' => '',
			]
		);

		// These checks happen in the REST permission_callback,
		// but we run them again here in case this method is called directly.
		if ( empty( $settings ) || ( isset( $settings[ static::ID ]['authenticated'] ) && false === $settings[ static::ID ]['authenticated'] ) || ! $feature->is_feature_enabled() ) {
			return new WP_Error( 'not_enabled', esc_html__( 'Title generation is disabled or Google AI authentication failed. Please check your settings.', 'classifai' ) );
		}

		$request = new APIRequest( $settings[ static::ID ]['api_key'] ?? '', $feature->get_option_name() );

		$prompt = esc_textarea( get_default_prompt( $settings['generate_title_prompt'] ) ?? $feature->prompt );

		/**
		 * Filter the prompt we will send to Gemini API.
		 *
		 * @since 3.0.0
		 * @hook classifai_googleai_gemini_api_title_prompt
		 *
		 * @param {string} $prompt Prompt we are sending to Gemini API. Gets added before post content.
		 * @param {int}    $post_id ID of post we are summarizing.
		 * @param {array}  $args Arguments passed to endpoint.
		 *
		 * @return {string} Prompt.
		 */
		$prompt = apply_filters( 'classifai_googleai_gemini_api_title_prompt', $prompt, $post_id, $args );

		/**
		 * Filter the request body before sending to Gemini API.
		 *
		 * @since 3.0.0
		 * @hook classifai_googleai_gemini_api_title_request_body
		 *
		 * @param {array} $body Request body that will be sent to Gemini API.
		 * @param {int}   $post_id ID of post we are summarizing.
		 *
		 * @return {array} Request body.
		 */
		$body = apply_filters(
			'classifai_googleai_gemini_api_title_request_body',
			[
				'contents'         => [
					[
						'parts' => [
							'text' => 'You will be provided with content delimited by triple quotes. ' . $prompt . '\n"""' . $this->get_content( $post_id, false, $args['content'] ) . '"""',
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
			$this->googleai_url . '/' . $this->googleai_model . ':generateContent',
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
		 * @param {string} $prompt Resize prompt we are sending to Gemini API. Gets added as a system prompt.
		 * @param {int} $post_id ID of post.
		 * @param {array} $args Arguments passed to endpoint.
		 *
		 * @return {string} Prompt.
		 */
		$prompt = apply_filters( 'classifai_googleai_gemini_api_' . $args['resize_type'] . '_content_prompt', $prompt, $post_id, $args );

		/**
		 * Filter the resize request body before sending to Gemini API.
		 *
		 * @since 2.3.0
		 * @hook classifai_googleai_gemini_api_resize_content_request_body
		 *
		 * @param {array} $body Request body that will be sent to Gemini API.
		 * @param {int}   $post_id ID of post.
		 *
		 * @return {array} Request body.
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
			$this->googleai_url . '/' . $this->googleai_model . ':generateContent',
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
	 * The Gemini Pro model can process up to 30,720 input tokens, which is approximately equivalent to 18,000 - 24,000 words. (https://ai.google.dev/models/gemini#model_variations)
	 * Given that the average blog post length ranges from 1,500 - 2,500 words, this limit is more than sufficient for our use case.
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
		 * @param {string} $content Content that will be sent to GoogleAI.
		 * @param {int} $post_id ID of post we are summarizing.
		 *
		 * @return {string} Content.
		 */
		return apply_filters( 'classifai_googleai_gemini_api_content', $content, $post_id );
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
