<?php
/**
 * Service definition for Language Processing
 */

namespace Classifai\Services;

class Personalizer extends Service {

	/**
	 * Personalizer constructor.
	 */
	public function __construct() {
		parent::__construct(
			__( 'Recommended Content', 'classifai' ),
			'personalizer',
			[
				'Classifai\Providers\Azure\Personalizer',
			]
		);
	}
}
