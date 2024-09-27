<?php
/**
 * Service definition for Recommended Content
 */

namespace Classifai\Services;

class Personalizer extends Service {

	/**
	 * Personalizer constructor.
	 */
	public function __construct() {
		parent::__construct(
			__( 'Recommendation Service', 'classifai' ),
			'personalizer',
			self::get_service_providers()
		);
	}

	/**
	 * Get service providers for Recommendation service.
	 *
	 * @return array
	 */
	public static function get_service_providers(): array {
		/**
		 * Filter the service providers for Recommendation service.
		 *
		 * @since 3.0.0
		 * @hook classifai_recommendation_service_providers
		 *
		 * @param {array} $providers Array of available providers for the service.
		 *
		 * @return {array} The filtered available providers.
		 */
		return apply_filters(
			'classifai_recommendation_service_providers',
			[
				'Classifai\Providers\AWS\AmazonPersonalize',
				'Classifai\Providers\Azure\Personalizer',
			]
		);
	}
}
