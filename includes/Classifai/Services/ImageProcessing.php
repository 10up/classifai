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
		add_filter( 'attachment_fields_to_edit', [ $this, 'custom_fields_edit' ] );
	}

	/**
	 * Register a common image tag taxonomy
	 */
	protected function register_image_tags_taxonomy() {
		$tax = new ImageTagTaxonomy();
		$tax->register();
		register_taxonomy_for_object_type( 'classifai-image-tags', 'attachment' );
	}

	/**
	 * Removes the UI on attachment modals for all taxonomies introduced by this plugin.
	 *
	 * @param array $form_fields The forms fields being rendered on the modal.
	 *
	 * @return mixed
	 */
	public function custom_fields_edit( $form_fields ) {
		unset( $form_fields['classifai-image-tags'] );
		unset( $form_fields['watson-category'] );
		unset( $form_fields['watson-keyword'] );
		unset( $form_fields['watson-concept'] );
		unset( $form_fields['watson-entity'] );
		return $form_fields;
	}
}
