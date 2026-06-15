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
	 * Mismatched lengths degrade gracefully by comparing the shared prefix.
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
}
