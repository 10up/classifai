<?php

namespace Classifai\Features;

use Classifai\Services\LanguageProcessing;
use \Classifai\Providers\OpenAI\DallE;

/**
 * Class TitleGeneration
 */
class ImageGeneration extends Feature {
	/**
	 * ID of the current feature.
	 *
	 * @var string
	 */
	const ID = 'feature_image_generation';

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct();

		/**
		 * Every feature must set the `provider_instances` variable with the list of provider instances
		 * that are registered to a service.
		 */
		$service_providers        = LanguageProcessing::get_service_providers();
		$this->provider_instances = $this->get_provider_instances( $service_providers );
	}

	/**
	 * Returns the label of the feature.
	 *
	 * @return string
	 */
	public function get_label() {
		return apply_filters(
			'classifai_' . static::ID . '_label',
			__( 'Image Generation', 'classifai' )
		);
	}

	/**
	 * Returns the providers supported by the feature.
	 *
	 * @return array
	 */
	public function get_providers() {
		return apply_filters(
			'classifai_' . static::ID . '_providers',
			[
				DallE::ID => __( 'OpenAI Dall-E', 'classifai' ),
			]
		);
	}

	/**
	 * Assigns user roles to the $roles array.
	 */
	public function setup_roles() {
		$default_settings = $this->get_default_settings();

		// Get all roles that have the upload_files cap.
		$roles = get_editable_roles() ?? [];
		$roles = array_filter(
			$roles,
			function( $role ) {
				return isset( $role['capabilities'], $role['capabilities']['upload_files'] ) && $role['capabilities']['upload_files'];
			}
		);
		$roles = array_combine( array_keys( $roles ), array_column( $roles, 'name' ) );

		/**
		 * Filter the allowed WordPress roles for DALL·E
		 *
		 * @since 2.3.0
		 * @hook classifai_feature_image_generation_roles
		 *
		 * @param {array} $roles            Array of arrays containing role information.
		 * @param {array} $default_settings Default setting values.
		 *
		 * @return {array} Roles array.
		 */
		$this->roles = apply_filters( 'classifai_' . static::ID . '_roles', $roles, $default_settings );
	}

	/**
	 * Sets up the fields and sections for the feature.
	 */
	public function setup_fields_sections() {
		$settings = $this->get_settings();

		/*
		 * These are the feature-level fields that are
		 * independent of the provider.
		 */
		add_settings_section(
			$this->get_option_name() . '_section',
			esc_html__( 'Feature settings', 'classifai' ),
			'__return_empty_string',
			$this->get_option_name()
		);

		add_settings_field(
			'status',
			esc_html__( 'Enable image generation', 'classifai' ),
			[ $this, 'render_input' ],
			$this->get_option_name(),
			$this->get_option_name() . '_section',
			[
				'label_for'     => 'status',
				'input_type'    => 'checkbox',
				'default_value' => $settings['status'],
				'description'   => __( 'When enabled, a new Generate images tab will be shown in the media upload flow, allowing you to generate and import images.', 'classifai' ),
			]
		);

		// Add user/role-based access fields.
		$this->add_access_control_fields();

		add_settings_field(
			'provider',
			esc_html__( 'Select a provider', 'classifai' ),
			[ $this, 'render_select' ],
			$this->get_option_name(),
			$this->get_option_name() . '_section',
			[
				'label_for'     => 'provider',
				'options'       => $this->get_providers(),
				'default_value' => $settings['provider'],
			]
		);

		/*
		 * The following renders the fields of all the providers
		 * that are registered to the feature.
		 */
		$this->render_provider_fields();
	}

	/**
	 * Returns true if the feature meets all the criteria to be enabled.
	 *
	 * @return boolean
	 */
	public function is_feature_enabled() {
		$settings           = $this->get_settings();
		$is_feature_enabled = parent::is_feature_enabled() && current_user_can( 'upload_files' );

		/** This filter is documented in includes/Classifai/Features/Feature.php */
		return apply_filters( 'classifai_' . static::ID . '_is_feature_enabled', $is_feature_enabled, $settings );
	}

	/**
	 * Returns the default settings for the feature.
	 *
	 * The root-level keys are the setting keys that are independent of the provider.
	 * Provider specific settings should be nested under the provider key.
	 *
	 * @todo Add a filter hook to allow other plugins to add their own settings.
	 *
	 * @return array
	 */
	protected function get_default_settings() {
		$provider_settings = $this->get_provider_default_settings();
		$feature_settings  = [
			'provider' => DallE::ID,
		];

		return apply_filters(
			'classifai_' . static::ID . '_get_default_settings',
			array_merge(
				parent::get_default_settings(),
				$feature_settings,
				$provider_settings
			)
		);
	}

	/**
	 * Sanitizes the settings before saving.
	 *
	 * @param array $new_settings The settings to be sanitized on save.
	 *
	 * @return array
	 */
	public function sanitize_settings( $new_settings ) {
		$settings = $this->get_settings();

		// Sanitization of the feature-level settings.
		$new_settings = parent::sanitize_settings( $new_settings );

		// Sanitization of the provider-level settings.
		$provider_instance = $this->get_feature_provider_instance( $new_settings['provider'] );
		$new_settings      = $provider_instance->sanitize_settings( $new_settings );

		return apply_filters(
			'classifai_' . static::ID . '_sanitize_settings',
			$new_settings,
			$settings
		);
	}

	/**
	 * Runs the feature.
	 *
	 * @param mixed ...$args Arguments required by the feature depending on the provider selected.
	 *
	 * @return mixed
	 */
	public function run( ...$args ) {
		$settings          = $this->get_settings();
		$provider_id       = $settings['provider'] ?? DallE::ID;
		$provider_instance = $this->get_feature_provider_instance( $provider_id );
		$result            = '';

		if ( DallE::ID === $provider_instance::ID ) {
			/** @var DallE $provider_instance */
			$result = call_user_func_array(
				[ $provider_instance, 'generate_image' ],
				[ ...$args ]
			);
		}

		return apply_filters(
			'classifai_' . static::ID . '_run',
			$result,
			$provider_instance,
			$args,
			$this
		);
	}
}
