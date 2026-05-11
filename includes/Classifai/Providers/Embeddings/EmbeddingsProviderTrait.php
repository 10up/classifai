<?php
/**
 * Shared behavior for embedding-based classification providers.
 */

namespace Classifai\Providers\Embeddings;

use Classifai\Features\Classification;
use Classifai\Features\Feature;
use Classifai\Normalizer;
use Classifai\Providers\OpenAI\EmbeddingCalculations;
use Classifai\Providers\OpenAI\Tokenizer;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Embedding provider helpers (classification flows, storage keys, chunking).
 */
trait EmbeddingsProviderTrait {

	/**
	 * Meta key used to persist embedding vectors for posts and terms.
	 *
	 * @return string
	 */
	protected function get_embeddings_meta_key(): string {
		return 'classifai_' . static::ID;
	}

	/**
	 * Hook prefix for provider-specific actions and filters.
	 *
	 * @return string
	 */
	protected function get_embeddings_hook_prefix(): string {
		return 'classifai_' . static::ID;
	}

	/**
	 * Max tokens to use when chunking content for embedding requests.
	 *
	 * @param Feature|null $feature Feature context (e.g. for model-specific limits); unused in default implementation.
	 * @return int
	 */
	protected function get_max_tokens_for_embedding_chunking( ?Feature $feature = null ): int { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
		return (int) $this->get_max_tokens();
	}

	/**
	 * Populate NLU feature labels from the current feature's supported taxonomies.
	 *
	 * @param bool $require_classification_feature When true, only runs for the Classification feature.
	 */
	protected function populate_nlu_features_from_supported_taxonomies( bool $require_classification_feature = false ): void {
		if ( $require_classification_feature ) {
			if ( ! $this->feature_instance || Classification::ID !== $this->feature_instance::ID ) {
				return;
			}
		}

		if (
			! $this->feature_instance ||
			! method_exists( $this->feature_instance, 'get_supported_taxonomies' )
		) {
			return;
		}

		$settings   = get_option( $this->feature_instance->get_option_name(), [] );
		$post_types = isset( $settings['post_types'] ) ? $settings['post_types'] : [ 'post' => 1 ];

		foreach ( $this->feature_instance->get_supported_taxonomies( $post_types ) as $tax => $label ) {
			$this->nlu_features[ $tax ] = [
				'feature'           => $label,
				'threshold'         => __( 'Threshold (%)', 'classifai' ),
				'threshold_default' => 75,
				'taxonomy'          => __( 'Taxonomy', 'classifai' ),
				'taxonomy_default'  => $tax,
			];
		}
	}

	/**
	 * Merge default taxonomy toggles and thresholds for the Classification feature.
	 *
	 * @param array   $settings Current settings.
	 * @param Feature $feature_instance Feature instance.
	 * @return array
	 */
	protected function merge_supported_taxonomy_defaults( array $settings, Feature $feature_instance ): array {
		$defaults = [];

		foreach ( array_keys( $feature_instance->get_supported_taxonomies() ) as $tax ) {
			$enabled = 'category' === $tax ? true : false;

			$defaults[ $tax ]                = $enabled;
			$defaults[ $tax . '_threshold' ] = 75;
			$defaults[ $tax . '_taxonomy' ]  = $tax;
		}

		return array_merge( $settings, $defaults );
	}

	/**
	 * Get the threshold for the similarity calculation.
	 *
	 * @param string $taxonomy Taxonomy slug.
	 * @return float
	 */
	public function get_threshold( string $taxonomy = '' ): float {
		$settings  = ( new Classification() )->get_settings();
		$threshold = 1;

		if ( ! empty( $taxonomy ) ) {
			$threshold = isset( $settings[ $taxonomy . '_threshold' ] ) ? $settings[ $taxonomy . '_threshold' ] : 75;
		}

		$threshold = 1 - ( (float) $threshold / 100 );

		/**
		 * Filter the threshold for the similarity calculation.
		 *
		 * @since 2.5.0
		 * @hook classifai_threshold
		 *
		 * @param float  $threshold The threshold to use.
		 * @param string $taxonomy  The taxonomy to get the threshold for.
		 *
		 * @return float The threshold to use.
		 */
		return apply_filters( 'classifai_threshold', $threshold, $taxonomy );
	}

