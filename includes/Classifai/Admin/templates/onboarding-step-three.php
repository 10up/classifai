<?php
/**
 * Step-3 template for ClassifAI Onboarding.
 *
 * @package ClassifAI
 */

$base_url          = admin_url( 'admin.php?page=classifai_setup&step=3' );
$enabled_providers = Classifai\Admin\Onboarding::get_enabled_providers();
$current_provider  = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : array_key_first( $enabled_providers ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$next_provider     = Classifai\Admin\Onboarding::get_next_provider( $current_provider );
?>
<h1 class="classifai-setup-heading">
	<?php esc_html_e( 'Set up AI Providers', 'classifai' ); ?>
</h1>
<?php
// Display any errors.
settings_errors();
?>
<div class="classifai-setup__content__row">
	<div class="classifai-setup__content__row__column">
		<div class="classifai-step3-content">
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
				<form method="POST" action="">
					<?php
					// Load the appropriate provider settings.
					if ( ! empty( $current_provider ) && ! empty( $enabled_providers ) ) {
						Classifai\Admin\Onboarding::render_classifai_setup_settings( 'classifai_' . $current_provider, $enabled_providers[ $current_provider ]['fields'] );
					} else {
						?>
						<p class="classifai-setup-error">
							<?php esc_html_e( 'No features are enabled.', 'classifai' ); ?>
						</p>
						<?php
					}

					$skip_url = add_query_arg( 'tab', $next_provider, $base_url );
					if ( empty( $next_provider ) ) {
						$skip_url = wp_nonce_url( admin_url( 'admin-post.php?action=classifai_skip_step&step=3' ), 'classifai_skip_step_action', 'classifai_skip_step_nonce' );
					}
					?>
					<div class="classifai-setup-footer">
						<span class="classifai-setup-footer__left">
							<a href="<?php echo esc_url( $skip_url ); ?>" class="classifai-setup-skip-link">
								<?php esc_html_e( 'Skip for now', 'classifai' ); ?>
							</a>
						</span>
						<span class="classifai-setup-footer__right">
							<input name="classifai-setup-step" type="hidden" value="3" />
							<input name="classifai-setup-provider" type="hidden" value="<?php echo esc_attr( $current_provider ); ?>" />
							<?php wp_nonce_field( 'classifai-setup-step-three-action', 'classifai-setup-step-three-nonce' ); ?>
							<input class="classifai-button" type="submit" value="<?php esc_attr_e( 'Submit', 'classifai' ); ?>" />
						</span>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>
