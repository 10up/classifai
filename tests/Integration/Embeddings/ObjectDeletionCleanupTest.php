<?php

namespace Classifai\Tests\Integration\Embeddings;

use Classifai\Embeddings\Repository;
use Classifai\Embeddings\Schema;

/**
 * Verifies embeddings are purged from the custom table when the underlying
 * post or term is permanently deleted (the Plugin::enable() cleanup hooks).
 *
 * @group embeddings
 */
class ObjectDeletionCleanupTest extends \WP_UnitTestCase {

	/**
	 * @var Repository
	 */
	private $repo;

	public function set_up() {
		parent::set_up();
		Schema::maybe_install();
		$this->repo = new Repository();
		global $wpdb;
		$wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}classifai_embeddings" ); // phpcs:ignore WordPress.DB
	}

	public function tear_down() {
		global $wpdb;
		$wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}classifai_embeddings" ); // phpcs:ignore WordPress.DB
		parent::tear_down();
	}

	public function test_permanently_deleting_a_post_purges_its_embeddings() {
		$post_id = self::factory()->post->create();
		$this->repo->put( 'post', $post_id, 'classification', 'openai_embeddings', 'm1', [ [ 0.1, 0.2 ] ], 'h1' );

		$this->assertNotEmpty( $this->repo->get( 'post', $post_id, 'classification', 'openai_embeddings', 'm1' ) );

		wp_delete_post( $post_id, true );

		$this->assertSame( [], $this->repo->get( 'post', $post_id, 'classification', 'openai_embeddings', 'm1' ) );
	}

	public function test_trashing_a_post_keeps_its_embeddings() {
		$post_id = self::factory()->post->create();
		$this->repo->put( 'post', $post_id, 'classification', 'openai_embeddings', 'm1', [ [ 0.1, 0.2 ] ], 'h1' );

		// Trashing is not a permanent deletion — embeddings should survive.
		wp_trash_post( $post_id );

		$this->assertNotEmpty( $this->repo->get( 'post', $post_id, 'classification', 'openai_embeddings', 'm1' ) );
	}

	public function test_deleting_a_term_purges_its_embeddings() {
		$term    = self::factory()->term->create_and_get( [ 'taxonomy' => 'category' ] );
		$term_id = (int) $term->term_id;
		$this->repo->put( 'term', $term_id, 'classification', 'openai_embeddings', 'm1', [ [ 0.3, 0.4 ] ], 'h2' );

		$this->assertNotEmpty( $this->repo->get( 'term', $term_id, 'classification', 'openai_embeddings', 'm1' ) );

		wp_delete_term( $term_id, 'category' );

		$this->assertSame( [], $this->repo->get( 'term', $term_id, 'classification', 'openai_embeddings', 'm1' ) );
	}
}
