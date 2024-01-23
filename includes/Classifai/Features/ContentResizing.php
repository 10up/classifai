<?php

namespace Classifai\Features;

use Classifai\Providers\OpenAI\ChatGPT;
use Classifai\Services\LanguageProcessing;

/**
 * Class ContentResizing
 */
class ContentResizing extends Feature {
	/**
	 * ID of the current feature.
	 *
	 * @var string
	 */
	const ID = 'feature_content_resizing';

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->label = __( 'Content Resizing', 'classifai' );

		// Contains all providers that are registered to the service.
		$this->provider_instances = $this->get_provider_instances( LanguageProcessing::get_service_providers() );

		// Contains just the providers this feature supports.
		$this->supported_providers = [
			ChatGPT::ID => __( 'OpenAI ChatGPT', 'classifai' ),
		];
	}

	/**
	 * Get the description for the enable field.
	 *
	 * @return string
	 */
	public function get_enable_description(): string {
		return esc_html__( '"Condense this text" and "Expand this text" menu items will be added to the paragraph block\'s toolbar menu.', 'classifai' );
	}

	/**
	 * Returns the default settings for the feature.
	 *
	 * @return array
	 */
	public function get_feature_default_settings(): array {
		return [
			'provider' => ChatGPT::ID,
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
		$provider_id       = $settings['provider'] ?? ChatGPT::ID;
		$provider_instance = $this->get_feature_provider_instance( $provider_id );
		$result            = '';

		if ( ChatGPT::ID === $provider_instance::ID ) {
			/** @var ChatGPT $provider_instance */
			$result = call_user_func_array(
				[ $provider_instance, 'resize_content' ],
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
