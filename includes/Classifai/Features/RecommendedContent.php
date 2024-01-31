<?php

namespace Classifai\Features;

use Classifai\Services\Personalizer as PersonalizerService;
use Classifai\Providers\Azure\Personalizer as PersonalizerProvider;

/**
 * Class RecommendedContent
 */
class RecommendedContent extends Feature {
	/**
	 * ID of the current feature.
	 *
	 * @var string
	 */
	const ID = 'feature_recommended_content';

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->label = __( 'Recommended Content', 'classifai' );

		// Contains all providers that are registered to the service.
		$this->provider_instances = $this->get_provider_instances( PersonalizerService::get_service_providers() );

		// Contains just the providers this feature supports.
		$this->supported_providers = [
			PersonalizerProvider::ID => __( 'Microsoft AI Personalizer', 'classifai' ),
		];
	}

	/**
	 * Get the description for the enable field.
	 *
	 * @return string
	 */
	public function get_enable_description(): string {
		return esc_html__( 'Enables the ability to generate recommended content data for the block.', 'classifai' );
	}

	/**
	 * Returns the default settings for the feature.
	 *
	 * @return array
	 */
	public function get_feature_default_settings(): array {
		return [
			'provider' => PersonalizerProvider::ID,
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
		$provider_id       = $settings['provider'] ?? PersonalizerProvider::ID;
		$provider_instance = $this->get_feature_provider_instance( $provider_id );
		$result            = '';

		if ( PersonalizerProvider::ID === $provider_instance::ID ) {
			/** @var PersonalizerProvider $provider_instance */
			$result = call_user_func_array(
				[ $provider_instance, 'personalizer_send_reward' ],
				[ ...$args ]
			);
		}
	}
}
