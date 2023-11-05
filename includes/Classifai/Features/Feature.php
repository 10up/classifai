<?php
namespace Classifai\Features;

use \Classifai\Services\Service;
use \Classifai\Settings;

/**
 * Feature abstract class
 */
abstract class Feature {
	/**
	 * ID of the feature.
	 * To be set in the subclass.
	 *
	 * @var string
	 */
	const ID = '';

	/**
	 * Roles that can use this feature.
	 */
	public $roles = [];

	/**
	 * Title of the feature. To be used in the UI.
	 *
	 * @var string
	 */
	protected $title = '';

	/**
	 * Settings instance for the feature.
	 */
	protected $feature_settings = null;

	/**
	 * Settings for the providers.
	 */
	protected $provider_settings = [];

	/**
	 * Constructor
	 */
	public function __construct( $provider_classes ) {
		$this->roles            = get_editable_roles() ?? [];
		$this->roles            = array_combine( array_keys( $this->roles ), array_column( $this->roles, 'name' ) );
		$this->feature_settings = new Settings( $this );
		$context                = \Classifai\get_admin_context();

		// We populate the $provider_settings array with the settings
		// for each provider for the current feature.
		foreach ( $provider_classes as $provider_class ) {
			$provider_class->set_feature_instance( $this );
			$provider_settings = $provider_class->get_settings_data();

			if ( isset( $provider_settings[ $context->feature ] ) ) {
				$this->provider_settings[ $provider_class::ID ] = $provider_settings[ $context->feature ];
			}
		}

		/**
		 * Filter the allowed WordPress roles for ChatGTP
		 *
		 * @since 2.3.0
		 * @hook classifai_chatgpt_allowed_roles
		 *
		 * @param {array} $roles            Array of arrays containing role information.
		 * @param {array} $default_settings Default setting values.
		 *
		 * @return {array} Roles array.
		 */
		$this->roles = apply_filters( 'classifai_' . static::ID . '_allowed_roles', $this->roles, $this->feature_settings );

		add_action( 'admin_init', [ $this, 'register_settings' ] );
	}

	/**
	 * Returns array of settings data structure.
	 * Used to render settings fields for the feature.
	 *
	 * @return array
	 */
	abstract protected function get_settings_data();

	/**
	 * Sanitization method for the feature settings.
	 *
	 * @return array
	 */
	abstract public function sanitize_settings( $settings );

	/**
	 * Returns an array of providers supported by the feature.
	 *
	 * @return array
	 */
	abstract public function get_providers();

	/**
	 * Returns the title of the feature.
	 *
	 * @return string
	 */
	public function get_title() {
		return apply_filters(
			'classifai_' . static::ID . '_title',
			$this->title
		);
	}

	/**
	 * Registers settings for each feature.
	 * All feature settings are under the same options group.
	 */
	public function register_settings() {
		// register_setting( Service::SETTINGS_GROUP, $this->feature_settings->get_option_name(), [ $this, 'sanitize_settings' ] );
		register_setting( Service::SETTINGS_GROUP . '_' . static::ID, $this->feature_settings->get_option_name(), [ $this, 'sanitize_settings' ] );
	}

	/**
	 * Returns the provider settings for the feature.
	 *
	 * @return array
	 */
	public function get_provider_settings() {
		$providers = $this->get_providers();
		$settings = [];

		foreach ( $providers as $provider ) {
			$provider_id = $provider['value'];
			$settings[ $provider_id ] = $this->provider_settings[ $provider_id ];
		}

		return $settings;
	}
}
