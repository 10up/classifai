<?php
/**
 * Header template for ClassifAI Onboarding.
 *
 * @package ClassifAI
 */

?>
<h1 class="classifai-setup-heading">
	<?php echo esc_html( $args['title'] ?? __( 'Welcome to ClassifAI', 'classifai' ) ); ?>
</h1>

<?php
// Display any errors.
settings_errors();
?>
<div class="classifai-setup__content__row">
	<?php
	if ( ! empty( $args['image'] ) ) {
		?>
		<div class="classifai-setup__content__row__column">
			<div class="classifai-setup-image">
				<img src="<?php echo esc_url( $args['image'] ); ?>" alt="<?php esc_attr_e( 'ClassifAI Setup', 'classifai' ); ?>" />
			</div>
		</div>
		<?php
	}
	?>
	<div class="classifai-setup__content__row__column">
		<div class="<?php echo esc_attr( sprintf( 'classifai-step%d-content', $args['step'] ) ); ?>">
			<form method="POST" action="">