	/**
	 * Chunk content into smaller pieces with an overlap.
	 *
	 * @param string $content Content to chunk.
	 * @param int    $chunk_size Size of each chunk, in words.
	 * @param int    $overlap_size Overlap size for each chunk, in words.
	 * @return array
	 */
	public function chunk_content( string $content = '', int $chunk_size = 150, $overlap_size = 25 ): array {
		$content = preg_replace( '/\s+/', ' ', $content );
		$words   = explode( ' ', $content );

		$chunks     = [];
		$text_count = count( $words );

		for ( $i = 0; $i < $text_count; $i += $chunk_size ) {
			$chunk = implode(
				' ',
				array_slice(
					$words,
					max( $i - $overlap_size, 0 ),
					$chunk_size + $overlap_size
				)
			);

			array_push( $chunks, $chunk );
		}

		return $chunks;
	}

	/**
	 * Get our content, ensuring it is normalized.
	 *
	 * @param int    $id ID of item to get content from.
	 * @param string $type Type of content. Default 'post'.
	 * @return string
	 */
	public function get_normalized_content( int $id = 0, string $type = 'post' ): string {
		$normalizer = new Normalizer();
		$content    = '';

		switch ( $type ) {
			case 'post':
				$content = $normalizer->normalize( $id );
				break;
			case 'term':
				$content = '';
				$term    = get_term( $id );

				if ( is_a( $term, '\WP_Term' ) ) {
					$content = $term->name . ' ' . $term->slug . ' ' . $term->description;
				}

				break;
		}

		/**
		 * Filter content that will be sent for embedding generation.
		 *
		 * @hook classifai_{provider_id}_content
		 *
		 * @param string $content Normalized content.
		 * @param int    $id      ID of the item.
		 * @param string $type    Content type.
		 */
		return apply_filters( $this->get_embeddings_hook_prefix() . '_content', $content, $id, $type );
	}

	/**
	 * AJAX handler: preview classifier terms from embeddings.
	 */
	public function get_post_classifier_embeddings_preview_data(): void {
		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : false;

		if ( ! $nonce || ! wp_verify_nonce( $nonce, 'classifai-previewer-action' ) ) {
			wp_send_json_error( esc_html__( 'Failed nonce check.', 'classifai' ) );
		}

		$post_id = filter_input( INPUT_POST, 'post_id', FILTER_SANITIZE_NUMBER_INT );

		$embeddings       = $this->generate_embeddings_for_post( $post_id, true );
		$embeddings_terms = [];

		if ( $embeddings && ! is_wp_error( $embeddings ) ) {
			$embeddings_terms = $this->get_terms( $embeddings );

			if ( is_wp_error( $embeddings_terms ) ) {
				wp_send_json_error( $embeddings_terms->get_error_message() );
			}
		}

		wp_send_json_success( $embeddings_terms );
	}

	/**
	 * Generate embeddings for a post (classification storage).
	 *
	 * @param int          $post_id ID of post.
	 * @param bool         $force Whether to force regeneration.
	 * @param Feature|null $feature Optional feature context.
	 * @return array[]|WP_Error
	 */
	public function generate_embeddings_for_post( int $post_id, bool $force = false, ?Feature $feature = null ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return new WP_Error( 'invalid', esc_html__( 'Embedding generation will not work during an autosave.', 'classifai' ) );
		}

		if ( ! current_user_can( 'edit_post', $post_id ) && ( ! defined( 'WP_CLI' ) || ! WP_CLI ) ) {
			return new WP_Error( 'invalid', esc_html__( 'User does not have permission to generate embeddings.', 'classifai' ) );
		}

		$hook = $this->get_embeddings_hook_prefix() . '_should_classify';

