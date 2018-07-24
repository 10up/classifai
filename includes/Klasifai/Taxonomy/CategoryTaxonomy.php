<?php

namespace Klasifai\Taxonomy;

/**
 * The Klasifai Category Taxonomy.
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
		return esc_html__( 'Watson Category', 'klasifai' );
	}

	public function get_plural_label() {
		return esc_html__( 'Watson Categories', 'klasifai' );
	}

	public function get_visibility() {
		return \Klasifai\get_feature_enabled( 'category' );
	}

}
