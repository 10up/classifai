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
			'OpenAI',
			'ChatGPT',
			'openai_chatgpt',
			$service
		);
	}

	/**
	 * Can the functionality be initialized?
	 *
	 * @return bool
	 */
	public function can_register() {
		$settings = $this->get_settings();

		if ( empty( $settings ) || ( isset( $settings['authenticated'] ) && false === $settings['authenticated'] ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Register what we need for the plugin.
	 *
	 * This only fires if can_register returns true.
	 */
	public function register() {
		add_action( 'enqueue_block_editor_assets', [ $this, 'enqueue_editor_assets' ] );
	}

	/**
	 * Enqueue the editor scripts.
	 */
	public function enqueue_editor_assets() {
		$settings = $this->get_settings();

		// Don't load our custom post excerpt if excerpt functionality isn't turned on.
		if ( ! isset( $settings['enable_excerpt'] ) || 1 !== (int) $settings['enable_excerpt'] ) {
			return;
		}

		// Check if the current user has permission to use the generate excerpt button.
		$roles      = $settings['roles'] ?? [];
		$user_roles = wp_get_current_user()->roles ?? [];

		if ( empty( $roles ) || ! empty( array_diff( $user_roles, $roles ) ) ) {
			return;
		}

		// This script removes the core excerpt panel and replaces it with our own.
		wp_enqueue_script(
			'classifai-post-excerpt',
			CLASSIFAI_PLUGIN_URL . 'dist/post-excerpt.js',
			get_asset_info( 'post-excerpt', 'dependencies' ),
			get_asset_info( 'post-excerpt', 'version' ),
			true
		);
	}

	/**
	 * Setup fields
	 */
	public function setup_fields_sections() {
		$default_settings = $this->get_default_settings();

		$this->setup_api_fields( $default_settings['api_key'] );

		add_settings_field(
			'enable-excerpt',
			esc_html__( 'Generate excerpt', 'classifai' ),
			[ $this, 'render_input' ],
			$this->get_option_name(),
			$this->get_option_name(),
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
			$this->get_option_name(),
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
			$this->get_option_name(),
			[
				'label_for'     => 'length',
				'input_type'    => 'number',
				'min'           => 1,
				'step'          => 1,
				'default_value' => $default_settings['length'],
				'description'   => __( 'How many words should the excerpt be? Note that the final result may not exactly match this. In testing, ChatGPT tended to exceed this number by 10-15 words.', 'classifai' ),
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
		}

		if ( isset( $settings['length'] ) && is_numeric( $settings['length'] ) && (int) $settings['length'] >= 0 ) {
			$new_settings['length'] = absint( $settings['length'] );
		} else {
			$new_settings['length'] = 55;
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
		return [
			'authenticated'  => false,
			'api_key'        => '',
			'enable_excerpt' => false,
			'roles'          => array_keys( get_editable_roles() ?? [] ),
			'length'         => (int) apply_filters( 'excerpt_length', 55 ),
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

		return [
			__( 'Authenticated', 'classifai' )    => $authenticated ? __( 'yes', 'classifai' ) : __( 'no', 'classifai' ),
			__( 'Generate excerpt', 'classifai' ) => $enable_excerpt ? __( 'yes', 'classifai' ) : __( 'no', 'classifai' ),
			__( 'Allowed roles', 'classifai' )    => implode( ', ', $settings['roles'] ?? [] ),
			__( 'Excerpt length', 'classifai' )   => $settings['length'] ?? 55,
			__( 'Latest response', 'classifai' )  => $this->get_formatted_latest_response( 'classifai_openai_chatgpt_latest_response' ),
		];
	}

	/**
	 * Common entry point for all REST endpoints for this provider.
	 * This is called by the Service.
	 *
	 * @param int    $post_id The Post Id we're processing.
	 * @param string $route_to_call The route we are processing.
	 * @return string|WP_Error
	 */
	public function rest_endpoint_callback( $post_id = 0, $route_to_call = '' ) {
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
		 * @hook classifai_chatgpt_prompt
		 *
		 * @param {string} $prompt Prompt we are sending to ChatGPT. Gets added before post content.
		 * @param {int} $post_id ID of post we are summarizing.
		 * @param {int} $excerpt_length Length of final excerpt.
		 *
		 * @return {string} Prompt.
		 */
		$prompt = apply_filters( 'classifai_chatgpt_prompt', 'Provide a kicker for the following text in ' . $excerpt_length . ' words', $post_id, $excerpt_length );

		/**
		 * Filter the request body before sending to ChatGPT.
		 *
		 * @since 2.0.0
		 * @hook classifai_chatgpt_request_body
		 *
		 * @param {array} $body Request body that will be sent to ChatGPT.
		 * @param {int} $post_id ID of post we are summarizing.
		 *
		 * @return {array} Request body.
		 */
		$body = apply_filters(
			'classifai_chatgpt_request_body',
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
					$response = sanitize_text_field( $choice['message']['content'] );
				}
			}
		}

		return $response;
	}

	/**
	 * Get our content, trimming if needed.
	 *
	 * @param int $post_id Post ID to get content from.
	 * @param int $excerpt_length Sentence length of final excerpt.
	 * @return string
	 */
	public function get_content( int $post_id = 0, int $excerpt_length = 0 ) {
		$tokenizer  = new Tokenizer( $this->max_tokens );
		$normalizer = new Normalizer();

		/**
		 * We first determine how many tokens, roughly, our excerpt will require.
		 * This is determined by the number of words our excerpt requested and how
		 * many tokens are in an average word.
		 */
		$excerpt_tokens = $tokenizer->tokens_in_words( $excerpt_length );

		/**
		 * We then subtract those tokens from the max number of tokens ChatGPT allows
		 * in a single request, as well as subtracting out the number of tokens in our
		 * prompt (13). ChatGPT counts both the tokens in the request and in
		 * the response towards the max.
		 */
		$max_content_tokens = $this->max_tokens - $excerpt_tokens - 13;

		// Then trim our content, if needed, to stay under the max.
		$content = $tokenizer->trim_content(
			$normalizer->normalize( $post_id ),
			(int) $max_content_tokens
		);

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
