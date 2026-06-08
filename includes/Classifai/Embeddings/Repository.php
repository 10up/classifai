<?php
/**
 * Read/write API for the ClassifAI embeddings table.
 */

namespace Classifai\Embeddings;

class Repository {

	/**
	 * VectorCodec instance for packing/unpacking float vectors.
	 *
	 * @var VectorCodec
	 */
	protected $codec;

	/**
	 * Repository constructor.
	 *
	 * @param VectorCodec|null $codec Optional codec; defaults to a fresh instance.
	 */
	public function __construct( ?VectorCodec $codec = null ) {
		$this->codec = null === $codec ? new VectorCodec() : $codec;
	}

	/**
	 * Store (or replace) all chunk vectors for one (object, feature, provider, model) tuple.
	 *
	 * Vectors are L2-normalized at write so cosine similarity == dot product on read.
	 *
	 * @param string $object_type  Either 'post', 'term', or another supported type.
	 * @param int    $object_id    ID of the object.
	 * @param string $feature      Feature ID (e.g. 'classification').
	 * @param string $provider     Provider ID (e.g. 'openai_embeddings').
	 * @param string $model        Model identifier.
	 * @param array  $chunks       Array of float[] vectors (one per content chunk).
	 * @param string $content_hash sha256/md5 of source content for cache busting.
	 */
	public function put( string $object_type, int $object_id, string $feature, string $provider, string $model, array $chunks, string $content_hash ): void {
		global $wpdb;
		$table = Schema::table_name();

		// Replace semantics — delete existing rows for this key first.
		$this->delete( $object_type, $object_id, $feature, $provider, $model );

		if ( empty( $chunks ) ) {
			return;
		}

		$now = current_time( 'mysql', true );

		foreach ( $chunks as $chunk_index => $vector ) {
			if ( ! is_array( $vector ) || empty( $vector ) ) {
				continue;
			}

			$normalized = $this->codec->normalize( $vector );
			$packed     = $this->codec->pack_floats( $normalized );

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- writing to our own custom table.
			$wpdb->insert(
				$table,
				[
					'object_type'  => $object_type,
					'object_id'    => $object_id,
					'feature'      => $feature,
					'provider'     => $provider,
					'model'        => $model,
					'dimensions'   => count( $normalized ),
					'chunk_index'  => (int) $chunk_index,
					'vector'       => $packed,
					'content_hash' => $content_hash,
					'created_at'   => $now,
					'updated_at'   => $now,
				],
				[ '%s', '%d', '%s', '%s', '%s', '%d', '%d', '%s', '%s', '%s', '%s' ]
			);
		}
	}

