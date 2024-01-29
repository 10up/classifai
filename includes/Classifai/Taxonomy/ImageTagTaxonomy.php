<?php

namespace Classifai\Taxonomy;

class ImageTagTaxonomy extends AbstractTaxonomy {

	/**
	 * Get the ClassifAI category taxonomy name.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'classifai-image-tags';
	}

	/**
	 * Get the ClassifAI category taxonomy label.
	 *
	 * @return string
	 */
	public function get_singular_label(): string {
		return esc_html__( 'Image Tag', 'classifai' );
	}

	/**
	 * Get the ClassifAI category taxonomy plural label.
	 *
	 * @return string
	 */
	public function get_plural_label(): string {
		return esc_html__( 'Image Tags', 'classifai' );
	}

	/**
	 * Get the ClassifAI category taxonomy visibility.
	 *
	 * @return bool
	 */
	public function get_visibility(): bool {
		return true;
	}

	/**
	 * Override the update_count_callback because we're using attachments.
	 *
	 * @return string
	 */
	public function update_count_callback(): string {
		return '_update_generic_term_count';
	}
}
