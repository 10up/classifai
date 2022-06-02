<?php

namespace Classifai\Taxonomy;

/**
 * TaxonomyFactory builds the Taxonomy taxonomy class instances. Instances
 * are stored locally and returned from cache on subsequent build calls.
 *
 * All taxonomies supported by Taxonomy are also declared here.
 *
 * Usage:
 *
 * ```php
 *
 * $factory = new TaxonomyFactory();
 * $factory->build_all();
 *
 * ```
 */
class TaxonomyFactory {

	/**
	 * Define the taxonomy mapping object.
	 *
	 * @var array $mapping A map of Watson taxonomies.
	 */
	public $mapping = [
		WATSON_CATEGORY_TAXONOMY => 'CategoryTaxonomy',
		WATSON_KEYWORD_TAXONOMY  => 'KeywordTaxonomy',
		WATSON_CONCEPT_TAXONOMY  => 'ConceptTaxonomy',
		WATSON_ENTITY_TAXONOMY   => 'EntityTaxonomy',
	];

	/**
	 * Previously created taxonomies instances.
	 *
	 * @var array $taxonomies Taxonomies instances.
	 */
	public $taxonomies = [];

	/**
	 * Builds all supported taxonomies. This is bound to the 'init' hook
	 * to allow both frontend and backend to get these taxonomies.
	 */
	public function build_all() {
		$supported_post_types = \Classifai\get_supported_post_types();

		foreach ( $this->get_supported_taxonomies() as $taxonomy ) {
			$this->build_if( $taxonomy, $supported_post_types );
		}
	}

	/**
	 * Conditionally builds a taxonomy or returns the stored instance.
	 *
	 * @param string $taxonomy            The taxonomy name.
	 * @param array  $supported_post_types The supported post types.
	 *
	 * @return BaseTaxonomy A base taxonomy subclass instance.
	 */
	public function build_if( $taxonomy, $supported_post_types = [] ) {
		if ( ! $this->exists( $taxonomy ) ) {
			$this->taxonomies[ $taxonomy ] = $this->build( $taxonomy );
			$instance                      = $this->taxonomies[ $taxonomy ];
			$instance->register();

			if ( ! empty( $supported_post_types ) ) {
				foreach ( $supported_post_types as $post_type ) {
					register_taxonomy_for_object_type( $taxonomy, $post_type );
				}
			}
		}

		return $this->taxonomies[ $taxonomy ];
	}

	/**
	 * Instantiates and returns a instance for the specified taxonomy.
	 * An exception is thrown if an invalid taxonomy name was specified.
	 *
	 * @param string $taxonomy The taxonomy name
	 *
	 * @return \Taxonomy\Taxonomy\BaseTaxonomy A base taxonomy subclass instance.
	 * @throws \Exception An exception.
	 */
	public function build( $taxonomy ) {
		if ( ! empty( $this->mapping[ $taxonomy ] ) ) {
			$class = $this->mapping[ $taxonomy ];

			/* If mapping is not fully qualified, qualify it now */
			if ( strpos( $class, 'Taxonomy' ) !== 0 ) {
				$class = 'Classifai\Taxonomy\\' . $class;
			}

			$instance = new $class();

			return $instance;
		} else {
			throw new \Exception( "Mapping not found for Taxonomy: $taxonomy " );
		}
	}

	/**
	 * Checks if the taxonomy specified was previously built.
	 *
	 * @param string $taxonomy The taxonomy name
	 * @return bool True if the taxonomy exists else false
	 */
	public function exists( $taxonomy ) {
		return ! empty( $this->taxonomies[ $taxonomy ] );
	}

	/**
	 * The list of supported taxonomy instances for Taxonomy.
	 *
	 * @return array List of taxonomy names
	 */
	public function get_supported_taxonomies() {
		return array_keys( $this->mapping );
	}

}
