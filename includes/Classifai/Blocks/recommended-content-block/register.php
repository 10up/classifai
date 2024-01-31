<?php
/**
 * ClassifAI Recommended Content Block setup
 *
 * @package Classifai\Blocks\RecommendedContentBlock
 */

namespace Classifai\Blocks\RecommendedContentBlock;

use Classifai\Providers\Azure\Personalizer;
use function Classifai\get_asset_info;

/**
 * Register the block
 */
function register() {
	$n = function ( $function_name ) {
		return __NAMESPACE__ . "\\$function_name";
	};

	$personalizer = new Personalizer( false );
	wp_register_script(
		'recommended-content-block-editor-script',
		CLASSIFAI_PLUGIN_URL . 'dist/recommended-content-block.js',
		get_asset_info( 'recommended-content-block', 'dependencies' ),
		get_asset_info( 'recommended-content-block', 'version' ),
		true
	);

	wp_add_inline_script(
		'recommended-content-block-editor-script',
		sprintf(
			'var hasRecommendedContentAccess = %d;',
			$personalizer->is_feature_enabled( 'recommended_content' )
		),
		'before'
	);

	// Register the block.
	register_block_type_from_metadata(
		CLASSIFAI_PLUGIN_DIR . '/includes/Classifai/Blocks/recommended-content-block', // this is the directory where the block.json is found.
		[
			'render_callback' => $n( 'render_block_callback' ),
		]
	);
}

/**
 * Render callback method for the block
 *
 * @param array $attributes The blocks attributes.
 * @return string The rendered block markup.
 */
function render_block_callback( array $attributes ): string {
	// Render block in Gutenberg Editor.
	if ( defined( 'REST_REQUEST' ) && \REST_REQUEST ) {
		$personalizer = new Personalizer( false );
		return $personalizer->render_recommended_content( $attributes );
	}

	// Render block in Front-end.
	ob_start();
	include __DIR__ . '/markup.php';
	return ob_get_clean();
}
