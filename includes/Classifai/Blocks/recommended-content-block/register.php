<?php
/**
 * ClassifAI Recommended Content Block setup
 *
 * @package Classifai\Blocks\RecommendedContentBlock
 */

namespace Classifai\Blocks\RecommendedContentBlock;

use Classifai\Providers\Azure\Personalizer;

/**
 * Register the block
 */
function register() {
	$n = function( $function ) {
		return __NAMESPACE__ . "\\$function";
	};
	// Register the block.
	register_block_type_from_metadata(
		CLASSIFAI_PLUGIN_DIR . '/includes/Classifai/Blocks/recommended-content-block', // this is the directory where the block.json is found.
		[
			'render_callback' => $n( 'render_block_callback' ),
		]
	);

	add_filter( 'classifai_recommended_block_attributes', $n( 'use_default_post_terms' ) );
	add_action( 'wp_ajax_classifai_get_post_info', $n( 'get_post_info' ) );
}

/**
 * Get post info to set default terms based on.
 *
 * @return void
 */
function get_post_info() {
	$post_id = ! empty( $_GET['post_id'] ) ? (int) $_GET['post_id'] : 0;
	$index   = ! empty( $_GET['block_index'] ) ? (int) $_GET['block_index'] : null;
	$content = get_the_content( null, false, $post_id );
	$blocks  = parse_blocks( $content );
	$blocks  = array_values(
		array_filter(
			$blocks,
			function( $block ) {
				return ! empty( $block['blockName'] ) && 'classifai/recommended-content-block' === $block['blockName'];
			}
		)
	);

	$attributes = isset( $blocks[ $index ] ) ? $blocks[ $index ] : null;
	$categories = wp_get_post_terms( $post_id, 'category', array( 'fields' => 'ids' ) );
	$tags       = wp_get_post_terms( $post_id, 'post_tag', array( 'fields' => 'ids' ) );

	wp_send_json_success(
		array(
			'attributes' => $attributes,  
			'categories' => $categories,
			'tags'       => $tags,
		)
	);
}

/**
 * Render callback method for the block
 *
 * @param array  $attributes The blocks attributes.
 * @param string $content    Data returned from InnerBlocks.Content.
 * @param array  $block      Block information such as context.
 *
 * @return string The rendered block markup.
 */
function render_block_callback( $attributes, $content, $block ) {
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

/**
 * Reset category and taxonomy if usePostTerms is true
 *
 * @param array $attributes Block attributes
 * @return array
 */
function use_default_post_terms( $attributes ) {
	if ( ! empty( $attributes['usePostTerms'] ) && true === $attributes['usePostTerms'] ) {
		if ( ! isset( $attributes['taxQuery'] ) || ! is_array( $attributes['taxQuery'] ) ) {
			$attributes['taxQuery'] = array();
		}

		$post_id    = $attributes['excludeId'];
		$categories = wp_get_post_terms( $post_id, 'category', array( 'fields' => 'ids' ) );
		$tags       = wp_get_post_terms( $post_id, 'post_tag', array( 'fields' => 'ids' ) );

		if ( ! empty( $categories ) ) {
			$attributes['taxQuery']['category'] = $categories;
		}

		if ( ! empty( $tags ) ) {
			$attributes['taxQuery']['post_tag'] = $tags;
		}
	}

	return $attributes;
}
