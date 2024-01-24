<?php

namespace Classifai\Features;

use Classifai\Services\LanguageProcessing;
use Classifai\Providers\OpenAI\DallE;

/**
 * Class ImageGeneration
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
		$this->label = __( 'Image Generation', 'classifai' );

		// Contains all providers that are registered to the service.
		$this->provider_instances = $this->get_provider_instances( LanguageProcessing::get_service_providers() );

		// Contains just the providers this feature supports.
		$this->supported_providers = [
			DallE::ID => __( 'OpenAI Dall-E', 'classifai' ),
		];
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
			function ( $role ) {
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
	 * Get the description for the enable field.
	 *
	 * @return string
	 */
	public function get_enable_description(): string {
		return esc_html__( 'When enabled, a new Generate images tab will be shown in the media upload flow, allowing you to generate and import images.', 'classifai' );
	}

	/**
	 * Returns true if the feature meets all the criteria to be enabled.
	 *
	 * @return bool
	 */
	public function is_feature_enabled(): bool {
		$settings           = $this->get_settings();
		$is_feature_enabled = parent::is_feature_enabled() && current_user_can( 'upload_files' );

		/** This filter is documented in includes/Classifai/Features/Feature.php */
		return apply_filters( 'classifai_' . static::ID . '_is_feature_enabled', $is_feature_enabled, $settings );
	}

	/**
	 * Returns the default settings for the feature.
	 *
	 * @return array
	 */
	public function get_feature_default_settings(): array {
		return [
			'provider' => DallE::ID,
		];
	}

	/**
	 * Runs the feature.
	 *
	 * @param mixed ...$args Arguments required by the feature depending on the provider selected.
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
