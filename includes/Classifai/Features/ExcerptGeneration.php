<?php

namespace Classifai\Features;

class ExcerptGeneration {
	const ID = 'feature_excerpt_generation';

	private $title;

	public function init() {
		$this->title = __( 'Excerpt Generation', 'classifai' );
	}

	public function get_title() {
		return apply_filters(
			'classifai_' . self::ID . '_title',
			$this->title
		);
	}
}
