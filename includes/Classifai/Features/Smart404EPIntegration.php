<?php
/**
 * Integration with ElasticPress for the Smart 404 Feature.
 */

namespace Classifai\Features;

use Classifai\Features\Smart404;
use Classifai\Providers\OpenAI\Tokenizer;
use ElasticPress\Indexables;
use ElasticPress\Indexable;
use ElasticPress\Elasticsearch;
use WP_Error;
use WP_CLI;

use function ElasticPress\Utils\is_indexing_wpcli;

/**
 * ElasticPress Integration class.
 */
class Smart404EPIntegration {

	/**
	 * Embeddings handler.
	 *
	 * @var Embeddings $embeddings_handler
	 */
	protected $embeddings_handler;

	/**
	 * Elasticsearch version.
	 *
	 * @var string $es_version
	 */
	protected $es_version;

	/**
	 * Tokenizer handler.
	 *
	 * @var Tokenizer $tokenizer
	 */
	protected $tokenizer;

	/**
	 * Embeddings meta key.
	 *
	 * @var string $embeddings_meta_key
	 */
	protected $embeddings_meta_key = '';

	/**
	 * Post content hash meta key.
	 *
	 * @var string $content_hash_meta_key
	 */
	protected $content_hash_meta_key = 'classifai_post_content_hash';

	/**
	 * Setup needed variables.
	 *
	 * @param Classifai\Providers\Provider $provider Provider to use for embeddings.
	 */
	public function __construct( $provider = null ) {
		$this->embeddings_handler = $provider;
		$this->es_version         = ! $provider ? '7.0' : Elasticsearch::factory()->get_elasticsearch_version();
		$this->tokenizer          = ! $this->embeddings_handler ? new Tokenizer( 8191 ) : new Tokenizer( (int) $this->embeddings_handler->get_max_tokens() );

		if ( $provider ) {
			if ( 'openai_embeddings' === $provider::ID ) {
				$this->embeddings_meta_key = 'classifai_openai_embeddings';
			} elseif ( 'azure_openai_embeddings' === $provider::ID ) {
				$this->embeddings_meta_key = 'classifai_azure_openai_embeddings';
			}
		}
	}

	/**
	 * Inintialize the class and register the needed hooks.
	 */
	public function init() {
		// Vector support was added in Elasticsearch 7.0.
		if ( ! $this->es_version || version_compare( $this->es_version, '7.0', '<=' ) ) {
			return;
		}

		add_filter( 'ep_post_mapping', [ $this, 'add_post_vector_field_mapping' ] );
		add_filter( 'ep_prepare_meta_excluded_public_keys', [ $this, 'exclude_vector_meta' ] );
		add_filter( 'ep_post_sync_args_post_prepare_meta', [ $this, 'add_vector_field_to_post_sync' ], 10, 2 );
		add_filter( 'ep_retrieve_the_post', [ $this, 'add_score_field_to_document' ], 10, 2 );
	}

	/**
	 * Add our vector field mapping to the Elasticsearch post index.
	 *
	 * @param array $mapping Current mapping.
	 * @param bool  $quantization Whether to use quantization for the vector field. Default false.
	 * @return array
	 */
	public function add_post_vector_field_mapping( array $mapping, bool $quantization = true ): array {
		// Don't add the field if it already exists.
		if ( isset( $mapping['mappings']['properties']['chunks'] ) ) {
			return $mapping;
		}

		// Add the default vector field mapping.
		$mapping['mappings']['properties']['chunks'] = [
			'type'       => 'nested',
			'properties' => [
				'vector' => [
					'type' => 'dense_vector',
					'dims' => (int) $this->embeddings_handler->get_dimensions(),
				],
			],
		];

		// Add extra vector fields for newer versions of Elasticsearch.
		if ( version_compare( $this->es_version, '8.0', '>=' ) ) {
			// The index (true or false, default true) and similarity (l2_norm, dot_product or cosine) fields
			// were added in 8.0. The similarity field must be set if index is true.
			$mapping['mappings']['properties']['chunks']['properties']['vector'] = array_merge(
				$mapping['mappings']['properties']['chunks']['properties']['vector'],
				[
					'index'      => true,
					'similarity' => 'cosine',
				]
			);

			// The element_type field was added in 8.6. This can be either float (default) or byte.
			if ( version_compare( $this->es_version, '8.6', '>=' ) ) {
				$mapping['mappings']['properties']['chunks']['properties']['vector']['element_type'] = 'float';
			}

			// The int8_hnsw type was added in 8.12.
			if ( $quantization && version_compare( $this->es_version, '8.12', '>=' ) ) {
				// This is supposed to result in better performance but slightly less accurate results.
				// See https://www.elastic.co/guide/en/elasticsearch/reference/8.13/knn-search.html#knn-search-quantized-example.
				// Can test with this on and off and compare results to see what works best.
				$mapping['mappings']['properties']['chunks']['properties']['vector']['index_options']['type'] = 'int8_hnsw';
			}
		}

		return $mapping;
	}

