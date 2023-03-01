<?php

namespace Classifai\Watson;

/**
 * Linker connects Watson classification results with Taxonomy Terms.
 *
 * Terms are added to the Taxonomies if absent. Parent-Child
 * relationships are preserved where relevant like for Categories.
 *
 * The PostClassifier uses the thresholds for each NLU feature,
 * to determine whether classifier results should be included or ignored.
 *
 * 1. WATSON_CATEGORY_THRESHOLD
 * 2. WATSON_KEYWORD_THRESHOLD
 * 3. WATSON_CONCEPT_THRESHOLD
 * 4. WATSON_ENTITY_THRESHOLD
 *
 * A classifai_can_link filter is also provided to override the default
 * behaviour.
 *
 * Usage:
 *
 * $linker = new Linker();
 * $linker->link( 555, $outcome );
 */
class Linker {

	/**
	 * Array of allowed Tags
	 *
	 * @var array
	 */
	protected $restricted_tags = [
		'type' => 'none',
		'tags' => [],
	];

	/**
	 * Construct the Linker object
	 */
	public function __construct() {
		$this->get_restricted_tags();
	}

	/**
	 * Get tag restrictions
	 *
	 * @return void
	 */
	public function get_restricted_tags() {
		$settings = new Classifai\Services\ServicesManager();
		$type     = $settings->get_setting( 'tag_restrict_type' );
		$tags     = [];

		if ( ! empty( $type ) && 'none' !== $type ) {
			if ( 'existing' === $type ) {
				$tags = get_tags( [ 'hide_empty' => false ] );

				if ( ! empty( $tags ) ) {
					$tags = wp_list_pluck( $tags, 'name' );
				}
			}

			if ( 'disallow' === $type ) {
				$tags = $settings->get_setting( 'disallowed_tags' );

				if ( ! empty( $tags ) ) {
					$tags = preg_split( '/\r\n|[\r\n]/', $tags );
				}
			}

			$this->restricted_tags = [
				'type' => $type,
				'tags' => $tags,
			];
		}
	}

