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
	 *
	 * @return string
	 */
	public function get_name(): string {
		return WATSON_CONCEPT_TAXONOMY;
	}

	/**
	 * Get the ClassifAI concept taxonomy label.
	 *
	 * @return string
	 */
	public function get_singular_label(): string {
		return esc_html__( 'Watson Concept', 'classifai' );
	}

	/**
	 * Get the ClassifAI concept taxonomy plural label.
	 *
	 * @return string
	 */
	public function get_plural_label(): string {
		return esc_html__( 'Watson Concepts', 'classifai' );
	}

	/**
	 * Get the ClassifAI concept taxonomy visibility.
	 *
	 * @return bool
	 */
	public function get_visibility(): bool {
		return \Classifai\get_feature_enabled( 'concept' ) &&
			\Classifai\get_feature_taxonomy( 'concept' ) === $this->get_name();
	}
}
