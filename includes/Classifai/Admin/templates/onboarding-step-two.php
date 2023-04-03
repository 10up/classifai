<?php
/**
 * Step-2 template for ClassifAI Onboarding.
 *
 * @package ClassifAI
 */

// Display any errors.
settings_errors( 'registration' );
?>
<h1 class="classifai-setup-heading">
	<?php esc_html_e( 'Register ClassifAI', 'classifai' ); ?>
</h1>
<div class="classifai-setup__content__row">
	<div class="classifai-setup__content__row__column">
		<div class="classifai-step2-content">
			<div class="classifai-setup-form">
				<form method="POST" action="">
					<?php
					Classifai\Admin\Onboarding::render_classifai_setup_settings( 'classifai_settings', array( 'email', 'registration-key' ) );
					?>
					<div class="classifai-setup-footer">
						<span class="classifai-setup-footer__left">
							<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=classifai_skip_step&step=2' ), 'classifai_skip_step_action', 'classifai_skip_step_nonce' ) ); ?>" class="classifai-setup-skip-link">
								<?php esc_html_e( 'Skip for now', 'classifai' ); ?>
							</a>
						</span>
						<span class="classifai-setup-footer__right">
							<input name="classifai-setup-step" type="hidden" value="2" />
							<?php wp_nonce_field( 'classifai-setup-step-action', 'classifai-setup-step-nonce' ); ?>
							<input class="classifai-button" type="submit" value="<?php esc_attr_e( 'Register', 'classifai' ); ?>" />
						</span>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>