		/**
		 * Filter whether ClassifAI should classify an item.
		 *
		 * @hook classifai_{provider_id}_should_classify
		 *
		 * @param bool   $should_classify Whether the item should be classified.
		 * @param int    $id              Item ID.
		 * @param string $type            Item type.
		 */
		if ( ! apply_filters( $hook, true, $post_id, 'post' ) ) {
			return new WP_Error( 'invalid', esc_html__( 'Embedding generation is disabled for this item.', 'classifai' ) );
		}

		$meta_key = $this->get_embeddings_meta_key();

		if ( ! $force ) {
			$embeddings = get_post_meta( $post_id, $meta_key, true );

			if ( ! empty( $embeddings ) ) {
				return $embeddings;
			}
		}

		$embeddings     = [];
		$content        = $this->get_normalized_content( $post_id, 'post' );
		$content_chunks = $this->chunk_content( $content );

		if ( ! empty( $content_chunks ) ) {
			$max_tokens   = $this->get_max_tokens_for_embedding_chunking( $feature );
			$tokenizer    = new Tokenizer( $max_tokens );
			$total_tokens = $tokenizer->tokens_in_content( $content );

			if ( $max_tokens < $total_tokens ) {
				foreach ( $content_chunks as $chunk ) {
					$embedding = $this->generate_embedding( $chunk, $feature );

					if ( $embedding && ! is_wp_error( $embedding ) ) {
						$embeddings[] = array_map( 'floatval', $embedding );
					} elseif ( is_wp_error( $embedding ) ) {
						return $embedding;
					}
				}
			} else {
				$all_embeddings = $this->generate_embeddings( $content_chunks, $feature );

				if ( $all_embeddings && ! is_wp_error( $all_embeddings ) ) {
					$embeddings = array_map(
						function ( $embedding ) {
							return array_map( 'floatval', $embedding );
						},
						$all_embeddings
					);
				} elseif ( is_wp_error( $all_embeddings ) ) {
					return $all_embeddings;
				}
			}
		}

		if ( ! empty( $embeddings ) ) {
			update_post_meta( $post_id, $meta_key, $embeddings );
		}

