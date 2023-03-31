<?php
/**
 * Header template for ClassifAI admin pages.
 *
 * @package ClassifAI
 */

// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$classifai_page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
?>
<header id="classifai-header">
	<div class="classifai-header-layout">
		<div id="classifai-branding">
			<div id="classifai-logo">
				<img src="<?php echo esc_url( CLASSIFAI_PLUGIN_URL . 'assets/img/classifai.png' ); ?>" alt="ClassifAI" />
			</div>
		</div>
		<div id="classifai-header-controls">
			<?php
			if ( 'classifai_setup' === $classifai_page ) {
				?>
				<div class="header-control-item">
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=classifai_settings' ) ); ?>" class="classifai-help-links">
						<span class="dashicons dashicons-admin-generic"></span>
						<span class="control-item-text"><?php esc_html_e( 'Settings', 'classifai' ); ?></span>
					</a>
				</div>
				<?php
			} else {
				if ( ! Classifai\Admin\Onboarding::is_onboarding_completed() ) {
					?>
					<div class="header-control-item">
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=classifai_setup' ) ); ?>" class="classifai-help-links">
							<span class="dashicons dashicons-admin-tools"></span>
							<span class="control-item-text"><?php esc_html_e( 'Set up', 'classifai' ); ?></span>
						</a>
					</div>
					<?php
				}
			}
			?>
			<div class="header-control-item">
				<a href="#" class="classifai-help-links classifai-help">
					<span class="dashicons dashicons-editor-help"></span>
					<span class="control-item-text"><?php esc_html_e( 'Help', 'classifai' ); ?></span>
				</a>
				<template id="help-menu-template">
					<div class="classifai-help-menu">
						<a class="classifai-help-menu__menu-item" target="_blank" href="https://github.com/10up/classifai#frequently-asked-questions"><?php esc_html_e( 'FAQs', 'classifai' ); ?></a>
						<a class="classifai-help-menu__menu-item" target="_blank"  href="https://github.com/10up/classifai/issues/new/choose"><?php esc_html_e( 'Report issue/enhancement', 'classifai' ); ?></a>
					</div>
				</template>
			</div>
		</div>
	</div>
</header>
<?php
if ( 'classifai_setup' === $classifai_page ) {
	return;
}

$classifai_settings = array(
	'classifai_settings'  => __( 'ClassifAI Registration', 'classifai' ),
	'language_processing' => __( 'Language Processing', 'classifai' ),
	'image_processing'    => __( 'Image Processing', 'classifai' ),
	'personalizer'        => __( 'Recommended Content', 'classifai' ),
);
?>
<h2 class="nav-tab-wrapper classifai-nav-wrapper">
	<?php
	foreach ( $classifai_settings as $key => $value ) {
		?>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . $key ) ); ?>" class="nav-tab <?php echo ( $page === $key ) ? 'nav-tab-active' : ''; ?>">
			<?php echo esc_html( $value ); ?>
		</a>
		<?php
	}
	?>
</h2>
