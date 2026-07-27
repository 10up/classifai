<?php

namespace Classifai\Tests\Integration\Embeddings;

use Classifai\Embeddings\VectorCodec;

/**
 * @group embeddings
 */
class VectorCodecTest extends \WP_UnitTestCase {

	/**
	 * Pack then unpack must round-trip floats within float32 precision.
	 */
	public function test_pack_unpack_round_trip() {
		$codec    = new VectorCodec();
		$vector   = [ 0.1, -0.2, 0.3, 0.4, -0.5 ];
		$packed   = $codec->pack_floats( $vector );
		$unpacked = $codec->unpack_floats( $packed );

		$this->assertCount( count( $vector ), $unpacked );
		foreach ( $vector as $i => $expected ) {
			$this->assertEqualsWithDelta( $expected, $unpacked[ $i ], 1e-6 );
		}
	}

	/**
	 * Each float must occupy exactly 4 bytes in the packed blob.
	 */
	public function test_packed_size_is_four_bytes_per_float() {
		$codec  = new VectorCodec();
		$vector = array_fill( 0, 1536, 0.01 );
		$packed = $codec->pack_floats( $vector );

		$this->assertSame( 1536 * 4, strlen( $packed ) );
	}

	/**
	 * Normalize returns a unit-length vector (magnitude 1.0).
	 */
	public function test_normalize_produces_unit_vector() {
		$codec      = new VectorCodec();
		$normalized = $codec->normalize( [ 3.0, 4.0 ] );

		$magnitude = sqrt( array_sum( array_map( fn ( $x ) => $x * $x, $normalized ) ) );
		$this->assertEqualsWithDelta( 1.0, $magnitude, 1e-6 );
		// 3-4-5 triangle: should produce [0.6, 0.8].
		$this->assertEqualsWithDelta( 0.6, $normalized[0], 1e-6 );
		$this->assertEqualsWithDelta( 0.8, $normalized[1], 1e-6 );
	}

	/**
	 * Normalize of a zero vector returns the input as-is (no NaN).
	 */
	public function test_normalize_zero_vector_returns_zero() {
		$codec  = new VectorCodec();
		$result = $codec->normalize( [ 0.0, 0.0, 0.0 ] );

		$this->assertSame( [ 0.0, 0.0, 0.0 ], $result );
	}

	/**
	 * dot_product on packed blobs matches the manually computed dot.
	 */
	public function test_dot_product_matches_manual_calculation() {
		$codec = new VectorCodec();
		$a     = [ 0.1, 0.2, 0.3, 0.4 ];
		$b     = [ 0.5, 0.6, 0.7, 0.8 ];

		$expected = 0.1 * 0.5 + 0.2 * 0.6 + 0.3 * 0.7 + 0.4 * 0.8;
		$actual   = $codec->dot_product( $codec->pack_floats( $a ), $codec->pack_floats( $b ) );

		$this->assertEqualsWithDelta( $expected, $actual, 1e-6 );
	}

	/**
	 * dot_product on two normalized vectors equals cosine similarity.
	 */
	public function test_dot_product_of_normalized_vectors_is_cosine_similarity() {
		$codec      = new VectorCodec();
		$a          = $codec->normalize( [ 1.0, 2.0, 3.0 ] );
		$b          = $codec->normalize( [ 4.0, 5.0, 6.0 ] );
		$dot_packed = $codec->dot_product( $codec->pack_floats( $a ), $codec->pack_floats( $b ) );

		// Manual cosine of [1,2,3] and [4,5,6] = (4+10+18) / (sqrt(14) * sqrt(77)).
		$cosine = 32.0 / ( sqrt( 14.0 ) * sqrt( 77.0 ) );
		$this->assertEqualsWithDelta( $cosine, $dot_packed, 1e-6 );
	}

	/**
	 * dot_product on mismatched-length blobs returns 0.0 (defensive).
	 */
	public function test_dot_product_mismatched_lengths_returns_zero() {
		$codec = new VectorCodec();
		$a     = $codec->pack_floats( [ 0.1, 0.2 ] );
		$b     = $codec->pack_floats( [ 0.1, 0.2, 0.3 ] );

		$this->assertSame( 0.0, $codec->dot_product( $a, $b ) );
	}

	/**
	 * unpack_floats on an empty string returns an empty array.
	 */
	public function test_unpack_empty_blob_returns_empty_array() {
		$codec = new VectorCodec();
		$this->assertSame( [], $codec->unpack_floats( '' ) );
	}

	/**
	 * pack_floats coerces non-float scalars to floats.
	 */
	public function test_pack_floats_coerces_to_floats() {
		$codec    = new VectorCodec();
		$packed   = $codec->pack_floats( [ '0.1', 1, '0.3' ] );
		$unpacked = $codec->unpack_floats( $packed );

		$this->assertEqualsWithDelta( 0.1, $unpacked[0], 1e-6 );
		$this->assertEqualsWithDelta( 1.0, $unpacked[1], 1e-6 );
		$this->assertEqualsWithDelta( 0.3, $unpacked[2], 1e-6 );
	}
}
