<?php
/**
 * Header template for ClassifAI admin pages.
 *
 * @package ClassifAI
 */

/* phpcs:disable PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage */

// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$active_page = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'classifai_settings';
// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$is_setup_page = isset( $_GET['page'] ) && ( 'classifai_setup' === sanitize_text_field( wp_unslash( $_GET['page'] ) ) || 'classifai-term-cleanup' === sanitize_text_field( wp_unslash( $_GET['page'] ) ) );
?>
<header id="classifai-header">
	<div class="classifai-header-layout">
		<div id="classifai-branding">
			<div id="classifai-logo">
				<img src="<?php echo esc_url( CLASSIFAI_PLUGIN_URL . 'assets/img/classifai.png' ); ?>" alt="<?php esc_attr_e( 'ClassifAI', 'classifai' ); ?>" />
			</div>
		</div>
		<div id="classifai-header-controls">
			<?php
			if ( $is_setup_page ) {
				?>
				<div class="header-control-item">
					<a class="components-button has-text has-icon" href="<?php echo esc_url( admin_url( 'tools.php?page=classifai' ) ); ?>" >
						<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false"><path fill-rule="evenodd" d="M10.289 4.836A1 1 0 0111.275 4h1.306a1 1 0 01.987.836l.244 1.466c.787.26 1.503.679 2.108 1.218l1.393-.522a1 1 0 011.216.437l.653 1.13a1 1 0 01-.23 1.273l-1.148.944a6.025 6.025 0 010 2.435l1.149.946a1 1 0 01.23 1.272l-.653 1.13a1 1 0 01-1.216.437l-1.394-.522c-.605.54-1.32.958-2.108 1.218l-.244 1.466a1 1 0 01-.987.836h-1.306a1 1 0 01-.986-.836l-.244-1.466a5.995 5.995 0 01-2.108-1.218l-1.394.522a1 1 0 01-1.217-.436l-.653-1.131a1 1 0 01.23-1.272l1.149-.946a6.026 6.026 0 010-2.435l-1.148-.944a1 1 0 01-.23-1.272l.653-1.131a1 1 0 011.217-.437l1.393.522a5.994 5.994 0 012.108-1.218l.244-1.466zM14.929 12a3 3 0 11-6 0 3 3 0 016 0z" clip-rule="evenodd"></path></svg>
						<span class="control-item-text"><?php esc_html_e( 'Settings', 'classifai' ); ?></span>
					</a>
				</div>
				<?php
			} else {
				?>
				<div class="header-control-item">
					<a class="components-button has-text has-icon" href="<?php echo esc_url( admin_url( 'admin.php?page=classifai_setup' ) ); ?>">
						<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false"><path d="M14.103 7.128l2.26-2.26a4 4 0 00-5.207 4.804L5.828 15a2 2 0 102.828 2.828l5.329-5.328a4 4 0 004.804-5.208l-2.261 2.26-1.912-.512-.513-1.912zm-7.214 9.64a.5.5 0 11.707-.707.5.5 0 01-.707.707z"></path></svg>
						<span class="control-item-text"><?php esc_html_e( 'Set up', 'classifai' ); ?></span>
					</a>
				</div>
				<?php
			}
			?>
			<div class="header-control-item">
				<button type="button" class="components-button has-text has-icon classifai-help-links classifai-help">
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false"><path d="M12 4.75a7.25 7.25 0 100 14.5 7.25 7.25 0 000-14.5zM3.25 12a8.75 8.75 0 1117.5 0 8.75 8.75 0 01-17.5 0zM12 8.75a1.5 1.5 0 01.167 2.99c-.465.052-.917.44-.917 1.01V14h1.5v-.845A3 3 0 109 10.25h1.5a1.5 1.5 0 011.5-1.5zM11.25 15v1.5h1.5V15h-1.5z"></path></svg>
					<?php esc_html_e( 'Help', 'classifai' ); ?>
				</button>
				<template id="help-menu-template">
					<div class="classifai-help-menu">
						<a class="classifai-help-menu__menu-item" target="_blank" rel="noopener noreferrer" href="https://github.com/10up/classifai#frequently-asked-questions"><?php esc_html_e( 'FAQs', 'classifai' ); ?></a>
						<a class="classifai-help-menu__menu-item" target="_blank" rel="noopener noreferrer" href="https://github.com/10up/classifai/issues/new/choose"><?php esc_html_e( 'Report issue/enhancement', 'classifai' ); ?></a>
					</div>
				</template>
			</div>
		</div>
	</div>
</header>
<?php
if ( $is_setup_page ) {
	return;
}

$services_menu      = Classifai\get_services_menu();
$classifai_settings = array(
	'classifai_settings' => __( 'ClassifAI Registration', 'classifai' ),
);

$classifai_header_menu = array_merge( $classifai_settings, $services_menu );
?>
<div class="classifai-settings-wrapper">
<h2 class="nav-tab-wrapper classifai-nav-wrapper">
	<?php
	foreach ( $classifai_header_menu as $key => $value ) {
		?>
		<a href="<?php echo esc_url( admin_url( 'tools.php?page=classifai&tab=' . $key ) ); ?>" class="nav-tab <?php echo esc_attr( ( $active_page === $key ) ? 'nav-tab-active' : '' ); ?>">
			<?php echo esc_html( $value ); ?>
		</a>
		<?php
	}
	?>
</h2>
