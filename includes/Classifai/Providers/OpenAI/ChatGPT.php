<?php
/**
 * OpenAI ChatGPT integration
 */

namespace Classifai\Providers\OpenAI;

use Classifai\Features\ContentResizing;
use Classifai\Features\ExcerptGeneration;
use Classifai\Features\TitleGeneration;
use Classifai\Providers\Provider;
use Classifai\Watson\Normalizer;
use WP_REST_Server;
use WP_REST_Request;
use WP_Error;
use function Classifai\get_asset_info;
use function Classifai\sanitize_prompts;
use function Classifai\get_default_prompt;

class ChatGPT extends Provider {

	use \Classifai\Providers\OpenAI\OpenAI;

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
	protected $chatgpt_model = 'gpt-3.5-turbo';

	/**
	 * Maximum number of tokens our model supports
	 *
	 * @var int
	 */
	protected $max_tokens = 16385;

	/**
	 * Prompt for shrinking content
	 *
	 * @var string
	 */
	protected $condense_text_prompt = 'Decrease the content length no more than 2 to 4 sentences.';

	/**
	 * Prompt for growing content
	 *
	 * @var string
	 */
	protected $expand_text_prompt = 'Increase the content length no more than 2 to 4 sentences.';

	/**
	 * OpenAI ChatGPT constructor.
	 *
	 * @param \Classifai\Features\Feature $feature_instance The feature instance.
	 */
	public function __construct( $feature_instance = null ) {
		parent::__construct(
			'OpenAI ChatGPT',
			'ChatGPT',
			'openai_chatgpt'
		);

		$this->feature_instance = $feature_instance;

		add_action( 'rest_api_init', [ $this, 'register_endpoints' ] );
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
				$this->add_content_resizing_fields();
				break;
		}

