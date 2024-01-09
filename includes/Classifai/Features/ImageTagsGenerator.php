<?php

namespace Classifai\Features;

use \Classifai\Providers\Azure\ComputerVision;
use Classifai\Services\ImageProcessing;

/**
 * Class TitleGeneration
 */
class ImageTagsGenerator extends Feature {
	/**
	 * ID of the current feature.
	 *
	 * @var string
	 */
	const ID = 'feature_image_tags_generator';

	/**
	 * Constructor.
	 */
	public function __construct() {
		/**
		 * Every feature must set the `provider_instances` variable with the list of provider instances
		 * that are registered to a service.
		 */
		$service_providers        = ImageProcessing::get_service_providers();
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
			__( 'Image Tags Generator', 'classifai' )
		);
	}

	/**
	 * Returns the providers supported by the feature.
	 *
	 * @internal
	 *
	 * @return array
	 */
	protected function get_providers() {
		return apply_filters(
			'classifai_' . static::ID . '_providers',
			[
				ComputerVision::ID => __( 'Microsoft Azure AI Vision', 'classifai' ),
			]
		);
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
			esc_html__( 'Enable image tag generation', 'classifai' ),
			[ $this, 'render_input' ],
			$this->get_option_name(),
			$this->get_option_name() . '_section',
			[
				'label_for'     => 'status',
				'input_type'    => 'checkbox',
				'default_value' => $settings['status'],
				'description'   => __( 'Image tags will be added automatically.', 'classifai' ),
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
	 * Returns the default settings for the feature.
	 *
	 * The root-level keys are the setting keys that are independent of the provider.
	 * Provider specific settings should be nested under the provider key.
	 *
	 * @internal
	 *
	 * @todo Add a filter hook to allow other plugins to add their own settings.
	 *
	 * @return array
	 */
	protected function get_default_settings() {
		$provider_settings = $this->get_provider_default_settings();
		$feature_settings  = [
			'provider' => ComputerVision::ID,
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
	 * @internal
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
		$provider_id       = $settings['provider'] ?? ComputerVision::ID;
		$provider_instance = $this->get_feature_provider_instance( $provider_id );
		$result            = '';

		if ( ComputerVision::ID === $provider_instance::ID ) {
			/** @var ComputerVision $provider_instance */
			$result = call_user_func_array(
				[ $provider_instance, 'generate_image_tags' ],
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