	/**
	 * Read all chunk vectors for a key, ordered by chunk_index.
	 *
	 * @param string $object_type Either 'post', 'term', etc.
	 * @param int    $object_id   ID of the object.
	 * @param string $feature     Feature ID.
	 * @param string $provider    Provider ID.
	 * @param string $model       Model identifier.
	 * @return array Array of float[] vectors. Empty array if no rows.
	 */
	public function get( string $object_type, int $object_id, string $feature, string $provider, string $model ): array {
		global $wpdb;
		$table = Schema::table_name();

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- reading from our own custom table.
		$rows = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT `vector` FROM {$table} WHERE object_type = %s AND object_id = %d AND feature = %s AND provider = %s AND model = %s ORDER BY chunk_index ASC",
				$object_type,
				$object_id,
				$feature,
				$provider,
				$model
			)
		);
		// phpcs:enable

		if ( empty( $rows ) ) {
			return [];
		}

		$chunks = [];
		foreach ( $rows as $blob ) {
			$chunks[] = $this->codec->unpack_floats( $blob );
		}

		return $chunks;
	}

	/**
	 * Delete every row for a key. Returns the number of rows removed.
	 *
	 * @param string $object_type Either 'post', 'term', etc.
	 * @param int    $object_id   ID of the object.
	 * @param string $feature     Feature ID.
	 * @param string $provider    Provider ID.
	 * @param string $model       Model identifier.
	 * @return int
	 */
	public function delete( string $object_type, int $object_id, string $feature, string $provider, string $model ): int {
		global $wpdb;
		$table = Schema::table_name();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- writing to our own custom table.
		$deleted = $wpdb->delete(
			$table,
			[
				'object_type' => $object_type,
				'object_id'   => $object_id,
				'feature'     => $feature,
				'provider'    => $provider,
				'model'       => $model,
			],
			[ '%s', '%d', '%s', '%s', '%s' ]
		);

		return (int) $deleted;
	}

	/**
	 * Delete every row for an object, regardless of feature, provider, or model.
	 *
	 * Used when the underlying object is permanently deleted — all of its vectors
	 * become garbage, including any stale rows left by a previously-configured
	 * provider/model. Returns the number of rows removed.
	 *
	 * @param string $object_type Either 'post', 'term', etc.
	 * @param int    $object_id   ID of the object.
	 * @return int
	 */
	public function delete_all_for_object( string $object_type, int $object_id ): int {
		global $wpdb;
		$table = Schema::table_name();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- writing to our own custom table.
		$deleted = $wpdb->delete(
			$table,
			[
				'object_type' => $object_type,
				'object_id'   => $object_id,
			],
			[ '%s', '%d' ]
		);

		return (int) $deleted;
	}

	/**
	 * Whether any chunk row exists for the given key.
	 *
	 * @param string $object_type Either 'post', 'term', etc.
	 * @param int    $object_id   ID of the object.
	 * @param string $feature     Feature ID.
	 * @param string $provider    Provider ID.
	 * @param string $model       Model identifier.
	 * @return bool
	 */
	public function exists( string $object_type, int $object_id, string $feature, string $provider, string $model ): bool {
		global $wpdb;
		$table = Schema::table_name();

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- reading from our own custom table.
		$found = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$table} WHERE object_type = %s AND object_id = %d AND feature = %s AND provider = %s AND model = %s LIMIT 1",
				$object_type,
				$object_id,
				$feature,
				$provider,
				$model
			)
		);
		// phpcs:enable

		return null !== $found;
	}

	/**
	 * Stored content_hash for a key, or null if no rows exist.
	 *
	 * @param string $object_type Either 'post', 'term', etc.
	 * @param int    $object_id   ID of the object.
	 * @param string $feature     Feature ID.
	 * @param string $provider    Provider ID.
	 * @param string $model       Model identifier.
	 * @return string|null
	 */
	public function content_hash( string $object_type, int $object_id, string $feature, string $provider, string $model ): ?string {
		global $wpdb;
		$table = Schema::table_name();

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- reading from our own custom table.
		$hash = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT content_hash FROM {$table} WHERE object_type = %s AND object_id = %d AND feature = %s AND provider = %s AND model = %s LIMIT 1",
				$object_type,
				$object_id,
				$feature,
				$provider,
				$model
			)
		);
		// phpcs:enable

		return null === $hash ? null : (string) $hash;
	}

	/**
	 * Unique list of object IDs that have at least one chunk row.
	 *
	 * @param string $object_type Either 'post', 'term', etc.
	 * @param string $feature     Feature ID.
	 * @param string $provider    Provider ID.
	 * @param string $model       Model identifier.
	 * @return int[]
	 */
	public function object_ids_with_embeddings( string $object_type, string $feature, string $provider, string $model ): array {
		global $wpdb;
		$table = Schema::table_name();

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- reading from our own custom table.
		$ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT DISTINCT object_id FROM {$table} WHERE object_type = %s AND feature = %s AND provider = %s AND model = %s",
				$object_type,
				$feature,
				$provider,
				$model
			)
		);
		// phpcs:enable

		return array_map( 'intval', $ids );
	}

	/**
	 * Compute cosine similarity between a query vector and stored vectors, ranked descending.
	 *
	 * Both sides are normalized so similarity == dot product. Results are deduplicated per
	 * object_id keeping the best-matching chunk.
	 *
	 * @param array $query_vector Float array to compare against.
	 * @param array $opts {
	 *     @type string $object_type Required.
	 *     @type string $feature     Required.
	 *     @type string $provider    Required.
	 *     @type string $model       Required.
	 *     @type int[]  $object_ids  Optional list of object IDs to restrict to.
	 *     @type int    $limit       Max results to return after dedup. Default 100.
	 * }
	 * @return array List of [ 'object_id' => int, 'chunk_index' => int, 'similarity' => float ].
	 */
	public function find_similar( array $query_vector, array $opts ): array {
		global $wpdb;
		$table = Schema::table_name();

		$object_type = $opts['object_type'] ?? 'post';
		$feature     = $opts['feature'] ?? '';
		$provider    = $opts['provider'] ?? '';
		$model       = $opts['model'] ?? '';
		$limit       = isset( $opts['limit'] ) ? (int) $opts['limit'] : 100;
		$object_ids  = $opts['object_ids'] ?? [];

		if ( empty( $query_vector ) || '' === $feature || '' === $provider || '' === $model ) {
			return [];
		}

		$query_normalized = $this->codec->normalize( $query_vector );
		$query_packed     = $this->codec->pack_floats( $query_normalized );

		$where        = 'object_type = %s AND feature = %s AND provider = %s AND model = %s';
		$where_values = [ $object_type, $feature, $provider, $model ];

		if ( ! empty( $object_ids ) ) {
			$object_ids   = array_map( 'intval', $object_ids );
			$placeholders = implode( ',', array_fill( 0, count( $object_ids ), '%d' ) );
			$where       .= " AND object_id IN ({$placeholders})";
			$where_values = array_merge( $where_values, $object_ids );
		}

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- reading from our own custom table; $where contains %s/%d placeholders.
		$rows = $wpdb->get_results(
			$wpdb->prepare( "SELECT object_id, chunk_index, `vector` FROM {$table} WHERE {$where}", $where_values ),
			ARRAY_A
		);
		// phpcs:enable

		if ( empty( $rows ) ) {
			return [];
		}

		$by_object = [];
		foreach ( $rows as $row ) {
			$score     = $this->codec->dot_product( $query_packed, $row['vector'] );
			$object_id = (int) $row['object_id'];

			if ( ! isset( $by_object[ $object_id ] ) || $score > $by_object[ $object_id ]['similarity'] ) {
				$by_object[ $object_id ] = [
					'object_id'   => $object_id,
					'chunk_index' => (int) $row['chunk_index'],
					'similarity'  => $score,
				];
			}
		}

		$results = array_values( $by_object );
		usort(
			$results,
			static function ( $a, $b ) {
				return $b['similarity'] <=> $a['similarity'];
			}
		);

		return array_slice( $results, 0, $limit );
	}
}
