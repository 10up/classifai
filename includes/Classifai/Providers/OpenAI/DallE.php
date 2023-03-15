<?php
/**
 * OpenAI DALL·E integration
 */

namespace Classifai\Providers\OpenAI;

use Classifai\Providers\Provider;
use Classifai\Providers\OpenAI\APIRequest;
use WP_Error;

class DallE extends Provider {

	/**
	 * OpenAI model URL
	 *
	 * @var string
	 */
	protected $model_url = 'https://api.openai.com/v1/models';

	/**
	 * OpenAI DALL·E URL
	 *
	 * @var string
	 */
	protected $dalle_url = 'https://api.openai.com/v1/images/generations';

	/**
	 * OpenAI DALL·E constructor.
	 *
	 * @param string $service The service this class belongs to.
	 */
	public function __construct( $service ) {
		parent::__construct(
			'OpenAI',
			'DALL·E',
			'openai_dalle',
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
	 * Register what we need for the provider.
	 *
	 * This only fires if can_register returns true.
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
			'enable-image-gen',
			esc_html__( 'Enable image generation', 'classifai' ),
			[ $this, 'render_input' ],
			$this->get_option_name(),
			$this->get_option_name(),
			[
				'label_for'     => 'enable_image_gen',
				'input_type'    => 'checkbox',
				'default_value' => $default_settings['enable_image_gen'],
				'description'   => __( 'Something will happen.', 'classifai' ),
			]
		);
	}

	/**
	 * Sanitization for the options being saved.
	 *
	 * @param array $settings Array of settings about to be saved.
	 * @return array The sanitized settings to be saved.
	 */
	public function sanitize_settings( $settings ) {
		$new_settings  = $this->get_settings();
		$authenticated = $this->authenticate_credentials( $settings['api_key'] ?? '' );

		if ( is_wp_error( $authenticated ) ) {
			$new_settings['authenticated'] = false;
			$error_message                 = $authenticated->get_error_message();

			// For response code 429, credentials are valid but rate limit is reached.
			if ( 429 === (int) $authenticated->get_error_code() ) {
				$new_settings['authenticated'] = true;
				$error_message                 = str_replace( 'plan and billing details', '<a href="https://platform.openai.com/account/billing/overview" target="_blank" rel="noopener">plan and billing details</a>', $error_message );
			} else {
				$error_message = str_replace( 'https://platform.openai.com/account/api-keys', '<a href="https://platform.openai.com/account/api-keys" target="_blank" rel="noopener">https://platform.openai.com/account/api-keys</a>', $error_message );
			}

			add_settings_error(
				'api_key',
				'classifai-auth',
				$error_message,
				'error'
			);
		} else {
			$new_settings['authenticated'] = true;
		}

		$new_settings['api_key'] = sanitize_text_field( $settings['api_key'] ?? '' );

		if ( empty( $settings['enable_image_gen'] ) || 1 !== (int) $settings['enable_image_gen'] ) {
			$new_settings['enable_image_gen'] = 'no';
		} else {
			$new_settings['enable_image_gen'] = '1';
		}

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
			return new WP_Error( 'auth', esc_html__( 'Please enter your OpenAI API key.', 'classifai' ) );
		}

		// Make request to ensure credentials work.
		$request  = new APIRequest( $api_key );
		$response = $request->get( $this->model_url );

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
			'authenticated'    => false,
			'api_key'          => '',
			'enable_image_gen' => false,
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
			__( 'Authenticated', 'classifai' )   => $authenticated ? __( 'yes', 'classifai' ) : __( 'no', 'classifai' ),
			__( 'Latest response', 'classifai' ) => $this->get_formatted_latest_response( 'classifai_openai_chatgpt_latest_response' ),
		];
	}

	/**
	 * Format the result of most recent request.
	 *
	 * @param string $transient Transient that holds our data.
	 * @return string
	 */
	private function get_formatted_latest_response( string $transient = '' ) {
		$data = get_transient( $transient );

		if ( ! $data ) {
			return __( 'N/A', 'classifai' );
		}

		if ( is_wp_error( $data ) ) {
			return $data->get_error_message();
		}

		return preg_replace( '/,"/', ', "', wp_json_encode( $data ) );
	}

}
