<?php

namespace Classifai\Tests\Integration\Embeddings;

use Classifai\Providers\OpenAI\EmbeddingCalculations;

/**
 * @group embeddings
 *
 * Pinned-value regression test for EmbeddingCalculations. The method has callers in
 * Classification/RecommendedContent and was previously implemented with array_map +
 * pow — the refactor must keep the public return semantics intact.
 */
class EmbeddingCalculationsTest extends \WP_UnitTestCase {

	public function test_returns_zero_distance_for_identical_vectors() {
		$calc   = new EmbeddingCalculations();
		$vector = [ 0.1, 0.2, 0.3, 0.4 ];

		// Identical vectors -> cosine=1 -> distance = 1 - 1 = 0.
		$this->assertEqualsWithDelta( 0.0, $calc->cosine_similarity( $vector, $vector ), 1e-6 );
	}

	public function test_returns_one_distance_for_orthogonal_vectors() {
		$calc = new EmbeddingCalculations();

		// Orthogonal vectors -> cosine=0 -> distance = 1.
		$this->assertEqualsWithDelta( 1.0, $calc->cosine_similarity( [ 1.0, 0.0 ], [ 0.0, 1.0 ] ), 1e-6 );
	}

	public function test_matches_manual_calculation() {
		$calc = new EmbeddingCalculations();
		$a    = [ 1.0, 2.0, 3.0 ];
		$b    = [ 4.0, 5.0, 6.0 ];

		// Manual cosine of [1,2,3] and [4,5,6] = (4+10+18) / (sqrt(14) * sqrt(77)).
		$expected_distance = 1.0 - ( 32.0 / ( sqrt( 14.0 ) * sqrt( 77.0 ) ) );
		$this->assertEqualsWithDelta( $expected_distance, $calc->cosine_similarity( $a, $b ), 1e-6 );
	}

	public function test_returns_false_for_empty_inputs() {
		$calc = new EmbeddingCalculations();
		$this->assertFalse( $calc->cosine_similarity( [], [ 1.0, 2.0 ] ) );
		$this->assertFalse( $calc->cosine_similarity( [ 1.0, 2.0 ], [] ) );
	}

	public function test_clamps_to_zero_to_one_range() {
		$calc = new EmbeddingCalculations();
		// Negative-cosine inputs (opposite vectors) -> distance should be clamped within [0,1].
		$result = $calc->cosine_similarity( [ 1.0, 1.0 ], [ -1.0, -1.0 ] );
		$this->assertGreaterThanOrEqual( 0.0, $result );
		$this->assertLessThanOrEqual( 1.0, $result );
	}
}
