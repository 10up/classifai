<?php

namespace Classifai\Features;

use Classifai\Providers\Azure\ComputerVision;
use Classifai\Services\ImageProcessing;

/**
 * Class ImageCropping
 */
class ImageCropping extends Feature {
	/**
	 * ID of the current feature.
	 *
	 * @var string
	 */
	const ID = 'feature_image_cropping';

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->label = __( 'Image Cropping', 'classifai' );

		// Contains all providers that are registered to the service.
		$this->provider_instances = $this->get_provider_instances( ImageProcessing::get_service_providers() );

		// Contains just the providers this feature supports.
		$this->supported_providers = [
			ComputerVision::ID => __( 'Microsoft Azure AI Vision', 'classifai' ),
		];
	}

	/**
	 * Get the description for the enable field.
	 *
	 * @return string
	 */
	public function get_enable_description(): string {
		return esc_html__( 'AI Vision detects and saves the most visually interesting part of your image (i.e., faces, animals, notable text).', 'classifai' );
	}

	/**
	 * Returns the default settings for the feature.
	 *
	 * @return array
	 */
	public function get_feature_default_settings(): array {
		return [
			'provider' => ComputerVision::ID,
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
		$provider_id       = $settings['provider'] ?? ComputerVision::ID;
		$provider_instance = $this->get_feature_provider_instance( $provider_id );
		$result            = '';

		if ( ComputerVision::ID === $provider_instance::ID ) {
			/** @var ComputerVision $provider_instance */
			$result = call_user_func_array(
				[ $provider_instance, 'smart_crop_image' ],
				[
					...$args,
					$this,
				]
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
