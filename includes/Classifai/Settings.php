<?php

namespace Classifai;

class Settings {
	/**
	 * Context of the settings.
	 * For eg; feature or provider instance.
	 */
	public $context = null;

	public function __construct( $context ) {
		$this->context = $context;
	}

	/**
	 * Returns the option name for the feature setting.
	 *
	 * @return string
	 */
	public function get_option_name() {
		return 'classifai_' . $this->context::ID;
	}

	/**
	 * Returns all the settings for the feature.
	 * This method returns the default settings if no settings are found.
	 *
	 * @return array
	 */
	public function get_settings() {
		$settings         = get_option( $this->get_option_name(), [] );
		$default_settings = [];

		foreach ( $this->context->get_settings_data() as $key => $setting ) {
			$default_settings[ $key ] = $setting['value'] ?? '';
		}

		return wp_parse_args( $settings, $default_settings );
	}

	/**
	 * Returns a specific setting for a feature by key.
	 * For example, 'status' or 'roles'.
	 *
	 * Returns false if no setting is found.
	 *
	 * @return boolean|mixed
	 */
	public function get_setting( $key ) {
		$settings = get_option( $this->get_option_name(), [] );

		if ( isset( $settings[ $key ] ) ) {
			return $settings[ $key ];
		}

		return false;
	}
}
