<?php
/**
 * Service definition for Language Processing
 */

namespace Classifai\Services;

class LanguageProcessing extends Service {

	/**
	 * LanguageProcessing constructor.
	 */
	public function __construct() {
		parent::__construct( __( 'Language Processing', 'classifai' ), 'language_processing', [ 'Classifai\Providers\Watson\NLU' ] );
	}
}