		return $embeddings;
	}

	/**
	 * Add terms to a post based on embeddings.
	 *
	 * @param int   $post_id ID of post to set terms on.
	 * @param array $embeddings Embeddings data.
	 * @param bool  $link Whether to link the terms or not.
	 * @return array|WP_Error
	 */
	public function set_terms( int $post_id = 0, array $embeddings = [], bool $link = true ) {
		if ( ! $post_id || ! get_post( $post_id ) ) {
			return new WP_Error( 'post_id_required', esc_html__( 'A valid post ID is required to set terms.', 'classifai' ) );
		}

		if ( empty( $embeddings ) ) {
			return new WP_Error( 'data_required', esc_html__( 'Valid embedding data is required to set terms.', 'classifai' ) );
		}

		$embeddings_similarity = [];

		foreach ( $embeddings as $embedding ) {
			$embeddings_similarity = array_merge( $embeddings_similarity, $this->get_term_embeddings_similarity( $embedding ) );
		}

		if ( empty( $embeddings_similarity ) ) {
			return new WP_Error( 'invalid', esc_html__( 'No matching terms found.', 'classifai' ) );
		}

		$prefix = $this->get_embeddings_hook_prefix();

		/**
		 * Fires after similarity is computed and before sorting.
		 *
		 * @hook classifai_{provider_id}_pre_sort_embeddings_similarity
		 */
		do_action( $prefix . '_pre_sort_embeddings_similarity', $embeddings_similarity, $post_id, $embeddings, $link );

		usort(
			$embeddings_similarity,
			function ( $a, $b ) {
				return $a['similarity'] <=> $b['similarity'];
			}
		);

		$uniques               = array_unique( array_column( $embeddings_similarity, 'term_id' ) );
		$embeddings_similarity = array_intersect_key( $embeddings_similarity, $uniques );

		$sorted_results = [];

		foreach ( $embeddings_similarity as $item ) {
			$sorted_results[ $item['taxonomy'] ][] = $item;
		}

		/**
		 * Fires after similarity results are sorted.
		 *
		 * @hook classifai_{provider_id}_post_sort_embeddings_similarity
		 */
		do_action( $prefix . '_post_sort_embeddings_similarity', $sorted_results, $embeddings_similarity, $post_id, $embeddings, $link );

		$return = [];

		foreach ( $sorted_results as $tax => $terms ) {
			if ( $link ) {
				wp_set_object_terms( $post_id, array_map( 'absint', array_column( $terms, 'term_id' ) ), $tax, false );
			} else {
				$terms_to_link = [];

				foreach ( $terms as $term ) {
					$found_term = get_term( $term['term_id'] );

					if ( $found_term && ! is_wp_error( $found_term ) ) {
						$terms_to_link[ $found_term->name ] = $term['term_id'];
					}
				}

				$return[ $tax ] = $terms_to_link;
			}
		}

		return empty( $return ) ? $embeddings_similarity : $return;
	}

	/**
	 * Determine which terms best match a post based on embeddings.
	 *
	 * @param array $embeddings An array of embeddings data.
	 * @return array|WP_Error
	 */
	public function get_terms( array $embeddings = [] ) {
		if ( empty( $embeddings ) ) {
			return new WP_Error( 'data_required', esc_html__( 'Valid embedding data is required to get terms.', 'classifai' ) );
		}

		$embeddings_similarity = [];

		foreach ( $embeddings as $embedding ) {
			$embeddings_similarity = array_merge( $embeddings_similarity, $this->get_term_embeddings_similarity( $embedding, false ) );
		}

		if ( empty( $embeddings_similarity ) ) {
			return new WP_Error( 'invalid', esc_html__( 'No matching terms found.', 'classifai' ) );
		}

		usort(
			$embeddings_similarity,
			function ( $a, $b ) {
				return $a['similarity'] <=> $b['similarity'];
			}
		);

		$uniques               = array_unique( array_column( $embeddings_similarity, 'term_id' ) );
		$embeddings_similarity = array_intersect_key( $embeddings_similarity, $uniques );

		$sorted_results = [];

		foreach ( $embeddings_similarity as $item ) {
			$sorted_results[ $item['taxonomy'] ][] = $item;
		}

		$results = [];

		foreach ( $sorted_results as $tax => $terms ) {
			$taxonomy = get_taxonomy( $tax );
			$tax_name = $taxonomy->labels->singular_name;

			$results[ $tax ] = [
				'label' => $tax_name,
				'data'  => [],
			];

			foreach ( $terms as $term ) {
				$similarity = round( ( 1 - $term['similarity'] ), 10 );

				$results[ $tax ]['data'][] = [
					'label' => get_term( $term['term_id'] )->name,
					'score' => $similarity,
				];
			}
		}

		return $results;
	}

	/**
	 * Similarity between one embedding vector and all enabled terms.
	 *
	 * @param array $embedding Embedding data.
	 * @param bool  $consider_threshold Whether to apply taxonomy thresholds.
	 * @return array
	 */
	protected function get_term_embeddings_similarity( array $embedding, bool $consider_threshold = true ): array {
		$feature              = new Classification();
		$embedding_similarity = [];
		$taxonomies           = $feature->get_all_feature_taxonomies();
		$calculations         = new EmbeddingCalculations();
		$meta_key             = $this->get_embeddings_meta_key();
		$single_hook          = $this->get_embeddings_hook_prefix() . '_single_embedding_similarity';

		foreach ( $taxonomies as $tax ) {
			$exclude = [];

			if ( is_numeric( $tax ) ) {
				continue;
			}

			if ( 'tags' === $tax ) {
				$tax = 'post_tag';
			}

			if ( 'categories' === $tax ) {
				$tax = 'category';

				$uncat_term = get_term_by( 'name', 'Uncategorized', 'category' );
				if ( $uncat_term ) {
					$exclude = [ $uncat_term->term_id ];
				}
			}

			$terms = get_terms(
				[
					'taxonomy'   => $tax,
					'orderby'    => 'count',
					'order'      => 'DESC',
					'hide_empty' => false,
					'fields'     => 'ids',
					'meta_key'   => $meta_key, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
					'number'     => $this->get_max_terms(),
					'exclude'    => $exclude, // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude
				]
			);

			if ( is_wp_error( $terms ) || empty( $terms ) ) {
				continue;
			}

			$threshold = $this->get_threshold( $tax );

			foreach ( $terms as $term_id ) {
				if ( ! current_user_can( 'assign_term', $term_id ) && ( ! defined( 'WP_CLI' ) || ! WP_CLI ) ) {
					continue;
				}

				$term_embedding = get_term_meta( $term_id, $meta_key, true );

				if ( ! empty( $term_embedding ) ) {
					foreach ( $term_embedding as $chunk ) {
						$similarity = $calculations->cosine_similarity( $embedding, $chunk );

						do_action( $single_hook, $similarity, $embedding, $chunk, $term_id, $tax, $consider_threshold );

						if ( false !== $similarity && ( ! $consider_threshold || $similarity <= $threshold ) ) {
							$embedding_similarity[] = [
								'taxonomy'   => $tax,
								'term_id'    => $term_id,
								'similarity' => $similarity,
							];
						}
					}
				}
			}
		}

		return $embedding_similarity;
	}

	/**
	 * Generate embeddings for a taxonomy term.
	 *
	 * @param int          $term_id ID of term.
	 * @param bool         $force Whether to force regeneration.
	 * @param Feature|null $feature Feature instance.
	 * @return array|WP_Error
	 */
	public function generate_embeddings_for_term( int $term_id, bool $force = false, ?Feature $feature = null ) {
		if ( ! current_user_can( 'edit_term', $term_id ) ) {
			return new WP_Error( 'invalid', esc_html__( 'User does not have valid permissions to edit this term.', 'classifai' ) );
		}

		$term = get_term( $term_id );

		if ( ! is_a( $term, '\WP_Term' ) ) {
			return new WP_Error( 'invalid', esc_html__( 'This is not a valid term.', 'classifai' ) );
		}

		if ( ! $feature ) {
			$feature = new Classification();
		}

		$taxonomies = $feature->get_all_feature_taxonomies();

		if ( in_array( 'tags', $taxonomies, true ) ) {
			$taxonomies[] = 'post_tag';
		}

		if ( in_array( 'categories', $taxonomies, true ) ) {
			$taxonomies[] = 'category';
		}

		if ( ! in_array( $term->taxonomy, $taxonomies, true ) ) {
			return new WP_Error( 'invalid', esc_html__( 'This taxonomy is not supported.', 'classifai' ) );
		}

		$hook = $this->get_embeddings_hook_prefix() . '_should_classify';

		if ( ! apply_filters( $hook, true, $term_id, 'term' ) ) {
			return new WP_Error( 'invalid', esc_html__( 'Embedding generation is disabled for this item.', 'classifai' ) );
		}

		$meta_key   = $this->get_embeddings_meta_key();
		$embeddings = get_term_meta( $term_id, $meta_key, true );

		if ( ! empty( $embeddings ) && ! $force ) {
			return $embeddings;
		}

		$embeddings     = [];
		$content        = $this->get_normalized_content( $term_id, 'term' );
		$content_chunks = $this->chunk_content( $content );

		if ( ! empty( $content_chunks ) ) {
			foreach ( $content_chunks as $chunk ) {
				$embedding = $this->generate_embedding( $chunk, $feature );

				if ( $embedding && ! is_wp_error( $embedding ) ) {
					$embeddings[] = array_map( 'floatval', $embedding );
				} elseif ( is_wp_error( $embedding ) ) {
					return $embedding;
				}
			}
		}

		if ( ! empty( $embeddings ) ) {
			update_term_meta( $term_id, $meta_key, $embeddings );
		}

		return $embeddings;
	}
}
