<?php

namespace Classifai\Taxonomy;

/**
 * The Classifai Keyword Taxonomy.
 *
 * Usage:
 *
 * ```php
 *
 * $taxonomy = new KeywordTaxonomy();
 * $taxonomy->register();
 *
 * ```
 */
class KeywordTaxonomy extends AbstractTaxonomy {

	/**
	 * Get the classifai keyword taxonomy name.
	 */
	public function get_name() {
		return WATSON_KEYWORD_TAXONOMY;
	}

	/**
	 * Get the classifai keyword taxonomy label.
	 */
	public function get_singular_label() {
		return esc_html__( 'Watson Keyword', 'classifai' );
	}

	/**
	 * Get the classifai keyword taxonomy plural label.
	 */
	public function get_plural_label() {
		return esc_html__( 'Watson Keywords', 'classifai' );
	}

	/**
	 * Get the classifai keyword taxonomy visibility.
	 */
	public function get_visibility() {
		return \Classifai\get_feature_enabled( 'keyword' ) &&
			\Classifai\get_feature_taxonomy( 'keyword' ) === $this->get_name();
	}

}
