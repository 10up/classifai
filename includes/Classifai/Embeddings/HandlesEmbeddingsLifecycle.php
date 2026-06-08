<?php
/**
 * Trait used by embedding Providers to share the embedding lifecycle: content
 * chunking, similarity calculations against terms and posts, term-linking, and
 * background job orchestration. Pairs with HasEmbeddingsStorage (storage layer).
 *
 * Consumers implement a small surface of abstract methods so the trait can
 * compose vendor-specific filter/action names and call into the right API
 * request methods without leaking those details across providers.
 */

namespace Classifai\Embeddings;

use Classifai\Features\Classification;
use Classifai\Features\Feature;
use Classifai\Features\RecommendedContent;
use Classifai\Normalizer;
use Classifai\Providers\OpenAI\EmbeddingCalculations;
use Classifai\Providers\OpenAI\Tokenizer;
use WP_Error;
use WP_Query;

trait HandlesEmbeddingsLifecycle {

	/**
	 * Filter/hook prefix for this provider (e.g. 'classifai_openai_embeddings').
	 *
	 * The trait composes all per-provider filter and action names off this prefix:
	 *   {$prefix}_should_classify, {$prefix}_pre_sort_embeddings_similarity,
	 *   {$prefix}_single_embedding_similarity, {$prefix}_content, etc.
	 */
	abstract protected function embeddings_filter_prefix(): string;

	/**
	 * Action Scheduler action name to schedule term-embedding jobs against.
	 */
	abstract protected function embeddings_term_job_action(): string;

	/**
	 * Action Scheduler action name to schedule post-embedding jobs against.
	 * Return '' when the provider does not support RecommendedContent.
	 */
	abstract protected function embeddings_post_job_action(): string;

	/**
	 * Generate an embedding for a single string via the vendor API.
	 *
	 * @param string  $text    Text to embed.
	 * @param Feature $feature Feature requesting the embedding.
	 * @return array|boolean|WP_Error
	 */
	abstract public function generate_embedding( string $text = '', $feature = null );

	/**
	 * Generate embeddings for multiple strings in one batched call.
	 *
	 * @param array   $strings Array of texts.
	 * @param Feature $feature Feature requesting the embeddings.
	 * @return array|boolean|WP_Error
	 */
	abstract public function generate_embeddings( array $strings = [], $feature = null );

	/**
	 * Maximum tokens the provider's current configuration supports.
	 *
	 * @return int
	 */
	abstract public function get_max_tokens(): int;

	/**
	 * Maximum number of terms to consider in similarity calculations.
	 *
	 * @return int
	 */
	abstract public function get_max_terms(): int;

	/**
	 * Generate (or return cached) embeddings for a post.
	 *
	 * @param int          $post_id ID of post.
	 * @param bool         $force   Force regeneration even when cached.
	 * @param Feature|null $feature Feature instance driving the request.
	 * @return array[]|WP_Error
	 */
	public function generate_embeddings_for_post( int $post_id, bool $force = false, $feature = null ) {
		// Don't run on autosaves.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return new WP_Error( 'invalid', esc_html__( 'Embedding generation will not work during an autosave.', 'classifai' ) );
		}

		// Ensure the user has permissions to edit.
		if ( ! current_user_can( 'edit_post', $post_id ) && ( ! defined( 'WP_CLI' ) || ! WP_CLI ) ) {
			return new WP_Error( 'invalid', esc_html__( 'User does not have permission to generate embeddings.', 'classifai' ) );
		}

		/** This filter is documented in the trait. */
		if ( ! apply_filters( $this->embeddings_filter_prefix() . '_should_classify', true, $post_id, 'post' ) ) {
			return new WP_Error( 'invalid', esc_html__( 'Embedding generation is disabled for this item.', 'classifai' ) );
		}

		if ( ! $force ) {
			$embeddings = $this->read_object_embedding( 'post', $post_id );
			if ( ! empty( $embeddings ) ) {
				return $embeddings;
			}
		}

		$content        = $this->get_normalized_content( $post_id, 'post' );
		$content_chunks = $this->chunk_content( $content );
		$embeddings     = $this->generate_embeddings_for_chunks( $content_chunks, $content, $feature );

		if ( is_wp_error( $embeddings ) ) {
			return $embeddings;
		}

		if ( ! empty( $embeddings ) ) {
			$this->write_object_embedding( 'post', $post_id, $embeddings, md5( $content ) );
		}

