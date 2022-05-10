<?php
/**
 * Classifai Blocks setup
 *
 * @package Classifai
 */

namespace Classifai\Blocks;

/**
 * Set up blocks
 *
 * @return void
 */
function setup() {
	$n = function( $function ) {
		return __NAMESPACE__ . "\\$function";
	};

	add_filter( 'block_categories_all', $n( 'blocks_categories' ), 10, 2 );

	register_blocks();
}

/**
 * Registers blocks that are located within the includes/blocks directory
 *
 * @return void
 */
function register_blocks() {
	// Require custom blocks
	require_once CLASSIFAI_PLUGIN_DIR . '/includes/Classifai/Blocks/recommended-content-block/register.php';

	// Call block Register function for each block.
	RecommendedContentBlock\register();
}

/**
 * Filters the registered block categories.
 *
 * @param array $categories Registered categories.
 *
 * @return array Filtered categories.
 */
function blocks_categories( $categories ) {
	return array_merge(
		$categories,
		array(
			array(
				'slug'  => 'classifai-blocks',
				'title' => __( 'ClassifAI', 'classifai' ),
			),
		)
	);
}
