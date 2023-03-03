<?php
/**
 * OpenAI ChatGPT integration
 */

namespace Classifai\Providers\OpenAI;

use Classifai\Providers\Provider;
use Classifai\Providers\OpenAI\APIRequest;
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
		$options = get_option( $this->get_option_name() );

		if ( empty( $options ) || ( isset( $options['authenticated'] ) && false === $options['authenticated'] ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Register what we need for the plugin.
	 */
	public function register() {
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

			// For response code 429, credentials are valid but rate limit is reached.
			if ( 429 === (int) $authenticated->get_error_code() ) {
				$new_settings['authenticated'] = true;
			}

			$new_settings['authenticated'] = false;
		} else {
			$new_settings['authenticated'] = true;
		}

		$new_settings['api_key'] = sanitize_text_field( $settings['api_key'] ?? '' );

		return $new_settings;
	}

	/**
	 * Authenticate our credentials.
	 *
	 * @param string $api_key Api Key.
	 *
	 * @return bool|WP_Error
	 */
	protected function authenticate_credentials( $api_key = '' ) {
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
			'authenticated' => false,
			'api_key'       => '',
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

		$authenticated = 1 === intval( $settings['authenticated'] ?? 0 );

		return [
			__( 'Authenticated', 'classifai' ) => $authenticated ? __( 'yes', 'classifai' ) : __( 'no', 'classifai' ),
		];
	}

}
