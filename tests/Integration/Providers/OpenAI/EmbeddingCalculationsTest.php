<?php
/**
 * Tests for EmbeddingCalculations.
 */

namespace Classifai\Tests\Providers\OpenAI;

use Classifai\Tests\TestCase;
use Classifai\Providers\OpenAI\EmbeddingCalculations;

/**
 * @group providers
 * @coversDefaultClass \Classifai\Providers\OpenAI\EmbeddingCalculations
 */
class EmbeddingCalculationsTest extends TestCase {

	/**
	 * The method returns cosine *distance*: 0 for identical vectors.
	 *
	 * @covers ::cosine_similarity
	 */
	public function test_identical_vectors_have_zero_distance() {
		$calc = new EmbeddingCalculations();

		$this->assertEqualsWithDelta(
			0.0,
			$calc->cosine_similarity( [ 1, 2, 3 ], [ 1, 2, 3 ] ),
			0.0001
		);
	}

	/**
	 * @covers ::cosine_similarity
	 */
	public function test_orthogonal_vectors_have_distance_one() {
		$calc = new EmbeddingCalculations();

		$this->assertEqualsWithDelta(
			1.0,
			$calc->cosine_similarity( [ 1, 0 ], [ 0, 1 ] ),
			0.0001
		);
	}

	/**
	 * Opposite vectors yield a raw distance of 2, clamped to the [0, 1] range.
	 *
	 * @covers ::cosine_similarity
	 */
	public function test_opposite_vectors_are_clamped_to_one() {
		$calc = new EmbeddingCalculations();

		$this->assertEqualsWithDelta(
			1.0,
			$calc->cosine_similarity( [ 1, 2, 3 ], [ -1, -2, -3 ] ),
			0.0001
		);
	}

	/**
	 * @covers ::cosine_similarity
	 */
	public function test_empty_arrays_return_false() {
		$calc = new EmbeddingCalculations();

		$this->assertFalse( $calc->cosine_similarity( [], [ 1, 2 ] ) );
		$this->assertFalse( $calc->cosine_similarity( [ 1, 2 ], [] ) );
		$this->assertFalse( $calc->cosine_similarity( [], [] ) );
	}

	/**
	 * Mismatched lengths degrade gracefully (shorter vector zero-padded).
	 *
	 * @covers ::cosine_similarity
	 */
	public function test_mismatched_lengths_return_a_bounded_float() {
		$calc   = new EmbeddingCalculations();
		$result = $calc->cosine_similarity( [ 1, 2, 3 ], [ 1, 2 ] );

		$this->assertIsFloat( $result );
		$this->assertGreaterThanOrEqual( 0.0, $result );
		$this->assertLessThanOrEqual( 1.0, $result );
	}

	/**
	 * A zero-magnitude vector has an undefined cosine similarity. Rather than
	 * dividing by zero (which throws on PHP 8.0+ and warns on PHP 7.4), the
	 * method bails out gracefully like it does for empty embeddings.
	 *
	 * @covers ::cosine_similarity
	 */
	public function test_zero_magnitude_vector_returns_false() {
		$calc = new EmbeddingCalculations();

		$this->assertFalse( $calc->cosine_similarity( [ 0, 0, 0 ], [ 1, 2, 3 ] ) );
		$this->assertFalse( $calc->cosine_similarity( [ 1, 2, 3 ], [ 0, 0, 0 ] ) );
	}

	/**
	 * A single 1D embedding vector is wrapped into a 2D array of float vectors.
	 *
	 * @covers ::normalize_embeddings
	 */
	public function test_normalize_embeddings_wraps_single_vector_and_casts_to_float() {
		$calc = new EmbeddingCalculations();

		$this->assertSame(
			[ [ 0.0023, -0.0093, 1.0 ] ],
			$calc->normalize_embeddings( [ '0.0023', -0.0093, 1 ] )
		);
	}

	/**
	 * Multiple chunk vectors in a 2D array preserve their structure and are cast to floats.
	 *
	 * @covers ::normalize_embeddings
	 */
	public function test_normalize_embeddings_preserves_multiple_chunks_and_casts_to_float() {
		$calc = new EmbeddingCalculations();

		$this->assertSame(
			[
				[ 0.1, 0.2, 0.3 ],
				[ -0.4, 0.5, 0.6 ],
			],
			$calc->normalize_embeddings(
				[
					[ '0.1', 0.2, '0.3' ],
					[ -0.4, '0.5', 0.6 ],
				]
			)
		);
	}

	/**
	 * Empty or invalid inputs return an empty array and malformed chunks are skipped.
	 *
	 * @covers ::normalize_embeddings
	 */
	public function test_normalize_embeddings_handles_empty_or_invalid_inputs() {
		$calc = new EmbeddingCalculations();

		$this->assertSame( [], $calc->normalize_embeddings( [] ) );
		$this->assertSame( [], $calc->normalize_embeddings( null ) );
		$this->assertSame( [], $calc->normalize_embeddings( 'invalid' ) );
		$this->assertSame(
			[ [ 0.5, 0.25 ] ],
			$calc->normalize_embeddings( [ [ 0.5, 0.25 ], [] ] )
		);
	}
}