		do_action( 'classifai_' . static::ID . '_render_provider_fields', $this );
	}

	/**
	 * Renders fields for the Content resizing feature.
	 */
	private function add_content_resizing_fields() {
		$settings = $this->feature_instance->get_settings( static::ID );

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
				'description'   => esc_html__( 'Number of suggestions that will be generated in one request.', 'classifai' ),
				'class'         => 'classifai-provider-field hidden provider-scope-' . static::ID, // Important to add this.
			]
		);

		add_settings_field(
			static::ID . '_condense_text_prompt',
			$args['label'] ?? esc_html__( 'Condense text prompt', 'classifai' ),
			[ $this->feature_instance, 'render_prompt_repeater_field' ],
			$this->feature_instance->get_option_name(),
			$this->feature_instance->get_option_name() . '_section',
			[
				'option_index'  => static::ID,
				'label_for'     => 'condense_text_prompt',
				'placeholder'   => esc_html__( 'Decrease the content length no more than 2 to 4 sentences.', 'classifai' ),
				'default_value' => $settings['condense_text_prompt'],
				'description'   => esc_html__( 'Enter your custom prompt.', 'classifai' ),
				'class'         => 'large-text classifai-provider-field hidden provider-scope-' . static::ID, // Important to add this.
			]
		);

		add_settings_field(
			static::ID . '_expand_text_prompt',
			$args['label'] ?? esc_html__( 'Expand text prompt', 'classifai' ),
			[ $this->feature_instance, 'render_prompt_repeater_field' ],
			$this->feature_instance->get_option_name(),
			$this->feature_instance->get_option_name() . '_section',
			[
				'option_index'  => static::ID,
				'label_for'     => 'expand_text_prompt',
				'placeholder'   => esc_html__( 'Increase the content length no more than 2 to 4 sentences.', 'classifai' ),
				'default_value' => $settings['expand_text_prompt'],
				'description'   => esc_html__( 'Enter your custom prompt.', 'classifai' ),
				'class'         => 'large-text classifai-provider-field hidden provider-scope-' . static::ID, // Important to add this.
			]
		);
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

		switch ( $this->feature_instance::ID ) {
			case ContentResizing::ID:
				return array_merge(
					$common_settings,
					[
						'number_of_suggestions' => 1,
						'condense_text_prompt'  => array(
							array(
								'title'    => esc_html__( 'ClassifAI default', 'classifai' ),
								'prompt'   => esc_html__( 'Descrease the content length no more than 2 to 4 sentences.', 'classifai' ),
								'original' => 1,
							),
						),
						'expand_text_prompt'    => array(
							array(
								'title'    => esc_html__( 'ClassifAI default', 'classifai' ),
								'prompt'   => esc_html__( 'Increase the content length no more than 2 to 4 sentences.', 'classifai' ),
								'original' => 1,
							),
						),
					]
				);
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
		$settings                                    = $this->feature_instance->get_settings();
		$api_key_settings                            = $this->sanitize_api_key_settings( $new_settings, $settings );
		$new_settings[ static::ID ]['api_key']       = $api_key_settings[ static::ID ]['api_key'];
		$new_settings[ static::ID ]['authenticated'] = $api_key_settings[ static::ID ]['authenticated'];

		if ( $this->feature_instance instanceof ContentResizing ) {
			$new_settings[ static::ID ]['number_of_suggestions'] = $this->sanitize_number_of_responses_field( 'number_of_suggestions', $new_settings, $settings );
			$new_settings[ static::ID ]['condense_text_prompt']  = sanitize_prompts( 'condense_text_prompt', $new_settings );
			$new_settings[ static::ID ]['expand_text_prompt']    = sanitize_prompts( 'expand_text_prompt', $new_settings );
		}

		return $new_settings;
	}

	/**
	 * Sanitisation callback for api key.
	 *
	 * @param array $new_settings The settings array.
	 * @return string
	 */
	public function sanitize_api_key( array $new_settings ): string {
		$settings = $this->feature_instance->get_settings();
		return sanitize_text_field( $new_settings[ static::ID ]['api_key'] ?? $settings[ static::ID ]['api_key'] ?? '' );
	}

	/**
	 * Register what we need for the plugin.
	 *
	 * This only fires if can_register returns true.
	 */
	public function register() {
		add_action( 'enqueue_block_assets', [ $this, 'enqueue_editor_assets' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
	}

	/**
	 * Enqueue the editor scripts.
	 */
	public function enqueue_editor_assets() {
		global $post;

		if ( empty( $post ) ) {
			return;
		}

		if ( ( new ContentResizing() )->is_feature_enabled() ) {
			wp_enqueue_script(
				'classifai-content-resizing-plugin-js',
				CLASSIFAI_PLUGIN_URL . 'dist/content-resizing-plugin.js',
				get_asset_info( 'content-resizing-plugin', 'dependencies' ),
				get_asset_info( 'content-resizing-plugin', 'version' ),
				true
			);

			wp_enqueue_style(
				'classifai-content-resizing-plugin-css',
				CLASSIFAI_PLUGIN_URL . 'dist/content-resizing-plugin.css',
				[],
				get_asset_info( 'content-resizing-plugin', 'version' ),
				'all'
			);
		}
	}

	/**
	 * Enqueue the admin scripts.
	 *
	 * @param string $hook_suffix The current admin page.
	 */
	public function enqueue_admin_assets( string $hook_suffix ) {
		$prompt_features = array(
			'feature_content_resizing',
		);

		// Load jQuery UI Dialog for prompt deletion.
		if (
			(
				'tools_page_classifai' === $hook_suffix
				&& ( isset( $_GET['tab'], $_GET['feature'] ) ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				&& 'language_processing' === sanitize_text_field( wp_unslash( $_GET['tab'] ) ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				&& in_array( $_GET['feature'], $prompt_features, true ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			) ||
			'admin_page_classifai_setup' === $hook_suffix
		) {
			wp_enqueue_script( 'jquery-ui-dialog' );
			wp_enqueue_style( 'wp-jquery-ui-dialog' );

			add_action(
				'admin_footer',
				static function () {
					printf(
						'<div id="js-classifai--delete-prompt-modal" style="display:none;"><p>%1$s</p></div>',
						esc_html__( 'Are you sure you want to delete the prompt?', 'classifai' ),
					);
				}
			);
		}

		// Load asset in new post and edit post screens.
		if ( 'post.php' === $hook_suffix || 'post-new.php' === $hook_suffix ) {
			wp_enqueue_style(
				'classifai-language-processing-style',
				CLASSIFAI_PLUGIN_URL . 'dist/language-processing.css',
				[],
				get_asset_info( 'language-processing', 'version' ),
			);
		}
	}

	/**
	 * Common entry point for all REST endpoints for this provider.
	 * This is called by the Service.
	 *
	 * @param int    $post_id The Post Id we're processing.
	 * @param string $route_to_call The route we are processing.
	 * @param array  $args Optional arguments to pass to the route.
	 * @return string|WP_Error
	 */
	public function rest_endpoint_callback( $post_id = 0, $route_to_call = '', $args = [] ) {
		if ( ! $post_id || ! get_post( $post_id ) ) {
			return new WP_Error( 'post_id_required', esc_html__( 'A valid post ID is required to generate an excerpt.', 'classifai' ) );
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
				$return = ( new ContentResizing() )->run( $post_id, $args );
				break;
		}

		return $return;
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
						'content' => $prompt,
					],
					[
						'role'    => 'user',
						'content' => $this->get_content( $post_id, $excerpt_length, false, $args['content'] ) . '',
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
				'num'     => $settings[ static::ID ]['number_of_titles'] ?? 1,
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
						'content' => $prompt,
					],
					[
						'role'    => 'user',
						'content' => $this->get_content( $post_id, absint( $args['num'] ) * 15, false, $args['content'] ) . '',
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
			$prompt = esc_textarea( get_default_prompt( $settings[ static::ID ]['condense_text_prompt'] ) ?? $this->condense_text_prompt );
		} else {
			$prompt = esc_textarea( get_default_prompt( $settings[ static::ID ]['expand_text_prompt'] ) ?? $this->expand_text_prompt );
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
						'content' => $prompt,
					],
					[
						'role'    => 'user',
						'content' => esc_html( $args['content'] ),
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
	 * Registers REST endpoints for this provider.
	 *
	 * @return void
	 */
	public function register_endpoints() {
		register_rest_route(
			'classifai/v1/openai',
			'resize-content',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'resize_post_content' ],
				'permission_callback' => [ $this, 'resize_post_content_permissions_check' ],
				'args'                => [
					'id'          => [
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
						'description'       => esc_html__( 'Post ID to resize the content for.', 'classifai' ),
					],
					'content'     => [
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
						'validate_callback' => 'rest_validate_request_arg',
						'description'       => esc_html__( 'The content to resize.', 'classifai' ),
					],
					'resize_type' => [
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
						'validate_callback' => 'rest_validate_request_arg',
						'description'       => esc_html__( 'The type of resize operation. "expand" or "condense".', 'classifai' ),
					],
				],
			]
		);
	}

	/**
	 * Handle request to resize content.
	 *
	 * @param WP_REST_Request $request The full request object.
	 * @return \WP_REST_Response|WP_Error
	 */
	public function resize_post_content( WP_REST_Request $request ) {
		$post_id = $request->get_param( 'id' );

		return rest_ensure_response(
			$this->rest_endpoint_callback(
				$post_id,
				'resize_content',
				[
					'content'     => $request->get_param( 'content' ),
					'resize_type' => $request->get_param( 'resize_type' ),
				]
			)
		);
	}

	/**
	 * Check if a given request has access to resize content.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|bool
	 */
	public function resize_post_content_permissions_check( WP_REST_Request $request ) {
		$post_id = $request->get_param( 'id' );

		// Ensure we have a logged in user that can edit the item.
		if ( empty( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return false;
		}

		$post_type     = get_post_type( $post_id );
		$post_type_obj = get_post_type_object( $post_type );

		// Ensure the post type is allowed in REST endpoints.
		if ( ! $post_type || empty( $post_type_obj ) || empty( $post_type_obj->show_in_rest ) ) {
			return false;
		}

		$feature = new ContentResizing();

		// Ensure the feature is enabled. Also runs a user check.
		if ( ! $feature->is_feature_enabled() ) {
			return new WP_Error( 'not_enabled', esc_html__( 'Content resizing is not currently enabled.', 'classifai' ) );
		}

		return true;
	}

	/**
	 * Returns the debug information for the provider settings.
	 *
	 * TODO: this should be in feature.
	 *
	 * @return array
	 */
	public function get_debug_information(): array {
		$settings          = $this->feature_instance->get_settings();
		$provider_settings = $settings[ static::ID ];
		$debug_info        = [];

		if ( $this->feature_instance instanceof TitleGeneration ) {
			$debug_info[ __( 'No. of titles', 'classifai' ) ]         = $provider_settings['number_of_titles'];
			$debug_info[ __( 'Generate title prompt', 'classifai' ) ] = wp_json_encode( $provider_settings['generate_title_prompt'] );
			$debug_info[ __( 'Latest response', 'classifai' ) ]       = $this->get_formatted_latest_response( get_transient( 'classifai_openai_chatgpt_title_generation_latest_response' ) );
		} elseif ( $this->feature_instance instanceof ExcerptGeneration ) {
			$debug_info[ __( 'Excerpt length', 'classifai' ) ]          = $settings['length'];
			$debug_info[ __( 'Generate excerpt prompt', 'classifai' ) ] = wp_json_encode( $provider_settings['generate_excerpt_prompt'] );
			$debug_info[ __( 'Latest response', 'classifai' ) ]         = $this->get_formatted_latest_response( get_transient( 'classifai_openai_chatgpt_excerpt_generation_latest_response' ) );
		} elseif ( $this->feature_instance instanceof ContentResizing ) {
			$debug_info[ __( 'No. of suggestions', 'classifai' ) ]   = $provider_settings['number_of_suggestions'];
			$debug_info[ __( 'Expand text prompt', 'classifai' ) ]   = wp_json_encode( $provider_settings['expand_text_prompt'] );
			$debug_info[ __( 'Condense text prompt', 'classifai' ) ] = wp_json_encode( $provider_settings['condense_text_prompt'] );
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
