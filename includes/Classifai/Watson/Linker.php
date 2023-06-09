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
	 * Links the IBM Watson NLU classification results to the
	 * corresponding taxonomies in WordPress.
	 *
	 * @param int   $post_id The post to link to.
	 * @param array $output  The classification results from Watson NLU.
	 * @param array $options Unused.
	 *
	 * @return array The terms that were linked.
	 */
	public function link( $post_id, $output, $options = [] ) {
		$all_terms = [];

		if ( ! empty( $output['categories'] ) ) {
			$terms     = $this->link_categories( $post_id, $output['categories'], false );
			$all_terms = $terms;
		}

		if ( ! empty( $output['keywords'] ) ) {
			$terms     = $this->link_keywords( $post_id, $output['keywords'], false );
			$all_terms = array_merge_recursive( $all_terms, $terms );
		}

		if ( ! empty( $output['concepts'] ) ) {
			$terms     = $this->link_concepts( $post_id, $output['concepts'], false );
			$all_terms = array_merge_recursive( $all_terms, $terms );
		}

		if ( ! empty( $output['entities'] ) ) {
			$terms     = $this->link_entities( $post_id, $output['entities'], false );
			$all_terms = array_merge_recursive( $all_terms, $terms );
		}

		if ( ! empty( $all_terms ) ) {
			foreach ( $all_terms as $taxonomy => $terms ) {
				wp_set_object_terms( $post_id, $terms, $taxonomy, false );
			}
		}

		return $all_terms;
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
	 * @param int   $post_id The id of the post to link.
	 * @param array $categories The list of categories to link
	 * @param bool  $link_categories Whether link categories to post or return array of term ids.
	 *
	 * @return array|\WP_Error List of the terms to link. WP_Error class object on error.
	 */
	public function link_categories( int $post_id, array $categories, bool $link_categories = true ) {
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
							$term = wp_insert_term( $part, $taxonomy, [ 'parent' => $parent ] );

							if ( ! is_wp_error( $term ) ) {
								$parent          = (int) $term['term_id'];
								$terms_to_link[] = (int) $term['term_id'];
							}
						} else {
							$parent          = $term->term_id;
							$terms_to_link[] = $term->term_id;
						}
					}
				}
			}
		}

		// Exit if there are not any term to link.
		if ( empty( $terms_to_link ) ) {
			return [];
		}

		if ( $link_categories ) {
			$result = wp_set_object_terms( $post_id, $terms_to_link, $taxonomy, false );

			if ( is_wp_error( $result ) ) {
				return $result;
			}
		}

		return [ $taxonomy => $terms_to_link ];
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
	 * @param int   $post_id The id of the post to link.
	 * @param array $keywords NLU returned keywords
	 * @param bool  $link_keywords Whether link keywords to post or return array of term ids.
	 *
	 * @return array|\WP_Error List of the terms to link. WP_Error class object on error.
	 */
	public function link_keywords( int $post_id, array $keywords, bool $link_keywords = true ) {
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
						$terms_to_link[] = (int) $term['term_id'];
					}
				} else {
					$terms_to_link[] = $term->term_id;
				}
			}
		}

		// Exit if there are not any term to link.
		if ( empty( $terms_to_link ) ) {
			return [];
		}

		if ( $link_keywords ) {
			$result = wp_set_object_terms( $post_id, $terms_to_link, $taxonomy, false );

			if ( is_wp_error( $result ) ) {
				return $result;
			}
		}

		return [ $taxonomy => $terms_to_link ];
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
	 * @param int   $post_id The id of the post to link.
	 * @param array $concepts The NLU returned concepts.
	 * @param bool  $link_concepts Whether link concepts to post or return array of term ids.
	 *
	 * @return array|\WP_Error List of the terms to link. WP_Error class object on error.
	 */
	public function link_concepts( int $post_id, array $concepts, bool $link_concepts = true ) {
		$terms_to_link = [];
		$taxonomy      = \Classifai\get_feature_taxonomy( 'concept' );

		foreach ( $concepts as $concept ) {
			if ( $this->can_link_concept( $concept ) ) {
				$name = $concept['text'];
				$term = get_term_by( 'name', $name, $taxonomy );

				if ( false === $term ) {
					$term = wp_insert_term( $name, $taxonomy, [] );

					if ( ! is_wp_error( $term ) ) {
						$terms_to_link[] = (int) $term['term_id'];

						if ( ! empty( $concept['dbpedia_resource'] ) ) {
							update_term_meta(
								(int) $term['term_id'],
								'dbpedia_resource',
								$concept['dbpedia_resource']
							);
						}
					}
				} else {
					$terms_to_link[] = $term->term_id;
				}
			}
		}

		// Exit if there are not any term to link.
		if ( empty( $terms_to_link ) ) {
			return [];
		}

		if ( $link_concepts ) {
			$result = wp_set_object_terms( $post_id, $terms_to_link, $taxonomy, false );

			if ( is_wp_error( $result ) ) {
				return $result;
			}
		}

		return [ $taxonomy => $terms_to_link ];
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
	 * @param int   $post_id The id of the post to link.
	 * @param array $entities The entities returned by the NLU api
	 * @param bool  $link_entities Whether link entities to post or return array of term ids.
	 *
	 * @return array|\WP_Error List of the terms to link. WP_Error class object on error.
	 */
	public function link_entities( int $post_id, array $entities, bool $link_entities = true ) {
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
						$terms_to_link[] = (int) $term['term_id'];

						if ( ! empty( $entity['disambiguation']['dbpedia_resource'] ) ) {
							update_term_meta(
								(int) $term['term_id'],
								'dbpedia_resource',
								$entity['disambiguation']['dbpedia_resource']
							);

							update_term_meta(
								(int) $term['term_id'],
								'type',
								$entity['type']
							);
						}
					}
				} else {
					$terms_to_link[] = $term->term_id;
				}
			}
		}

		// Exit if there are not any term to link.
		if ( empty( $terms_to_link ) ) {
			return [];
		}

		if ( $link_entities ) {
			$result = wp_set_object_terms( $post_id, $terms_to_link, $taxonomy, false );

			if ( is_wp_error( $result ) ) {
				return $result;
			}
		}

		return [ $taxonomy => $terms_to_link ];
	}

	/**
	 * Checks whether an NLU category can be linked based on its score.
	 *
	 * @param array $category The category to check.
	 */
	public function can_link_category( $category ) {
		if ( ! empty( $category['label'] ) ) {
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
