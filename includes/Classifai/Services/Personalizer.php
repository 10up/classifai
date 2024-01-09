<?php
/**
 * Service definition for Recommended Content
 */

namespace Classifai\Services;

use WP_REST_Server;
use WP_REST_Request;
use WP_Error;
use function Classifai\find_provider_class;

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
	 * Register the rest API endpoints
	 */
	public function init() {
		parent::init();
	}

	/**
	 * Get service providers for Recommendation service.
	 *
	 * @return array
	 */
	public static function get_service_providers() {
		return apply_filters(
			'classifai_recommendation_service_providers',
			[
				'Classifai\Providers\Azure\Personalizer',
			]
		);
	}
}
