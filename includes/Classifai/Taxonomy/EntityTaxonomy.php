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
	 */
	public function get_name() {
		return WATSON_ENTITY_TAXONOMY;
	}

	/**
	 * Get the ClassifAI entity taxonomy label.
	 */
	public function get_singular_label() {
		return esc_html__( 'Watson Entity', 'classifai' );
	}

	/**
	 * Get the ClassifAI entity taxonomy plural label.
	 */
	public function get_plural_label() {
		return esc_html__( 'Watson Entities', 'classifai' );
	}

	/**
	 * Get the ClassifAI entity taxonomy visibility.
	 */
	public function get_visibility() {
		return \Classifai\get_feature_enabled( 'entity' ) &&
			\Classifai\get_feature_taxonomy( 'entity' ) === $this->get_name();
	}

}
