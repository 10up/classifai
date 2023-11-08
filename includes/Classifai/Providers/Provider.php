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
	 * @var string The display name for the provider. ie. Azure
	 */
	public $provider_name;


	/**
	 * @var string $provider_service_name The formal name of the service being provided. i.e Computer Vision, NLU, Rekongnition.
	 */
	public $provider_service_name;

	/**
	 * @var string $option_name Name of the option where the provider settings are stored.
	 */
	protected $option_name;


	/**
	 * @var string $service The name of the service this provider belongs to.
	 */
	protected $service;

	/**
	 * @var array $onboarding The onboarding options for this provider.
	 */
	public $onboarding_options;

	/**
	 * Feature instance.
	 *
	 * @var \Classifai\Features\Feature
	 */
	protected $feature_instance = null;

	/**
	 * Provider constructor.
	 *
	 * @param string $provider_name         The name of the Provider that will appear in the admin tab
	 * @param string $provider_service_name The name of the Service.
	 * @param string $option_name           Name of the option where the provider settings are stored.
	 */
	public function __construct( $provider_name, $provider_service_name, $option_name ) {
		$this->provider_name         = $provider_name;
		$this->provider_service_name = $provider_service_name;
		$this->option_name           = $option_name;
		$this->onboarding_options    = array();
	}

	/**
	 * Provides the provider name.
	 *
	 * @return string
	 */
	public function get_provider_name() {
		return $this->provider_name;
	}

	/** Returns the name of the settings section for this provider
	 *
	 * @return string
	 */
	public function get_settings_section() {
		return $this->option_name;
	}

	/**
	 * Get the option name.
	 *
	 * @return string
	 */
	public function get_option_name() {
		return 'classifai_' . $this->option_name;
	}

	/**
	 * Get the onboarding options.
	 *
	 * @return array
	 */
	public function get_onboarding_options() {
		if ( empty( $this->onboarding_options ) || ! isset( $this->onboarding_options['features'] ) ) {
			return array();
		}

		$settings      = $this->get_settings();
		$is_configured = $this->is_configured();

		foreach ( $this->onboarding_options['features'] as $key => $title ) {
			$enabled = isset( $settings[ $key ] ) ? 1 === absint( $settings[ $key ] ) : false;
			if ( count( explode( '__', $key ) ) > 1 ) {
				$keys    = explode( '__', $key );
				$enabled = isset( $settings[ $keys[0] ][ $keys[1] ] ) ? 1 === absint( $settings[ $keys[0] ][ $keys[1] ] ) : false;
			}
			// Handle enable_image_captions
			if ( 'enable_image_captions' === $key ) {
				$enabled = isset( $settings['enable_image_captions']['alt'] ) && 'alt' === $settings['enable_image_captions']['alt'];
			}
			$enabled = $enabled && $is_configured;

			$this->onboarding_options['features'][ $key ] = array(
				'title'   => $title,
				'enabled' => $enabled,
			);
		}

		return $this->onboarding_options;
	}

	/**
	 * Can the Provider be initialized?
	 */
	public function can_register() {
		return $this->is_configured();
	}

	/**
	 * Register the functionality for the Provider.
	 */
	abstract public function register();

	/**
	 * Initialization routine
	 */
	public function register_admin() {
		add_action( 'admin_init', [ $this, 'setup_fields_sections' ] );
	}

	/**
	 * Helper to get the settings and allow for settings default values.
	 *
	 * @param string|bool|mixed $index Optional. Name of the settings option index.
	 *
	 * @return string|array|mixed
	 */
	public function get_settings( $index = false ) {
		$defaults = $this->get_default_settings();
		$settings = get_option( $this->get_option_name(), [] );
		$settings = wp_parse_args( $settings, $defaults );

		if ( $index && isset( $settings[ $index ] ) ) {
			return $settings[ $index ];
		}

		return $settings;
	}

	/**
	 * Returns the default settings.
	 *
	 * @return array
	 */
	public function get_default_settings() {
		return [];
	}

	/**
	 * Set up the fields for each section.
	 */
	abstract public function setup_fields_sections();

	/**
	 * Sanitization
	 *
	 * @param array $settings The settings being saved.
	 */
	abstract public function sanitize_settings( $settings );

	/**
	 * Provides debug information related to the provider.
	 *
	 * @return string|array Debug info to display on the Site Health screen. Accepts a string or key-value pairs.
	 * @since 1.4.0
	 */
	abstract public function get_provider_debug_information();

	/**
	 * Common entry point for all REST endpoints for this provider.
	 * This is called by the Service.
	 *
	 * @param int    $post_id       The Post Id we're processing.
	 * @param string $route_to_call The name of the route we're going to be processing.
	 * @param array  $args          Optional arguments to pass to the route.
	 *
	 * @return mixed
	 */
	public function rest_endpoint_callback( $post_id, $route_to_call, $args = [] ) {
		return null;
	}

	/**
	 * Format the result of most recent request.
	 *
	 * @param array|WP_Error $data Response data to format.
	 *
	 * @return string
	 */
	protected function get_formatted_latest_response( $data ) {
		if ( ! $data ) {
			return __( 'N/A', 'classifai' );
		}

		if ( is_wp_error( $data ) ) {
			return $data->get_error_message();
		}

		return preg_replace( '/,"/', ', "', wp_json_encode( $data ) );
	}

	/**
	 * Returns whether the provider is configured or not.
	 *
	 * @return bool
	 */
	public function is_configured() {
		$settings = $this->get_settings();

		$is_configured = false;
		if ( ! empty( $settings ) && ! empty( $settings['authenticated'] ) ) {
			$is_configured = true;
		}

		return $is_configured;
	}

	/**
	 * Adds an api key field.
	 *
	 * @param array $args API key field arguments.
	 */
	public function add_api_key_field( $args = [] ) {
		$default_settings = $this->feature_instance->get_default_settings();
		$default_settings = $default_settings[ static::ID ];
		$id = $args['id'] ?? 'api_key';

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
				'data_attr'     => [
					'provider-scope' => [ static::ID ],
				],
			]
		);
	}
}
