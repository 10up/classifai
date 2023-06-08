<?php
/**
 * ClassifAI Blocks setup
 *
 * @package Classifai
 */

namespace Classifai\Blocks;

use function Classifai\get_asset_info;

/**
 * Set up blocks
 *
 * @return void
 */
function setup() {
	$n = function( $function ) {
		return __NAMESPACE__ . "\\$function";
	};

	add_action( 'enqueue_block_assets', $n( 'blocks_styles' ) );
	add_filter( 'block_categories_all', $n( 'blocks_categories' ), 10, 2 );

	register_blocks();
}

/**
 * Registers blocks that are located within the includes/blocks directory.
 *
 * @return void
 */
function register_blocks() {
	// Require custom blocks.
	require_once CLASSIFAI_PLUGIN_DIR . '/includes/Classifai/Blocks/recommended-content-block/register.php';

	// Call register function for each block.
	RecommendedContentBlock\register();
}

/**
 * Enqueue JavaScript/CSS for blocks.
 *
 * @return void
 */
function blocks_styles() {
	wp_enqueue_style(
		'recommended-content-block-style',
		CLASSIFAI_PLUGIN_URL . 'dist/recommended-content-block-frontend.css',
		[],
		CLASSIFAI_PLUGIN_VERSION
	);

	wp_enqueue_script(
		'recommended-content-block-script',
		CLASSIFAI_PLUGIN_URL . 'dist/recommended-content-block-frontend.js',
		get_asset_info( 'recommended-content-block-frontend', 'dependencies' ),
		get_asset_info( 'recommended-content-block-frontend', 'version' ),
		true
	);

	wp_localize_script(
		'recommended-content-block-script',
		'classifai_personalizer_params',
		array(
			'reward_endpoint' => get_rest_url( null, 'classifai/v1/personalizer/reward/{eventId}' ),
			'ajax_url'        => esc_url( admin_url( 'admin-ajax.php' ) ),
			'ajax_nonce'      => wp_create_nonce( 'classifai-recommended-block' ),
		)
	);
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
