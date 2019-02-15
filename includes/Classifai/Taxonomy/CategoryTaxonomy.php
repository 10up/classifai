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

	public function get_name() {
		return WATSON_CATEGORY_TAXONOMY;
	}

	public function get_singular_label() {
		return esc_html__( 'Watson Category', 'classifai' );
	}

	public function get_plural_label() {
		return esc_html__( 'Watson Categories', 'classifai' );
	}

	public function get_visibility() {
		return \Classifai\get_feature_enabled( 'category' ) &&
			\Classifai\get_feature_taxonomy( 'category' ) === $this->get_name();
	}

}
