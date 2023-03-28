<?php
/**
 * Step-1 template for ClassifAI Onboarding.
 *
 * @package ClassifAI
 */

$service_manager = new \Classifai\Services\ServicesManager();
$email           = $service_manager->get_settings( 'email' );
$license_key     = $service_manager->get_settings( 'license_key' );

// Display any errors.
settings_errors( 'registration' );
?>
<div class="classifai-setup__content__row">
	<div class="classifai-setup__content__row__column">
		<div class="classifai-step2-content">
			<h1 class="classifai-setup-title center">
				<?php esc_html_e( 'Register ClassifAI', 'classifai' ); ?>
			</h1>
			<div class="classifai-setup-form">
				<form method="POST" action="">
					<div class="classifai-setup-form-field">
						<label for="email">
							<?php esc_html_e( 'Registered Email', 'classifai' ); ?>
						</label>
						<input type="text" name="classifai_settings[email]" class="regular-text" value="<?php echo esc_attr( $email ); ?>" required="required" id="email"/>
					</div>
					<div class="classifai-setup-form-field">
						<label for="license_key">
							<?php esc_html_e( 'Registered Key', 'classifai' ); ?>
						</label>
						<input type="password" name="classifai_settings[license_key]" class="regular-text" value="<?php echo esc_attr( $license_key ); ?>" required="required" id="license_key"/>
						<br />
						<span class="description">
							<?php
							// translators: %1$s: <br />,<a> tag, %2$s: </a> tag.
							printf( esc_html__( 'Registration is 100%% free and provides update notifications and upgrades inside the dashboard. %1$sRegister for your key%2$s', 'classifai' ), '<br /><a href="https://classifaiplugin.com/#cta" target="blank">', '</a>' );
							?>
						</span>
					</div>

					<div class="classifai-setup-footer">
						<span class="classifai-setup-footer__left">
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=classifai_setup&step=3' ) ); ?>" class="classifai-setup-skip-link">
								<?php esc_html_e( 'Skip for now', 'classifai' ); ?>
							</a>
						</span>
						<span class="classifai-setup-footer__right">
							<input class="classifai-setup-step" type="hidden" value="2" />
							<?php wp_nonce_field( 'classifai-setup-step-two-action', 'classifai-setup-step-two-nonce' ); ?>
							<input class="classifai-button" type="submit" value="<?php esc_attr_e( 'Register', 'classifai' ); ?>" />
						</span>
					</div>
				</form>
			</div>
		</div>
		</form>
	</div>
</div>
