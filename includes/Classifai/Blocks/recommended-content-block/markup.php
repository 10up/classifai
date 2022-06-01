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
	const blockId = '<?php echo $block_id;?>';
	const cached  = window.sessionStorage.getItem(blockId);
	if( cached !== null ) {
		const cacheStorage = JSON.parse(cached);
		if (new Date(cacheStorage.expiresAt) > new Date()) {
			jQuery(`#${blockId}`).html(cacheStorage.response);
			return;
		}
	}
	const ajaxURL = '<?php echo esc_url( admin_url( "admin-ajax.php" ) ); ?>';
	const data = JSON.parse('<?php echo wp_json_encode( $attributes ); ?>');
	data.action   = 'render_recommended_content';
	data.security = '<?php echo $ajax_nonce;?>';
	jQuery.post(ajaxURL, data)
	.done(function(response) {
		if(response) {
			window.sessionStorage.setItem(blockId, JSON.stringify({
				expiresAt: new Date(new Date().getTime() + 60000 * 60), // 1 hour expiry time.
				response,
			}));
		}
		jQuery(`#${blockId}`).html(response);
	})
	.fail(function(error) {
		jQuery(`#${blockId}`).html('');
		console.log(error);
	});
});
</script>
<?php
