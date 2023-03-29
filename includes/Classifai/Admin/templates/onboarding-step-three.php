<?php
/**
 * Step-3 template for ClassifAI Onboarding.
 *
 * @package ClassifAI
 */

$base_url          = admin_url( 'admin.php?page=classifai_setup&step=3' );
$enabled_providers = Classifai\Admin\Onboarding::get_enabled_providers();
$provider_keys     = array_keys( $enabled_providers );
$current_provider  = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : $provider_keys[0]; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$next_provider     = $provider_keys[ array_search( $current_provider, $provider_keys, true ) + 1 ];

// Display any errors.
settings_errors();
?>
<div class="classifai-setup__content__row">
	<div class="classifai-setup__content__row__column">
		<div class="classifai-step3-content">
			<h1 class="classifai-setup-title center">
				<?php esc_html_e( 'Set up AI Services', 'classifai' ); ?>
			</h1>
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
							<?php esc_html_e( 'No providers are enabled.', 'classifai' ); ?>
						</p>
						<?php
					}
					?>
					<div class="classifai-setup-footer">
						<span class="classifai-setup-footer__left">
							<a href="<?php echo esc_url( add_query_arg( 'tab', $next_provider, $base_url ) ); ?>" class="classifai-setup-skip-link">
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
