<?php
/**
 * Azure Language Provider
 *
 * @package Classifai
 */

namespace Classifai\Providers\Azure;

use Classifai\Providers\Provider;
use WP_Error;

class Language extends Provider {
	/**
	 * The Provider ID.
	 *
	 * Required and should be unique.
	 */
	const ID = 'azure_language';

	/**
	 * MyProvider constructor.
	 *
	 * @param \Classifai\Features\Feature $feature_instance The feature instance.
	 */
	public function __construct( $feature_instance = null ) {
		$this->feature_instance = $feature_instance;
	}

	/**
	 * This method will be called by the feature to render the fields
	 * required by the provider, such as API key, endpoint URL, etc.
	 *
	 * This should also register settings that are required for the feature
	 * to work.
	 */
	public function render_provider_fields() {
		$settings = $this->feature_instance->get_settings( static::ID );
		$id       = 'endpoint_url';

		$this->add_api_key_field();
		add_settings_field(
			$id,
			$args['label'] ?? esc_html__( 'Endpoint URL', 'classifai' ),
			[ $this->feature_instance, 'render_input' ],
			$this->feature_instance->get_option_name(),
			$this->feature_instance->get_option_name() . '_section',
			[
				'option_index'  => static::ID,
				'label_for'     => $id,
				'input_type'    => 'text',
				'default_value' => $settings[ $id ] ?? '',
				'class'         => 'classifai-provider-field hidden provider-scope-' . static::ID, // Important to add this.
				'description'   => sprintf(
					wp_kses(
						// translators: 1 - link to create a Language resource.
						__( 'Azure Cognitive Service Language Endpoint, <a href="%1$s" target="_blank">create a Language resource</a> in the Azure portal to get your key and endpoint.', 'classifai' ),
						array(
							'a' => array(
								'href'   => array(),
								'target' => array(),
							),
						)
					),
					esc_url( 'https://portal.azure.com/#home' )
				),
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
			'uses_prompt'   => false,
		];

		return $common_settings;
	}

	/**
	 * Sanitize the settings for this provider.
	 *
	 * Can also be useful to verify the Provider API connection
	 * works as expected here, returning an error if needed.
	 *
	 * @param array $new_settings The settings array.
	 * @return array
	 */
	public function sanitize_settings( array $new_settings ): array {
		$settings = $this->feature_instance->get_settings();

		// Ensure proper validation of credentials happens here.
		$new_settings[ static::ID ]['api_key']       = sanitize_text_field( $new_settings[ static::ID ]['api_key'] ?? $settings[ static::ID ]['api_key'] );
		$new_settings[ static::ID ]['authenticated'] = true;

		return $new_settings;
	}

	/**
	 * Common entry point for all REST endpoints for this provider.
	 *
	 * All Features will end up calling the rest_endpoint_callback method for their assigned Provider.
	 * This method should validate the route that is being called and then call the appropriate method
	 * for that route. This method typically will validate we have all the requried data and if so,
	 * make a request to the appropriate API endpoint.
	 *
	 * @param int    $post_id The Post ID we're processing.
	 * @param string $route_to_call The route we are processing.
	 * @param array  $args Optional arguments to pass to the route.
	 * @return string|WP_Error
	 */
	public function rest_endpoint_callback( $post_id = 0, string $route_to_call = '', array $args = [] ) {
		if ( ! $post_id || ! get_post( $post_id ) ) {
			return new WP_Error( 'post_id_required', esc_html__( 'A valid post ID is required to generate an excerpt.', 'text-domain' ) );
		}

		$route_to_call = strtolower( $route_to_call );
		$return        = '';

		// Handle all of our routes.
		switch ( $route_to_call ) {
			case 'test':
				// Ensure this method exists.
				$return = $this->generate( $post_id, $args );
				break;
		}

		return $return;
	}

	/**
	 * Returns the debug information for the provider settings.
	 *
	 * This is used to display various settings in the Site Health screen.
	 * Not required but useful for debugging.
	 *
	 * @return array
	 */
	public function get_debug_information(): array {
		$settings   = $this->feature_instance->get_settings();
		$debug_info = [];

		return $debug_info;
	}
}
