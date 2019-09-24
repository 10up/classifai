<?php

namespace Classifai\Taxonomy;

/**
 * Abstract Base Class for ClassifAI Taxonomies. A Taxonomy should
 * declare a constant in the config.php file.
 *
 * Usage:
 *
 * class FooTaxonomy extends AbstractTaxonomy {
 *
 *     public function get_name() {
 *         return FOO_TAXONOMY;
 *     }
 *
 *     public function get_singular_label() {
 *         return 'foo';
 *     }
 *
 *     public function get_plural_label() {
 *         return 'foos';
 *     }
 * }
 *
 * Then add it to the Taxonomy Factory. And add as a supported Taxonomy
 * on the corresponding post types.
 */
abstract class AbstractTaxonomy {

	/**
	 * Get the taxonomy name constant.
	 *
	 * @return string
	 */
	abstract public function get_name();

	/**
	 * Get the singular taxonomy label.
	 *
	 * @return string
	 */
	abstract public function get_singular_label();

	/**
	 * Get the plural taxonomy label.
	 *
	 * @return string
	 */
	abstract public function get_plural_label();

	/**
	 * Return true or false based on whether to show this taxonomy. Maps
	 * to show_ui option.
	 *
	 * @return bool
	 */
	abstract public function get_visibility();

	/**
	 * Register hooks and actions.
	 */
	public function register() {
		\register_taxonomy(
			$this->get_name(),
			$this->get_post_types(),
			$this->get_options()
		);
	}

	/**
	 * Get the options for the taxonomy.
	 *
	 * @return array
	 */
	public function get_options() {
		$visibility = $this->get_visibility();

		return array(
			'labels'                => $this->get_labels(),
			'hierarchical'          => false,
			'show_ui'               => $visibility,
			'show_in_rest'          => $visibility,
			'show_admin_column'     => $visibility,
			'query_var'             => true,
			'rewrite'               => $this->get_rewrite_option(),
			'update_count_callback' => $this->update_count_callback(),
		);
	}

	/**
	 * Return the default value for the update_count_callback param
	 *
	 * @return string
	 */
	public function update_count_callback() {
		return '';
	}



	/**
	 * Get the labels for the taxonomy.
	 *
	 * @return array
	 */
	public function get_labels() {
		$plural_label   = $this->get_plural_label();
		$singular_label = $this->get_singular_label();

		$labels = array(
			'name'                       => $plural_label, // Already translated via get_plural_label().
			'singular_name'              => $singular_label, // Already translated via get_singular_label().
			'search_items'               => sprintf( __( 'Search %s', 'classifai' ), $plural_label ),
			'popular_items'              => sprintf( __( 'Popular %s', 'classifai' ), $plural_label ),
			'all_items'                  => sprintf( __( 'All %s', 'classifai' ), $plural_label ),
			'edit_item'                  => sprintf( __( 'Edit %s', 'classifai' ), $singular_label ),
			'update_item'                => sprintf( __( 'Update %s', 'classifai' ), $singular_label ),
			'add_new_item'               => sprintf( __( 'Add New %s', 'classifai' ), $singular_label ),
			'new_item_name'              => sprintf( __( 'New %s Name', 'classifai' ), $singular_label ),
			'separate_items_with_commas' => sprintf( __( 'Separate %s with commas', 'classifai' ), strtolower( $plural_label ) ),
			'add_or_remove_items'        => sprintf( __( 'Add or remove %s', 'classifai' ), strtolower( $plural_label ) ),
			'choose_from_most_used'      => sprintf( __( 'Choose from the most used %s', 'classifai' ), strtolower( $plural_label ) ),
			'not_found'                  => sprintf( __( 'No %s found.', 'classifai' ), strtolower( $plural_label ) ),
			'not_found_in_trash'         => sprintf( __( 'No %s found in Trash.', 'classifai' ), strtolower( $plural_label ) ),
		);

		return $labels;
	}

	/**
	 * Setting the post types to null to ensure no post type is
	 * registered with this taxonomy.
	 */
	public function get_post_types() {
		return null;
	}

	/**
	 * Get rewrite options
	 * register_taxonomy.
	 *
	 * @return array|string
	 */
	public function get_rewrite_option() {
		return false;
	}

	/**
	 * Check to see if a feature is enabled.
	 *
	 * @param string $feature The feature name.
	 *
	 * @return bool|mixed
	 */
	public function get_feature_enabled( $feature ) {
		$settings = get_option( 'classifai_settings' );

		if ( ! empty( $settings ) && ! empty( $settings['features'] ) ) {
			if ( ! empty( $settings['features'][ $feature ] ) ) {
				return filter_var( $settings['features'][ $feature ], FILTER_VALIDATE_BOOLEAN );
			}
		}

		return false;
	}
}
