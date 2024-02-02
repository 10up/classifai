<?php
/**
 *  Abstract class that defines the providers for a service.
 */

namespace Classifai\Providers;

abstract class Provider {

	/**
	 * @var string The ID of the provider.
	 *
	 * To be set in the subclass.
	 */
	const ID = '';

	/**
	 * Feature instance.
	 *
	 * @var \Classifai\Features\Feature
	 */
	protected $feature_instance = null;

	/**
	 * Format the result of most recent request.
	 *
	 * @param array|WP_Error $data Response data to format.
	 * @return string
	 */
	protected function get_formatted_latest_response( $data ): string {
		if ( ! $data ) {
			return __( 'N/A', 'classifai' );
		}

		if ( is_wp_error( $data ) ) {
			return $data->get_error_message();
		}

		return preg_replace( '/,"/', ', "', wp_json_encode( $data ) );
	}

	/**
	 * Adds an API key field.
	 *
	 * @param array $args API key field arguments.
	 */
	public function add_api_key_field( array $args = [] ) {
		$default_settings = $this->feature_instance->get_settings();
		$default_settings = $default_settings[ static::ID ];
		$id               = $args['id'] ?? 'api_key';

		add_settings_field(
			$id,
			$args['label'] ?? esc_html__( 'API Key', 'classifai' ),
			[ $this->feature_instance, 'render_input' ],
			$this->feature_instance->get_option_name(),
			$this->feature_instance->get_option_name() . '_section',
			[
				'option_index'  => static::ID,
				'label_for'     => $id,
				'input_type'    => 'password',
				'default_value' => $default_settings[ $id ],
				'class'         => 'classifai-provider-field hidden provider-scope-' . static::ID, // Important to add this.
			]
		);
	}

}
