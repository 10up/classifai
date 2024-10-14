<?php
/**
 * OpenAI Embedding calculations
 */

namespace Classifai\Providers\OpenAI;

class EmbeddingCalculations {

	/**
	 * Calculate the cosine similarity between two embeddings.
	 *
	 * This code is based on what OpenAI does in their Python SDK.
	 * See https://github.com/openai/openai-python/blob/ede0882939656ce4289cb4f61142e7658bb2dec7/openai/embeddings_utils.py#L141
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

		// Get the combined value between the two embeddings.
		$combined_value = array_sum(
			array_map(
				function ( $x, $y ) {
					return (float) $x * (float) $y;
				},
				$source_embedding,
				$compare_embedding
			)
		);

		// Get the combined value of the source embedding.
		$source_value = array_sum(
			array_map(
				function ( $x ) {
					return pow( (float) $x, 2 );
				},
				$source_embedding
			)
		);

		// Get the combined value of the compare embedding.
		$compare_value = array_sum(
			array_map(
				function ( $x ) {
					return pow( (float) $x, 2 );
				},
				$compare_embedding
			)
		);

		// Do the math.
		$distance = 1.0 - ( $combined_value / sqrt( $source_value * $compare_value ) );

		// Ensure we are within the range of 0 to 1.0.
		return max( 0, min( abs( (float) $distance ), 1.0 ) );
	}
}
