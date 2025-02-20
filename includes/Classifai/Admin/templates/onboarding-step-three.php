<?php
/**
 * Step-3 template for ClassifAI Onboarding.
 *
 * @package ClassifAI
 */

$base_url            = admin_url( 'admin.php?page=classifai_setup&step=3' );
$onboarding          = new Classifai\Admin\Onboarding();
$enabled_features    = $onboarding->get_enabled_features();
$onboarding_options  = $onboarding->get_onboarding_options();
$configured_features = $onboarding_options['configured_features'] ?? array();
$current_feature     = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : array_key_first( $enabled_features ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$next_feature        = $onboarding->get_next_feature( $current_feature );
$skip_url            = add_query_arg( 'tab', $next_feature, $base_url );
if ( empty( $next_feature ) ) {
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
<div class="classifai-providers-wrapper">
	<div class="classifai-tabs tabs-center">
		<?php
		$feature_keys = array_keys( $enabled_features );
		foreach ( $enabled_features as $key => $feature_class ) {
			$feature       = new $feature_class();
			$is_configured = $feature->is_enabled();
			$feature_url   = add_query_arg( 'tab', $key, $base_url );
			$is_active     = ( $current_feature === $key ) ? 'active' : '';
			$icon_class    = 'dashicons-clock';
			if ( $is_configured ) {
				$icon_class = 'dashicons-yes-alt';
			} elseif ( array_search( $current_feature, $feature_keys, true ) > array_search( $key, $feature_keys, true ) ) {
				$icon_class = 'dashicons-warning';
			}
			?>
			<a href="<?php echo esc_url( $feature_url ); ?>" class="tab <?php echo esc_attr( $is_active ); ?>">
				<span class="dashicons <?php echo esc_attr( $icon_class ); ?>"></span>
				<?php echo esc_html( $feature_class->get_label() ); ?>
			</a>
			<?php
		}
		?>
	</div>
	<div class="classifai-setup-form">
		<?php
		/**
		 * Fires before the settings form for a feature.
		 *
		 * @since 3.2.0
		 * @hook classifai_before_onboarding_feature_settings_form
		 *
		 * @param {string} $current_feature Current feature.
		 */
		do_action( 'classifai_before_onboarding_feature_settings_form', $current_feature );
		?>

		<input name="classifai-setup-feature" type="hidden" value="<?php echo esc_attr( $current_feature ); ?>" />
		<table class="form-table">
			<?php
			// Load the appropriate provider settings.
			if ( ! empty( $current_feature ) && ! empty( $enabled_features ) && array_key_exists( $current_feature, $enabled_features ) ) {
				$onboarding->render_classifai_setup_feature( $current_feature );
			} else {
				?>
				<p class="classifai-setup-error">
					<?php esc_html_e( 'No features are enabled.', 'classifai' ); ?>
				</p>
				<?php
			}
			?>
		</table>
		<?php
		/**
		 * Fires after the settings form for a feature.
		 *
		 * @since 3.2.0
		 * @hook classifai_after_onboarding_feature_settings_form
		 *
		 * @param {string} $current_feature Current active feature.
		 */
		do_action( 'classifai_after_onboarding_feature_settings_form', $current_feature );
		?>
	</div>
</div>

<?php
// Footer
require_once 'onboarding-footer.php';
