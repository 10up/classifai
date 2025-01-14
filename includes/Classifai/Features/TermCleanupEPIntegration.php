<?php
/**
 * Integration with ElasticPress for the Term Cleanup Feature.
 */

namespace Classifai\Features;

use ElasticPress\Indexables;
use ElasticPress\Elasticsearch;
use WP_Error;

/**
 * ElasticPress Integration class.
 */
class TermCleanupEPIntegration {

	/**
	 * Elasticsearch version.
	 *
	 * @var string $es_version
	 */
	protected $es_version;

	/**
	 * Feature instance.
	 *
	 * @var TermCleanup $term_cleanup
	 */
	protected $term_cleanup;

	/**
	 * Setup needed variables.
	 *
	 * @param TermCleanup $feature Feature instance.
	 */
	public function __construct( $feature ) {
		$this->term_cleanup = $feature;
		$this->es_version   = Elasticsearch::factory()->get_elasticsearch_version();
	}

	/**
	 * Inintialize the class and register the needed hooks.
	 */
	public function init() {
		// Vector support was added in Elasticsearch 7.0.
		if ( version_compare( $this->es_version, '7.0', '<=' ) ) {
			return;
		}

		add_filter( 'ep_term_mapping', [ $this, 'add_term_vector_field_mapping' ] );
		add_filter( 'ep_prepare_term_meta_excluded_public_keys', [ $this, 'exclude_vector_meta' ] );
		add_filter( 'ep_term_sync_args', [ $this, 'add_vector_field_to_term_sync' ], 10, 2 );
	}

	/**
	 * Add our vector field mapping to the Elasticsearch term index.
	 *
	 * @param array $mapping Current mapping.
	 * @param int   $dimensions Number of dimensions for the vector field. Default 512.
	 * @param bool  $quantization Whether to use quantization for the vector field. Default false.
	 * @return array
	 */
	public function add_term_vector_field_mapping( array $mapping, int $dimensions = 512, bool $quantization = true ): array {
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
					'dims' => (int) $dimensions, // This needs to match the dimensions your model uses.
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
		$excluded_keys[] = $this->term_cleanup->get_embeddings_meta_key();

		return $excluded_keys;
	}

	/**
	 * Add the embedding data to the term vector sync args.
	 *
	 * @param array $args Current sync args.
	 * @param int   $term_id Term ID being synced.
	 * @return array
	 */
	public function add_vector_field_to_term_sync( array $args, int $term_id ): array {
		// Try to use the stored embeddings first.
		$meta_key   = $this->term_cleanup->get_embeddings_meta_key();
		$embeddings = get_term_meta( $term_id, $meta_key, true );

		// If they don't exist, make API requests to generate them.
		if ( ! $embeddings ) {
			$provider   = $this->term_cleanup->get_feature_provider_instance();
			$embeddings = $provider->generate_embeddings_for_term( $term_id, false, $this->term_cleanup );
		}

		// If we still don't have embeddings, return early.
		if ( ! $embeddings || empty( $embeddings ) ) {
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
		// Add the score to the document if it exists.
		if ( isset( $hit['_score'] ) ) {
			$document['score'] = $hit['_score'];
		}

		return $document;
	}

	/**
	 * Run an exact k-nearest neighbor (kNN) search.
	 *
	 * @param int    $term_id Term ID to search for.
	 * @param string $index Indexable to run the query against. Default term.
	 * @param int    $num Number of items to return.
	 * @param int    $threshold Threshold for the minimum score.
	 * @return array|WP_Error
	 */
	public function exact_knn_search( int $term_id, string $index = 'term', int $num = 1000, $threshold = 75 ) {
		$provider        = $this->term_cleanup->get_feature_provider_instance();
		$query_embedding = $provider->generate_embeddings_for_term( $term_id, false, $this->term_cleanup );
		$min_score       = 1 + ( $threshold / 100 );

		if ( is_wp_error( $query_embedding ) ) {
			return $query_embedding;
		}

		if ( is_array( $query_embedding ) ) {
			$query_embedding = $query_embedding[0];
		}

		// Get the ElasticPress indexable.
		$indexable = Indexables::factory()->get( $index );

		if ( ! $indexable ) {
			return new WP_Error( 'invalid_index', esc_html__( 'Invalid indexable provided.', 'classifai' ) );
		}

		// Build our exact kNN query.
		$knn_query = [
			'from'      => 0,
			'size'      => (int) $num,
			'query'     => [
				'bool' => [
					'must'     => [
						[
							'nested' => [
								'path'  => 'chunks',
								'query' => [
									'script_score' => [
										'query'  => [
											'match_all' => (object) [],
										],
										'script' => [
											'source' => 'cosineSimilarity(params.query_vector, "chunks.vector") + 1.0',
											'params' => [
												'query_vector' => array_map( 'floatval', $query_embedding ),
											],
										],
									],
								],
							],
						],
					],
					'must_not' => [
						[
							'term' => [
								'term_id' => $term_id,
							],
						],
					],
				],
			],
			'_source'   => [ 'term_id', 'score', 'taxonomy' ],
			'min_score' => $min_score,
		];

		// Add the score field to the document.
		add_filter( 'ep_retrieve_the_term', [ $this, 'add_score_field_to_document' ], 10, 2 );

		// Run the query using the ElasticPress indexable.
		$res = $indexable->query_es( $knn_query, [] );

		if ( false === $res || ! isset( $res['documents'] ) ) {
			return new WP_Error( 'es_error', esc_html__( 'Unable to query Elasticsearch', 'classifai' ) );
		}

		return $res['documents'];
	}
}