	/**
	 * Determine if a specific tag can be used based on user settings.
	 *
	 * @param string $tag Tag Name.
	 * @return boolean
	 */
	public function can_use_tag( $tag ) {
		if ( 'none' !== $this->restricted_tags['type'] ) {
			$restricted_tags = array_map( 'strtolower', $this->restricted_tags['tags'] );

			// Restricted Tags Disallowed List
			if (
				( 'disallowed' === $this->restricted_tags['type'] && ! empty( $restricted_tags ) ) &&
				in_array( strtolower( $tag ), $restricted_tags, true )
			) {
				return false;
			}

			// Existing Tags Only
			if (
				( 'existing' === $this->restricted_tags['type'] ) &&
				! in_array( strtolower( $tag ), $restricted_tags, true )
			) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Links the IBM Watson NLU classification results to the
	 * corresponding taxonomies in WordPress.
	 *
	 * @param int   $post_id The post to link to.
	 * @param array $output  The classification results from Watson NLU.
	 * @param array $options Unused.

	 * @return void
	 */
	public function link( $post_id, $output, $options = [] ) {
		if ( ! empty( $output['categories'] ) ) {
			$this->link_categories( $post_id, $output['categories'] );
		}

		if ( ! empty( $output['keywords'] ) ) {
			$this->link_keywords( $post_id, $output['keywords'] );
		}

		if ( ! empty( $output['concepts'] ) ) {
			$this->link_concepts( $post_id, $output['concepts'] );
		}

		if ( ! empty( $output['entities'] ) ) {
			$this->link_entities( $post_id, $output['entities'] );
		}
	}

	/* helpers */

	/**
	 * Links the NLU returned categories to terms in the category
	 * taxonomy. Terms are created on demand if absent.
	 *
	 * The format of the NLU categories array is,
	 *
	 * [
	 *    [ label, score ],
	 *    ...
	 * ]
	 *
	 * Where label is path to a category,
	 *
	 * Eg:- /animals/pets/cats
	 *
	 * @param int   $post_id The id of the post to link
	 * @param array $categories The list of categories to link
	 * @return void
	 */
	public function link_categories( $post_id, $categories ) {
		$terms_to_link = [];
		$taxonomy      = \Classifai\get_feature_taxonomy( 'category' );

		foreach ( $categories as $category ) {
			if ( $this->can_link_category( $category ) ) {
				$parts = explode( '/', $category['label'] );
				$parts = array_filter( $parts );

				if ( ! empty( $parts ) ) {
					$parent = null;

					foreach ( $parts as $part ) {
						$term = get_term_by( 'name', $part, $taxonomy );

						if ( false === $term ) {
							$term = wp_insert_term(
								$part,
								$taxonomy,
								[
									'parent' => $parent,
								]
							);

							if ( ! is_wp_error( $term ) ) {
								$parent          = intval( $term['term_id'] );
								$terms_to_link[] = intval( $term['term_id'] );
							}
						} else {
							$parent          = intval( $term->term_id );
							$terms_to_link[] = intval( $term->term_id );
						}
					}
				}
			}
		}

		if ( ! empty( $terms_to_link ) ) {
			wp_set_object_terms( $post_id, $terms_to_link, $taxonomy, false );
		}
	}

	/**
	 * Links Watson NLU Keywords to terms in the Keyword Taxonomy.
	 *
	 * The structure of the NLU Keywords array is,
	 *
	 * [
	 *   [ text, relevance ]
	 *   ...
	 * ]
	 *
	 * @param int   $post_id The post to link to
	 * @param array $keywords NLU returned keywords
	 * @return void
	 */
	public function link_keywords( $post_id, $keywords ) {
		$terms_to_link = [];
		$taxonomy      = \Classifai\get_feature_taxonomy( 'keyword' );

		foreach ( $keywords as $keyword ) {
			if ( $this->can_link_keyword( $keyword ) ) {
				$name = $keyword['text'];
				$name = preg_replace( '#^[a-z]+ ([A-Z].*)$#', '$1', $name );
				$term = get_term_by( 'name', $name, $taxonomy );

				if ( false === $term ) {
					$term = wp_insert_term( $name, $taxonomy, [] );

					if ( ! is_wp_error( $term ) ) {
						$terms_to_link[] = intval( $term['term_id'] );
					}
				} else {
					$terms_to_link[] = intval( $term->term_id );
				}
			}
		}

		if ( ! empty( $terms_to_link ) ) {
			wp_set_object_terms( $post_id, $terms_to_link, $taxonomy, false );
		}
	}

	/**
	 * Links the IBM Watson concepts to the Concept Taxonomy.
	 *
	 * The structure of the Watson concept array is,
	 *
	 * [
	 *   [ text, relevance, dbpedia_resource ]
	 *   ...
	 * ]
	 *
	 * @param int   $post_id  The post to link to.
	 * @param array $concepts The NLU returned concepts.
	 * @return void
	 */
	public function link_concepts( $post_id, $concepts ) {
		$terms_to_link = [];
		$taxonomy      = \Classifai\get_feature_taxonomy( 'concept' );

		foreach ( $concepts as $concept ) {
			if ( $this->can_link_concept( $concept ) ) {
				$name = $concept['text'];
				$term = get_term_by( 'name', $name, $taxonomy );

				if ( false === $term ) {
					$term = wp_insert_term( $name, $taxonomy, [] );

					if ( ! is_wp_error( $term ) ) {
						$terms_to_link[] = intval( $term['term_id'] );

						if ( ! empty( $concept['dbpedia_resource'] ) ) {
							update_term_meta(
								intval( $term['term_id'] ),
								'dbpedia_resource',
								$concept['dbpedia_resource']
							);
						}
					}
				} else {
					$terms_to_link[] = intval( $term->term_id );
				}
			}
		}

		if ( ! empty( $terms_to_link ) ) {
			wp_set_object_terms( $post_id, $terms_to_link, $taxonomy, false );
		}
	}

	/**
	 * Links the Watson returned entities to the Entity taxonomy.
	 *
	 * The structure of the NLU entities is,
	 *
	 * [
	 *   [ type, text, relevance, disambiguaton, count ]
	 *   ...
	 * ]
	 *
	 * @param int   $post_id The post to link to
	 * @param array $entities The entities returned by the NLU api
	 * @return void
	 */
	public function link_entities( $post_id, $entities ) {
		$terms_to_link = [];
		$taxonomy      = \Classifai\get_feature_taxonomy( 'entity' );

		foreach ( $entities as $entity ) {
			if ( $this->can_link_entity( $entity ) ) {
				if ( ! empty( $entity['disambiguation'] ) && ! empty( $entity['disambiguation']['name'] ) ) {
					$name = $entity['disambiguation']['name'];
				} else {
					$name = $entity['text'];
				}

				$term = get_term_by( 'name', $name, $taxonomy );

				if ( false === $term ) {
					$term = wp_insert_term( $name, $taxonomy, [] );

					if ( ! is_wp_error( $term ) ) {
						$terms_to_link[] = intval( $term['term_id'] );

						if ( ! empty( $entity['disambiguation']['dbpedia_resource'] ) ) {
							update_term_meta(
								intval( $term['term_id'] ),
								'dbpedia_resource',
								$entity['disambiguation']['dbpedia_resource']
							);

							update_term_meta(
								intval( $term['term_id'] ),
								'type',
								$entity['type']
							);
						}
					}
				} else {
					$terms_to_link[] = intval( $term->term_id );
				}
			}
		}

		if ( ! empty( $terms_to_link ) ) {
			wp_set_object_terms( $post_id, $terms_to_link, $taxonomy, false );
		}
	}

	/**
	 * Checks whether an NLU category can be linked based on its score.
	 *
	 * @param array $category The category to check.
	 */
	public function can_link_category( $category ) {
		if ( ! empty( $category['label'] ) ) {

			if ( ! $this->can_use_tag( $category['label'] ) ) {
				return false;
			}

			if ( ! empty( $category['score'] ) ) {
				$score     = floatval( $category['score'] );
				$threshold = \Classifai\get_feature_threshold( 'category' );
				return $score >= $threshold;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}

	/**
	 * Checks whether an NLU keyword can be linked based on its relevance.
	 *
	 * @param array $keyword The keyword to check.
	 */
	public function can_link_keyword( $keyword ) {
		if ( ! empty( $keyword['text'] ) ) {

			if ( ! $this->can_use_tag( $keyword['text'] ) ) {
				return false;
			}

			if ( ! empty( $keyword['relevance'] ) ) {
				$relevance = floatval( $keyword['relevance'] );
				$threshold = \Classifai\get_feature_threshold( 'keyword' );
				return $relevance >= $threshold;
			}
		} else {
			return false;
		}
	}

	/**
	 * Checks whether an NLU concept can be linked based on its relevance.
	 *
	 * @param array $concept The concept to check.
	 */
	public function can_link_concept( $concept ) {
		if ( ! empty( $concept['text'] ) ) {

			if ( ! $this->can_use_tag( $concept['text'] ) ) {
				return false;
			}

			if ( ! empty( $concept['relevance'] ) ) {
				$relevance = floatval( $concept['relevance'] );
				$threshold = \Classifai\get_feature_threshold( 'concept' );
				return $relevance >= $threshold;
			}
		} else {
			return false;
		}
	}

	/**
	 * Checks whether an NLU entity can be linked based in its relevance.
	 *
	 * @param array $entity The entity to check.
	 */
	public function can_link_entity( $entity ) {
		if ( ! empty( $entity['text'] ) ) {

			if ( ! $this->can_use_tag( $entity['text'] ) ) {
				return false;
			}

			if ( ! empty( $entity['relevance'] ) ) {
				$relevance = floatval( $entity['relevance'] );
				$threshold = \Classifai\get_feature_threshold( 'entity' );
				return $relevance >= $threshold;
			}
		} else {
			return false;
		}
	}
}
