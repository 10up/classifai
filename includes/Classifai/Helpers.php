<?php

namespace Classifai;

/**
 * Miscellaneous Helper functions to access different parts of the
 * ClassifAI plugin.
 */

/**
 * Returns the ClassifAI plugin's singleton instance
 *
 * @return Plugin
 */
function get_plugin() {
	return Plugin::get_instance();
}

/**
 * Returns the ClassifAI plugin's stored settings in the WP options
 */
function get_plugin_settings() {
	return get_option( 'classifai_settings' );
}

/**
 * Overwrites the ClassifAI plugin's stored settings. Expected format is,
 *
 * [
 *     'post_types' => [ <list of post type names> ]
 *     'features' => [
 *         <feature_name> => <bool>
 *         <feature_threshold> => <int>
 *     ],
 *     'credentials' => [
 *         'watson_username' => <string>
 *         'watson_password' => <string>
 *     ]
 * ]
 *
 * @param array $settings The settings we're saving.
 */
function set_plugin_settings( $settings ) {
	update_option( 'classifai_settings', $settings );
}

/**
 * Resets the plugin to factory defaults.
 */
function reset_plugin_settings() {
	$settings = [
		'post_types' => [
			'post',
			'page',
		],
		'features'   => [
			'category'           => true,
			'category_threshold' => WATSON_CATEGORY_THRESHOLD,
			'category_taxonomy'  => WATSON_CATEGORY_TAXONOMY,

			'keyword'            => true,
			'keyword_threshold'  => WATSON_KEYWORD_THRESHOLD,
			'keyword_taxonomy'   => WATSON_KEYWORD_TAXONOMY,

			'concept'            => false,
			'concept_threshold'  => WATSON_CONCEPT_THRESHOLD,
			'concept_taxonomy'   => WATSON_CONCEPT_TAXONOMY,

			'entity'             => false,
			'entity_threshold'   => WATSON_ENTITY_THRESHOLD,
			'entity_taxonomy'    => WATSON_ENTITY_TAXONOMY,
		],
	];

	update_option( 'classifai_settings', $settings );
}


/**
 * Returns the currently configured Watson API URL. Lookup order is,
 *
 * - Options
 * - Constant
 *
 * @return string
 */
function get_watson_api_url() {
	$settings = get_plugin_settings();
	$creds    = ! empty( $settings['credentials'] ) ? $settings['credentials'] : [];

	if ( ! empty( $creds['watson_url'] ) ) {
		return $creds['watson_url'];
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
function get_watson_username() {
	$settings = get_plugin_settings();
	$creds    = ! empty( $settings['credentials'] ) ? $settings['credentials'] : [];

	if ( ! empty( $creds['watson_username'] ) ) {
		return $creds['watson_username'];
	} else if ( defined( 'WATSON_USERNAME' ) ) {
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
function get_watson_password() {
	$settings = get_plugin_settings();
	$creds    = ! empty( $settings['credentials'] ) ? $settings['credentials'] : [];

	if ( ! empty( $creds['watson_password'] ) ) {
		return $creds['watson_password'];
	} else if ( defined( 'WATSON_PASSWORD' ) ) {
		return WATSON_PASSWORD;
	} else {
		return '';
	}
}

/**
 * The list of post types that get the ClassifAI taxonomies. Defaults
 * to 'post'.
 *
 * return array
 */
function get_supported_post_types() {
	$classifai_settings = get_plugin_settings();

	if ( empty( $classifai_settings ) ) {
		$post_types = [];
	} else {
		$post_types = [];
		foreach ( $classifai_settings['post_types'] as $post_type => $enabled ) {
			if ( ! empty( $enabled ) ) {
				$post_types[] = $post_type;
			}
		}
	}

	if ( empty( $post_types ) ) {
		$post_types = [ 'post' ];
	}

	$post_types = apply_filters( 'classifai_post_types', $post_types );

	return $post_types;
}

/**
 * Returns a bool based on whether the specified feature is enabled
 *
 * @param string $feature category,keyword,entity,concept
 * @return bool
 */
function get_feature_enabled( $feature ) {
	$settings = get_plugin_settings();

	if ( ! empty( $settings ) && ! empty( $settings['features'] ) ) {
		if ( ! empty( $settings['features'][ $feature ] ) ) {
			return filter_var(
				$settings['features'][ $feature ],
				FILTER_VALIDATE_BOOLEAN
			);
		}
	}

	return false;
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
 * @return int
 */
function get_feature_threshold( $feature ) {
	$settings  = get_plugin_settings();
	$threshold = 0;

	if ( ! empty( $settings ) && ! empty( $settings['features'] ) ) {
		if ( ! empty( $settings['features'][ $feature . '_threshold' ] ) ) {
			$threshold = filter_var(
				$settings['features'][ $feature . '_threshold' ],
				FILTER_VALIDATE_INT
			);
		}
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
	 * @param string $threshold The threshold to use.
	 * @param string $feature   The feature whose threshold to lookup.
	 *
	 * @ return string $threshold The filtered threshold.
	 */
	return apply_filters( 'classifai_feature_threshold', $threshold, $feature );
}

/**
 * Returns the Taxonomy for the specified NLU feature. Returns defaults
 * in config.php if options have not been configured.
 *
 * @param string $feature NLU feature name
 * @return string Taxonomy mapped to the feature
 */
function get_feature_taxonomy( $feature ) {
	$settings  = get_plugin_settings();
	$taxonomy  = 0;

	if ( ! empty( $settings ) && ! empty( $settings['features'] ) ) {
		if ( ! empty( $settings['features'][ $feature . '_taxonomy' ] ) ) {
			$taxonomy = $settings['features'][ $feature . '_taxonomy' ];
		}
	}

	if ( empty( $taxonomy ) ) {
		$constant = 'WATSON_' . strtoupper( $feature ) . '_TAXONOMY';

		if ( defined( $constant ) ) {
			$taxonomy = constant( $constant );
		}
	}

	/**
	 * Filter the Taxonomy for the specified NLU feature.
	 *
	 * @param string $taxonomy The taxonomy to use.
	 * @param string $feature  The NLU feature this taxonomy is for.
	 *
	 * @return string $taxonomy The filtered taxonomy.
	 */
	return apply_filters( 'classifai_taxonomy_for_feature', $taxonomy, $feature );
}
