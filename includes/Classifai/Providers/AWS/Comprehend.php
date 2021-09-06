<?php
/**
 * Azure Computer vision
 */

namespace Classifai\Providers\AWS;

use Classifai\Providers\Provider;

class Comprehend extends Provider {

	/**
	 * ComputerVision constructor.
	 *
	 * @param string $service The service this class belongs to.
	 */
	public function __construct( $service ) {
		parent::__construct(
			'Amazon Web Services',
			'Comprehend',
			'comprehend',
			$service
		);
	}

	/**
	 * Resets the settings for the Comprehend provider.
	 */
	public function reset_settings() {
		update_option( $this->get_option_name(), $this->get_default_settings() );
	}

	/**
	 * Default settings
	 *
	 * @return array
	 */
	protected function get_default_settings() {
		return [
			'url'     => '',
			'api_key' => '',
		];
	}

	/**
	 * Can the functionality be initialized?
	 *
	 * @return bool
	 */
	public function can_register() {
		// TODO: Implement can_register() method.
		return true;
	}

	/**
	 * Setup fields
	 */
	public function setup_fields_sections() {
		add_settings_section( $this->get_option_name(), $this->provider_service_name, '', $this->get_option_name() );
		add_settings_field(
			'url',
			esc_html__( 'Endpoint URL', 'classifai' ),
			[ $this, 'render_input' ],
			$this->get_option_name(),
			$this->get_option_name(),
			[
				'label_for'  => 'url',
				'input_type' => 'text',
			]
		);
		add_settings_field(
			'api-key',
			esc_html__( 'API Key', 'classifai' ),
			[ $this, 'render_input' ],
			$this->get_option_name(),
			$this->get_option_name(),
			[
				'label_for'  => 'api_key',
				'input_type' => 'text',
			]
		);
	}

	/**
	 * Sanitization
	 *
	 * @param array $settings The settings being saved.
	 *
	 * @return array|mixed
	 */
	public function sanitize_settings( $settings ) {
		$new_settings    = [];
		$settings_errors = [];

		if ( ! empty( $settings['url'] ) ) {
			$new_settings['url'] = esc_url_raw( $settings['url'] );
		}

		if ( ! empty( $settings['api_key'] ) ) {
			$new_settings['api_key'] = sanitize_text_field( $settings['api_key'] );
		}

		if ( empty( $settings['url'] ) || empty( $settings['api-key'] ) ) {
			$settings_errors['classifai-aws-comprehend-credentials-empty'] = __( 'Please enter your credentials', 'classifai' );
		}

		if ( ! empty( $settings_errors ) ) {

			$registered_settings_errors = wp_list_pluck( get_settings_errors( $this->get_option_name() ), 'code' );

			foreach ( $settings_errors as $code => $message ) {

				if ( ! in_array( $code, $registered_settings_errors, true ) ) {
					add_settings_error(
						$this->get_option_name(),
						$code,
						esc_html( $message ),
						'error'
					);
				}
			}
		}

		return $new_settings;

	}
}
