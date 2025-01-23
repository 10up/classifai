<?php
/**
 * Render callback for the Key Takeaways block.
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Block content.
 * @var WP_Block $block      Block instance.
 */

$layout = $attributes['render'] ?? 'list';
?>

<div <?php echo get_block_wrapper_attributes(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<?php if ( 'list' === $layout ) : ?>
		<ul>
			<li>Point 1</li>
			<li>Point 2</li>
		</ul>
	<?php else : ?>
		This is a summary.
	<?php endif; ?>
</div>
