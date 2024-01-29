<?php

namespace Classifai\Taxonomy;

use function Classifai\Providers\Watson\get_feature_enabled;
use function Classifai\Providers\Watson\get_feature_taxonomy;

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
	 * Get the ClassifAI category taxonomy name.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return WATSON_CATEGORY_TAXONOMY;
	}

	/**
	 * Get the ClassifAI category taxonomy label.
	 *
	 * @return string
	 */
	public function get_singular_label(): string {
		return esc_html__( 'Watson Category', 'classifai' );
	}

	/**
	 * Get the ClassifAI category taxonomy plural label.
	 *
	 * @return string
	 */
	public function get_plural_label(): string {
		return esc_html__( 'Watson Categories', 'classifai' );
	}

	/**
	 * Get the ClassifAI category taxonomy visibility.
	 *
	 * @return bool
	 */
	public function get_visibility(): bool {
		return get_feature_enabled( 'category' ) &&
			get_feature_taxonomy( 'category' ) === $this->get_name();
	}
}
