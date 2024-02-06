<?php
/**
 * Helper functions specific to IBM Watson.
 */

namespace Classifai\Providers\Watson;

use Classifai\Features\Classification;

/**
 * Returns the currently configured Watson API URL. Lookup order is,
 *
 * - Options
 * - Constant
 *
 * @return string
 */
function get_api_url(): string {
	$settings = ( new Classification() )->get_settings();
	$creds    = ! empty( $settings[ NLU::ID ] ) ? $settings[ NLU::ID ] : [];

	if ( ! empty( $creds['endpoint_url'] ) ) {
		return $creds['endpoint_url'];
	} elseif ( defined( 'WATSON_URL' ) ) {
		return WATSON_URL;
	} else {
		return '';
	}
}

/**
 * Returns the currently configured Watson username. Lookup order is,
 *
 * - Options
 * - Constant
 *
 * @return string
 */
function get_username(): string {
	$settings = ( new Classification() )->get_settings( NLU::ID );
	$username = ! empty( $settings['username'] ) ? $settings['username'] : '';

	if ( ! empty( $username ) ) {
		return $username;
	} elseif ( defined( 'WATSON_USERNAME' ) ) {
		return WATSON_USERNAME;
	} else {
		return '';
	}
}

/**
 * Returns the currently configured Watson username. Lookup order is,
 *
 * - Options
 * - Constant
 *
 * @return string
 */
function get_password(): string {
	$settings = ( new Classification() )->get_settings( NLU::ID );
	$password = ! empty( $settings['password'] ) ? $settings['password'] : '';

	if ( ! empty( $password ) ) {
		return $password;
	} elseif ( defined( 'WATSON_PASSWORD' ) ) {
		return WATSON_PASSWORD;
	} else {
		return '';
	}
}

/**
 * Get Content Classification method.
 *
 * @since 2.6.0
 *
 * @return string
 */
function get_classification_method(): string {
	$settings = ( new Classification() )->get_settings( NLU::ID );

	return $settings['classification_method'] ?? '';
}

/**
 * Get Classification mode.
 *
 * @since 2.5.0
 *
 * @return string
 */
function get_classification_mode(): string {
	$feature  = new Classification();
	$settings = $feature->get_settings( NLU::ID );
	$value    = $settings['classification_mode'] ?? '';

	if ( $feature->is_feature_enabled() ) {
		if ( empty( $value ) ) {
			// existing users
			// default: automatic_classification
			return 'automatic_classification';
		}
	} else {
		// new users
		// default: manual_review
		return 'manual_review';
	}

	return $value;
}

/**
 * Returns a bool based on whether the specified feature is enabled
 *
 * @param string $classify_by category,keyword,entity,concept
 * @return bool
 */
function get_feature_enabled( string $classify_by ): bool {
	$feature  = new Classification();
	$settings = $feature->get_settings( NLU::ID );

	return filter_var(
		$settings[ $classify_by ],
		FILTER_VALIDATE_BOOLEAN
	);
}

/**
 * Returns the feature threshold based on current configuration. Lookup
 * order is.
 *
 * - Option
 * - Constant
 *
 * Any results below the threshold will be ignored.
 *
 * @param string $feature The feature whose threshold to lookup
 * @return float
 */
function get_feature_threshold( string $feature ): float {
	$classification_feature = new Classification();
	$settings               = $classification_feature->get_settings( NLU::ID );
	$threshold              = 0;

	if ( ! empty( $settings ) && ! empty( $settings[ $feature . '_threshold' ] ) ) {
		$threshold = filter_var(
			$settings[ $feature . '_threshold' ],
			FILTER_VALIDATE_INT
		);
	}

	if ( empty( $threshold ) ) {
		$constant = 'WATSON_' . strtoupper( $feature ) . '_THRESHOLD';

		if ( defined( $constant ) ) {
			$threshold = intval( constant( $constant ) );
		}
	}

	$threshold = empty( $threshold ) ? 0.7 : $threshold / 100;

	/**
	 * Filter the threshold for a specific feature. Any results below the
	 * threshold will be ignored.
	 *
	 * @since 1.0.0
	 * @hook classifai_feature_threshold
	 *
	 * @param {float} $threshold The threshold to use, expressed as a decimal between 0 and 1 inclusive.
	 * @param {string} $feature  The feature in question.
	 *
	 * @return {float} The filtered threshold.
	 */
	return apply_filters( 'classifai_feature_threshold', $threshold, $feature );
}

/**
 * Returns the Taxonomy for the specified NLU feature.
 *
 * Returns defaults in config.php if options have not been configured.
 *
 * @param string $classify_by NLU feature name
 * @return string Taxonomy mapped to the feature
 */
function get_feature_taxonomy( string $classify_by = '' ): string {
	$taxonomy = 0;

	$feature  = new Classification();
	$settings = $feature->get_settings( NLU::ID );

	if ( ! empty( $settings[ $classify_by . '_taxonomy' ] ) ) {
		$taxonomy = $settings[ $classify_by . '_taxonomy' ];
	}

	if ( empty( $taxonomy ) ) {
		$constant = 'WATSON_' . strtoupper( $classify_by ) . '_TAXONOMY';

		if ( defined( $constant ) ) {
			$taxonomy = constant( $constant );
		}
	}

	/**
	 * Filter the Taxonomy for the specified NLU feature.
	 *
	 * @since 1.1.0
	 * @hook classifai_taxonomy_for_feature
	 *
	 * @param {string} $taxonomy The slug of the taxonomy to use.
	 * @param {string} $classify_by  The NLU feature this taxonomy is for.
	 *
	 * @return {string} The filtered taxonomy slug.
	 */
	return apply_filters( 'classifai_taxonomy_for_feature', $taxonomy, $classify_by );
}