	/**
	 * Exclude our vector meta from being synced.
	 *
	 * @param array $excluded_keys Current excluded keys.
	 * @return array
	 */
	public function exclude_vector_meta( array $excluded_keys ): array {
		$excluded_keys[] = $this->embeddings_meta_key;
		$excluded_keys[] = $this->content_hash_meta_key;

		return $excluded_keys;
	}

	/**
	 * Add the embedding data to the post vector sync args.
	 *
	 * @param array $args Current sync args.
	 * @param int   $post_id Post ID being synced.
	 * @return array
	 */
	public function add_vector_field_to_post_sync( array $args, int $post_id ): array {
		// No need to add vector data if no content exists.
		$post = get_post( $post_id );
		if ( empty( $post->post_content ) ) {
			return $args;
		}

		// Try to use the stored embeddings first if content hasn't changed.
		$embeddings   = get_post_meta( $post_id, $this->embeddings_meta_key, true );
		$content_hash = get_post_meta( $post_id, $this->content_hash_meta_key, true );

		// This will include the post title and post content combined.
		$content = $this->embeddings_handler->get_normalized_content( $post_id, 'post' );

		// Add the post slug to our content as well.
		$content = $post->post_name . ".\n\n" . $content;

		// If they don't exist or content has changed, make API requests to generate them.
		if ( ! $embeddings || md5( $content ) !== $content_hash ) {
			$embeddings = [];

			// Chunk the content into smaller pieces.
			$content_chunks = $this->embeddings_handler->chunk_content( $content );

			// Get the embeddings for each chunk.
			if ( ! empty( $content_chunks ) ) {
				$total_tokens = $this->tokenizer->tokens_in_content( $content );

				// If we have a lot of tokens, we need to get embeddings for each chunk individually.
				if ( (int) $this->embeddings_handler->get_max_tokens() < $total_tokens || ! method_exists( $this->embeddings_handler, 'generate_embeddings' ) ) {
					foreach ( $content_chunks as $chunk ) {
						$embedding = $this->get_embedding( $chunk );

						if ( $embedding && ! is_wp_error( $embedding ) ) {
							$embeddings[] = $embedding;
						}

						// Show an error message if something went wrong.
						if ( is_wp_error( $embedding ) ) {
							if ( is_indexing_wpcli() ) {
								WP_CLI::warning(
									sprintf(
										/* translators: %d is the post ID; %s is the error message */
										esc_html__( 'Error generating embedding for ID #%1$d: %2$s', 'classifai' ),
										$post_id,
										$embedding->get_error_message()
									)
								);
							}
						}
					}
				} else {
					// Otherwise let's get all embeddings in a single request.
					$embeddings = $this->get_embeddings( $content_chunks );

					// Show an error message if something went wrong.
					if ( is_wp_error( $embeddings ) ) {
						if ( is_indexing_wpcli() ) {
							WP_CLI::warning(
								sprintf(
									/* translators: %d is the post ID; %s is the error message */
									esc_html__( 'Error generating embedding for ID #%1$d: %2$s', 'classifai' ),
									$post_id,
									$embeddings->get_error_message()
								)
							);
						}

						$embeddings = [];
					}
				}
			}

			// Store the embeddings for future use.
			if ( ! empty( $embeddings ) ) {
				update_post_meta( $post_id, $this->embeddings_meta_key, $embeddings );
				update_post_meta( $post_id, $this->content_hash_meta_key, md5( $content ) );
			}
		}

		// If we still don't have embeddings, return early.
		if ( empty( $embeddings ) ) {
			if ( is_indexing_wpcli() ) {
				WP_CLI::warning(
					sprintf(
						/* translators: %d is the post ID */
						esc_html__( 'No embeddings generated for ID #%d', 'classifai' ),
						$post_id
					)
				);
			}

			return $args;
		}

		// Add the embeddings data to the sync args.
		$args['chunks'] = [];

		foreach ( $embeddings as $embedding ) {
			$args['chunks'][] = [
				'vector' => array_map( 'floatval', $embedding ),
			];
		}

		return $args;
	}

