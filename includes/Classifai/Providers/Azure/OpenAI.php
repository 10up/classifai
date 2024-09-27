<?php
/**
 * Azure OpenAI integration
 */

namespace Classifai\Providers\Azure;

use Classifai\Features\ContentResizing;
use Classifai\Features\ExcerptGeneration;
use Classifai\Features\TitleGeneration;
use Classifai\Providers\Provider;
use Classifai\Normalizer;
use WP_Error;

use function Classifai\get_default_prompt;
use function Classifai\sanitize_number_of_responses_field;

class OpenAI extends Provider {

	/**
	 * Provider ID
	 *
	 * @var string
	 */
	const ID = 'azure_openai';

	/**
	 * Chat completion URL fragment.
	 *
	 * @var string
	 */
	protected $chat_completion_url = 'openai/deployments/{deployment-id}/chat/completions';

	/**
	 * Completion URL fragment.
	 *
	 * @var string
	 */
	protected $completion_url = 'openai/deployments/{deployment-id}/completions';

	/**
	 * Chat completion API version.
	 *
	 * @var string
	 */
	protected $chat_completion_api_version = '2023-05-15';

	/**
	 * Completion API version.
	 *
	 * @var string
	 */
	protected $completion_api_version = '2023-05-15';

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
			static::ID . '_endpoint_url',
			esc_html__( 'Endpoint URL', 'classifai' ),
			[ $this->feature_instance, 'render_input' ],
			$this->feature_instance->get_option_name(),
			$this->feature_instance->get_option_name() . '_section',
			[
				'option_index'  => static::ID,
				'label_for'     => 'endpoint_url',
				'input_type'    => 'text',
				'default_value' => $settings['endpoint_url'],
				'description'   => $this->feature_instance->is_configured_with_provider( static::ID ) ?
					'' :
					__( 'Supported protocol and hostname endpoints, e.g., <code>https://EXAMPLE.openai.azure.com</code>.', 'classifai' ),
				'class'         => 'large-text classifai-provider-field hidden provider-scope-' . static::ID,
			]
		);

		add_settings_field(
			static::ID . '_api_key',
			esc_html__( 'API key', 'classifai' ),
			[ $this->feature_instance, 'render_input' ],
			$this->feature_instance->get_option_name(),
			$this->feature_instance->get_option_name() . '_section',
			[
				'option_index'  => static::ID,
				'label_for'     => 'api_key',
				'input_type'    => 'password',
				'default_value' => $settings['api_key'],
				'class'         => 'classifai-provider-field hidden provider-scope-' . static::ID,
			]
		);

		add_settings_field(
			static::ID . '_deployment',
			esc_html__( 'Deployment name', 'classifai' ),
			[ $this->feature_instance, 'render_input' ],
			$this->feature_instance->get_option_name(),
			$this->feature_instance->get_option_name() . '_section',
			[
				'option_index'  => static::ID,
				'label_for'     => 'deployment',
				'input_type'    => 'text',
				'default_value' => $settings['deployment'],
				'description'   => $this->feature_instance->is_configured_with_provider( static::ID ) ?
					'' :
					__( 'Custom name you chose for your deployment when you deployed a model.', 'classifai' ),
				'class'         => 'large-text classifai-provider-field hidden provider-scope-' . static::ID,
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
						'class'         => 'classifai-provider-field hidden provider-scope-' . static::ID,
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
			'endpoint_url'  => '',
			'api_key'       => '',
			'deployment'    => '',
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
		$settings = $this->feature_instance->get_settings();

		if (
			! empty( $new_settings[ static::ID ]['endpoint_url'] ) &&
			! empty( $new_settings[ static::ID ]['api_key'] ) &&
			! empty( $new_settings[ static::ID ]['deployment'] )
		) {
			$new_settings[ static::ID ]['authenticated'] = $settings[ static::ID ]['authenticated'];
			$new_settings[ static::ID ]['endpoint_url']  = esc_url_raw( $new_settings[ static::ID ]['endpoint_url'] ?? $settings[ static::ID ]['endpoint_url'] );
			$new_settings[ static::ID ]['api_key']       = sanitize_text_field( $new_settings[ static::ID ]['api_key'] ?? $settings[ static::ID ]['api_key'] );
			$new_settings[ static::ID ]['deployment']    = sanitize_text_field( $new_settings[ static::ID ]['deployment'] ?? $settings[ static::ID ]['deployment'] );

			$is_authenticated   = $new_settings[ static::ID ]['authenticated'];
			$is_endpoint_same   = $new_settings[ static::ID ]['endpoint_url'] === $settings[ static::ID ]['endpoint_url'];
			$is_api_key_same    = $new_settings[ static::ID ]['api_key'] === $settings[ static::ID ]['api_key'];
			$is_deployment_same = $new_settings[ static::ID ]['deployment'] === $settings[ static::ID ]['deployment'];

			if ( ! ( $is_authenticated && $is_endpoint_same && $is_api_key_same && $is_deployment_same ) ) {
				$auth_check = $this->authenticate_credentials(
					$new_settings[ static::ID ]['endpoint_url'],
					$new_settings[ static::ID ]['api_key'],
					$new_settings[ static::ID ]['deployment']
				);

				if ( is_wp_error( $auth_check ) ) {
					$new_settings[ static::ID ]['authenticated'] = false;
					$error_message                               = $auth_check->get_error_message();

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
			}
		} else {
			$new_settings[ static::ID ]['endpoint_url'] = $settings[ static::ID ]['endpoint_url'];
			$new_settings[ static::ID ]['api_key']      = $settings[ static::ID ]['api_key'];
			$new_settings[ static::ID ]['deployment']   = $settings[ static::ID ]['deployment'];
		}

		switch ( $this->feature_instance::ID ) {
			case ContentResizing::ID:
			case TitleGeneration::ID:
				$new_settings[ static::ID ]['number_of_suggestions'] = sanitize_number_of_responses_field( 'number_of_suggestions', $new_settings[ static::ID ], $settings[ static::ID ] );
				break;
		}

		return $new_settings;
	}

	/**
	 * Build and return the API endpoint based on settings.
	 *
	 * @param \Classifai\Features\Feature $feature Feature instance
	 * @return string
	 */
	protected function prep_api_url( ?\Classifai\Features\Feature $feature = null ): string {
		$settings   = $feature->get_settings( static::ID );
		$endpoint   = $settings['endpoint_url'] ?? '';
		$deployment = $settings['deployment'] ?? '';

		if ( ! $endpoint ) {
			return '';
		}

		if (
			( $feature instanceof ContentResizing ||
			$feature instanceof ExcerptGeneration ||
			$feature instanceof TitleGeneration ) &&
			$deployment
		) {
			$endpoint = trailingslashit( $endpoint ) . str_replace( '{deployment-id}', $deployment, $this->chat_completion_url );
			$endpoint = add_query_arg( 'api-version', $this->chat_completion_api_version, $endpoint );
		}

		return $endpoint;
	}

	/**
	 * Authenticates our credentials.
	 *
	 * @param string $url Endpoint URL.
	 * @param string $api_key Api Key.
	 * @param string $deployment Deployment name.
	 * @return bool|WP_Error
	 */
	protected function authenticate_credentials( string $url, string $api_key, string $deployment ) {
		$rtn = false;

		// This does basically the same thing that prep_api_url does but when running authentication,
		// we don't have settings saved yet, which prep_api_url needs.
		$endpoint = trailingslashit( $url ) . str_replace( '{deployment-id}', $deployment, $this->chat_completion_url );
		$endpoint = add_query_arg( 'api-version', $this->completion_api_version, $endpoint );

		$request = wp_remote_post(
			$endpoint,
			[
				'headers' => [
					'api-key'      => $api_key,
					'Content-Type' => 'application/json',
				],
				'body'    => wp_json_encode(
					[
						'prompt'     => 'Once upon a time',
						'max_tokens' => 5,
					]
				),
			]
		);

		if ( ! is_wp_error( $request ) ) {
			$response = json_decode( wp_remote_retrieve_body( $request ) );
			if ( ! empty( $response->error ) ) {
				$rtn = new WP_Error( 'auth', $response->error->message );
			} else {
				$rtn = true;
			}
		}

		return $rtn;
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
	 * Generate an excerpt.
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
			return new WP_Error( 'not_enabled', esc_html__( 'Excerpt generation is disabled or authentication failed. Please check your settings.', 'classifai' ) );
		}

		$excerpt_length = absint( $settings['length'] ?? 55 );
		$excerpt_prompt = esc_textarea( get_default_prompt( $settings['generate_excerpt_prompt'] ) ?? $feature->prompt );

		// Replace our variables in the prompt.
		$prompt_search  = array( '{{WORDS}}', '{{TITLE}}' );
		$prompt_replace = array( $excerpt_length, $args['title'] );
		$prompt         = str_replace( $prompt_search, $prompt_replace, $excerpt_prompt );

		/**
		 * Filter the prompt we will send to Azure OpenAI.
		 *
		 * @since 3.0.0
		 * @hook classifai_azure_openai_excerpt_prompt
		 *
		 * @param {string} $prompt Prompt we are sending. Gets added before post content.
		 * @param {int} $post_id ID of post we are summarizing.
		 * @param {int} $excerpt_length Length of final excerpt.
		 *
		 * @return {string} Prompt.
		 */
		$prompt = apply_filters( 'classifai_azure_openai_excerpt_prompt', $prompt, $post_id, $excerpt_length );

		/**
		 * Filter the request body before sending to Azure OpenAI.
		 *
		 * @since 3.0.0
		 * @hook classifai_azure_openai_excerpt_request_body
		 *
		 * @param {array} $body Request body that will be sent.
		 * @param {int} $post_id ID of post we are summarizing.
		 *
		 * @return {array} Request body.
		 */
		$body = apply_filters(
			'classifai_azure_openai_excerpt_request_body',
			[
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
		$response = wp_remote_post(
			$this->prep_api_url( $feature ),
			[
				'headers' => [
					'api-key'      => $settings[ static::ID ]['api_key'],
					'Content-Type' => 'application/json',
				],
				'body'    => wp_json_encode( $body ),
			]
		);
		$response = $this->get_result( $response );

		set_transient( 'classifai_azure_openai_excerpt_generation_latest_response', $response, DAY_IN_SECONDS * 30 );

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
	 * Generate titles using Azure OpenAI.
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
			return new WP_Error( 'not_enabled', esc_html__( 'Title generation is disabled or authentication failed. Please check your settings.', 'classifai' ) );
		}

		$prompt = esc_textarea( get_default_prompt( $settings['generate_title_prompt'] ) ?? $feature->prompt );

		/**
		 * Filter the prompt we will send to Azure OpenAI.
		 *
		 * @since 2.2.0
		 * @hook classifai_azure_openai_title_prompt
		 *
		 * @param {string} $prompt Prompt we are sending. Gets added before post content.
		 * @param {int} $post_id ID of post we are summarizing.
		 * @param {array} $args Arguments passed to endpoint.
		 *
		 * @return {string} Prompt.
		 */
		$prompt = apply_filters( 'classifai_azure_openai_title_prompt', $prompt, $post_id, $args );

		/**
		 * Filter the request body before sending to Azure OpenAI.
		 *
		 * @since 2.2.0
		 * @hook classifai_azure_openai_title_request_body
		 *
		 * @param {array} $body Request body that will be sent.
		 * @param {int} $post_id ID of post we are summarizing.
		 *
		 * @return {array} Request body.
		 */
		$body = apply_filters(
			'classifai_azure_openai_title_request_body',
			[
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
		$response = wp_remote_post(
			$this->prep_api_url( $feature ),
			[
				'headers' => [
					'api-key'      => $settings[ static::ID ]['api_key'],
					'Content-Type' => 'application/json',
				],
				'body'    => wp_json_encode( $body ),
			]
		);
		$response = $this->get_result( $response );

		set_transient( 'classifai_azure_openai_title_generation_latest_response', $response, DAY_IN_SECONDS * 30 );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( empty( $response['choices'] ) ) {
			return new WP_Error( 'no_choices', esc_html__( 'No choices were returned from Azure OpenAI.', 'classifai' ) );
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

		if ( 'shrink' === $args['resize_type'] ) {
			$prompt = esc_textarea( get_default_prompt( $settings['condense_text_prompt'] ) ?? $feature->condense_prompt );
		} else {
			$prompt = esc_textarea( get_default_prompt( $settings['expand_text_prompt'] ) ?? $feature->expand_prompt );
		}

		/**
		 * Filter the resize prompt we will send to Azure OpenAI.
		 *
		 * @since 2.3.0
		 * @hook classifai_azure_openai_' . $args['resize_type'] . '_content_prompt
		 *
		 * @param {string} $prompt Resize prompt we are sending. Gets added as a system prompt.
		 * @param {int} $post_id ID of post.
		 * @param {array} $args Arguments passed to endpoint.
		 *
		 * @return {string} Prompt.
		 */
		$prompt = apply_filters( 'classifai_azure_openai_' . $args['resize_type'] . '_content_prompt', $prompt, $post_id, $args );

		/**
		 * Filter the resize request body before sending to Azure OpenAI.
		 *
		 * @since 2.3.0
		 * @hook classifai_azure_openai_resize_content_request_body
		 *
		 * @param {array} $body Request body that will be sent.
		 * @param {int}   $post_id ID of post.
		 *
		 * @return {array} Request body.
		 */
		$body = apply_filters(
			'classifai_azure_openai_resize_content_request_body',
			[
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
		$response = wp_remote_post(
			$this->prep_api_url( $feature ),
			[
				'headers' => [
					'api-key'      => $settings[ static::ID ]['api_key'],
					'Content-Type' => 'application/json',
				],
				'body'    => wp_json_encode( $body ),
			]
		);
		$response = $this->get_result( $response );

		set_transient( 'classifai_azure_openai_content_resizing_latest_response', $response, DAY_IN_SECONDS * 30 );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( empty( $response['choices'] ) ) {
			return new WP_Error( 'no_choices', esc_html__( 'No choices were returned from Azure OpenAI.', 'classifai' ) );
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
	 * Get our content.
	 *
	 * We don't trim content here as we don't know for sure which model
	 * someone is using.
	 *
	 * @param int    $post_id Post ID to get content from.
	 * @param int    $return_length Word length of returned content.
	 * @param bool   $use_title Whether to use the title or not.
	 * @param string $post_content The post content.
	 * @return string
	 */
	public function get_content( int $post_id = 0, int $return_length = 0, bool $use_title = true, string $post_content = '' ): string {
		$normalizer = new Normalizer();

		if ( empty( $post_content ) ) {
			$post         = get_post( $post_id );
			$post_content = apply_filters( 'the_content', $post->post_content );
		}

		$post_content = preg_replace( '#\[.+\](.+)\[/.+\]#', '$1', $post_content );

		// Add the title to the content, if needed, and normalize things.
		if ( $use_title ) {
			$content = $normalizer->normalize( $post_id, $post_content );
		} else {
			$content = $normalizer->normalize_content( $post_content, '', $post_id );
		}

		/**
		 * Filter content that will get sent to Azure OpenAI.
		 *
		 * @since 3.0.0
		 * @hook classifai_azure_openai_content
		 *
		 * @param {string} $content Content that will be sent.
		 * @param {int} $post_id ID of post we are summarizing.
		 *
		 * @return {string} Content.
		 */
		return apply_filters( 'classifai_azure_openai_content', $content, $post_id );
	}

	/**
	 * Get results from the response.
	 *
	 * @param object $response The API response.
	 * @return array|WP_Error
	 */
	public function get_result( $response ) {
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$body = wp_remote_retrieve_body( $response );
		$code = wp_remote_retrieve_response_code( $response );
		$json = json_decode( $body, true );

		if ( json_last_error() === JSON_ERROR_NONE ) {
			if ( empty( $json['error'] ) ) {
				return $json;
			} else {
				$message = $json['error']['message'] ?? esc_html__( 'An error occured', 'classifai' );
				return new WP_Error( $code, $message );
			}
		} elseif ( ! empty( wp_remote_retrieve_response_message( $response ) ) ) {
			return new WP_Error( $code, wp_remote_retrieve_response_message( $response ) );
		} else {
			return new WP_Error( 'Invalid JSON: ' . json_last_error_msg(), $body );
		}
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
			$debug_info[ __( 'Latest response', 'classifai' ) ]       = $this->get_formatted_latest_response( get_transient( 'classifai_azure_openai_title_generation_latest_response' ) );
		} elseif ( $this->feature_instance instanceof ExcerptGeneration ) {
			$debug_info[ __( 'Excerpt length', 'classifai' ) ]          = $settings['length'] ?? 55;
			$debug_info[ __( 'Generate excerpt prompt', 'classifai' ) ] = wp_json_encode( $settings['generate_excerpt_prompt'] ?? [] );
			$debug_info[ __( 'Latest response', 'classifai' ) ]         = $this->get_formatted_latest_response( get_transient( 'classifai_azure_openai_excerpt_generation_latest_response' ) );
		} elseif ( $this->feature_instance instanceof ContentResizing ) {
			$debug_info[ __( 'No. of suggestions', 'classifai' ) ]   = $provider_settings['number_of_suggestions'] ?? 1;
			$debug_info[ __( 'Expand text prompt', 'classifai' ) ]   = wp_json_encode( $settings['expand_text_prompt'] ?? [] );
			$debug_info[ __( 'Condense text prompt', 'classifai' ) ] = wp_json_encode( $settings['condense_text_prompt'] ?? [] );
			$debug_info[ __( 'Latest response', 'classifai' ) ]      = $this->get_formatted_latest_response( get_transient( 'classifai_azure_openai_content_resizing_latest_response' ) );
		}

		return apply_filters(
			'classifai_' . self::ID . '_debug_information',
			$debug_info,
			$settings,
			$this->feature_instance
		);
	}
}
