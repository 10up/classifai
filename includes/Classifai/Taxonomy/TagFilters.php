<?php

namespace Classifai\Taxonomy;

/**
 * TagFilters supports an allowlist or disallowed list of automated tags returned.
 */
class TagFilters {

	/**
	 * Type of Filtering Selected.
	 * This value is derived from the plugin settings page.
	 *
	 * @var string
	 */
	public $type = 'none';

	/**
	 * Array of tags to be filtered.
	 *
	 * @var array
	 */
	public $tags_list = [];

	/**
	 * Construct the Linker object
	 */
	public function __construct() {
		$this->set_filter_settings();
	}

	/**
	 * Get Filter Settings
	 */
	public function set_filter_settings() {
		$settings   = new Classifai\Services\ServicesManager();
		$this->type = $settings->get_setting( 'filter_tags_type' ) ?? 'none';

		if ( 'none' !== $this->type ) {
			if ( 'allowed' === $this->type ) {
				$allowed_tags = $settings->get_setting( 'allowed_tags' );

				if ( ! empty( $allowed_tags ) ) {
					foreach ( $allowed_tags as $tag_id ) {
						$tag = get_term( $tag_id );

						if ( $tag ) {
							$this->tags_list[] = $tag->name;
						}
					}
				}
			}

			if ( 'disabled' === $this->type ) {
				$this->tags_list = $settings->get_setting( 'disabled_tags' );
			}
		}
	}

	/**
	 * Determine if a specific tag can be used based on user settings.
	 *
	 * @param string $tag Tag Name.
	 * @return boolean
	 */
	public function can_use_tag( $tag ) {
		if ( 'none' !== $this->type ) {
			$filtered_tags = array_map( 'strtolower', $this->tags_list );
			$filtered_tags = array_map( 'trim', $filtered_tags );
			$compare_tag   = trim( strtolower( $tag ) );

			// If the filtered tags list is empty,
			// That means all tags are allowed or disabled.
			if ( empty( $filtered_tags ) ) {
				return 'disabled' === $this->type ? true : false;
			}

			// Compare tags based on filter type
			if ( 'disabled' === $this->type ) {
				return ! in_array( $compare_tag, $filtered_tags, true );
			} else {
				return in_array( $compare_tag, $filtered_tags, true );
			}
		}

		return true;
	}
}