	/**
	 * Add the score field to the document.
	 *
	 * @param array $document Document retrieved from Elasticsearch.
	 * @param array $hit Raw Elasticsearch hit.
	 * @return array
	 */
	public function add_score_field_to_document( array $document, array $hit ): array {
		// Only modify if our field is present.
		if ( ! isset( $document['chunks'] ) ) {
			return $document;
		}

		// Add the score to the document if it exists.
		if ( isset( $hit['_score'] ) ) {
			$document['score'] = $hit['_score'];
		}

		return $document;
	}

	/**
	 * Get an embedding from a given text.
	 *
	 * @param string $text Text to get the embedding for.
	 * @param bool   $cache Whether to cache the result. Default false.
	 * @return array|WP_Error
	 */
	public function get_embedding( string $text, bool $cache = false ) {
		// Check to see if we have a stored embedding.
		if ( $cache ) {
			$key             = 'classifai_ep_embedding_' . sanitize_title( $text );
			$query_embedding = wp_cache_get( $key );

			if ( $query_embedding ) {
				return $query_embedding;
			}
		}

		// Generate the embedding.
		$embedding = $this->embeddings_handler->generate_embedding( $text, new Smart404() );

		if ( is_wp_error( $embedding ) ) {
			return $embedding;
		}

		// Store the embedding for future use if desired.
		if ( $cache ) {
			wp_cache_set( $key, $embedding );
		}

		return $embedding;
	}

	/**
	 * Get multiple embeddings at once.
	 *
	 * @param array $strings Array of text to get embeddings for.
	 * @return array|WP_Error
	 */
	public function get_embeddings( array $strings ) {
		// Generate the embeddings.
		$embeddings = $this->embeddings_handler->generate_embeddings( $strings, new Smart404() );

		return $embeddings;
	}

	/**
	 * Run an exact k-nearest neighbor (kNN) search.
	 *
	 * @param string $query Query to search for.
	 * @param array  $args {
	 *     Optional. Arguments to pass to the search.
	 *
	 *     @type string $index Indexable to run the query against. Default post.
	 *     @type array  $post_type Post types to return results of. Defaults to just post.
	 *     @type int    $num Number of items to return.
	 *     @type string $score_function Function to use for scoring. Default cosine.
	 * }
	 * @return array|WP_Error
	 */
	public function exact_knn_search( string $query, array $args = [] ) {
		$query_embedding = $this->get_embedding( $query, true );

		if ( is_wp_error( $query_embedding ) ) {
			return $query_embedding;
		}

		// Parse the arguments, setting our defaults.
		$args = wp_parse_args(
			$args,
			[
				'index'          => 'post',
				'post_type'      => [ 'post' ],
				'num'            => 5,
				'score_function' => 'cosine',
			]
		);

		// Get the ElasticPress indexable.
		$indexable = Indexables::factory()->get( $args['index'] );

		if ( ! $indexable ) {
			return new WP_Error( 'invalid_index', esc_html__( 'Invalid indexable provided.', 'classifai' ) );
		}

		// Build our exact kNN query.
		$knn_query = [
			'from'  => 0,
			'size'  => (int) $args['num'],
			'query' => [
				'bool' => [
					'must' => [
						[
							'terms' => [
								'post_type.raw' => $args['post_type'],
							],
						],
						[
							'terms' => [
								'post_status' => [
									'publish',
								],
							],
						],
						[
							'nested' => [
								'path'  => 'chunks',
								'query' => [
									'script_score' => [
										'query'  => [
											'match_all' => (object) [],
										],
										'script' => [
											'source' => $this->get_script_source( $args['score_function'] ),
											'params' => [
												'query_vector' => array_map( 'floatval', $query_embedding ),
											],
										],
									],
								],
							],
						],
					],
				],
			],
		];

		// Run the query using the ElasticPress indexable.
		$res = $indexable->query_es( $knn_query, [] );

		if ( false === $res || ! isset( $res['documents'] ) ) {
			return new WP_Error( 'es_error', esc_html__( 'Unable to query Elasticsearch', 'classifai' ) );
		}

		return $res['documents'];
	}