		return $embeddings;
	}

	/**
	 * Generate (or return cached) embeddings for a term.
	 *
	 * @param int          $term_id ID of term.
	 * @param bool         $force   Force regeneration even when cached.
	 * @param Feature|null $feature Feature instance driving the request.
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

		/** This filter is documented in the trait. */
		if ( ! apply_filters( $this->embeddings_filter_prefix() . '_should_classify', true, $term_id, 'term' ) ) {
			return new WP_Error( 'invalid', esc_html__( 'Classification is disabled for this item.', 'classifai' ) );
		}

		$embeddings = $this->read_object_embedding( 'term', $term_id );
		if ( ! empty( $embeddings ) && ! $force ) {
			return $embeddings;
		}

		$content        = $this->get_normalized_content( $term_id, 'term' );
		$content_chunks = $this->chunk_content( $content );

		$embeddings = [];
		foreach ( $content_chunks as $chunk ) {
			$embedding = $this->generate_embedding( $chunk, $feature );

			if ( $embedding && ! is_wp_error( $embedding ) ) {
				$embeddings[] = array_map( 'floatval', $embedding );
			} elseif ( is_wp_error( $embedding ) ) {
				return $embedding;
			}
		}

		if ( ! empty( $embeddings ) ) {
			$this->write_object_embedding( 'term', $term_id, $embeddings, md5( $content ) );
		}

		return $embeddings;
	}

	/**
	 * Regenerate a term's embedding after it has been edited.
	 *
	 * @param int $term_id ID of the edited term.
	 */
	public function update_embeddings_for_term( $term_id ) {
		$this->generate_embeddings_for_term( (int) $term_id, true );
	}

	/**
	 * Decide between one-shot and per-chunk embedding requests based on token budget.
	 *
	 * @param array        $content_chunks Pre-chunked content.
	 * @param string       $content        Full content (for token counting).
	 * @param Feature|null $feature        Driving feature, passed to the API helpers.
	 * @return array|WP_Error
	 */
	protected function generate_embeddings_for_chunks( array $content_chunks, string $content, $feature = null ) {
		if ( empty( $content_chunks ) ) {
			return [];
		}

		$max_tokens   = $this->get_max_tokens();
		$tokenizer    = new Tokenizer( $max_tokens );
		$total_tokens = $tokenizer->tokens_in_content( $content );

		// Per-chunk pathway: too many tokens, or provider doesn't expose a batch method.
		if ( $max_tokens < $total_tokens || ! method_exists( $this, 'generate_embeddings' ) ) {
			$embeddings = [];
			foreach ( $content_chunks as $chunk ) {
				$embedding = $this->generate_embedding( $chunk, $feature );

				if ( $embedding && ! is_wp_error( $embedding ) ) {
					$embeddings[] = array_map( 'floatval', $embedding );
				} elseif ( is_wp_error( $embedding ) ) {
					return $embedding;
				}
			}

			return $embeddings;
		}

		$all_embeddings = $this->generate_embeddings( $content_chunks, $feature );
		if ( is_wp_error( $all_embeddings ) ) {
			return $all_embeddings;
		}

		if ( ! $all_embeddings ) {
			return [];
		}

		return array_map(
			static function ( $embedding ) {
				return array_map( 'floatval', $embedding );
			},
			$all_embeddings
		);
	}

	/**
	 * Trigger embeddings generation when a post is saved (RecommendedContent path).
	 *
	 * @param int $post_id Post ID being saved.
	 */
	public function maybe_generated_embeddings_for_post( $post_id ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Only run on the 'post' post type for now.
		if ( 'post' !== get_post_type( $post_id ) ) {
			return;
		}

		if ( 'publish' !== get_post_status( $post_id ) ) {
			return;
		}

		$this->generate_embeddings_for_post( $post_id, true, new RecommendedContent() );
	}

	/**
	 * Link a post to terms based on its embeddings, or return the unlinked term list.
	 *
	 * @param int   $post_id    ID of post to classify.
	 * @param array $embeddings Embeddings (array of float[] chunks).
	 * @param bool  $link       Whether to call wp_set_object_terms.
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

		$prefix = $this->embeddings_filter_prefix();

		/** This action is documented in the trait. */
		do_action( $prefix . '_pre_sort_embeddings_similarity', $embeddings_similarity, $post_id, $embeddings, $link );

		usort(
			$embeddings_similarity,
			static function ( $a, $b ) {
				return $a['similarity'] <=> $b['similarity'];
			}
		);

		// Remove duplicates based on the term_id field.
		$uniques               = array_unique( array_column( $embeddings_similarity, 'term_id' ) );
		$embeddings_similarity = array_intersect_key( $embeddings_similarity, $uniques );

		$sorted_results = [];
		foreach ( $embeddings_similarity as $item ) {
			$sorted_results[ $item['taxonomy'] ][] = $item;
		}

		/** This action is documented in the trait. */
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
	 * @param array $embeddings Array of float[] chunks.
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
			static function ( $a, $b ) {
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
	 * Compute the similarity between a single embedding and every term that has
	 * an embedding stored.
	 *
	 * @param array $embedding         Single chunk vector.
	 * @param bool  $consider_threshold Whether to drop matches below threshold.
	 * @return array
	 */
	public function get_term_embeddings_similarity( array $embedding, bool $consider_threshold = true ): array {
		$feature              = new Classification();
		$embedding_similarity = [];
		$taxonomies           = $feature->get_all_feature_taxonomies();
		$calculations         = new EmbeddingCalculations();
		$prefix               = $this->embeddings_filter_prefix();

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

			$term_ids_with_embeddings = $this->objects_with_embeddings( 'term' );
			if ( empty( $term_ids_with_embeddings ) ) {
				continue;
			}

			$terms = get_terms(
				[
					'taxonomy'   => $tax,
					'orderby'    => 'count',
					'order'      => 'DESC',
					'hide_empty' => false,
					'fields'     => 'ids',
					'include'    => $term_ids_with_embeddings,
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

				$term_embedding = $this->read_object_embedding( 'term', (int) $term_id );
				if ( empty( $term_embedding ) ) {
					continue;
				}

				foreach ( $term_embedding as $chunk ) {
					$similarity = $calculations->cosine_similarity( $embedding, $chunk );

					/** This action is documented in the trait. */
					do_action( $prefix . '_single_embedding_similarity', $similarity, $embedding, $chunk, $term_id, $tax, $consider_threshold );

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

		return $embedding_similarity;
	}

	/**
	 * Determine which posts best match another post based on embeddings.
	 *
	 * @param array $embeddings Array of float[] chunks.
	 * @param int   $post_id    Source post ID (used for cache key).
	 * @return array|WP_Error
	 */
	public function get_posts( array $embeddings = [], int $post_id = 0 ) {
		$cache_key = \Classifai\recommended_content_cache_key( $post_id );
		$results   = wp_cache_get( $cache_key );

		if ( is_array( $results ) ) {
			return $results;
		}

		if ( empty( $embeddings ) ) {
			return new WP_Error( 'data_required', esc_html__( 'Valid embedding data is required.', 'classifai' ) );
		}

		$embeddings_similarity = [];
		foreach ( $embeddings as $embedding ) {
			$embeddings_similarity = array_merge( $embeddings_similarity, $this->get_post_embeddings_similarity( $embedding ) );
		}

		if ( empty( $embeddings_similarity ) ) {
			return new WP_Error( 'invalid', esc_html__( 'No matching items found.', 'classifai' ) );
		}

		usort(
			$embeddings_similarity,
			static function ( $a, $b ) {
				return $a['similarity'] <=> $b['similarity'];
			}
		);

		$uniques               = array_unique( array_column( $embeddings_similarity, 'post_id' ) );
		$embeddings_similarity = array_intersect_key( $embeddings_similarity, $uniques );

		$results = [];
		foreach ( $embeddings_similarity as $result ) {
			$similarity = round( ( 1 - $result['similarity'] ), 10 );
			$results[]  = [
				'post_id' => $result['post_id'],
				'score'   => $similarity,
			];
		}

		wp_cache_set( $cache_key, $results, '', 60 * MINUTE_IN_SECONDS );

		return $results;
	}

	/**
	 * Compute similarity between an embedding and a set of posts.
	 *
	 * @param array  $embedding          Single chunk vector.
	 * @param string $post_type          Post type to constrain to.
	 * @param bool   $consider_threshold Whether to drop matches below threshold.
	 * @return array
	 */
	public function get_post_embeddings_similarity( array $embedding, string $post_type = 'post', bool $consider_threshold = true ): array {
		$embedding_similarity = [];
		$calculations         = new EmbeddingCalculations();
		$prefix               = $this->embeddings_filter_prefix();

		static $posts     = null;
		static $threshold = null;

		if ( null === $posts ) {
			$post_ids_with_embeddings = $this->objects_with_embeddings( 'post' );
			if ( empty( $post_ids_with_embeddings ) ) {
				$posts = [];
			} else {
				$query_posts_per_page = method_exists( $this, 'get_max_posts' ) ? $this->get_max_posts() : 5000;
				$query                = new WP_Query(
					[
						'post_type'      => $post_type,
						'post_status'    => 'publish',
						'posts_per_page' => $query_posts_per_page,
						'fields'         => 'ids',
						'post__in'       => $post_ids_with_embeddings,
					]
				);
				$posts                = $query->get_posts();
			}
		}

		if ( empty( $posts ) ) {
			return [];
		}

		if ( null === $threshold ) {
			$settings  = $this->feature_instance->get_settings();
			$threshold = $settings[ static::ID ]['embedding_threshold'] ?? 75;
			$threshold = 1 - ( (float) $threshold / 100 );
		}

		foreach ( $posts as $post_id ) {
			$post_embedding = $this->read_object_embedding( 'post', (int) $post_id );
			if ( empty( $post_embedding ) ) {
				continue;
			}

			foreach ( $post_embedding as $chunk ) {
				$similarity = $calculations->cosine_similarity( $embedding, $chunk );

				/** This action is documented in the trait. */
				do_action( $prefix . '_single_post_embedding_similarity', $similarity, $embedding, $chunk, $post_id, $consider_threshold );

				if ( false !== $similarity && ( ! $consider_threshold || $similarity <= $threshold ) ) {
					$embedding_similarity[] = [
						'post_id'    => $post_id,
						'similarity' => $similarity,
					];
				}
			}
		}

		return $embedding_similarity;
	}

	/**
	 * Threshold for the similarity calculation (mapped to a 0..1 cosine distance).
	 *
	 * @param string $taxonomy Taxonomy slug to read the per-taxonomy threshold from.
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
	 * Schedule the term-embedding generation job for every term in a taxonomy
	 * that doesn't yet have an embedding.
	 *
	 * @param string $taxonomy Taxonomy slug.
	 * @param bool   $all      If true, regenerate all (including ones with embeddings).
	 * @param array  $args     Query overrides for get_terms().
	 * @param int    $user_id  User to attribute the job to.
	 */
	public function trigger_taxonomy_update( string $taxonomy = '', bool $all = false, array $args = [], int $user_id = 0 ) {
		$feature = new Classification();

		if (
			! $feature->is_feature_enabled() ||
			$feature->get_feature_provider_instance()::ID !== static::ID
		) {
			return;
		}

		$exclude = [];
		if ( 'category' === $taxonomy ) {
			$uncat_term = get_term_by( 'name', 'Uncategorized', 'category' );
			if ( $uncat_term ) {
				$exclude = [ $uncat_term->term_id ];
			}
		}

		/**
		 * Filter the number of terms to process in a batch.
		 *
		 * @hook {classifai_*_embeddings_terms_per_job}
		 *
		 * @param int $number Number of terms to process per job.
		 *
		 * @return int Filtered number of terms to process per job.
		 */
		$number = apply_filters( $this->embeddings_filter_prefix() . '_terms_per_job', 100 );

		$term_exclude = $exclude;
		if ( ! $all ) {
			$term_exclude = array_values(
				array_unique(
					array_merge( $term_exclude, $this->objects_with_embeddings( 'term' ) )
				)
			);
		}

		$default_args = [
			'taxonomy'   => $taxonomy,
			'orderby'    => 'count',
			'order'      => 'DESC',
			'hide_empty' => false,
			'fields'     => 'ids',
			'number'     => $number,
			'offset'     => 0,
			'exclude'    => $term_exclude, // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude
		];

		$default_args = array_merge( $default_args, $args );

		if ( $all ) {
			unset( $default_args['offset'] );
		}

		if ( 0 === $user_id ) {
			$user_id = get_current_user_id();
		}

		$job_args = [
			'taxonomy' => $taxonomy,
			'all'      => $all,
			'args'     => $default_args,
			'user_id'  => $user_id,
		];

		$action = $this->embeddings_term_job_action();
		if ( function_exists( 'as_has_scheduled_action' ) && ! \as_has_scheduled_action( $action, $job_args ) ) {
			$terms = get_terms( $default_args );
			if ( is_wp_error( $terms ) || empty( $terms ) ) {
				return;
			}
		}

		if ( function_exists( 'as_enqueue_async_action' ) ) {
			\as_enqueue_async_action( $action, $job_args );
		}
	}

	/**
	 * Action Scheduler handler that generates embeddings for a batch of terms.
	 *
	 * @param string $taxonomy Taxonomy slug.
	 * @param bool   $all      Whether to (re)generate for all terms.
	 * @param array  $args     get_terms() arguments.
	 * @param int    $user_id  User to attribute the work to.
	 */
	public function generate_term_embedding_job( string $taxonomy = '', bool $all = false, array $args = [], int $user_id = 0 ) {
		if ( $user_id > 0 ) {
			// current_user_can() fails when this function runs under the AS context.
			wp_set_current_user( $user_id );
		}

		$terms = get_terms( $args );
		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return;
		}

		$terms   = array_values( $terms );
		$exclude = [];

		foreach ( $terms as $term_id ) {
			$has_generated = $this->generate_embeddings_for_term( (int) $term_id, $all );
			if ( is_wp_error( $has_generated ) ) {
				$exclude[] = $term_id;
			}
		}

		if ( $all && isset( $args['offset'], $args['number'] ) ) {
			$args['offset'] = $args['offset'] + $args['number'];
		}

		if ( ! empty( $exclude ) ) {
			$args['exclude'] = array_merge( $args['exclude'] ?? [], $exclude ); // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude
		}

		$this->trigger_taxonomy_update( $taxonomy, $all, $args, $user_id );
	}

	/**
	 * Schedule the post-embedding generation job for every published post that
	 * doesn't yet have an embedding.
	 *
	 * @param string $post_type Post type slug.
	 * @param bool   $all       If true, regenerate all (including ones with embeddings).
	 * @param array  $args      Query overrides for WP_Query().
	 * @param int    $user_id   User to attribute the job to.
	 */
	public function trigger_post_update( string $post_type = 'post', bool $all = false, array $args = [], int $user_id = 0 ) {
		$feature = new RecommendedContent();

		if (
			! $feature->is_feature_enabled() ||
			$feature->get_feature_provider_instance()::ID !== static::ID
		) {
			return;
		}

		/**
		 * Filter the number of post items to process in a batch.
		 *
		 * @hook {classifai_*_embeddings_items_per_job}
		 *
		 * @param int $number Number of post items to process per job.
		 *
		 * @return int Filtered number of post items to process per job.
		 */
		$number = apply_filters( $this->embeddings_filter_prefix() . '_items_per_job', 100 );

		$post_ids_with_embeddings = $all ? [] : $this->objects_with_embeddings( 'post' );

		$default_args = [
			'post_type'      => $post_type,
			'post_status'    => 'publish',
			'posts_per_page' => $number,
			'fields'         => 'ids',
			'offset'         => 0,
			'post__not_in'   => $post_ids_with_embeddings, // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude
		];

		$default_args = array_merge( $default_args, $args );

		if ( $all ) {
			unset( $default_args['post__not_in'] );
		} else {
			unset( $default_args['offset'] );
		}

		if ( 0 === $user_id ) {
			$user_id = get_current_user_id();
		}

		$job_args = [
			'post_type' => $post_type,
			'all'       => $all,
			'args'      => $default_args,
			'user_id'   => $user_id,
		];

		$query = new WP_Query( $default_args );
		$posts = $query->get_posts();
		if ( empty( $posts ) ) {
			return;
		}

		$action = $this->embeddings_post_job_action();
		if ( '' === $action ) {
			return;
		}

		if ( function_exists( 'as_enqueue_async_action' ) ) {
			\as_enqueue_async_action( $action, $job_args );
		}
	}

	/**
	 * Action Scheduler handler for a batch of post-embedding work.
	 *
	 * @param string $post_type Post type.
	 * @param bool   $all       Whether to (re)generate for all posts.
	 * @param array  $args      WP_Query arguments.
	 * @param int    $user_id   User to attribute the work to.
	 */
	public function generate_post_embedding_job( string $post_type = '', bool $all = false, array $args = [], int $user_id = 0 ) {
		if ( $user_id > 0 ) {
			wp_set_current_user( $user_id );
		}

		$query = new WP_Query( $args );
		$posts = $query->get_posts();

		if ( empty( $posts ) ) {
			return;
		}

		$exclude = [];
		foreach ( $posts as $post_id ) {
			$has_generated = $this->generate_embeddings_for_post( (int) $post_id, $all, new RecommendedContent() );
			if ( is_wp_error( $has_generated ) ) {
				$exclude[] = $post_id;
			}
		}

		if ( $all && isset( $args['offset'], $args['posts_per_page'] ) ) {
			$args['offset'] = $args['offset'] + $args['posts_per_page'];
		}

		if ( ! empty( $exclude ) ) {
			$args['post__not_in'] = array_merge( $args['post__not_in'] ?? [], $exclude ); // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude
		}

		$this->trigger_post_update( $post_type, $all, $args, $user_id );
	}

	/**
	 * Regenerate every term + post embedding for the configured Classification feature.
	 */
	public function regenerate_embeddings() {
		$feature  = new Classification();
		$settings = $feature->get_settings();

		if (
			! $feature->is_feature_enabled() ||
			$feature->get_feature_provider_instance()::ID !== static::ID
		) {
			return;
		}

		foreach ( array_keys( $this->nlu_features ) as $feature_name ) {
			if ( isset( $settings[ $feature_name ] ) && 1 === (int) $settings[ $feature_name ] ) {
				$this->trigger_taxonomy_update( $feature_name, true );
			}
		}

		foreach ( $this->objects_with_embeddings( 'post' ) as $post_id ) {
			$this->delete_object_embedding( 'post', (int) $post_id );
		}

		update_option( 'classifai_hide_embeddings_notice', true, false );

		$notifications = new \Classifai\Admin\Notifications();
		$notifications->set_notice(
			esc_html__( 'Embeddings have been regenerated.', 'classifai' ),
			'success',
		);

		$redirect_url = admin_url( 'tools.php?page=classifai#/language_processing/feature_classification' );
		if ( \Classifai\should_use_legacy_settings_panel() ) {
			$redirect_url = admin_url( 'tools.php?page=classifai&tab=language_processing&feature=feature_classification' );
		}
		wp_safe_redirect( $redirect_url );
		exit;
	}

	/**
	 * AJAX previewer used by the settings UI.
	 */
	public function get_post_classifier_embeddings_preview_data() {
		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : false;

		if ( ! $nonce || ! wp_verify_nonce( $nonce, 'classifai-previewer-action' ) ) {
			wp_send_json_error( esc_html__( 'Failed nonce check.', 'classifai' ) );
		}

		$post_id          = filter_input( INPUT_POST, 'post_id', FILTER_SANITIZE_NUMBER_INT );
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
	 * Chunk content into smaller pieces with an overlap.
	 *
	 * @param string $content     Content to chunk.
	 * @param int    $chunk_size  Words per chunk.
	 * @param int    $overlap_size Words of overlap between adjacent chunks.
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
	 * Get the content for an object, ensuring it is normalized for embedding generation.
	 *
	 * @param int    $id   Object ID.
	 * @param string $type 'post' or 'term'.
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
				$term = get_term( $id );
				if ( is_a( $term, '\WP_Term' ) ) {
					$content = $term->name . ' ' . $term->slug . ' ' . $term->description;
				}
				break;
		}

		/**
		 * Filter the content that gets sent to the embedding provider.
		 *
		 * @hook {classifai_*_embeddings_content}
		 *
		 * @param string $content Content that will be sent to the provider.
		 * @param int    $id      Object ID.
		 * @param string $type    Type of content ('post' or 'term').
		 *
		 * @return string Content.
		 */
		return apply_filters( $this->embeddings_filter_prefix() . '_content', $content, $id, $type );
	}

	/**
	 * Default REST endpoint dispatcher — Classification 'classify' route.
	 *
	 * @param int    $post_id       Post ID we're processing.
	 * @param string $route_to_call Route name.
	 * @param array  $args          Optional arguments.
	 * @return array|string|WP_Error
	 */
	public function rest_endpoint_callback( $post_id = 0, string $route_to_call = '', array $args = [] ) {
		if ( ! $post_id || ! get_post( $post_id ) ) {
			return new WP_Error( 'post_id_required', esc_html__( 'A valid post ID is required.', 'classifai' ) );
		}

		$route_to_call = strtolower( $route_to_call );
		$return        = '';

		switch ( $route_to_call ) {
			case 'classify':
				$return = $this->generate_embeddings_for_post( $post_id, true );
				break;
		}

		return $return;
	}
}
