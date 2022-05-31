<?php
// phpcs:ignoreFile WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
/**
 * Recommended Content block markup
 *
 * @package Classifai\Blocks
 *
 * @var array    $attributes         Block attributes.
 * @var string   $content            Block content.
 * @var WP_Block $block              Block instance.
 * @var array    $context            BLock context.
 */
$block_id   = 'classifai-recommended-block-' . md5( maybe_serialize( $attributes ) );
$ajax_nonce = wp_create_nonce( 'classifai-recommended-block' );
?>
<div id="<?php echo $block_id;?>">
<?php esc_html_e( 'Loading...', 'classifai' ); ?>
</div>
<script>
	jQuery(document).ready(function() {
		const ajaxURL = '<?php echo esc_url( admin_url( "admin-ajax.php" ) ); ?>';
		const data = JSON.parse('<?php echo wp_json_encode( $attributes ); ?>');
		data.action   = 'render_recommended_content';
		data.security = '<?php echo $ajax_nonce;?>';
		jQuery.post(ajaxURL, data)
		.done(function(response) {
			jQuery('#<?php echo $block_id;?>').html(response);
		})
		.fail(function(error) {
			jQuery('#<?php echo $block_id;?>').html('');
			console.log(error);
		});
	});
</script>
<?php
