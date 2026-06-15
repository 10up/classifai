<?php

namespace Classifai\Tests\Integration\Embeddings;

use Classifai\Embeddings\MigrationRunner;
use Classifai\Embeddings\Repository;
use Classifai\Embeddings\Schema;

/**
 * @group embeddings
 */
class MigrationRunnerTest extends \WP_UnitTestCase {

	/**
	 * @var Repository
	 */
	private $repo;

	/**
	 * @var MigrationRunner
	 */
	private $runner;

	public function set_up() {
		parent::set_up();
		Schema::maybe_install();
		global $wpdb;
		$wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}classifai_embeddings" ); // phpcs:ignore WordPress.DB
		// Some test infra leaves postmeta/termmeta across tests — clean every legacy key.
		foreach ( array_keys( MigrationRunner::LEGACY_KEYS ) as $key ) {
			$wpdb->delete( $wpdb->postmeta, [ 'meta_key' => $key ] ); // phpcs:ignore WordPress.DB
			$wpdb->delete( $wpdb->termmeta, [ 'meta_key' => $key ] ); // phpcs:ignore WordPress.DB
		}
		delete_option( MigrationRunner::STATUS_OPTION );

		$this->repo   = new Repository();
		$this->runner = new MigrationRunner( $this->repo );
	}

	public function tear_down() {
		global $wpdb;
		$wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}classifai_embeddings" ); // phpcs:ignore WordPress.DB
		foreach ( array_keys( MigrationRunner::LEGACY_KEYS ) as $key ) {
			$wpdb->delete( $wpdb->postmeta, [ 'meta_key' => $key ] ); // phpcs:ignore WordPress.DB
			$wpdb->delete( $wpdb->termmeta, [ 'meta_key' => $key ] ); // phpcs:ignore WordPress.DB
		}
		delete_option( MigrationRunner::STATUS_OPTION );
		parent::tear_down();
	}

	public function test_migrate_post_moves_meta_to_table() {
		$post_id = self::factory()->post->create();
		$legacy  = [
			[ 0.1, 0.2, 0.3, 0.4 ],
			[ 0.5, 0.6, 0.7, 0.8 ],
		];
		update_post_meta( $post_id, 'classifai_openai_embeddings', $legacy );

		$result = $this->runner->migrate_post( $post_id, 'classifai_openai_embeddings' );

		$this->assertTrue( $result );
		$stored = $this->repo->get( 'post', $post_id, MigrationRunner::SHARED_FEATURE, 'openai_embeddings', $this->runner->default_model_for( 'openai_embeddings' ) );
		$this->assertCount( 2, $stored );
	}

	public function test_migrate_post_deletes_legacy_meta() {
		$post_id = self::factory()->post->create();
		update_post_meta( $post_id, 'classifai_openai_embeddings', [ [ 0.1, 0.2 ] ] );

		$this->runner->migrate_post( $post_id, 'classifai_openai_embeddings' );

		$this->assertSame( '', get_post_meta( $post_id, 'classifai_openai_embeddings', true ) );
	}

	public function test_migrate_post_on_already_migrated_is_noop() {
		$post_id = self::factory()->post->create();
		update_post_meta( $post_id, 'classifai_openai_embeddings', [ [ 0.1, 0.2 ] ] );
		$this->runner->migrate_post( $post_id, 'classifai_openai_embeddings' );

		// No legacy meta left, so a second call should report "nothing to do".
		$this->assertFalse( $this->runner->migrate_post( $post_id, 'classifai_openai_embeddings' ) );
	}

	public function test_migrate_post_handles_missing_meta_gracefully() {
		$post_id = self::factory()->post->create();
		$this->assertFalse( $this->runner->migrate_post( $post_id, 'classifai_openai_embeddings' ) );
	}

	public function test_migrate_post_handles_single_chunk_legacy_format() {
		// Some old data may have been stored as a single flat array rather than array-of-arrays.
		$post_id = self::factory()->post->create();
		update_post_meta( $post_id, 'classifai_openai_embeddings', [ 0.1, 0.2, 0.3 ] );

		$result = $this->runner->migrate_post( $post_id, 'classifai_openai_embeddings' );

		$this->assertTrue( $result );
		$stored = $this->repo->get( 'post', $post_id, MigrationRunner::SHARED_FEATURE, 'openai_embeddings', $this->runner->default_model_for( 'openai_embeddings' ) );
		$this->assertCount( 1, $stored );
	}

	public function test_migrate_term_moves_meta_to_table() {
		$term_id = self::factory()->term->create();
		update_term_meta( $term_id, 'classifai_azure_openai_embeddings', [ [ 0.1, 0.2 ] ] );

		$result = $this->runner->migrate_term( $term_id, 'classifai_azure_openai_embeddings' );

		$this->assertTrue( $result );
		$this->assertSame( '', get_term_meta( $term_id, 'classifai_azure_openai_embeddings', true ) );
		$this->assertTrue(
			$this->repo->exists( 'term', $term_id, MigrationRunner::SHARED_FEATURE, 'azure_openai_embeddings', $this->runner->default_model_for( 'azure_openai_embeddings' ) )
		);
	}

	public function test_status_progresses_through_pending_running_completed() {
		$this->assertSame( MigrationRunner::STATUS_PENDING, $this->runner->status() );

		$this->runner->mark_running();
		$this->assertSame( MigrationRunner::STATUS_RUNNING, $this->runner->status() );

		$this->runner->mark_completed();
		$this->assertSame( MigrationRunner::STATUS_COMPLETED, $this->runner->status() );
	}

	public function test_scan_returns_ids_with_legacy_embeddings() {
		$post1 = self::factory()->post->create();
		$post2 = self::factory()->post->create();
		$term1 = self::factory()->term->create();
		update_post_meta( $post1, 'classifai_openai_embeddings', [ [ 0.1 ] ] );
		update_post_meta( $post2, 'classifai_ollama_embeddings', [ [ 0.2 ] ] );
		update_term_meta( $term1, 'classifai_openai_embeddings', [ [ 0.3 ] ] );

		$batch = $this->runner->scan( 100 );

		$found_objects = array_map(
			static function ( $item ) {
				return $item['object_type'] . ':' . $item['object_id'];
			},
			$batch
		);
		sort( $found_objects );

		$this->assertContains( 'post:' . $post1, $found_objects );
		$this->assertContains( 'post:' . $post2, $found_objects );
		$this->assertContains( 'term:' . $term1, $found_objects );
	}

	public function test_scan_returns_empty_when_nothing_left() {
		$this->assertSame( [], $this->runner->scan( 100 ) );
	}

	public function test_run_inline_migrates_everything_and_marks_completed() {
		$post1 = self::factory()->post->create();
		$post2 = self::factory()->post->create();
		$term1 = self::factory()->term->create();
		update_post_meta( $post1, 'classifai_openai_embeddings', [ [ 0.1 ] ] );
		update_post_meta( $post2, 'classifai_ollama_embeddings', [ [ 0.2 ] ] );
		update_term_meta( $term1, 'classifai_openai_embeddings', [ [ 0.3 ] ] );

		$this->runner->run_inline();

		$this->assertSame( MigrationRunner::STATUS_COMPLETED, $this->runner->status() );
		$this->assertSame( '', get_post_meta( $post1, 'classifai_openai_embeddings', true ) );
		$this->assertSame( '', get_post_meta( $post2, 'classifai_ollama_embeddings', true ) );
		$this->assertSame( '', get_term_meta( $term1, 'classifai_openai_embeddings', true ) );

		$this->assertTrue( $this->repo->exists( 'post', $post1, MigrationRunner::SHARED_FEATURE, 'openai_embeddings', $this->runner->default_model_for( 'openai_embeddings' ) ) );
		$this->assertTrue( $this->repo->exists( 'post', $post2, MigrationRunner::SHARED_FEATURE, 'ollama_embeddings', $this->runner->default_model_for( 'ollama_embeddings' ) ) );
		$this->assertTrue( $this->repo->exists( 'term', $term1, MigrationRunner::SHARED_FEATURE, 'openai_embeddings', $this->runner->default_model_for( 'openai_embeddings' ) ) );
	}

	public function test_schedule_completes_immediately_when_nothing_to_migrate() {
		// Fresh install: no legacy embedding meta anywhere.
		$this->assertSame( [], $this->runner->scan( 1 ) );

		$this->runner->schedule();

		// Should short-circuit to completed rather than queue a no-op background
		// scan that competes for Action Scheduler's single batch claim.
		$this->assertSame( MigrationRunner::STATUS_COMPLETED, $this->runner->status() );
	}

	public function test_schedule_does_not_complete_when_legacy_data_present() {
		$post_id = self::factory()->post->create();
		update_post_meta( $post_id, 'classifai_openai_embeddings', [ [ 0.1 ] ] );

		$this->runner->schedule();

		// There is real work to do, so the migration must not be marked done.
		$this->assertNotSame( MigrationRunner::STATUS_COMPLETED, $this->runner->status() );
	}
}
