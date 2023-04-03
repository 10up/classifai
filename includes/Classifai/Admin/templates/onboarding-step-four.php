<?php
/**
 * Step-4 template for ClassifAI Onboarding.
 *
 * @package ClassifAI
 */

$onboarding_options   = get_option( 'classifai_onboarding_options', array() );
$enabled_features     = $onboarding_options['enabled_features'] ?? array();
$configured_providers = $onboarding_options['configured_providers'] ?? array();
$onboarding           = new Classifai\Admin\Onboarding();
$features             = array_filter(
	$onboarding->get_features(),
	function( $feature ) use ( $enabled_features ) {
		return ! empty( $feature['features'] ) && array_intersect( array_keys( $feature['features'] ), array_keys( $enabled_features ) );
	}
);
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
				<?php esc_html_e( 'ClassifAI configured successfully!', 'classifai' ); ?>
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
								if ( ! in_array( $provider, $configured_providers, true ) ) {
									continue;
								}
								foreach ( $provider_features as $feature_key => $feature_name ) {
									if ( ! in_array( $feature_key, array_keys( $enabled_features[ $provider ] ?? array() ), true ) ) {
										continue;
									}
									?>
									<li class="classifai-enable-feature">
										<span class="dashicons dashicons-yes-alt"></span>
										<label class="classifai-feature-text">
											<?php echo esc_html( $feature_name ); ?>
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
			}
			?>

			<div class="classifai-setup-form">
				<div class="classifai-setup-footer">
					<span class="classifai-setup-footer__left">
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=classifai_settings' ) ); ?>" class="classifai-setup-skip-link">
							<?php esc_html_e( 'Adjust ClassifAI settings', 'classifai' ); ?>
						</a>
					</span>
					<span class="classifai-setup-footer__right">
						<a class="classifai-button" href="<?php echo esc_url( admin_url() ); ?>">
							<?php esc_html_e( 'Done', 'classifai' ); ?>
						</a>
					</span>
				</div>
			</div>
		</div>
	</div>
</div>
