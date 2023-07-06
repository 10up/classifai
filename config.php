<?php
/**
 * Global Constants for the ClassifAI Support Plugin. Constants should be
 * declared here instead of a Class.
 */

$plugin_version = '2.2.2';

if ( file_exists( __DIR__ . '/.commit' ) ) {
	$plugin_version .= '-' . file_get_contents( __DIR__ . '/.commit' );
}

// Useful global constants
classifai_define( 'CLASSIFAI_PLUGIN', __DIR__ . '/classifai.php' );
classifai_define( 'CLASSIFAI_PLUGIN_VERSION', $plugin_version );
classifai_define( 'CLASSIFAI_PLUGIN_DIR', __DIR__ );
classifai_define( 'CLASSIFAI_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// API
classifai_define( 'WATSON_NLU_VERSION', '2018-03-19' );
// Taxonomies
classifai_define( 'WATSON_CATEGORY_TAXONOMY', 'watson-category' );
classifai_define( 'WATSON_KEYWORD_TAXONOMY', 'watson-keyword' );
classifai_define( 'WATSON_ENTITY_TAXONOMY', 'watson-entity' );
classifai_define( 'WATSON_CONCEPT_TAXONOMY', 'watson-concept' );

// Misc defaults
classifai_define( 'WATSON_TIMEOUT', 60 ); // seconds

// Default Thresholds
classifai_define( 'WATSON_CATEGORY_THRESHOLD', 70 );
classifai_define( 'WATSON_KEYWORD_THRESHOLD', 70 );
classifai_define( 'WATSON_ENTITY_THRESHOLD', 70 );
classifai_define( 'WATSON_CONCEPT_THRESHOLD', 70 );

classifai_define( 'WATSON_KEYWORD_LIMIT', 10 );

// For Debugging
