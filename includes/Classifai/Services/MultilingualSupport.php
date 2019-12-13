<?php
/**
 * Service definition for Language Processing
 */

namespace Classifai\Services;

class MultilingualSupport extends Service {

	/**
	 * LanguageProcessing constructor.
	 */
	public function __construct() {
		parent::__construct( __( 'Multilingual Support', 'classifai' ), 'multilingual_support', [ 'Classifai\Providers\Watson\LanguageTranslator' ] );
	}
}
