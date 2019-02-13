<?php
/**
 * Global Constants for the Classifai Support Plugin. Constants should be
 * declared here instead of a Class.
 */

$plugin_version = '1.1.0';

if ( file_exists( __DIR__ . '/.commit' ) ) {
	$plugin_version .= '-' . file_get_contents( __DIR__ . '/.commit' );
}

// Useful global constants
klasifai_define( 'CLASSIFAI_PLUGIN', __DIR__ . '/classifai.php' );
klasifai_define( 'CLASSIFAI_PLUGIN_VERSION', $plugin_version );
klasifai_define( 'CLASSIFAI_PLUGIN_DIR', __DIR__ );
klasifai_define( 'CLASSIFAI_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Taxonomies
klasifai_define( 'WATSON_CATEGORY_TAXONOMY', 'watson-category' );
klasifai_define( 'WATSON_KEYWORD_TAXONOMY', 'watson-keyword' );
klasifai_define( 'WATSON_ENTITY_TAXONOMY', 'watson-entity' );
klasifai_define( 'WATSON_CONCEPT_TAXONOMY', 'watson-concept' );

// Misc defaults
klasifai_define( 'WATSON_TIMEOUT', 60 ); // seconds

// Default Thresholds
klasifai_define( 'WATSON_CATEGORY_THRESHOLD', 70 );
klasifai_define( 'WATSON_KEYWORD_THRESHOLD', 70 );
klasifai_define( 'WATSON_ENTITY_THRESHOLD', 70 );
klasifai_define( 'WATSON_CONCEPT_THRESHOLD', 70 );

klasifai_define( 'WATSON_KEYWORD_LIMIT', 10 );

// For Debugging
