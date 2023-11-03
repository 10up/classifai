<?php
namespace Classifai\Features;

use \Classifai\Services\Service;

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
	 * Default settings for the feature.
	 *
	 * @var array
	 */
	public $feature_settings = [];

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
	 * Constructor
	 */
	public function __construct() {
		$this->feature_settings = $this->get_settings();
		$this->roles            = get_editable_roles() ?? [];
		$this->roles            = array_combine( array_keys( $this->roles ), array_column( $this->roles, 'name' ) );

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
		register_setting( Service::SETTINGS_GROUP, $this->get_option_name(), [ $this, 'sanitize_settings' ] );
	}

	/**
	 * Returns the option name for the feature setting.
	 *
	 * @return string
	 */
	public function get_option_name() {
		return 'classifai_' . static::ID;
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

		foreach ( $this->get_settings_data() as $key => $setting ) {
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
