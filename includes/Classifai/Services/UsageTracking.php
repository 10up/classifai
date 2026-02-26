<?php
/**
 * Service definition for Usage Tracking
 */

namespace Classifai\Services;

class UsageTracking extends Service {

	/**
	 * UsageTracking constructor.
	 */
	public function __construct() {
		parent::__construct(
			__( 'Usage Tracking', 'classifai' ),
			'usage_tracking',
			self::get_service_providers()
		);
	}

	/**
	 * Get service providers for Usage Tracking service.
	 *
	 * @return array
	 */
	public static function get_service_providers(): array {
		/**
		 * Filter the service providers for Recommendation service.
		 *
		 * @since x.x.x
		 * @hook classifai_usage_tracking_service_providers
		 *
		 * @param array $providers Array of available providers for the service.
		 *
		 * @return array The filtered available providers.
		 */
		return apply_filters(
			'classifai_usage_tracking_service_providers',
			[
				'Classifai\Providers\OpenAI\UsageTracking',
			]
		);
	}
}
