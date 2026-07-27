<?php
/**
 * Migrates legacy embedding meta into the classifai_embeddings table.
 */

namespace Classifai\Embeddings;

class MigrationRunner {

	const STATUS_OPTION    = 'classifai_embeddings_migration_status';
	const STATUS_PENDING   = 'pending';
	const STATUS_RUNNING   = 'running';
	const STATUS_COMPLETED = 'completed';

	const SHARED_FEATURE = 'embeddings';

	const SCAN_ACTION  = 'classifai_migrate_embeddings_scan';
	const BATCH_ACTION = 'classifai_migrate_embeddings_job';
	const AS_GROUP     = 'classifai_embeddings_migration';

	/**
	 * Legacy meta key -> provider id mapping.
	 *
	 * @var array<string,string>
	 */
	const LEGACY_KEYS = array(
		'classifai_openai_embeddings'       => 'openai_embeddings',
		'classifai_azure_openai_embeddings' => 'azure_openai_embeddings',
		'classifai_ollama_embeddings'       => 'ollama_embeddings',
	);

	/**
	 * Default model per provider (used when migrating legacy data that doesn't carry model info).
	 *
	 * @var array<string,string>
	 */
	const DEFAULT_MODELS = array(
		'openai_embeddings'       => 'text-embedding-3-small',
		'azure_openai_embeddings' => 'text-embedding-3-small',
		'ollama_embeddings'       => '',
	);

	/**
	 * Repository for reading/writing the new table.
	 *
	 * @var Repository
	 */
	protected $repo;

	/**
	 * Constructor.
	 *
	 * @param Repository|null $repo Optional repository; defaults to a fresh instance.
	 */
	public function __construct( ?Repository $repo = null ) {
		$this->repo = null === $repo ? new Repository() : $repo;
	}

	/**
	 * Current migration status.
	 *
	 * @return string
	 */
	public function status(): string {
		$value = get_option( self::STATUS_OPTION, self::STATUS_PENDING );
		return is_string( $value ) ? $value : self::STATUS_PENDING;
	}

	/**
	 * Flip status to "running".
	 */
	public function mark_running(): void {
		update_option( self::STATUS_OPTION, self::STATUS_RUNNING, false );
	}

	/**
	 * Flip status to "completed".
	 */
	public function mark_completed(): void {
		update_option( self::STATUS_OPTION, self::STATUS_COMPLETED, false );
	}

	/**
	 * Provider ID for a legacy meta key, or '' if unknown.
	 *
	 * @param string $meta_key Legacy meta key.
	 * @return string
	 */
	public function provider_for( string $meta_key ): string {
		return self::LEGACY_KEYS[ $meta_key ] ?? '';
	}

	/**
	 * Model name to use when migrating legacy data for a given provider.
	 *
	 * @param string $provider Provider ID.
	 * @return string
	 */
	public function default_model_for( string $provider ): string {
		$default = self::DEFAULT_MODELS[ $provider ] ?? 'unknown';

		/**
		 * Filter the model used when migrating legacy embeddings for a provider.
		 *
		 * Lets sites that filtered the provider's model also keep migration aligned.
		 *
		 * @since x.x.x
		 * @hook classifai_embeddings_migration_model
		 *
		 * @param string $model    Model id.
		 * @param string $provider Provider id.
		 */
		return (string) apply_filters( 'classifai_embeddings_migration_model', $default, $provider );
	}

	/**
	 * Migrate a single post's legacy embedding to the new table.
	 *
	 * Returns true on successful migration, false on no-op (missing or empty meta).
	 *
	 * @param int    $post_id  Post ID.
	 * @param string $meta_key Legacy meta key.
	 * @return bool
	 */
	public function migrate_post( int $post_id, string $meta_key ): bool {
		return $this->migrate_object( 'post', $post_id, $meta_key );
	}

	/**
	 * Migrate a single term's legacy embedding to the new table.
	 *
	 * Returns true on successful migration, false on no-op.
	 *
	 * @param int    $term_id  Term ID.
	 * @param string $meta_key Legacy meta key.
	 * @return bool
	 */
	public function migrate_term( int $term_id, string $meta_key ): bool {
		return $this->migrate_object( 'term', $term_id, $meta_key );
	}

