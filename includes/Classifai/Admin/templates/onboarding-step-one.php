<?php
/**
 * Step-1 template for ClassifAI Onboarding.
 *
 * @package ClassifAI
 */

$onboarding         = new Classifai\Admin\Onboarding();
$features           = $onboarding->get_features();
$onboarding_options = Classifai\Admin\Onboarding::get_onboarding_options();
$default_enabled    = array(
	array(
		'classifai_watson_nlu'  => array(
			'post_types_post' => '1',
			'post_types_page' => '1',
		),
	),
	'classifai_computer_vision' => array(
		'enable_image_captions' => '1',
		'enable_image_tagging'  => '1',
	),
);
$enabled_features   = $onboarding_options['enabled_features'] ?? $default_enabled;

// Display any errors.
settings_errors( 'classifai-setup' );
?>
<h1 class="classifai-setup-heading">
	<?php esc_html_e( 'Welcome to ClassifAI', 'classifai' ); ?>
</h1>
<div class="classifai-spacer"></div>
<div class="classifai-setup__content__row">
	<div class="classifai-setup__content__row__column">
		<div class="classifai-setup-image">
			<img src="https://via.placeholder.com/334x334" alt="<?php esc_attr_e( 'ClassifAI Setup', 'classifai' ); ?>" />
		</div>
	</div>
	<div class="classifai-setup__content__row__column">
		<form method="POST" action="">
			<div class="classifai-step1-content">
			<h1 class="classifai-setup-title">
				<?php esc_html_e( 'Set up ClassifAI to meet your needs', 'classifai' ); ?>
			</h1>
			<?php
			foreach ( $features as $key => $feature ) {
				if ( empty( $feature['title'] ) || empty( $feature['features'] ) ) {
					continue;
				}
				?>
				<div class="classifai-feature-box">
					<div class="classifai-feature-box-title">
						<?php echo esc_html( $feature['title'] ); ?>
					</div>
					<div class="classifai-features">
						<ul>
							<?php
							foreach ( $feature['features'] as $provider => $provider_features ) {
								foreach ( $provider_features as $feature_key => $feature_name ) {
									$checked = $enabled_features[ $provider ][ $feature_key ] ?? '';
									?>
									<li class="classifai-enable-feature">
										<label class="classifai-toggle">
											<span class="classifai-feature-text">
												<?php echo esc_html( $feature_name ); ?>
											</span>
											<input type="checkbox" class="classifai-toggle-checkbox" name="<?php echo esc_attr( 'classifai-features[' . $provider . '][' . $feature_key . ']' ); ?>" value="1" <?php checked( $checked, '1', true ); ?>>
											<span class="classifai-toggle-switch"></span>
										</label>
									</li>
									<?php
								}
							}
							?>
						</ul>
					</div>
				</div>
				<?php
				if ( array_key_last( $features ) !== $key ) {
					?>
					<div class="classifai-feature-box-divider"></div>
					<?php
				}
			}
			?>

			<div class="classifai-setup-footer">
				<span class="classifai-setup-footer__left">
					<a href="<?php echo esc_url( admin_url() ); ?>" class="classifai-setup-skip-link">
						<?php esc_html_e( 'Skip for now', 'classifai' ); ?>
					</a>
				</span>
				<span class="classifai-setup-footer__right">
					<input name="classifai-setup-step" type="hidden" value="1" />
					<?php wp_nonce_field( 'classifai-setup-step-action', 'classifai-setup-step-nonce' ); ?>
					<input class="classifai-button" type="submit" value="<?php esc_attr_e( 'Start Setup', 'classifai' ); ?>" />
				</span>
			</div>
		</div>
		</form>
	</div>
</div>
