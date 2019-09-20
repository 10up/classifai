<?php

namespace Classifai\Taxonomy;

class ImageTagTaxonomy extends AbstractTaxonomy {

	/**
	 * Get the ClassifAI category taxonomy name.
	 */
	public function get_name() {
		return 'classifai-image-tags';
	}

	/**
	 * Get the ClassifAI category taxonomy label.
	 */
	public function get_singular_label() {
		return esc_html__( 'Image Tag', 'classifai' );
	}

	/**
	 * Get the ClassifAI category taxonomy plural label.
	 */
	public function get_plural_label() {
		return esc_html__( 'Image Tags', 'classifai' );
	}

	/**
	 * Get the ClassifAI category taxonomy visibility.
	 */
	public function get_visibility() {
		return true;
	}

	/**
	 * Override the update_count_callback because we're using attachments.
	 *
	 * @return string
	 */
	public function update_count_callback() {
		return '_update_generic_term_count';
	}
}
