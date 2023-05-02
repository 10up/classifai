<?php
/**
 * Step-3 template for ClassifAI Onboarding.
 *
 * @package ClassifAI
 */

$base_url          = admin_url( 'admin.php?page=classifai_setup&step=3' );
$onboarding        = new Classifai\Admin\Onboarding();
$enabled_providers = $onboarding->get_enabled_providers();
$current_provider  = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : array_key_first( $enabled_providers ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$next_provider     = $onboarding->get_next_provider( $current_provider );
$skip_url          = add_query_arg( 'tab', $next_provider, $base_url );
if ( empty( $next_provider ) ) {
	$skip_url = wp_nonce_url( admin_url( 'admin-post.php?action=classifai_skip_step&step=3' ), 'classifai_skip_step_action', 'classifai_skip_step_nonce' );
}

$args = array(
	'step'       => 3,
	'title'      => __( 'Set up AI Providers', 'classifai' ),
	'left_link'  => array(
		'text' => __( 'Skip for now', 'classifai' ),
		'url'  => $skip_url,
	),
	'right_link' => array(
		'text'   => __( 'Submit', 'classifai' ),
		'submit' => true,
	),
);

// Header
require_once 'onboarding-header.php';
?>

<div class="classifai-tabs tabs-center">
	<?php
	foreach ( $enabled_providers as $key => $provider ) {
		$provider_url = add_query_arg( 'tab', $key, $base_url );
		$is_active    = ( $current_provider === $key ) ? 'active' : '';
		?>
		<a href="<?php echo esc_url( $provider_url ); ?>" class="tab <?php echo esc_attr( $is_active ); ?>">
			<?php echo esc_html( $provider['title'] ); ?>
		</a>
		<?php
	}
	?>
</div>
<div class="classifai-setup-form">
	<input name="classifai-setup-provider" type="hidden" value="<?php echo esc_attr( $current_provider ); ?>" />
	<?php
	// Load the appropriate provider settings.
	if ( ! empty( $current_provider ) && ! empty( $enabled_providers ) ) {
		Classifai\Admin\Onboarding::render_classifai_setup_settings( $current_provider, $enabled_providers[ $current_provider ]['fields'] );
	} else {
		?>
		<p class="classifai-setup-error">
			<?php esc_html_e( 'No features are enabled.', 'classifai' ); ?>
		</p>
		<?php
	}
	?>
</div>

<?php
// Footer
require_once 'onboarding-footer.php';
