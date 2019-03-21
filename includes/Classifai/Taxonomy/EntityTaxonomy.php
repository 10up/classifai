<?php

namespace Classifai\Taxonomy;

/**
 * The Classifai Entity Taxonomy.
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
	 * Get the classifai entity taxonomy name.
	 */
	public function get_name() {
		return WATSON_ENTITY_TAXONOMY;
	}

	/**
	 * Get the classifai entity taxonomy label.
	 */
	public function get_singular_label() {
		return esc_html__( 'Watson Entity', 'classifai' );
	}

	/**
	 * Get the classifai entity taxonomy plural label.
	 */
	public function get_plural_label() {
		return esc_html__( 'Watson Entities', 'classifai' );
	}

	/**
	 * Get the classifai entity taxonomy visibility.
	 */
	public function get_visibility() {
		return \Classifai\get_feature_enabled( 'entity' ) &&
			\Classifai\get_feature_taxonomy( 'entity' ) === $this->get_name();
	}

}
