<?php
/**
 * Trait used by embedding Providers to read/write vectors through the embeddings table
 * instead of post/term meta. Includes a transparent fallback to legacy meta during the
 * upgrade window so reads keep working before the backfill finishes.
 */

namespace Classifai\Embeddings;

trait HasEmbeddingsStorage {

	/**
	 * Cached Repository instance.
	 *
	 * @var Repository|null
	 */
	protected $embeddings_repo;

	/**
	 * Cached MigrationRunner instance.
	 *
	 * @var MigrationRunner|null
	 */
	protected $embeddings_migration_runner;

	/**
	 * Consumers must declare the meta key they used in the legacy storage layer.
	 */
	abstract protected function legacy_embedding_meta_key(): string;

	/**
	 * Provider ID for this embeddings provider (e.g. 'openai_embeddings').
	 *
	 * Defaults to the Provider's static ID — override if a class shares logic across providers.
	 *
	 * @return string
	 */
	protected function embeddings_provider_id(): string {
		return static::ID;
	}

	/**
	 * Model identifier for the currently configured embedding model.
	 *
	 * Defaults to the provider's own get_model() (OpenAI/Ollama style) or, when that
	 * doesn't exist (e.g. Azure, where the "model" is the user-configured deployment),
	 * to the documented default in MigrationRunner::DEFAULT_MODELS so reads of migrated
	 * rows still line up.
	 *
	 * @return string
	 */
	protected function embeddings_model_id(): string {
		if ( method_exists( $this, 'get_model' ) ) {
			return (string) $this->get_model();
		}

		return MigrationRunner::DEFAULT_MODELS[ $this->embeddings_provider_id() ] ?? 'default';
	}

	/**
	 * Lazy-initialized Repository accessor.
	 *
	 * @return \Classifai\Embeddings\Repository
	 */
	protected function embeddings_repo(): Repository {
		if ( null === $this->embeddings_repo ) {
			$this->embeddings_repo = new Repository();
		}
		return $this->embeddings_repo;
	}

	/**
	 * Lazy-initialized MigrationRunner accessor.
	 *
	 * @return \Classifai\Embeddings\MigrationRunner
	 */
	protected function embeddings_migration_runner(): MigrationRunner {
		if ( null === $this->embeddings_migration_runner ) {
			$this->embeddings_migration_runner = new MigrationRunner( $this->embeddings_repo() );
		}
		return $this->embeddings_migration_runner;
	}

	/**
	 * Read the stored embedding for an object. Returns array of chunk vectors.
	 *
	 * Falls back to the legacy meta key during the migration window and inline-migrates
	 * any rows it finds, so subsequent reads hit the table directly.
	 *
	 * @param string $object_type 'post' or 'term'.
	 * @param int    $object_id   Object ID.
	 * @return array<float[]>
	 */
	public function read_object_embedding( string $object_type, int $object_id ): array {
		$repo = $this->embeddings_repo();

		$stored = $repo->get(
			$object_type,
			$object_id,
			MigrationRunner::SHARED_FEATURE,
			$this->embeddings_provider_id(),
			$this->embeddings_model_id()
		);

		if ( ! empty( $stored ) ) {
			return $stored;
		}

		if ( MigrationRunner::STATUS_COMPLETED === $this->embeddings_migration_runner()->status() ) {
			return array();
		}

		$meta_key = $this->legacy_embedding_meta_key();
		$legacy   = 'post' === $object_type
			? get_post_meta( $object_id, $meta_key, true )
			: get_term_meta( $object_id, $meta_key, true );

		if ( empty( $legacy ) ) {
			return array();
		}

		if ( 'post' === $object_type ) {
			$this->embeddings_migration_runner()->migrate_post( $object_id, $meta_key );
		} else {
			$this->embeddings_migration_runner()->migrate_term( $object_id, $meta_key );
		}

		return $repo->get(
			$object_type,
			$object_id,
			MigrationRunner::SHARED_FEATURE,
			$this->embeddings_provider_id(),
			$this->embeddings_model_id()
		);
	}

	/**
	 * Persist an embedding (chunk array) for an object.
	 *
	 * @param string $object_type  'post' or 'term'.
	 * @param int    $object_id    Object ID.
	 * @param array  $chunks       Array of float[] vectors.
	 * @param string $content_hash sha256/md5 of the source content (optional).
	 */
	public function write_object_embedding( string $object_type, int $object_id, array $chunks, string $content_hash = '' ): void {
		if ( empty( $chunks ) ) {
			return;
		}

		$this->embeddings_repo()->put(
			$object_type,
			$object_id,
			MigrationRunner::SHARED_FEATURE,
			$this->embeddings_provider_id(),
			$this->embeddings_model_id(),
			$chunks,
			$content_hash
		);
	}

	/**
	 * Remove an embedding for an object.
	 *
	 * @param string $object_type 'post' or 'term'.
	 * @param int    $object_id   Object ID.
	 */
	public function delete_object_embedding( string $object_type, int $object_id ): void {
		$this->embeddings_repo()->delete(
			$object_type,
			$object_id,
			MigrationRunner::SHARED_FEATURE,
			$this->embeddings_provider_id(),
			$this->embeddings_model_id()
		);

		if ( MigrationRunner::STATUS_COMPLETED !== $this->embeddings_migration_runner()->status() ) {
			$meta_key = $this->legacy_embedding_meta_key();
			if ( 'post' === $object_type ) {
				delete_post_meta( $object_id, $meta_key );
			} else {
				delete_term_meta( $object_id, $meta_key );
			}
		}
	}

	/**
	 * IDs of objects that have an embedding stored. Union of the new table and any
	 * remaining legacy meta rows until the backfill completes.
	 *
	 * @param string $object_type 'post' or 'term'.
	 * @return int[]
	 */
	public function objects_with_embeddings( string $object_type ): array {
		$ids = $this->embeddings_repo()->object_ids_with_embeddings(
			$object_type,
			MigrationRunner::SHARED_FEATURE,
			$this->embeddings_provider_id(),
			$this->embeddings_model_id()
		);

		if ( MigrationRunner::STATUS_COMPLETED === $this->embeddings_migration_runner()->status() ) {
			return $ids;
		}

		global $wpdb;
		$meta_table = 'post' === $object_type ? $wpdb->postmeta : $wpdb->termmeta;
		$id_column  = 'post' === $object_type ? 'post_id' : 'term_id';
		$meta_key   = $this->legacy_embedding_meta_key();

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- enumerate IDs that still have a legacy meta row during the migration window.
		$legacy_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT DISTINCT {$id_column} FROM {$meta_table} WHERE meta_key = %s",
				$meta_key
			)
		);
		// phpcs:enable

		return array_values( array_unique( array_merge( $ids, array_map( 'intval', $legacy_ids ) ) ) );
	}
}
