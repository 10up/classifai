<?php
/**
 * OpenAI ChatGPT integration
 */

namespace Classifai\Providers\OpenAI;

use Classifai\Providers\Provider;
use Classifai\Providers\OpenAI\APIRequest;
use \Classifai\Watson\Normalizer;
use function Classifai\get_asset_info;
use WP_Error;

class ChatGPT extends Provider {

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
	 * How many characters in one token (roughly)
	 *
	 * @var int
	 */
	protected $characters_in_token = 3;

	/**
	 * How many tokens a sentence will take (roughly)
	 *
	 * @var int
	 */
	protected $tokens_per_sentence = 40;

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

		// Add the settings section.
		add_settings_section(
			$this->get_option_name(),
			$this->provider_service_name,
			function() {
				printf(
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
				);
			},
			$this->get_option_name()
		);

		// Add all our settings.
		add_settings_field(
			'api-key',
			esc_html__( 'API Key', 'classifai' ),
			[ $this, 'render_input' ],
			$this->get_option_name(),
			$this->get_option_name(),
			[
				'label_for'     => 'api_key',
				'input_type'    => 'password',
				'default_value' => $default_settings['api_key'],
			]
		);

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

		add_settings_field(
			'length',
			esc_html__( 'Excerpt length', 'classifai' ),
			[ $this, 'render_input' ],
			$this->get_option_name(),
			$this->get_option_name(),
			[
				'label_for'     => 'length',
				'input_type'    => 'number',
				'max'           => 5,
				'min'           => 1,
				'step'          => 1,
				'default_value' => $default_settings['length'],
				'description'   => __( 'How many sentences should the excerpt be?', 'classifai' ),
			]
		);

		add_settings_field(
			'temperature',
			esc_html__( 'Temperature value', 'classifai' ),
			[ $this, 'render_input' ],
			$this->get_option_name(),
			$this->get_option_name(),
			[
				'label_for'     => 'temperature',
				'input_type'    => 'number',
				'max'           => 2,
				'min'           => 0,
				'step'          => 0.1,
				'default_value' => $default_settings['temperature'],
				'description'   => __( 'What sampling temperature to use, between 0 and 2. Higher values like 1.8 will make the output more random, while lower values like 0.2 will make it more focused and deterministic.', 'classifai' ),
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
		$new_settings  = $this->get_settings();
		$authenticated = $this->authenticate_credentials( $settings['api_key'] ?? '' );

		if ( is_wp_error( $authenticated ) ) {
			add_settings_error(
				'api_key',
				'classifai-auth',
				$authenticated->get_error_message(),
				'error'
			);

			$new_settings['authenticated'] = false;

			// For response code 429, credentials are valid but rate limit is reached.
			if ( 429 === (int) $authenticated->get_error_code() ) {
				$new_settings['authenticated'] = true;
			}
		} else {
			$new_settings['authenticated'] = true;
		}

		$new_settings['api_key'] = sanitize_text_field( $settings['api_key'] ?? '' );

		if ( empty( $settings['enable_excerpt'] ) || 1 !== (int) $settings['enable_excerpt'] ) {
			$new_settings['enable_excerpt'] = 'no';
		} else {
			$new_settings['enable_excerpt'] = '1';
		}

		if ( isset( $settings['length'] ) && is_numeric( $settings['length'] ) && (int) $settings['length'] >= 0 && (int) $settings['length'] <= 5 ) {
			$new_settings['length'] = absint( $settings['length'] );
		} else {
			$new_settings['length'] = 2;
		}

		if ( isset( $settings['temperature'] ) && is_numeric( $settings['temperature'] ) && (float) $settings['temperature'] >= 0 && (float) $settings['temperature'] <= 2 ) {
			$new_settings['temperature'] = abs( (float) $settings['temperature'] );
		} else {
			$new_settings['temperature'] = 1;
		}

		return $new_settings;
	}

	/**
	 * Authenticate our credentials.
	 *
	 * @param string $api_key Api Key.
	 *
	 * @return bool|WP_Error
	 */
	protected function authenticate_credentials( string $api_key = '' ) {
		// Check that we have credentials before hitting the API.
		if ( empty( $api_key ) ) {
			return new WP_Error( 'auth', esc_html__( 'Please enter your API key', 'classifai' ) );
		}

		// Make request to ensure credentials work.
		$request  = new APIRequest( $api_key );
		$response = $request->post(
			$this->chatgpt_url,
			[
				'body' => wp_json_encode(
					[
						'model'    => $this->chatgpt_model,
						'messages' => [
							'role'    => 'user',
							'content' => 'Hello',
						],
					]
				),
			]
		);

		return ! is_wp_error( $response ) ? true : $response;
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
			'length'         => 2,
			'temperature'    => 1,
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
			__( 'Authenticated', 'classifai' )     => $authenticated ? __( 'yes', 'classifai' ) : __( 'no', 'classifai' ),
			__( 'Generate excerpt', 'classifai' )  => $enable_excerpt ? __( 'yes', 'classifai' ) : __( 'no', 'classifai' ),
			__( 'Excerpt length', 'classifai' )    => $settings['length'] ?? 2,
			__( 'Temperature value', 'classifai' ) => $settings['temperature'] ?? 1,
			__( 'Latest response', 'classifai' )   => $this->get_formatted_latest_response(),
		];
	}

