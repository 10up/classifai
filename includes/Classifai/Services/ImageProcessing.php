<?php
/**
 * Service definition for Language Processing
 */

namespace Classifai\Services;

class ImageProcessing extends Service {

	/**
	 * ImageProcessing constructor.
	 */
	public function __construct() {
		parent::__construct( 'Image Processing', 'image_processing', [ 'Classifai\Providers\AzureComputerVision' ] );
	}
}
