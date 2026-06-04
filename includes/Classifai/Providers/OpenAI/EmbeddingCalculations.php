<?php
/**
 * OpenAI Embedding calculations
 */

namespace Classifai\Providers\OpenAI;

class EmbeddingCalculations {

	/**
	 * Calculate the cosine distance between two embeddings.
	 *
	 * Returns `1 - cosine_similarity` so smaller numbers mean closer matches —
	 * this matches the existing caller convention (e.g. threshold comparisons in
	 * Classification, RecommendedContent). The original implementation used
	 * array_map closures; the unrolled loop here is roughly an order of magnitude
	 * faster on 1536-d vectors, which matters once Repository::find_similar fans
	 * the comparison across thousands of stored chunks.
	 *
	 * @param array $source_embedding Embedding data of the source item.
	 * @param array $compare_embedding Embedding data of the item to compare.
	 *
	 * @return bool|float
	 */
	public function cosine_similarity( array $source_embedding = [], array $compare_embedding = [] ) {
		if ( empty( $source_embedding ) || empty( $compare_embedding ) ) {
			return false;
		}

		// Re-index to 0-based so the tight loop can use $i indexing.
		$source  = array_values( $source_embedding );
		$compare = array_values( $compare_embedding );
		$length  = min( count( $source ), count( $compare ) );

		$dot           = 0.0;
		$source_norm2  = 0.0;
		$compare_norm2 = 0.0;

		for ( $i = 0; $i < $length; $i++ ) {
			$a              = (float) $source[ $i ];
			$b              = (float) $compare[ $i ];
			$dot           += $a * $b;
			$source_norm2  += $a * $a;
			$compare_norm2 += $b * $b;
		}

		$denominator = sqrt( $source_norm2 * $compare_norm2 );
		if ( 0.0 === $denominator ) {
			return false;
		}

		$distance = 1.0 - ( $dot / $denominator );

		// Ensure we are within the range of 0 to 1.0.
		return max( 0, min( abs( (float) $distance ), 1.0 ) );
	}
}
