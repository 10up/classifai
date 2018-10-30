<?php

namespace Klasifai\Taxonomy;

/**
 * The Klasifai Keyword Taxonomy.
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

	public function get_name() {
		return WATSON_KEYWORD_TAXONOMY;
	}

	public function get_singular_label() {
		return esc_html__( 'Watson Keyword', 'klasifai' );
	}

	public function get_plural_label() {
		return esc_html__( 'Watson Keywords', 'klasifai' );
	}

	public function get_visibility() {
		return \Klasifai\get_feature_enabled( 'keyword' ) &&
			\Klasifai\get_feature_taxonomy( 'keyword' ) === $this->get_name();
	}

}
