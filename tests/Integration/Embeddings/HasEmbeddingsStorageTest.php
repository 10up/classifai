<?php

namespace Classifai\Tests\Integration\Embeddings;

use Classifai\Embeddings\HasEmbeddingsStorage;
use Classifai\Embeddings\MigrationRunner;
use Classifai\Embeddings\Repository;
use Classifai\Embeddings\Schema;
use Classifai\Embeddings\VectorCodec;

/**
 * Test double consumer of the trait so we can exercise it in isolation.
 */
class HasEmbeddingsStorageDouble {

	use HasEmbeddingsStorage;

	const ID = 'openai_embeddings';

	public function get_model(): string {
		return 'text-embedding-3-small';
	}

	protected function legacy_embedding_meta_key(): string {
		return 'classifai_openai_embeddings';
	}

	// Expose trait methods for testing.
	public function public_read( string $object_type, int $object_id ): array {
		return $this->read_object_embedding( $object_type, $object_id );
	}

	public function public_write( string $object_type, int $object_id, array $chunks ): void {
		$this->write_object_embedding( $object_type, $object_id, $chunks, 'hash' );
	}

	public function public_delete( string $object_type, int $object_id ): void {
		$this->delete_object_embedding( $object_type, $object_id );
	}

	public function public_objects_with_embeddings( string $object_type ): array {
		return $this->objects_with_embeddings( $object_type );
	}
}

/**
 * @group embeddings
 */
class HasEmbeddingsStorageTest extends \WP_UnitTestCase {

	public function set_up() {
		parent::set_up();
		Schema::maybe_install();
		global $wpdb;
		$wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}classifai_embeddings" ); // phpcs:ignore WordPress.DB
		foreach ( array_keys( MigrationRunner::LEGACY_KEYS ) as $key ) {
			$wpdb->delete( $wpdb->postmeta, [ 'meta_key' => $key ] ); // phpcs:ignore WordPress.DB
			$wpdb->delete( $wpdb->termmeta, [ 'meta_key' => $key ] ); // phpcs:ignore WordPress.DB
		}
		delete_option( MigrationRunner::STATUS_OPTION );
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

	public function test_write_then_read_round_trips() {
		$double  = new HasEmbeddingsStorageDouble();
		$post_id = self::factory()->post->create();
		$codec   = new VectorCodec();
		$chunks  = [ $codec->normalize( [ 0.1, 0.2 ] ), $codec->normalize( [ 0.3, 0.4 ] ) ];

		$double->public_write( 'post', $post_id, $chunks );
		$result = $double->public_read( 'post', $post_id );

		$this->assertCount( 2, $result );
	}

	public function test_read_falls_back_to_legacy_meta_and_inline_migrates() {
		$double  = new HasEmbeddingsStorageDouble();
		$post_id = self::factory()->post->create();
		update_post_meta( $post_id, 'classifai_openai_embeddings', [ [ 0.1, 0.2 ] ] );

		// Migration not yet completed — read should pull from legacy meta.
		$result = $double->public_read( 'post', $post_id );

		$this->assertCount( 1, $result );
		// Legacy meta should be gone after inline migration.
		$this->assertSame( '', get_post_meta( $post_id, 'classifai_openai_embeddings', true ) );
	}

	public function test_read_skips_legacy_fallback_when_migration_completed() {
		$double  = new HasEmbeddingsStorageDouble();
		$post_id = self::factory()->post->create();
		update_post_meta( $post_id, 'classifai_openai_embeddings', [ [ 0.1, 0.2 ] ] );
		( new MigrationRunner() )->mark_completed();

		$result = $double->public_read( 'post', $post_id );

		$this->assertSame( [], $result );
		// Legacy meta is left untouched when the fallback is short-circuited.
		$this->assertNotEmpty( get_post_meta( $post_id, 'classifai_openai_embeddings', true ) );
	}

	public function test_delete_removes_data_in_both_locations_during_migration() {
		$double  = new HasEmbeddingsStorageDouble();
		$post_id = self::factory()->post->create();
		$double->public_write( 'post', $post_id, [ [ 1.0, 0.0 ] ] );
		update_post_meta( $post_id, 'classifai_openai_embeddings', [ [ 0.5, 0.5 ] ] );

		$double->public_delete( 'post', $post_id );

		$this->assertSame( [], $double->public_read( 'post', $post_id ) );
		$this->assertSame( '', get_post_meta( $post_id, 'classifai_openai_embeddings', true ) );
	}

	public function test_objects_with_embeddings_unions_table_and_legacy_meta() {
		$double = new HasEmbeddingsStorageDouble();
		$p1     = self::factory()->post->create();
		$p2     = self::factory()->post->create();

		// p1 lives in the new table, p2 in legacy meta.
		$double->public_write( 'post', $p1, [ [ 1.0 ] ] );
		update_post_meta( $p2, 'classifai_openai_embeddings', [ [ 0.2 ] ] );

		$ids = $double->public_objects_with_embeddings( 'post' );
		sort( $ids );

		$expected = [ $p1, $p2 ];
		sort( $expected );
		$this->assertSame( $expected, $ids );
	}

	public function test_objects_with_embeddings_skips_legacy_after_migration() {
		$double = new HasEmbeddingsStorageDouble();
		$p1     = self::factory()->post->create();
		$p2     = self::factory()->post->create();

		$double->public_write( 'post', $p1, [ [ 1.0 ] ] );
		update_post_meta( $p2, 'classifai_openai_embeddings', [ [ 0.2 ] ] );
		( new MigrationRunner() )->mark_completed();

		$ids = $double->public_objects_with_embeddings( 'post' );

		$this->assertSame( [ $p1 ], $ids );
	}
}
