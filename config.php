<?php
/**
 * Global Constants.
 */

$plugin_version = '3.2.0';

// Useful global constants
classifai_define( 'CLASSIFAI_PLUGIN', __DIR__ . '/classifai.php' );
classifai_define( 'CLASSIFAI_PLUGIN_VERSION', $plugin_version );
classifai_define( 'CLASSIFAI_PLUGIN_DIR', __DIR__ );
classifai_define( 'CLASSIFAI_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
classifai_define( 'CLASSIFAI_PLUGIN_BASENAME', plugin_basename( __DIR__ . '/classifai.php' ) );

// IBM Watson constants

// API - https://cloud.ibm.com/docs/natural-language-understanding?topic=natural-language-understanding-release-notes#active-version-dates
classifai_define( 'WATSON_NLU_VERSION', '2022-08-10' );

// Taxonomies
classifai_define( 'WATSON_CATEGORY_TAXONOMY', 'watson-category' );
classifai_define( 'WATSON_KEYWORD_TAXONOMY', 'watson-keyword' );
classifai_define( 'WATSON_ENTITY_TAXONOMY', 'watson-entity' );
classifai_define( 'WATSON_CONCEPT_TAXONOMY', 'watson-concept' );

// Misc defaults
classifai_define( 'WATSON_TIMEOUT', 60 ); // seconds
classifai_define( 'WATSON_KEYWORD_LIMIT', 10 );

// Default Thresholds
classifai_define( 'WATSON_CATEGORY_THRESHOLD', 70 );
classifai_define( 'WATSON_KEYWORD_THRESHOLD', 70 );
classifai_define( 'WATSON_ENTITY_THRESHOLD', 70 );
classifai_define( 'WATSON_CONCEPT_THRESHOLD', 70 );
