<?php
/**
 * Service definition for Language Processing
 */

namespace Classifai\Services;

use Classifai\Taxonomy\ImageTagTaxonomy;

class ImageProcessing extends Service {

	/**
	 * ImageProcessing constructor.
	 */
	public function __construct() {
		parent::__construct(
			'Image Processing',
			'image_processing',
			[
				'Classifai\Providers\Azure\ComputerVision',
			]
		);
	}

	/**
	 * Register the Image Tags taxonomy along with
	 */
	public function init() {
		parent::init();
		$this->register_image_tags_taxonomy();
	}

	/**
	 * Register a common image tag taxonomy
	 */
	protected function register_image_tags_taxonomy() {
		$tax = new ImageTagTaxonomy();
		$tax->register();
		register_taxonomy_for_object_type( 'classifai-image-tags', 'attachment' );
	}
}
