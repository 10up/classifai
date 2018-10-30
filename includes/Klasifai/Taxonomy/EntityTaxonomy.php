<?php

namespace Klasifai\Taxonomy;

/**
 * The Klasifai Entity Taxonomy.
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

	public function get_name() {
		return WATSON_ENTITY_TAXONOMY;
	}

	public function get_singular_label() {
		return esc_html__( 'Watson Entity', 'klasifai' );
	}

	public function get_plural_label() {
		return esc_html__( 'Watson Entities', 'klasifai' );
	}

	public function get_visibility() {
		return \Klasifai\get_feature_enabled( 'entity' ) &&
			\Klasifai\get_feature_taxonomy( 'entity' ) === $this->get_name();
	}

}
