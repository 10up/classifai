<?php
/**
 * Render callback for the Key Takeaways block.
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Block content.
 * @var WP_Block $block      Block instance.
 */

$layout    = $attributes['render'] ?? 'list';
$takeaways = $attributes['takeaways'] ?? [];
?>

<div <?php echo get_block_wrapper_attributes(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
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