	/**
	 * Runs a normal ES search query then rescores results with an exact kNN search.
	 *
	 * @param string $query Query to search for.
	 * @param array  $args {
	 *     Optional. Arguments to pass to the search.
	 *
	 *     @type string $index Indexable to run the query against. Default post.
	 *     @type array  $post_type Post types to return results of. Defaults to just post.
	 *     @type int    $num Number of items to return.
	 *     @type int    $num_candidates Number of candidates to search. Larger numbers give better results but are slower.
	 *     @type string $score_function Function to use for scoring. Default cosine.
	 * }
	 * @return array|WP_Error
	 */
	public function search_rescored_by_exact_knn( string $query, array $args = [] ) {
		$query_embedding = $this->get_embedding( $query, true );

		if ( is_wp_error( $query_embedding ) ) {
			return $query_embedding;
		}

		// Parse the arguments, setting our defaults.
		$args = wp_parse_args(
			$args,
			[
				'index'          => 'post',
				'post_type'      => [ 'post' ],
				'num'            => 5,
				'num_candidates' => 50,
				'score_function' => 'cosine',
			]
		);

		// Get the ElasticPress indexable.
		$indexable = Indexables::factory()->get( $args['index'] );

		if ( ! $indexable ) {
			return new WP_Error( 'invalid_index', esc_html__( 'Invalid indexable provided.', 'classifai' ) );
		}

		// Build our default search query.
		$default_es_query = [
			'from' => 0,
			'size' => (int) $args['num_candidates'],
		];

		// Expand our default search query depending on the indexable type.
		switch ( $args['index'] ) {
			case 'post':
				$default_query = $this->default_search_post_query( $query, $args['post_type'], (int) $args['num_candidates'], $indexable );

				if ( isset( $default_query['query'] ) ) {
					$default_es_query['query'] = $default_query['query'];

					// Add the post_name field to the multi_match fields.
					for ( $key = 0; $key < 3; $key++ ) {
						if ( isset( $default_es_query['query']['function_score']['query']['bool']['should'][ $key ]['multi_match']['fields'] ) ) {
							$default_es_query['query']['function_score']['query']['bool']['should'][ $key ]['multi_match']['fields'] = array_merge( $default_es_query['query']['function_score']['query']['bool']['should'][ $key ]['multi_match']['fields'], [ 'post_name' ] );
						}
					}

					if ( isset( $default_query['post_filter'] ) ) {
						$default_es_query['post_filter'] = $default_query['post_filter'];
					}
				}

				break;
		}

		// Run the query using the ElasticPress indexable.
		$default_res = $indexable->query_es( $default_es_query, [] );

		if ( false === $default_res || ! isset( $default_res['documents'] ) ) {
			return new WP_Error( 'es_error', esc_html__( 'Unable to query Elasticsearch', 'classifai' ) );
		}

		// Get the post IDs from the default search.
		$post_ids = array_column( $default_res['documents'], 'post_id' );

		if ( empty( $post_ids ) ) {
			return new WP_Error( 'es_error', esc_html__( 'No post IDs found', 'classifai' ) );
		}

		// Build our exact kNN query.
		$knn_query = [
			'from'  => 0,
			'size'  => (int) $args['num'],
			'query' => [
				'bool' => [
					'must' => [
						[
							'bool' => [
								'must' => [
									'terms' => [
										'post_id' => $post_ids,
									],
								],
							],
						],
						[
							'nested' => [
								'path'  => 'chunks',
								'query' => [
									'script_score' => [
										'query'  => [
											'match_all' => (object) [],
										],
										'script' => [
											'source' => $this->get_script_source( $args['score_function'] ),
											'params' => [
												'query_vector' => array_map( 'floatval', $query_embedding ),
											],
										],
									],
								],
							],
						],
					],
				],
			],
		];

		// Run the query using the ElasticPress indexable.
		$res = $indexable->query_es( $knn_query, [] );

		if ( false === $res || ! isset( $res['documents'] ) ) {
			return new WP_Error( 'es_error', esc_html__( 'Unable to query Elasticsearch', 'classifai' ) );
		}

		return $res['documents'];
	}

