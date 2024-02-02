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
	 * @var string The display name for the provider, i.e. Azure
	 */
	public $provider_name;

	/**
	 * @var string $provider_service_name Formal name of the provider, i.e AI Vision, NLU, Rekongnition.
	 */
	public $provider_service_name;

	/**
	 * Feature instance.
	 *
	 * @var \Classifai\Features\Feature
	 */
	protected $feature_instance = null;

	/**
	 * @var array $features Array of features provided by this provider.
	 */
	protected $features = array();


	/**
	 * Provides the provider name.
	 *
	 * @return string
	 */
	public function get_provider_name(): string {
		return $this->provider_name;
	}

	/**
	 * Get provider features.
	 *
	 * @return array
	 */
	public function get_features(): array {
		return $this->features;
	}

	/**
	 * Default settings for Provider.
	 *
	 * @return array
	 */
	public function get_default_settings(): array {
		return [];
	}

	/**
	 * Common entry point for all REST endpoints for this provider.
	 *
	 * @param mixed  $item The item we're processing.
	 * @param string $route_to_call The name of the route we're going to be processing.
	 * @param array  $args Optional arguments to pass to the route.
	 * @return mixed
	 */
	public function rest_endpoint_callback( $item, string $route_to_call, array $args = [] ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		return null;
	}

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
