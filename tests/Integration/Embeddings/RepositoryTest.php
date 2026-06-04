<?php

namespace Classifai\Tests\Integration\Embeddings;

use Classifai\Embeddings\Repository;
use Classifai\Embeddings\Schema;
use Classifai\Embeddings\VectorCodec;

/**
 * @group embeddings
 */
class RepositoryTest extends \WP_UnitTestCase {

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

	public function test_put_then_get_returns_chunks() {
		// Use pre-normalized vectors so put/get is a clean round-trip.
		$codec  = new VectorCodec();
		$chunks = [
			$codec->normalize( [ 0.1, 0.2, 0.3, 0.4 ] ),
			$codec->normalize( [ 0.5, 0.6, 0.7, 0.8 ] ),
		];

		$this->repo->put( 'post', 42, 'classification', 'openai_embeddings', 'text-embedding-3-small', $chunks, 'hash-abc' );

		$result = $this->repo->get( 'post', 42, 'classification', 'openai_embeddings', 'text-embedding-3-small' );
		$this->assertCount( 2, $result );
		foreach ( $chunks[0] as $i => $expected ) {
			$this->assertEqualsWithDelta( $expected, $result[0][ $i ], 1e-6 );
		}
		foreach ( $chunks[1] as $i => $expected ) {
			$this->assertEqualsWithDelta( $expected, $result[1][ $i ], 1e-6 );
		}
	}

	public function test_put_is_upsert_replaces_existing_rows() {
		$codec  = new VectorCodec();
		$first  = [ $codec->normalize( [ 0.1, 0.2, 0.3 ] ) ];
		$second = [ $codec->normalize( [ 0.9, 0.8, 0.7 ] ), $codec->normalize( [ 0.4, 0.5, 0.6 ] ) ];

		$this->repo->put( 'post', 1, 'classification', 'openai_embeddings', 'm1', $first, 'h1' );
		$this->repo->put( 'post', 1, 'classification', 'openai_embeddings', 'm1', $second, 'h2' );

		$result = $this->repo->get( 'post', 1, 'classification', 'openai_embeddings', 'm1' );
		$this->assertCount( 2, $result );
		$this->assertEqualsWithDelta( $second[0][0], $result[0][0], 1e-6 );
	}

	public function test_get_returns_empty_array_for_missing_record() {
		$this->assertSame( [], $this->repo->get( 'post', 999, 'classification', 'openai_embeddings', 'm1' ) );
	}

	public function test_delete_removes_rows_and_returns_count() {
		$chunks = [ [ 0.1, 0.2 ], [ 0.3, 0.4 ] ];
		$this->repo->put( 'post', 7, 'classification', 'openai_embeddings', 'm1', $chunks, 'h' );

		$deleted = $this->repo->delete( 'post', 7, 'classification', 'openai_embeddings', 'm1' );

		$this->assertSame( 2, $deleted );
		$this->assertSame( [], $this->repo->get( 'post', 7, 'classification', 'openai_embeddings', 'm1' ) );
	}

	public function test_exists_reflects_put_and_delete() {
		$this->assertFalse( $this->repo->exists( 'post', 5, 'classification', 'openai_embeddings', 'm1' ) );

		$this->repo->put( 'post', 5, 'classification', 'openai_embeddings', 'm1', [ [ 0.1 ] ], 'h' );
		$this->assertTrue( $this->repo->exists( 'post', 5, 'classification', 'openai_embeddings', 'm1' ) );

		$this->repo->delete( 'post', 5, 'classification', 'openai_embeddings', 'm1' );
		$this->assertFalse( $this->repo->exists( 'post', 5, 'classification', 'openai_embeddings', 'm1' ) );
	}

	public function test_content_hash_returns_stored_value() {
		$this->repo->put( 'post', 1, 'classification', 'openai_embeddings', 'm1', [ [ 0.1 ] ], 'hash-xyz' );

		$this->assertSame( 'hash-xyz', $this->repo->content_hash( 'post', 1, 'classification', 'openai_embeddings', 'm1' ) );
		$this->assertNull( $this->repo->content_hash( 'post', 999, 'classification', 'openai_embeddings', 'm1' ) );
	}