	/**
	 * Build a default search post query.
	 *
	 * @param string    $query Query to search for.
	 * @param array     $post_type Post types to return results of.
	 * @param int       $num Number of items to return.
	 * @param Indexable $indexable Indexable to run the query against.
	 * @return array
	 */
	private function default_search_post_query( string $query, array $post_type, int $num, Indexable $indexable ): array {
		$search_args = [
			's'              => $query,
			'post_type'      => ! empty( $post_type ) ? $post_type : 'any',
			'posts_per_page' => (int) $num,
		];

		$search_query = new \WP_Query();

		$search_query->init();
		$search_query->query      = wp_parse_args( $search_args );
		$search_query->query_vars = $search_query->query;

		$default_query = $indexable->format_args( $search_query->query_vars, $search_query );

		return $default_query;
	}

	/**
	 * Set the script source based on the desired score function.
	 *
	 * @param string $type Type of score function to use. Default "cosineSimilarity".
	 * @return string
	 */
	private function get_script_source( string $type = 'cosine' ): string {
		$source = '';

		switch ( $type ) {
			case 'cosine':
			case 'cosine_similarity':
				$source = 'cosineSimilarity(params.query_vector, "chunks.vector") + 1.0';
				break;

			case 'dot':
			case 'dot_product':
				$source = 'double value = dotProduct(params.query_vector, "chunks.vector"); return sigmoid(1, Math.E, -value);';
				break;

			case 'l1_norm':
			case 'l1norm':
				$source = '1 / (1 + l1norm(params.query_vector, "chunks.vector"))';
				break;

			case 'l2_norm':
			case 'l2norm':
				$source = '1 / (1 + l2norm(params.query_vector, "chunks.vector"))';
				break;
		}

		return $source;
	}

	/**
	 * Convert Elasticsearch results to WP_Post objects.
	 *
	 * @param array $results Document results from Elasticsearch.
	 * @return array
	 */
	public function convert_es_results_to_post_objects( array $results ): array {
		$new_posts = [];

		// Turn each ES result into a WP_Post object.
		// Copied from ElasticPress\Indexable\Post\QueryIntegration::format_hits_as_posts.
		foreach ( $results as $post_array ) {
			// Don't convert if not needed.
			if ( is_a( $post_array, 'WP_Post' ) ) {
				$new_posts[] = $post_array;
				continue;
			}

			$post = new \stdClass();

			$post->ID      = $post_array['post_id'];
			$post->site_id = get_current_blog_id();

			if ( ! empty( $post_array['site_id'] ) ) {
				$post->site_id = $post_array['site_id'];
			}

			$post_return_args = [
				'post_type',
				'post_author',
				'post_name',
				'post_status',
				'post_title',
				'post_content',
				'post_excerpt',
				'post_date',
				'post_date_gmt',
				'permalink',
			];

			foreach ( $post_return_args as $key ) {
				if ( 'post_author' === $key ) {
					$post->$key = $post_array[ $key ]['id'];
				} elseif ( isset( $post_array[ $key ] ) ) {
					$post->$key = $post_array[ $key ];
				}
			}

			$post->elasticsearch = true;

			if ( $post ) {
				$new_posts[] = $post;
			}
		}

		return $new_posts;
	}
}
