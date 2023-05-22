<?php
/**
 * OpenAI Embedding calculations
 */

namespace Classifai\Providers\OpenAI;

class EmbeddingCalculations {

	/**
	 * Calculate the similarity between two embeddings.
	 *
	 * This code is based on what OpenAI does in their Python SDK.
	 * See https://github.com/openai/openai-python/blob/ede0882939656ce4289cb4f61142e7658bb2dec7/openai/embeddings_utils.py#L141
	 *
	 * @param array $source_embedding Embedding data of the source item.
	 * @param array $compare_embedding Embedding data of the item to compare.
	 * @return bool|float
	 */
	public function similarity( array $source_embedding = [], array $compare_embedding = [] ) {
		if ( empty( $source_embedding ) || empty( $compare_embedding ) ) {
			return false;
		}

		// Get the combined average between the two embeddings.
		$combined_average = array_sum(
			array_map(
				function( $x, $y ) {
					return (float) $x * (float) $y;
				},
				$source_embedding,
				$compare_embedding
			)
		) / count( $source_embedding );

		// Get the average of the source embedding.
		$source_average = array_sum(
			array_map(
				function( $x ) {
					return pow( (float) $x, 2 );
				},
				$source_embedding
			)
		) / count( $source_embedding );

		// Get the average of the compare embedding.
		$compare_average = array_sum(
			array_map(
				function( $x ) {
					return pow( (float) $x, 2 );
				},
				$compare_embedding
			)
		) / count( $compare_embedding );

		// Do the math.
		$distance = 1.0 - ( $combined_average / sqrt( $source_average * $compare_average ) );

		// Ensure we are within the range of 0 to 2.0.
		return max( 0, min( abs( (float) $distance ), 2.0 ) );
	}

}
