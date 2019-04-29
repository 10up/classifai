<?php

namespace Classifai\Taxonomy;

/**
 * The Classifai Concept Taxonomy.
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

	/**
	 * Get the ClassifAI concept taxonomy name.
	 */
	public function get_name() {
		return WATSON_CONCEPT_TAXONOMY;
	}

	/**
	 * Get the ClassifAI concept taxonomy label.
	 */
	public function get_singular_label() {
		return esc_html__( 'Watson Concept', 'classifai' );
	}

	/**
	 * Get the ClassifAI concept taxonomy plural label.
	 */
	public function get_plural_label() {
		return esc_html__( 'Watson Concepts', 'classifai' );
	}

	/**
	 * Get the ClassifAI concept taxonomy visibility.
	 */
	public function get_visibility() {
		return \Classifai\get_feature_enabled( 'concept' ) &&
			\Classifai\get_feature_taxonomy( 'concept' ) === $this->get_name();
	}

}