	/**
	 * Core migration routine for a single object.
	 *
	 * @param string $object_type 'post' or 'term'.
	 * @param int    $object_id   Object ID.
	 * @param string $meta_key    Legacy meta key.
	 * @return bool
	 */
	protected function migrate_object( string $object_type, int $object_id, string $meta_key ): bool {
		$provider = $this->provider_for( $meta_key );
		if ( '' === $provider ) {
			return false;
		}

		$value = 'post' === $object_type
			? get_post_meta( $object_id, $meta_key, true )
			: get_term_meta( $object_id, $meta_key, true );

		if ( empty( $value ) ) {
			return false;
		}

		$chunks = $this->normalize_legacy_value( $value );
		if ( empty( $chunks ) ) {
			$this->delete_legacy_meta( $object_type, $object_id, $meta_key );
			return false;
		}

		$content_hash = $this->derive_content_hash( $object_type, $object_id );
		$model        = $this->default_model_for( $provider );

		$this->repo->put( $object_type, $object_id, self::SHARED_FEATURE, $provider, $model, $chunks, $content_hash );

		$this->delete_legacy_meta( $object_type, $object_id, $meta_key );

		return true;
	}

	/**
	 * Legacy values are sometimes a single flat float array, sometimes an array of float arrays.
	 *
	 * Normalize to array<array<float>>.
	 *
	 * @param mixed $value Raw value from postmeta/termmeta.
	 * @return array<array<float>>
	 */
	protected function normalize_legacy_value( $value ): array {
		if ( ! is_array( $value ) || empty( $value ) ) {
			return array();
		}

		// Already array-of-arrays.
		if ( is_array( reset( $value ) ) ) {
			$chunks = array();
			foreach ( $value as $chunk ) {
				if ( is_array( $chunk ) && ! empty( $chunk ) ) {
					$chunks[] = array_map( 'floatval', $chunk );
				}
			}
			return $chunks;
		}

		// Flat float array -> single chunk.
		return array( array_map( 'floatval', $value ) );
	}

	/**
	 * Remove the legacy meta row after we've migrated it.
	 *
	 * @param string $object_type 'post' or 'term'.
	 * @param int    $object_id   Object ID.
	 * @param string $meta_key    Legacy meta key.
	 */
	protected function delete_legacy_meta( string $object_type, int $object_id, string $meta_key ): void {
		if ( 'post' === $object_type ) {
			delete_post_meta( $object_id, $meta_key );
		} else {
			delete_term_meta( $object_id, $meta_key );
		}
	}

	/**
	 * Synthesize a content hash for the object so future regenerations can short-circuit.
	 *
	 * @param string $object_type 'post' or 'term'.
	 * @param int    $object_id   Object ID.
	 * @return string
	 */
	protected function derive_content_hash( string $object_type, int $object_id ): string {
		if ( 'post' === $object_type ) {
			$post = get_post( $object_id );
			if ( $post ) {
				return md5( $post->post_title . $post->post_content );
			}
		} else {
			$term = get_term( $object_id );
			if ( $term && ! is_wp_error( $term ) ) {
				return md5( $term->name . $term->description );
			}
		}

		return md5( 'migrated:' . $object_type . ':' . $object_id );
	}

