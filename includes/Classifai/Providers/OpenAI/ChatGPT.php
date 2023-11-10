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
	protected $max_tokens = 4096;

	/**
	 * Prompt for generating excerpts
	 *
	 * @var string
	 */
	protected $generate_excerpt_prompt = 'Summarize the following message using a maximum of {{WORDS}} words. Ensure this summary pairs well with the following text: {{TITLE}}.';

	/**
	 * Prompt for generating titles
	 *
	 * @var string
	 */
	protected $generate_title_prompt = 'Write an SEO-friendly title for the following content that will encourage readers to clickthrough, staying within a range of 40 to 60 characters.';

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
	public function __construct( $feature_instance ) {
		parent::__construct(
			'OpenAI ChatGPT',
			'ChatGPT',
			'openai_chatgpt'
		);

		// Set the onboarding options.
		$this->onboarding_options = array(
			'title'    => __( 'OpenAI ChatGPT', 'classifai' ),
			'fields'   => array( 'api-key' ),
			'features' => array(
				'enable_excerpt'        => __( 'Excerpt generation', 'classifai' ),
				'enable_titles'         => __( 'Title generation', 'classifai' ),
				'enable_resize_content' => __( 'Content resizing', 'classifai' ),
			),
		);

		$this->feature_instance = $feature_instance;

		add_action( 'rest_api_init', [ $this, 'register_endpoints' ] );
		do_action( 'classifai_' . static::ID . '_init', $this );
	}

	/**
	 * Adds a prompt repeater field.
	 * The prompt fields allow users to add their own prompts for ChatGPT.
	 *
	 * This is an optional field and depends on the feature.
	 *
	 * @param array $args Arguments passed in.
	 */
	public function add_prompt_field( $args = [] ) {
		$settings = $this->feature_instance->get_settings( static::ID );

		add_settings_field(
			$args['id'],
			$args['label'] ?? esc_html__( 'Prompt', 'classifai' ),
			[ $this->feature_instance, 'render_prompt_repeater_field' ],
			$this->feature_instance->get_option_name(),
			$this->feature_instance->get_option_name() . '_section',
			[
				'option_index'  => static::ID,
				'label_for'     => $args['id'],
				'placeholder'   => $args['prompt_placeholder'],
				'default_value' => $settings[ $args['id'] ],
				'description'   => $args['description'],
			]
		);
	}

	/**
	 * Adds number of responses number field.
	 * ChatGPT is capable of returning variable number of responses.
	 *
	 * This field is helpful to set the number of responses to be returned.
	 *
	 * This is an optional field and depends on the feature.
	 *
	 * @param array $args Arguments passed in.
	 */
	public function add_number_of_responses_field( $args = [] ) {
		$settings = $this->feature_instance->get_settings( static::ID );

		add_settings_field(
			$args['id'],
			$args['label'],
			[ $this->feature_instance, 'render_input' ],
			$this->feature_instance->get_option_name(),
			$this->feature_instance->get_option_name() . '_section',
			[
				'option_index'  => static::ID,
				'label_for'     => $args['id'],
				'input_type'    => 'number',
				'min'           => 1,
				'step'          => 1,
				'default_value' => $settings[ $args['id'] ],
				'description'   => $args['description'],
			]
		);
	}

	/**
	 * Sanitisation callback for api key.
	 *
	 * @param array $settings The settings array.
	 *
	 * @return string
	 */
	public function sanitize_api_key( $settings ) {
		if ( isset( $settings[ self::ID ]['api_key'] ) ) {
			return sanitize_text_field( $settings[ self::ID ]['api_key'] );
		}

		return '';
	}

	/**
	 * Sanitisation callback for number of responses.
	 *
	 * @param string $key The key of the value we are sanitizing.
	 * @param array  $settings The settings array.
	 *
	 * @return integer
	 */
	public function sanitize_number_of_responses_field( $key, $settings ) {
		if ( isset( $settings[ self::ID ][ $key ] ) ) {
			return absint( $settings[ self::ID ][ $key ] );
		}

		return 1;
	}

	/**
	 * Register what we need for the plugin.
	 *
	 * This only fires if can_register returns true.
	 */
	public function register() {
		add_action( 'enqueue_block_assets', [ $this, 'enqueue_editor_assets' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
		add_action( 'edit_form_before_permalink', [ $this, 'register_generated_titles_template' ] );
	}

	/**
	 * Returns localised data for title generation.
	 */
	public function get_localised_vars() {
		global $post;

		return [
			'enabledFeatures' => [
				0 => [
					'feature'       => 'title',
					'path'          => '/classifai/v1/openai/generate-title/',
					'buttonText'    => __( 'Generate titles', 'classifai' ),
					'modalTitle'    => __( 'Select a title', 'classifai' ),
					'selectBtnText' => __( 'Select', 'classifai' ),
				],
			],
			'noPermissions'   => ! is_user_logged_in() || ! current_user_can( 'edit_post', $post->ID ),
		];
	}

	/**
	 * Enqueue the editor scripts.
	 */
	public function enqueue_editor_assets() {
		global $post;

		if ( empty( $post ) ) {
			return;
		}

		if ( $this->feature_instance instanceof ExcerptGeneration && $this->feature_instance->is_feature_enabled() ) {
			// This script removes the core excerpt panel and replaces it with our own.
			wp_enqueue_script(
				'classifai-post-excerpt',
				CLASSIFAI_PLUGIN_URL . 'dist/post-excerpt.js',
				array_merge( get_asset_info( 'post-excerpt', 'dependencies' ), [ 'lodash' ] ),
				get_asset_info( 'post-excerpt', 'version' ),
				true
			);
		}

		if ( $this->feature_instance instanceof TitleGeneration && $this->feature_instance->is_feature_enabled() ) {
			wp_enqueue_script(
				'classifai-post-status-info',
				CLASSIFAI_PLUGIN_URL . 'dist/post-status-info.js',
				get_asset_info( 'post-status-info', 'dependencies' ),
				get_asset_info( 'post-status-info', 'version' ),
				true
			);

			wp_add_inline_script(
				'classifai-post-status-info',
				sprintf(
					'var classifaiChatGPTData = %s;',
					wp_json_encode( $this->get_localised_vars() )
				),
				'before'
			);
		}

		if ( $this->feature_instance instanceof ContentResizing && $this->feature_instance->is_feature_enabled() ) {
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
		// Load asset in OpenAI ChatGPT settings page.
		if (
			'tools_page_classifai' === $hook_suffix
			&& ( isset( $_GET['tab'], $_GET['provider'] ) ) // phpcs:ignore
			&& 'language_processing' === $_GET['tab'] // phpcs:ignore
			&& 'openai_chatgpt' === $_GET['provider'] // phpcs:ignore
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
			$screen = get_current_screen();

			// Load the assets for the classic editor.
			if ( $screen && ! $screen->is_block_editor() ) {
				if (
					post_type_supports( $screen->post_type, 'title' ) &&
					$this->feature_instance instanceof TitleGeneration && $this->feature_instance->is_feature_enabled()
				) {
					wp_enqueue_style(
						'classifai-generate-title-classic-css',
						CLASSIFAI_PLUGIN_URL . 'dist/generate-title-classic.css',
						[],
						get_asset_info( 'generate-title-classic', 'version' ),
						'all'
					);

					wp_enqueue_script(
						'classifai-generate-title-classic-js',
						CLASSIFAI_PLUGIN_URL . 'dist/generate-title-classic.js',
						array_merge( get_asset_info( 'generate-title-classic', 'dependencies' ), array( 'wp-api' ) ),
						get_asset_info( 'generate-title-classic', 'version' ),
						true
					);

					wp_add_inline_script(
						'classifai-generate-title-classic-js',
						sprintf(
							'var classifaiChatGPTData = %s;',
							wp_json_encode( $this->get_localised_vars() )
						),
						'before'
					);
				}

				if (
					post_type_supports( $screen->post_type, 'excerpt' ) &&
					$this->feature_instance instanceof ExcerptGeneration && $this->feature_instance->is_feature_enabled()
				) {
					wp_enqueue_style(
						'classifai-generate-title-classic-css',
						CLASSIFAI_PLUGIN_URL . 'dist/generate-title-classic.css',
						[],
						get_asset_info( 'generate-title-classic', 'version' ),
						'all'
					);

					wp_enqueue_script(
						'classifai-generate-excerpt-classic-js',
						CLASSIFAI_PLUGIN_URL . 'dist/generate-excerpt-classic.js',
						array_merge( get_asset_info( 'generate-excerpt-classic', 'dependencies' ), array( 'wp-api' ) ),
						get_asset_info( 'generate-excerpt-classic', 'version' ),
						true
					);

					wp_add_inline_script(
						'classifai-generate-excerpt-classic-js',
						sprintf(
							'var classifaiGenerateExcerpt = %s;',
							wp_json_encode(
								[
									'path'           => '/classifai/v1/openai/generate-excerpt/',
									'buttonText'     => __( 'Generate excerpt', 'classifai' ),
									'regenerateText' => __( 'Re-generate excerpt', 'classifai' ),
								]
							)
						),
						'before'
					);
				}
			}

			wp_enqueue_style(
				'classifai-language-processing-style',
				CLASSIFAI_PLUGIN_URL . 'dist/language-processing.css',
				[],
				get_asset_info( 'language-processing', 'version' ),
			);
		}
	}

	/**
	 * HTML template for title generation result popup.
	 */
	public function register_generated_titles_template() {
		?>
		<div id="classifai-openai__results" style="display: none;">
			<div id="classifai-openai__overlay" style="opacity: 0;"></div>
			<div id="classifai-openai__modal" style="opacity: 0;">
				<h2 id="classifai-openai__results-title"></h2>
				<div id="classifai-openai__close-modal-button"></div>
				<div id="classifai-openai__results-content">
				</div>
			</div>
		</div>
		<?php
	}

	public function setup_fields_sections() {}

	public function reset_settings() {}

	public function sanitize_settings( $settings ) {}

	/**
	 * Default settings for ChatGPT
	 *
	 * @return array
	 */
	public function get_default_settings() {}

	/**
	 * Provides debug information related to the provider.
	 *
	 * @param array|null $settings Settings array. If empty, settings will be retrieved.
	 * @param boolean    $configured Whether the provider is correctly configured. If null, the option will be retrieved.
	 * @return string|array
	 */
	public function get_provider_debug_information( $settings = null, $configured = null ) {
		if ( is_null( $settings ) ) {
			$settings = $this->sanitize_settings( $this->get_settings() );
		}

		$authenticated  = 1 === intval( $settings['authenticated'] ?? 0 );
		$enable_excerpt = 1 === intval( $settings['enable_excerpt'] ?? 0 );
		$enable_titles  = 1 === intval( $settings['enable_titles'] ?? 0 );

		return [
			__( 'Authenticated', 'classifai' )           => $authenticated ? __( 'yes', 'classifai' ) : __( 'no', 'classifai' ),
			__( 'Generate excerpt', 'classifai' )        => $enable_excerpt ? __( 'yes', 'classifai' ) : __( 'no', 'classifai' ),
			__( 'Allowed roles (excerpt)', 'classifai' ) => implode( ', ', $settings['roles'] ?? [] ),
			__( 'Excerpt length', 'classifai' )          => $settings['length'] ?? 55,
			__( 'Generate titles', 'classifai' )         => $enable_titles ? __( 'yes', 'classifai' ) : __( 'no', 'classifai' ),
			__( 'Allowed roles (titles)', 'classifai' )  => implode( ', ', $settings['title_roles'] ?? [] ),
			__( 'Number of titles', 'classifai' )        => absint( $settings['number_titles'] ?? 1 ),
			__( 'Allowed roles (resize)', 'classifai' )  => implode( ', ', $settings['resize_content_roles'] ?? [] ),
			__( 'Number of suggestions', 'classifai' )   => absint( $settings['number_resize_content'] ?? 1 ),
			__( 'Latest response', 'classifai' )         => $this->get_formatted_latest_response( get_transient( 'classifai_openai_chatgpt_latest_response' ) ),
		];
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
		$route_to_call = strtolower( $route_to_call );
		if ( ! $post_id || ! get_post( $post_id ) ) {
			return new WP_Error( 'post_id_required', esc_html__( 'A valid post ID is required to generate an excerpt.', 'classifai' ) );
		}

		$return = '';

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

		$excerpt_prompt = esc_textarea( $this->get_default_prompt( $settings[ static::ID ]['generate_excerpt_prompt'] ) ?? $this->generate_excerpt_prompt );

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

		set_transient( 'classifai_openai_chatgpt_latest_response', $response, DAY_IN_SECONDS * 30 );

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

		$prompt = esc_textarea( $this->get_default_prompt( $settings[ static::ID ]['generate_title_prompt'] ) ?? $this->generate_title_prompt );

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

		set_transient( 'classifai_openai_chatgpt_latest_response', $response, DAY_IN_SECONDS * 30 );

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
			$prompt = esc_textarea( $this->get_default_prompt( $settings[ static::ID ]['condense_text_prompt'] ) ?? $this->condense_text_prompt );
		} else {
			$prompt = esc_textarea( $this->get_default_prompt( $settings[ static::ID ]['expand_text_prompt'] ) ?? $this->expand_text_prompt );
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

		set_transient( 'classifai_openai_chatgpt_latest_response', $response, DAY_IN_SECONDS * 30 );

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
	public function get_content( int $post_id = 0, int $return_length = 0, bool $use_title = true, string $post_content = '' ) {
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
	 * Sanitize the prompt data.
	 * This is used for the repeater field.
	 *
	 * @since 2.4.0
	 *
	 * @param array $prompt_key Prompt key.
	 * @param array $settings   Settings data.
	 *
	 * @return array Sanitized prompt data.
	 */
	public function sanitize_prompts( $prompt_key = '', array $settings ): array {
		if ( isset( $settings[ self::ID ][ $prompt_key ] ) && is_array( $settings[ self::ID ][ $prompt_key ] ) ) {

			$prompts = $settings[ self::ID ][ $prompt_key ];

			// Remove any prompts that don't have a title and prompt.
			$prompts = array_filter(
				$prompts,
				function ( $prompt ) {
					return ! empty( $prompt['title'] ) && ! empty( $prompt['prompt'] );
				}
			);

			// Sanitize the prompts and make sure only one prompt is marked as default.
			$has_default = false;

			$prompts = array_map(
				function ( $prompt ) use ( &$has_default ) {
					$default = $prompt['default'] && ! $has_default;

					if ( $default ) {
						$has_default = true;
					}

					return array(
						'title'    => sanitize_text_field( $prompt['title'] ),
						'prompt'   => sanitize_textarea_field( $prompt['prompt'] ),
						'default'  => absint( $default ),
						'original' => absint( $prompt['original'] ),
					);
				},
				$prompts
			);

			// If there is no default, use the first prompt.
			if ( false === $has_default && ! empty( $prompts ) ) {
				$prompts[0]['default'] = 1;
			}

			return $prompts;
		}

		return array();
	}

	/**
	 * Get the default prompt for use.
	 *
	 * @since 2.4.0
	 *
	 * @param array $prompts Prompt data.
	 *
	 * @return string|null Default prompt.
	 */
	public function get_default_prompt( array $prompts ): ?string {
		$default_prompt = null;

		if ( ! empty( $prompts ) ) {
			$prompt_data = array_filter(
				$prompts,
				function ( $prompt ) {
					return $prompt['default'] && ! $prompt['original'];
				}
			);

			if ( ! empty( $prompt_data ) ) {
				$default_prompt = current( $prompt_data )['prompt'];
			} elseif ( ! empty( $prompts[0]['prompt'] ) && ! $prompts[0]['original'] ) {
				// If there is no default, use the first prompt, unless it's the original prompt.
				$default_prompt = $prompts[0]['prompt'];
			}
		}

		return $default_prompt;
	}

	public function register_endpoints() {
		register_rest_route(
			'classifai/v1/openai',
			'generate-title(?:/(?P<id>\d+))?',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'generate_post_title' ],
					'args'                => [
						'id' => [
							'required'          => true,
							'type'              => 'integer',
							'sanitize_callback' => 'absint',
							'description'       => esc_html__( 'Post ID to generate title for.', 'classifai' ),
						],
						'n'  => [
							'type'              => 'integer',
							'minimum'           => 1,
							'maximum'           => 10,
							'sanitize_callback' => 'absint',
							'validate_callback' => 'rest_validate_request_arg',
							'description'       => esc_html__( 'Number of titles to generate', 'classifai' ),
						],
					],
					'permission_callback' => [ $this, 'generate_post_title_permissions_check' ],
				],
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'generate_post_title' ],
					'args'                => [
						'content' => [
							'required'          => true,
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
							'validate_callback' => 'rest_validate_request_arg',
							'description'       => esc_html__( 'Content to generate a title for', 'classifai' ),
						],
					],
					'permission_callback' => [ $this, 'generate_post_title_permissions_check' ],
				],
			]
		);

		register_rest_route(
			'classifai/v1/openai',
			'generate-excerpt(?:/(?P<id>\d+))?',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'generate_post_excerpt' ],
					'args'                => [
						'id' => [
							'required'          => true,
							'type'              => 'integer',
							'sanitize_callback' => 'absint',
							'description'       => esc_html__( 'Post ID to generate excerpt for.', 'classifai' ),
						],
					],
					'permission_callback' => [ $this, 'generate_post_excerpt_permissions_check' ],
				],
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'generate_post_excerpt' ],
					'args'                => [
						'content' => [
							'required'          => true,
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
							'validate_callback' => 'rest_validate_request_arg',
							'description'       => esc_html__( 'Content to summarize into an excerpt.', 'classifai' ),
						],
						'title'   => [
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
							'validate_callback' => 'rest_validate_request_arg',
							'description'       => esc_html__( 'Title of content we want a summary for.', 'classifai' ),
						],
					],
					'permission_callback' => [ $this, 'generate_post_excerpt_permissions_check' ],
				],
			]
		);

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
	 * Handle request to generate title for given post ID.
	 *
	 * @param WP_REST_Request $request The full request object.
	 * @return \WP_REST_Response|WP_Error
	 */
	public function generate_post_title( WP_REST_Request $request ) {
		$post_id  = $request->get_param( 'id' );

		return rest_ensure_response(
			$this->rest_endpoint_callback(
				$post_id,
				'title',
				[
					'num'     => $request->get_param( 'n' ),
					'content' => $request->get_param( 'content' ),
				]
			)
		);
	}

	/**
	 * Check if a given request has access to generate a title.
	 *
	 * This check ensures we have a proper post ID, the current user
	 * making the request has access to that post, that we are
	 * properly authenticated with OpenAI and that title generation
	 * is turned on.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|bool
	 */
	public function generate_post_title_permissions_check( WP_REST_Request $request ) {
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

		$feature  = new TitleGeneration();
		$settings = $feature->get_settings();

		// Check if valid authentication is in place.
		if ( empty( $settings ) || ( isset( $settings[ static::ID ]['authenticated'] ) && false === $settings[ static::ID ]['authenticated'] ) ) {
			return new WP_Error( 'auth', esc_html__( 'Please set up valid authentication with OpenAI.', 'classifai' ) );
		}

		// Ensure the feature is enabled. Also runs a user check.
		if ( ! $feature->is_feature_enabled() ) {
			return new WP_Error( 'not_enabled', esc_html__( 'Title generation not currently enabled.', 'classifai' ) );
		}

		return true;
	}

	/**
	 * Handle request to generate excerpt for given post ID.
	 *
	 * @param WP_REST_Request $request The full request object.
	 * @return \WP_REST_Response|WP_Error
	 */
	public function generate_post_excerpt( WP_REST_Request $request ) {
		$post_id = $request->get_param( 'id' );
		$content = $request->get_param( 'content' );
		$title   = $request->get_param( 'title' );

		return rest_ensure_response(
			$this->rest_endpoint_callback(
				$post_id,
				'excerpt',
				[
					'content' => $content,
					'title'   => $title,
				]
			)
		);
	}

	/**
	 * Check if a given request has access to generate an excerpt.
	 *
	 * This check ensures we have a proper post ID, the current user
	 * making the request has access to that post, that we are
	 * properly authenticated with OpenAI and that excerpt generation
	 * is turned on.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|bool
	 */
	public function generate_post_excerpt_permissions_check( WP_REST_Request $request ) {
		$post_id  = $request->get_param( 'id' );

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

		$feature  = new ExcerptGeneration();
		$settings = $feature->get_settings();

		// Check if valid authentication is in place.
		if ( empty( $settings ) || ( isset( $settings[ static::ID ]['authenticated'] ) && false === $settings[ static::ID ]['authenticated'] ) ) {
			return new WP_Error( 'auth', esc_html__( 'Please set up valid authentication with OpenAI.', 'classifai' ) );
		}

		// Ensure the feature is enabled. Also runs a user check.
		if ( ! $feature->is_feature_enabled() ) {
			return new WP_Error( 'not_enabled', esc_html__( 'Excerpt generation not currently enabled.', 'classifai' ) );
		}

		return true;
	}

	/**
	 * Handle request to resize content.
	 *
	 * @param WP_REST_Request $request The full request object.
	 * @return \WP_REST_Response|WP_Error
	 */
	public function resize_post_content( WP_REST_Request $request ) {
		$post_id  = $request->get_param( 'id' );

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

		$feature  = new ContentResizing();
		$settings = $feature->get_settings();

		// Check if valid authentication is in place.
		if ( empty( $settings ) || ( isset( $settings[ static::ID ]['authenticated'] ) && false === $settings[ static::ID ]['authenticated'] ) ) {
			return new WP_Error( 'auth', esc_html__( 'Please set up valid authentication with OpenAI.', 'classifai' ) );
		}

		// Check if resize content feature is turned on.
		if ( empty( $settings ) || ( isset( $settings['status'] ) && 'no' === $settings['status'] ) ) {
			return new WP_Error( 'not_enabled', esc_html__( 'Content resizing not currently enabled.', 'classifai' ) );
		}

		// Check if the current user's role is allowed.
		$roles      = $settings['roles'] ?? [];
		$user_roles = wp_get_current_user()->roles ?? [];

		if ( empty( $roles ) || ! empty( array_diff( $user_roles, $roles ) ) ) {
			return false;
		}

		return true;
	}
}
