<?php

namespace Classifai\Taxonomy;

/**
 * The ClassifAI Entity Taxonomy.
 *
 * Usage:
 *
 * ```php
 *
 * $taxonomy = new EntityTaxonomy();
 * $taxonomy->register();
 *
 * ```
 */
class EntityTaxonomy extends AbstractTaxonomy {

	/**
	 * Get the ClassifAI entity taxonomy name.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return WATSON_ENTITY_TAXONOMY;
	}

	/**
	 * Get the ClassifAI entity taxonomy label.
	 *
	 * @return string
	 */
	public function get_singular_label(): string {
		return esc_html__( 'Watson Entity', 'classifai' );
	}

	/**
	 * Get the ClassifAI entity taxonomy plural label.
	 *
	 * @return string
	 */
	public function get_plural_label(): string {
		return esc_html__( 'Watson Entities', 'classifai' );
	}

	/**
	 * Get the ClassifAI entity taxonomy visibility.
	 *
	 * @return bool
	 */
	public function get_visibility(): bool {
		return \Classifai\get_feature_enabled( 'entity' ) &&
			\Classifai\get_feature_taxonomy( 'entity' ) === $this->get_name();
	}
}
