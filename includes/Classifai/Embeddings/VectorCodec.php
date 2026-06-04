<?php
/**
 * Pack/unpack float32 vectors and compute dot products on packed blobs.
 */

namespace Classifai\Embeddings;

class VectorCodec {

	/**
	 * Pack an array of floats into a little-endian float32 binary blob.
	 *
	 * @param array $floats Numeric values; coerced to float.
	 * @return string Packed binary (4 bytes per element).
	 */
	public function pack_floats( array $floats ): string {
		if ( empty( $floats ) ) {
			return '';
		}

		$values = [];
		foreach ( $floats as $value ) {
			$values[] = (float) $value;
		}

		return pack( 'g*', ...$values );
	}

	/**
	 * Unpack a float32 binary blob back into an array of floats.
	 *
	 * @param string $blob Packed binary produced by pack_floats().
	 * @return array
	 */
	public function unpack_floats( string $blob ): array {
		if ( '' === $blob ) {
			return [];
		}

		$result = unpack( 'g*', $blob );
		if ( false === $result ) {
			return [];
		}

		return array_values( $result );
	}

	/**
	 * L2-normalize a vector so its magnitude is 1.
	 *
	 * Zero vectors are returned unchanged.
	 *
	 * @param array $vector Input vector.
	 * @return array
	 */
	public function normalize( array $vector ): array {
		$sum_squares = 0.0;
		foreach ( $vector as $value ) {
			$value        = (float) $value;
			$sum_squares += $value * $value;
		}

		if ( 0.0 === $sum_squares ) {
			$zeros = [];
			foreach ( $vector as $_ ) {
				$zeros[] = 0.0;
			}
			return $zeros;
		}

		$magnitude  = sqrt( $sum_squares );
		$normalized = [];
		foreach ( $vector as $value ) {
			$normalized[] = ( (float) $value ) / $magnitude;
		}

		return $normalized;
	}

	/**
	 * Dot product of two packed float32 blobs.
	 *
	 * Returns 0.0 on length mismatch or empty input.
	 *
	 * @param string $a_blob First packed vector.
	 * @param string $b_blob Second packed vector.
	 * @return float
	 */
	public function dot_product( string $a_blob, string $b_blob ): float {
		if ( strlen( $a_blob ) !== strlen( $b_blob ) || '' === $a_blob ) {
			return 0.0;
		}

		$a = unpack( 'g*', $a_blob );
		$b = unpack( 'g*', $b_blob );

		if ( false === $a || false === $b ) {
			return 0.0;
		}

		$sum   = 0.0;
		$count = count( $a );
		// unpack('g*', …) returns a 1-indexed array.
		for ( $i = 1; $i <= $count; $i++ ) {
			$sum += $a[ $i ] * $b[ $i ];
		}

		return $sum;
	}
}
