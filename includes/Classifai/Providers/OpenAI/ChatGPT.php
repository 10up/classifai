<?php
/**
 * OpenAI ChatGPT integration
 */

namespace Classifai\Providers\OpenAI;

use Classifai\Providers\Provider;
use Classifai\Providers\OpenAI\APIRequest;
use Classifai\Providers\OpenAI\Tokenizer;
use Classifai\Watson\Normalizer;
use function Classifai\get_asset_info;
use WP_Error;

class ChatGPT extends Provider {

	use \Classifai\Providers\OpenAI\OpenAI;

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
	 * OpenAI ChatGPT constructor.
	 *
	 * @param string $service The service this class belongs to.
	 */
	public function __construct( $service ) {
		parent::__construct(
			'OpenAI ChatGPT',
			'ChatGPT',
			'openai_chatgpt',
			$service
		);

		// Set the onboarding options.
		$this->onboarding_options = array(
			'title'    => __( 'OpenAI ChatGPT', 'classifai' ),
			'fields'   => array( 'api-key' ),
			'features' => array(
				'enable_excerpt' => __( 'Excerpt generation', 'classifai' ),
				'enable_titles'  => __( 'Title generation', 'classifai' ),
			),
		);
	}

	/**
	 * Register what we need for the plugin.
	 *
	 * This only fires if can_register returns true.
	 */
	public function register() {
		add_action( 'enqueue_block_editor_assets', [ $this, 'enqueue_editor_assets' ] );
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

		$settings      = $this->get_settings();
		$user_roles    = wp_get_current_user()->roles ?? [];
		$excerpt_roles = $settings['roles'] ?? [];

		if (
			( ! empty( $excerpt_roles ) && empty( array_diff( $user_roles, $excerpt_roles ) ) )
			&& ( isset( $settings['enable_excerpt'] ) && 1 === (int) $settings['enable_excerpt'] )
		) {
			// This script removes the core excerpt panel and replaces it with our own.
			wp_enqueue_script(
				'classifai-post-excerpt',
				CLASSIFAI_PLUGIN_URL . 'dist/post-excerpt.js',
				array_merge( get_asset_info( 'post-excerpt', 'dependencies' ), [ 'lodash' ] ),
				get_asset_info( 'post-excerpt', 'version' ),
				true
			);
		}

		$title_roles = $settings['title_roles'] ?? [];

		if (
			( ! empty( $title_roles ) && empty( array_diff( $user_roles, $title_roles ) ) )
			&& ( isset( $settings['enable_titles'] ) && 1 === (int) $settings['enable_titles'] )
		) {
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
	}

	/**
	 * Enqueue the admin scripts.
	 *
	 * @param string $hook_suffix The current admin page.
	 */
	public function enqueue_admin_assets( $hook_suffix = '' ) {
		if ( 'post.php' !== $hook_suffix && 'post-new.php' !== $hook_suffix ) {
			return;
		}

		$screen      = get_current_screen();
		$settings    = $this->get_settings();
		$user_roles  = wp_get_current_user()->roles ?? [];
		$title_roles = $settings['title_roles'] ?? [];

		// Load the assets for the classic editor.
		if (
			$screen && ! $screen->is_block_editor()
			&& ( ! empty( $title_roles ) && empty( array_diff( $user_roles, $title_roles ) ) )
			&& ( isset( $settings['enable_titles'] ) && 1 === (int) $settings['enable_titles'] )
		) {
			wp_enqueue_style(
				'classifai-generate-title-classic-css',
				CLASSIFAI_PLUGIN_URL . 'dist/generate-title-classic.css',
				[],
				CLASSIFAI_PLUGIN_VERSION,
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

		wp_enqueue_style(
			'classifai-language-processing-style',
			CLASSIFAI_PLUGIN_URL . 'dist/language-processing.css',
			[],
			CLASSIFAI_PLUGIN_VERSION,
			'all'
		);
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

	/**
	 * Setup fields
	 */
	public function setup_fields_sections() {
		$default_settings = $this->get_default_settings();

		// Add API fields.
		$this->setup_api_fields( $default_settings['api_key'] );

		// Add excerpt fields.
		add_settings_section(
			$this->get_option_name() . '_excerpt',
			esc_html__( 'Excerpt settings', 'classifai' ),
			'',
			$this->get_option_name()
		);

		add_settings_field(
			'enable-excerpt',
			esc_html__( 'Generate excerpt', 'classifai' ),
			[ $this, 'render_input' ],
			$this->get_option_name(),
			$this->get_option_name() . '_excerpt',
			[
				'label_for'     => 'enable_excerpt',
				'input_type'    => 'checkbox',
				'default_value' => $default_settings['enable_excerpt'],
				'description'   => __( 'A button will be added to the excerpt panel that can be used to generate an excerpt.', 'classifai' ),
			]
		);

		$roles = get_editable_roles() ?? [];
		$roles = array_combine( array_keys( $roles ), array_column( $roles, 'name' ) );

		add_settings_field(
			'roles',
			esc_html__( 'Allowed roles', 'classifai' ),
			[ $this, 'render_checkbox_group' ],
			$this->get_option_name(),
			$this->get_option_name() . '_excerpt',
			[
				'label_for'      => 'roles',
				'options'        => $roles,
				'default_values' => $default_settings['roles'],
				'description'    => __( 'Choose which roles are allowed to generate excerpts.', 'classifai' ),
			]
		);

		add_settings_field(
			'length',
			esc_html__( 'Excerpt length', 'classifai' ),
			[ $this, 'render_input' ],
			$this->get_option_name(),
			$this->get_option_name() . '_excerpt',
			[
				'label_for'     => 'length',
				'input_type'    => 'number',
				'min'           => 1,
				'step'          => 1,
				'default_value' => $default_settings['length'],
				'description'   => __( 'How many words should the excerpt be? Note that the final result may not exactly match this. In testing, ChatGPT tended to exceed this number by 10-15 words.', 'classifai' ),
			]
		);

		// Add title fields.
		add_settings_section(
			$this->get_option_name() . '_title',
			esc_html__( 'Title settings', 'classifai' ),
			'',
			$this->get_option_name()
		);

		add_settings_field(
			'enable-titles',
			esc_html__( 'Generate titles', 'classifai' ),
			[ $this, 'render_input' ],
			$this->get_option_name(),
			$this->get_option_name() . '_title',
			[
				'label_for'     => 'enable_titles',
				'input_type'    => 'checkbox',
				'default_value' => $default_settings['enable_titles'],
				'description'   => __( 'A button will be added to the status panel that can be used to generate titles.', 'classifai' ),
			]
		);

		add_settings_field(
			'title-roles',
			esc_html__( 'Allowed roles', 'classifai' ),
			[ $this, 'render_checkbox_group' ],
			$this->get_option_name(),
			$this->get_option_name() . '_title',
			[
				'label_for'      => 'title_roles',
				'options'        => $roles,
				'default_values' => $default_settings['title_roles'],
				'description'    => __( 'Choose which roles are allowed to generate titles.', 'classifai' ),
			]
		);

		add_settings_field(
			'number-titles',
			esc_html__( 'Number of titles', 'classifai' ),
			[ $this, 'render_select' ],
			$this->get_option_name(),
			$this->get_option_name() . '_title',
			[
				'label_for'     => 'number_titles',
				'options'       => array_combine( range( 1, 10 ), range( 1, 10 ) ),
				'default_value' => $default_settings['number_titles'],
				'description'   => __( 'Number of titles that will be generated in one request.', 'classifai' ),
			]
		);
	}

	/**
	 * Sanitization for the options being saved.
	 *
	 * @param array $settings Array of settings about to be saved.
	 *
	 * @return array The sanitized settings to be saved.
	 */
	public function sanitize_settings( $settings ) {
		$new_settings = $this->get_settings();
		$new_settings = array_merge(
			$new_settings,
			$this->sanitize_api_key_settings( $new_settings, $settings )
		);

		if ( empty( $settings['enable_excerpt'] ) || 1 !== (int) $settings['enable_excerpt'] ) {
			$new_settings['enable_excerpt'] = 'no';
		} else {
			$new_settings['enable_excerpt'] = '1';
		}

		if ( isset( $settings['roles'] ) && is_array( $settings['roles'] ) ) {
			$new_settings['roles'] = array_map( 'sanitize_text_field', $settings['roles'] );
		} else {
			$new_settings['roles'] = array_keys( get_editable_roles() ?? [] );
		}

		if ( isset( $settings['length'] ) && is_numeric( $settings['length'] ) && (int) $settings['length'] >= 0 ) {
			$new_settings['length'] = absint( $settings['length'] );
		} else {
			$new_settings['length'] = 55;
		}

		if ( empty( $settings['enable_titles'] ) || 1 !== (int) $settings['enable_titles'] ) {
			$new_settings['enable_titles'] = 'no';
		} else {
			$new_settings['enable_titles'] = '1';
		}

		if ( isset( $settings['title_roles'] ) && is_array( $settings['title_roles'] ) ) {
			$new_settings['title_roles'] = array_map( 'sanitize_text_field', $settings['title_roles'] );
		} else {
			$new_settings['title_roles'] = array_keys( get_editable_roles() ?? [] );
		}

		if ( isset( $settings['number_titles'] ) && is_numeric( $settings['number_titles'] ) && (int) $settings['number_titles'] >= 1 && (int) $settings['number_titles'] <= 10 ) {
			$new_settings['number_titles'] = absint( $settings['number_titles'] );
		} else {
			$new_settings['number_titles'] = 1;
		}

		return $new_settings;
	}

	/**
	 * Resets settings for the provider.
	 */
	public function reset_settings() {
		update_option( $this->get_option_name(), $this->get_default_settings() );
	}

	/**
	 * Default settings for ChatGPT
	 *
	 * @return array
	 */
	private function get_default_settings() {
		$editable_roles = get_editable_roles() ?? [];

		return [
			'authenticated'  => false,
			'api_key'        => '',
			'enable_excerpt' => false,
			'roles'          => array_keys( $editable_roles ),
			'length'         => (int) apply_filters( 'excerpt_length', 55 ),
			'enable_titles'  => false,
			'title_roles'    => array_keys( $editable_roles ),
			'number_titles'  => 1,
		];
	}

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
				$return = $this->generate_excerpt( $post_id );
				break;
			case 'title':
				$return = $this->generate_titles( $post_id, $args );
				break;
		}

		return $return;
	}

	/**
	 * Generate an excerpt using ChatGPT.
	 *
	 * @param int $post_id The Post Id we're processing
	 * @return string|WP_Error
	 */
	public function generate_excerpt( int $post_id = 0 ) {
		if ( ! $post_id || ! get_post( $post_id ) ) {
			return new WP_Error( 'post_id_required', esc_html__( 'Post ID is required to generate an excerpt.', 'classifai' ) );
		}

		$settings = $this->get_settings();

		// These checks (and the one above) happen in the REST permission_callback,
		// but we run them again here in case this method is called directly.
		if ( empty( $settings ) || ( isset( $settings['authenticated'] ) && false === $settings['authenticated'] ) || ( isset( $settings['enable_excerpt'] ) && 'no' === $settings['enable_excerpt'] ) ) {
			return new WP_Error( 'not_enabled', esc_html__( 'Excerpt generation is disabled or OpenAI authentication failed. Please check your settings.', 'classifai' ) );
		}

		$excerpt_length = absint( $settings['length'] ?? 55 );

		$request = new APIRequest( $settings['api_key'] ?? '' );

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
		$prompt = apply_filters( 'classifai_chatgpt_excerpt_prompt', 'Provide a teaser for the following text using a maximum of ' . $excerpt_length . ' words', $post_id, $excerpt_length );

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
						'role'    => 'user',
						'content' => $prompt . ': ' . $this->get_content( $post_id, $excerpt_length ) . '',
					],
				],
				'temperature' => 0,
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

		$settings = $this->get_settings();
		$args     = wp_parse_args(
			array_filter( $args ),
			[
				'num' => $settings['number_titles'] ?? 1,
			]
		);

		// These checks happen in the REST permission_callback,
		// but we run them again here in case this method is called directly.
		if ( empty( $settings ) || ( isset( $settings['authenticated'] ) && false === $settings['authenticated'] ) || ( isset( $settings['enable_titles'] ) && 'no' === $settings['enable_titles'] ) ) {
			return new WP_Error( 'not_enabled', esc_html__( 'Title generation is disabled or OpenAI authentication failed. Please check your settings.', 'classifai' ) );
		}

		$request = new APIRequest( $settings['api_key'] ?? '' );

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
		$prompt = apply_filters( 'classifai_chatgpt_title_prompt', 'Write an SEO-friendly title for the following content that will encourage readers to clickthrough, staying within a range of 40 to 60 characters', $post_id, $args );

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
						'role'    => 'user',
						'content' => esc_html( $prompt ) . ': ' . $this->get_content( $post_id, absint( $args['num'] ) * 15, false ) . '',
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
	 * @param int  $post_id Post ID to get content from.
	 * @param int  $return_length Word length of returned content.
	 * @param bool $use_title Whether to use the title or not.
	 * @return string
	 */
	public function get_content( int $post_id = 0, int $return_length = 0, bool $use_title = true ) {
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
		 * prompt (13). ChatGPT counts both the tokens in the request and in
		 * the response towards the max.
		 */
		$max_content_tokens = $this->max_tokens - $return_tokens - 13;

		// Then trim our content, if needed, to stay under the max.
		if ( $use_title ) {
			$content = $tokenizer->trim_content(
				$normalizer->normalize( $post_id ),
				(int) $max_content_tokens
			);
		} else {
			$post         = get_post( $post_id );
			$post_content = apply_filters( 'the_content', $post->post_content );
			$post_content = preg_replace( '#\[.+\](.+)\[/.+\]#', '$1', $post_content );

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

}
