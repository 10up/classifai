/**
 * Normalize a score for display as a percentage.
 *
 * This function takes a score and normalizes it to a percentage format for display purposes.
 *
 * @param {number} score The score to normalize.
 *
 * @return {number} The normalized score as a percentage.
 */
export function normalizeScore( score ) {
	return Number( ( score * 100 ).toFixed( 2 ) );
}