	/**
	 * Find pending legacy meta rows.
	 *
	 * @param int $batch_size Maximum rows to return.
	 * @return array<int, array{object_type:string, object_id:int, meta_key:string}>
	 */
	public function scan( int $batch_size = 50 ): array {
		global $wpdb;

		$meta_keys    = array_keys( self::LEGACY_KEYS );
		$placeholders = implode( ',', array_fill( 0, count( $meta_keys ), '%s' ) );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- batch scan of legacy meta keys for migration.
		$post_rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT post_id AS object_id, meta_key FROM {$wpdb->postmeta} WHERE meta_key IN ({$placeholders}) ORDER BY meta_id ASC LIMIT %d",
				array_merge( $meta_keys, array( $batch_size ) )
			),
			ARRAY_A
		);

		$term_rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT term_id AS object_id, meta_key FROM {$wpdb->termmeta} WHERE meta_key IN ({$placeholders}) ORDER BY meta_id ASC LIMIT %d",
				array_merge( $meta_keys, array( $batch_size ) )
			),
			ARRAY_A
		);
		// phpcs:enable

		// phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- 'meta_key' here is a return-array key, not a meta_query argument.
		$results = array();
		foreach ( $post_rows as $row ) {
			$results[] = array(
				'object_type' => 'post',
				'object_id'   => (int) $row['object_id'],
				'meta_key'    => (string) $row['meta_key'],
			);
		}
		foreach ( $term_rows as $row ) {
			$results[] = array(
				'object_type' => 'term',
				'object_id'   => (int) $row['object_id'],
				'meta_key'    => (string) $row['meta_key'],
			);
		}
		// phpcs:enable

		return array_slice( $results, 0, $batch_size );
	}

	/**
	 * Run the whole migration inline (used by WP-CLI). Iterates until scan() is empty.
	 *
	 * @param int $batch_size Per-iteration batch size.
	 */
	public function run_inline( int $batch_size = 100 ): void {
		$this->mark_running();

		while ( true ) {
			$batch = $this->scan( $batch_size );
			if ( empty( $batch ) ) {
				break;
			}

			$this->process_batch( $batch );
		}

		$this->mark_completed();
	}

	/**
	 * Process one batch of legacy rows.
	 *
	 * @param array<int, array{object_type:string, object_id:int, meta_key:string}> $batch Batch produced by scan().
	 */
	public function process_batch( array $batch ): void {
		foreach ( $batch as $item ) {
			if ( 'post' === $item['object_type'] ) {
				$this->migrate_post( $item['object_id'], $item['meta_key'] );
			} else {
				$this->migrate_term( $item['object_id'], $item['meta_key'] );
			}
		}
	}

	/**
	 * Schedule the migration via Action Scheduler. No-op if already running/completed.
	 */
	public function schedule(): void {
		if ( self::STATUS_COMPLETED === $this->status() ) {
			return;
		}

		// Nothing to migrate; Flip straight to "completed"
		// instead of scheduling a no-op background job.
		if ( empty( $this->scan( 1 ) ) ) {
			$this->mark_completed();
			return;
		}

		if ( ! function_exists( 'as_enqueue_async_action' ) || ! function_exists( 'as_next_scheduled_action' ) ) {
			return;
		}

		$this->mark_running();

		if ( ! as_next_scheduled_action( self::SCAN_ACTION, array(), self::AS_GROUP ) ) {
			as_enqueue_async_action( self::SCAN_ACTION, array(), self::AS_GROUP );
		}
	}

	/**
	 * Action Scheduler handler for SCAN_ACTION.
	 *
	 * Pulls a batch, enqueues a BATCH_ACTION for it, and re-queues itself if there's more.
	 */
	public function handle_scan(): void {
		$batch = $this->scan( 50 );

		if ( empty( $batch ) ) {
			$this->mark_completed();
			return;
		}

		if ( function_exists( 'as_enqueue_async_action' ) ) {
			as_enqueue_async_action( self::BATCH_ACTION, array( $batch ), self::AS_GROUP );
			as_enqueue_async_action( self::SCAN_ACTION, array(), self::AS_GROUP );
		} else {
			$this->process_batch( $batch );
		}
	}

	/**
	 * Action Scheduler handler for BATCH_ACTION.
	 *
	 * @param array $batch Batch of legacy rows produced by scan().
	 */
	public function handle_batch( array $batch ): void {
		$this->process_batch( $batch );
	}

	/**
	 * Register Action Scheduler callbacks. Call from plugin bootstrap.
	 */
	public function register_hooks(): void {
		add_action( self::SCAN_ACTION, array( $this, 'handle_scan' ) );
		add_action( self::BATCH_ACTION, array( $this, 'handle_batch' ) );
		add_action( 'admin_notices', array( $this, 'maybe_render_admin_notice' ) );
	}

	/**
	 * Show a one-line admin notice while the backfill is running.
	 */
	public function maybe_render_admin_notice(): void {
		if ( self::STATUS_RUNNING !== $this->status() ) {
			return;
		}

		$remaining = count( $this->scan( 1 ) );
		if ( 0 === $remaining ) {
			// Scan reports nothing left but the AS callback hasn't fired yet — flip the flag.
			$this->mark_completed();
			return;
		}

		printf(
			'<div class="notice notice-info"><p>%s</p></div>',
			esc_html__( 'ClassifAI: migrating embedding data to a dedicated table in the background. This runs once per upgrade.', 'classifai' )
		);
	}
}