	public function test_find_similar_orders_by_similarity_descending() {
		$codec = new VectorCodec();
		$query = $codec->normalize( [ 1.0, 0.0, 0.0 ] );

		// Stored embeddings get normalized at write so similarity == dot product.
		$this->repo->put( 'post', 1, 'classification', 'openai_embeddings', 'm1', [ [ 0.0, 1.0, 0.0 ] ], 'h1' ); // 0
		$this->repo->put( 'post', 2, 'classification', 'openai_embeddings', 'm1', [ [ 1.0, 0.0, 0.0 ] ], 'h2' ); // 1
		$this->repo->put( 'post', 3, 'classification', 'openai_embeddings', 'm1', [ [ 0.5, 0.5, 0.0 ] ], 'h3' ); // ~0.707

		$results = $this->repo->find_similar(
			$query,
			[
				'object_type' => 'post',
				'feature'     => 'classification',
				'provider'    => 'openai_embeddings',
				'model'       => 'm1',
			]
		);

		$this->assertCount( 3, $results );
		$this->assertSame( 2, $results[0]['object_id'] );
		$this->assertSame( 3, $results[1]['object_id'] );
		$this->assertSame( 1, $results[2]['object_id'] );
		$this->assertEqualsWithDelta( 1.0, $results[0]['similarity'], 1e-6 );
	}

	public function test_find_similar_respects_limit() {
		$this->repo->put( 'post', 1, 'classification', 'openai_embeddings', 'm1', [ [ 1.0, 0.0 ] ], 'h1' );
		$this->repo->put( 'post', 2, 'classification', 'openai_embeddings', 'm1', [ [ 0.0, 1.0 ] ], 'h2' );
		$this->repo->put( 'post', 3, 'classification', 'openai_embeddings', 'm1', [ [ 1.0, 1.0 ] ], 'h3' );

		$results = $this->repo->find_similar(
			[ 1.0, 0.0 ],
			[
				'object_type' => 'post',
				'feature'     => 'classification',
				'provider'    => 'openai_embeddings',
				'model'       => 'm1',
				'limit'       => 2,
			]
		);

		$this->assertCount( 2, $results );
	}

	public function test_find_similar_can_restrict_to_object_ids() {
		$this->repo->put( 'post', 1, 'classification', 'openai_embeddings', 'm1', [ [ 1.0, 0.0 ] ], 'h1' );
		$this->repo->put( 'post', 2, 'classification', 'openai_embeddings', 'm1', [ [ 1.0, 0.0 ] ], 'h2' );
		$this->repo->put( 'post', 3, 'classification', 'openai_embeddings', 'm1', [ [ 1.0, 0.0 ] ], 'h3' );

		$results = $this->repo->find_similar(
			[ 1.0, 0.0 ],
			[
				'object_type' => 'post',
				'feature'     => 'classification',
				'provider'    => 'openai_embeddings',
				'model'       => 'm1',
				'object_ids'  => [ 1, 3 ],
			]
		);

		$ids = array_column( $results, 'object_id' );
		sort( $ids );
		$this->assertSame( [ 1, 3 ], $ids );
	}

	public function test_find_similar_separates_features() {
		$this->repo->put( 'post', 1, 'classification', 'openai_embeddings', 'm1', [ [ 1.0, 0.0 ] ], 'h1' );
		$this->repo->put( 'post', 2, 'recommended_content', 'openai_embeddings', 'm1', [ [ 1.0, 0.0 ] ], 'h2' );

		$results = $this->repo->find_similar(
			[ 1.0, 0.0 ],
			[
				'object_type' => 'post',
				'feature'     => 'classification',
				'provider'    => 'openai_embeddings',
				'model'       => 'm1',
			]
		);

		$this->assertCount( 1, $results );
		$this->assertSame( 1, $results[0]['object_id'] );
	}

	public function test_object_ids_with_embeddings_returns_unique_set() {
		// Two chunks for object 1 — should produce one id, not two.
		$this->repo->put( 'post', 1, 'classification', 'openai_embeddings', 'm1', [ [ 0.1, 0.2 ], [ 0.3, 0.4 ] ], 'h1' );
		$this->repo->put( 'post', 2, 'classification', 'openai_embeddings', 'm1', [ [ 0.5, 0.6 ] ], 'h2' );
		$this->repo->put( 'term', 9, 'classification', 'openai_embeddings', 'm1', [ [ 0.7, 0.8 ] ], 'h3' );

		$post_ids = $this->repo->object_ids_with_embeddings( 'post', 'classification', 'openai_embeddings', 'm1' );
		sort( $post_ids );
		$this->assertSame( [ 1, 2 ], $post_ids );

		$term_ids = $this->repo->object_ids_with_embeddings( 'term', 'classification', 'openai_embeddings', 'm1' );
		$this->assertSame( [ 9 ], $term_ids );
	}

	public function test_stored_vectors_are_normalized_for_unit_input() {
		// Non-unit input vector — Repository should normalize at write.
		$this->repo->put( 'post', 1, 'classification', 'openai_embeddings', 'm1', [ [ 3.0, 4.0 ] ], 'h' );

		$result = $this->repo->get( 'post', 1, 'classification', 'openai_embeddings', 'm1' );
		$magnitude = sqrt( $result[0][0] ** 2 + $result[0][1] ** 2 );
		$this->assertEqualsWithDelta( 1.0, $magnitude, 1e-6 );
	}
}
