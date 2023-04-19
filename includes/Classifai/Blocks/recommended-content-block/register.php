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
	add_action( 'admin_enqueue_scripts', $n( 'nonce_field' ) );
}

/**
 * Get post info to set default terms based on.
 *
 * @return void
 */
function get_post_info() {
	if ( empty( $_GET['classifai_editor_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['classifai_editor_nonce'] ) ), 'classifai_editor_nonce' ) ) {
		wp_send_json_error( array( 'message' => __( 'Invalid nonce', 'classifai' ) ) );
		exit;
	}

	$post_id = ! empty( $_GET['post_id'] ) ? (int) wp_unslash( $_GET['post_id'] ) : 0;
	$index   = ! empty( $_GET['block_index'] ) ? (int) wp_unslash( $_GET['block_index'] ) : null;
	
	// Get block attributes
	$post    = $post_id ? get_post( $post_id ) : null;
	$blocks  = ! empty( $post ) ? parse_blocks( $post->post_content ) : array();
	$blocks  = array_values(
		array_filter(
			$blocks,
			function( $block ) {
				return ! empty( $block['blockName'] ) && 'classifai/recommended-content-block' === $block['blockName'];
			}
		)
	);
	$attributes = $blocks[ $index ] ?? null;

	wp_send_json_success(
		array(
			'attributes' => $attributes,
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
	if ( empty( $attributes['usePostTerms'] ) || ! $attributes['usePostTerms'] ) {
		return $attributes;
	}

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

	return $attributes;
}

/**
 * Send nonce value
 *
 * @return void
 */
function nonce_field() {
	wp_localize_script( 'jquery', 'classifai_editor_nonce', array( 'action' => wp_create_nonce( 'classifai_editor_nonce' ) ) );
}
