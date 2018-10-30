<?php

namespace Klasifai\Taxonomy;

/**
 * The Klasifai Concept Taxonomy.
 *
 * Usage:
 *
 * ```php
 *
 * $taxonomy = new ConceptTaxonomy();
 * $taxonomy->register();
 *
 * ```
 */
class ConceptTaxonomy extends AbstractTaxonomy {

	public function get_name() {
		return WATSON_CONCEPT_TAXONOMY;
	}

	public function get_singular_label() {
		return esc_html__( 'Watson Concept', 'klasifai' );
	}

	public function get_plural_label() {
		return esc_html__( 'Watson Concepts', 'klasifai' );
	}

	public function get_visibility() {
		return \Klasifai\get_feature_enabled( 'concept' ) &&
			\Klasifai\get_feature_taxonomy( 'concept' ) === $this->get_name();
	}

}
