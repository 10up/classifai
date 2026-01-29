<?php
/**
 * Render callback for the Key Takeaways block.
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Block content.
 * @var WP_Block $block      Block instance.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$block_title = $attributes['title'] ?? '';
$layout      = $attributes['render'] ?? 'list';
$takeaways   = $attributes['takeaways'] ?? [];

// If there are no takeaways, don't render the block.
if ( empty( $takeaways ) ) {
	return;
}
?>

<div <?php echo get_block_wrapper_attributes(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<?php if ( $block_title ) : ?>
		<h2 class="wp-block-heading wp-block-classifai-key-takeaways__title">
			<?php echo wp_kses_post( $block_title ); ?>
		</h2>
	<?php endif; ?>

	<div class="wp-block-classifai-key-takeaways__content">
	<?php
	if ( 'list' === $layout ) {
		echo '<ul>';
		foreach ( (array) $takeaways as $takeaway ) {
			printf(
				'<li>%s</li>',
				esc_html( $takeaway )
			);
		}
		echo '</ul>';
	} else {
		foreach ( (array) $takeaways as $takeaway ) {
			printf(
				'<p>%s</p>',
				esc_html( $takeaway )
			);
		}
	}
	?>
	</div>
</div>
