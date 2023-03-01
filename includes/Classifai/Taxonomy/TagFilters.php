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
		$this->get_filter_settings();
	}

	/**
	 * Get Filter Settings
	 *
	 * @return array
	 */
	public function get_filter_settings() {
		$settings   = new Classifai\Services\ServicesManager();
		$this->type = $settings->get_setting( 'tag_restrict_type' ) ?? 'none';

		if ( 'none' !== $this->type ) {
			if ( 'existing' === $this->type ) {
				$tags = get_tags( [ 'hide_empty' => false ] );

				if ( ! empty( $tags ) ) {
					$this->tags_list = wp_list_pluck( $tags, 'name' );
				}
			}

			if ( 'disallow' === $this->type ) {
				$tags = $settings->get_setting( 'disallowed_tags' );

				if ( ! empty( $tags ) ) {
					$this->tags_list = preg_split( '/\r\n|[\r\n]/', $tags );
				}
			}
		}

		return [
			'type'      => $this->type,
			'tags_list' => $this->tags_list,
		];
	}

	/**
	 * Determine if a specific tag can be used based on user settings.
	 *
	 * @param string $tag Tag Name.
	 * @return boolean
	 */
	public function can_use_tag( $tag ) {
		if ( 'none' !== $this->type ) {
			$restricted_tags = array_map( 'strtolower', $this->tags_list );

			// Restricted Tags Disallowed List
			if (
				( 'disallowed' === $this->type && ! empty( $restricted_tags ) ) &&
				in_array( strtolower( $tag ), $restricted_tags, true )
			) {
				return false;
			}

			// Existing Tags Only
			if (
				( 'existing' === $this->type ) &&
				! in_array( strtolower( $tag ), $restricted_tags, true )
			) {
				return false;
			}
		}

		return true;
	}
}
