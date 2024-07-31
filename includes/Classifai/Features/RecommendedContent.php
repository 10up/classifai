<?php

namespace Classifai\Features;

use Classifai\Services\Personalizer as PersonalizerService;
use Classifai\Providers\Azure\Personalizer as PersonalizerProvider;
use Classifai\Providers\AWS\AmazonPersonalize as PersonalizeProvider;

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
			PersonalizeProvider::ID  => __( 'Amazon AWS Personalize', 'classifai' ),
			PersonalizerProvider::ID => __( 'Microsoft Azure AI Personalizer', 'classifai' ),
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

	/**
	 * Generates feature setting data required for migration from
	 * ClassifAI < 3.0.0 to 3.0.0
	 *
	 * @return array
	 */
	public function migrate_settings() {
		$old_settings = get_option( 'classifai_personalizer', array() );
		$new_settings = $this->get_default_settings();

		if ( isset( $old_settings['enable_recommended_content'] ) ) {
			$new_settings['status'] = $old_settings['enable_recommended_content'];
		}

		$new_settings['provider'] = PersonalizerProvider::ID;

		if ( isset( $old_settings['url'] ) ) {
			$new_settings[ PersonalizerProvider::ID ]['endpoint_url'] = $old_settings['url'];
		}

		if ( isset( $old_settings['api_key'] ) ) {
			$new_settings[ PersonalizerProvider::ID ]['api_key'] = $old_settings['api_key'];
		}

		if ( isset( $old_settings['authenticated'] ) ) {
			$new_settings[ PersonalizerProvider::ID ]['authenticated'] = $old_settings['authenticated'];
		}

		if ( isset( $old_settings['recommended_content_roles'] ) ) {
			$new_settings['roles'] = $old_settings['recommended_content_roles'];
		}

		if ( isset( $old_settings['recommended_content_users'] ) ) {
			$new_settings['users'] = $old_settings['recommended_content_users'];
		}

		if ( isset( $old_settings['recommended_content_user_based_opt_out'] ) ) {
			$new_settings['user_based_opt_out'] = $old_settings['recommended_content_user_based_opt_out'];
		}

		return $new_settings;
	}
}