	/**
	 * Format the result of most recent request.
	 *
	 * @param mixed $data Response data to format.
	 * @return string
	 */
	private function get_formatted_latest_response( $data ) {
		if ( ! $data ) {
			return __( 'N/A', 'classifai' );
		}

		if ( is_wp_error( $data ) ) {
			return $data->get_error_message();
		}

		return preg_replace( '/,"/', ', "', wp_json_encode( $data ) );
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
			return new WP_Error( 'not_enabled', esc_html__( 'Excerpt generation not currently enabled.', 'classifai' ) );
		}

		$normalizer     = new Normalizer();
		$excerpt_length = $settings['length'] ?? 2;
		$content        = $this->trim_content( $normalizer->normalize( $post_id ), $excerpt_length );

		// Make our API request
		$request  = new APIRequest( $settings['api_key'] ?? '' );
		$response = $request->post(
			$this->chatgpt_url,
			[
				'body' => wp_json_encode(
					[
						'model'       => $this->chatgpt_model,
						'messages'    => [
							'role'    => 'user',
							'content' => 'Summarize the following text into ' . $excerpt_length . ' sentences: ' . $content . '',
						],
						'temperature' => $settings['temperature'] ?? 1,
					]
				),
			]
		);

		set_transient( 'classifai_openai_chatgpt_latest_response', $response, DAY_IN_SECONDS * 30 );

		// TODO: test a positive response works as expected
		return $response;
	}

	/**
	 * Trim our content, if needed.
	 *
	 * @param string $content Content we may need to trim.
	 * @param int    $excerpt_length Length of final excerpt.
	 * @return string
	 */
	public function trim_content( string $content = '', int $excerpt_length = 2 ) {
		/**
		 * We first determine how many tokens, roughly, our excerpt will require.
		 * This is determined by the sentence length of the excerpt requested and how
		 * many tokens are in a sentence.
		 *
		 * We then subtract those tokens from the max number of tokens ChatGPT allows
		 * in a single request. ChatGPT counts both the tokens in the request and in
		 * the response towards that max.
		 *
		 * We then figure out how many characters are in the content and figure out
		 * how many tokens that is, rounding that number up.
		 */
		$excerpt_tokens     = $this->tokens_per_sentence * (int) $excerpt_length;
		$max_content_tokens = $this->max_tokens - $excerpt_tokens;
		$content_tokens     = ceil( mb_strlen( $content ) / $this->characters_in_token );

		// If we don't need to trim, return full content.
		if ( $content_tokens < $max_content_tokens ) {
			return $content;
		}

		/**
		 * Next we determine how many tokens we need to trim by taking the
		 * number of tokens in the content and subtracting the max tokens
		 * we can have.
		 *
		 * Then we convert that token number to characters.
		 *
		 * Finally we determine what the max character length our content
		 * can be and trim it up.
		 */
		$tokens_to_trim     = $content_tokens - $max_content_tokens;
		$characters_to_trim = $tokens_to_trim * $this->characters_in_token;
		$max_content_length = mb_strlen( $content ) - $characters_to_trim;
		$trimmed_content    = mb_substr( $content, 0, $max_content_length );

		// Ensure we our final string ends on a full word instead of truncating in the middle.
		if ( ! preg_match( '/\\W/u', mb_substr( $content, $max_content_length - 1, 2 ) ) ) {
			if ( preg_match( '/.*\\W/u', $trimmed_content, $matches ) ) {
				$trimmed_content = $matches[0];
			}
		}

		return $trimmed_content;
	}

}
