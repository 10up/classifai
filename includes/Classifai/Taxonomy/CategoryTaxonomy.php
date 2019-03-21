<?php

namespace Classifai\Taxonomy;

/**
 * The Classifai Category Taxonomy.
 *
 * Usage:
 *
 * ```php
 *
 * $taxonomy = new CategoryTaxonomy();
 * $taxonomy->register();
 *
 * ```
 */
class CategoryTaxonomy extends AbstractTaxonomy {

	/**
	 * Get the classifai category taxonomy name.
	 */
	public function get_name() {
		return WATSON_CATEGORY_TAXONOMY;
	}

	/**
	 * Get the classifai category taxonomy label.
	 */
	public function get_singular_label() {
		return esc_html__( 'Watson Category', 'classifai' );
	}

	/**
	 * Get the classifai category taxonomy plural label.
	 */
	public function get_plural_label() {
		return esc_html__( 'Watson Categories', 'classifai' );
	}

	/**
	 * Get the classifai category taxonomy visibility.
	 */
	public function get_visibility() {
		return \Classifai\get_feature_enabled( 'category' ) &&
			\Classifai\get_feature_taxonomy( 'category' ) === $this->get_name();
	}

}
