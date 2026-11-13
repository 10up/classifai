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
	public function cosine_similarity( array $source_embedding = array(), array $compare_embedding = array() ) {
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

		// Guard against a zero-magnitude vector.
		$magnitude = sqrt( $source_value * $compare_value );

		if ( 0.0 === $magnitude ) {
			return false;
		}

		// Do the math.
		$distance = 1.0 - ( $combined_value / $magnitude );

		// Ensure we are within the range of 0 to 1.0.
		return max( 0, min( abs( (float) $distance ), 1.0 ) );
	}

	/**
	 * Normalize embeddings into an array of float vector arrays.
	 *
	 * Ensures that both single-vector (1D) embeddings and multi-chunk (2D)
	 * embeddings are consistently structured as `array<int, array<int, float>>`
	 * prior to saving or comparing.
	 *
	 * @param mixed $embeddings Embedding data to normalize.
	 * @return array[] Normalized array of float embedding vectors.
	 */
	public function normalize_embeddings( $embeddings ): array {
		if ( ! is_array( $embeddings ) || empty( $embeddings ) ) {
			return array();
		}

		$first_element = reset( $embeddings );

		// Wrap a single 1D embedding vector into a 2D array of chunks.
		if ( ! is_array( $first_element ) ) {
			$embeddings = array( array_values( $embeddings ) );
		}

		$normalized = array();

		foreach ( $embeddings as $chunk ) {
			if ( ! is_array( $chunk ) || empty( $chunk ) ) {
				continue;
			}

			$normalized[] = array_map( 'floatval', array_values( $chunk ) );
		}

		return $normalized;
	}
}
