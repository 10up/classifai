<?php
/**
 * Recommended Content block markup
 *
 * @package Classifai\Blocks
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Block content.
 * @var WP_Block $block      Block instance.
 * @var array    $context    Block context.
 */

// Excluded current page/post from the recommended content.
if ( is_singular() ) {
	$attributes['excludeId'] = get_the_ID();
}

// Backward compatibillity for default post type. 
// We can not keep default post type in block.json as it makes impossible to distinguish whether it is previously selected or default one in the editor.
// Because we've introduced auto selection of post type based on currently edit screen for the first time block component mount. 
if ( empty( $attributes['contentPostType'] ) ) {
	$attributes['contentPostType'] = 'post';
}

$attributes = apply_filters( 'classifai_recommended_block_attributes', $attributes );
$attr_key   = md5( maybe_serialize( $attributes ) );
$block_id   = 'classifai-recommended-block-' . $attr_key;
?>
<div class="classifai-recommended-block-wrap" id="<?php echo esc_attr( $block_id ); ?>" data-attr_key="<?php echo esc_attr( $attr_key ); ?>">
	<?php esc_html_e( 'Loading...', 'classifai' ); ?>
</div>
<script id="attributes-<?php echo esc_attr( $attr_key ); ?>" type="application/json"><?php echo wp_json_encode( $attributes ); ?></script>
<?php
