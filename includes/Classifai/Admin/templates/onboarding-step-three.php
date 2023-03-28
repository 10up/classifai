<?php
/**
 * Step-3 template for ClassifAI Onboarding.
 *
 * @package ClassifAI
 */

$base_url           = admin_url( 'admin.php?page=classifai_setup&step=3' );
$onboarding_options = get_option( 'classifai_onboarding_options', array() );
$enabled_features   = isset( $onboarding_options['enabled_features'] ) ? $onboarding_options['enabled_features'] : array();
$enabled_providers  = array();
$providers          = Classifai\Admin\Onboarding::get_setup_providers();

if ( isset( $enabled_features['language'] ) && ! empty( $enabled_features['language']['classify'] ) ) {
	$enabled_providers[] = 'watson_nlu';
}

if ( isset( $enabled_features['language'] ) && ! empty( $enabled_features['language']['excerpt_generation'] ) ) {
	$enabled_providers[] = 'openai_chatgpt';
}

if ( ! empty( $enabled_features['images'] ) ) {
	$enabled_providers[] = 'computer_vision';
}

if ( ! empty( $enabled_features['recommended_content'] ) ) {
	$enabled_providers[] = 'personalizer';
}
$current_provider = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : $enabled_providers[0]; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$next_provider    = $enabled_providers[ array_search( $current_provider, $enabled_providers, true ) + 1 ];

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

				foreach ( $enabled_providers as $provider ) {
					$provider_url = add_query_arg( 'tab', $provider, $base_url );
					$is_active    = ( $current_provider === $provider ) ? 'active' : '';
					?>
					<a href="<?php echo esc_url( $provider_url ); ?>" class="tab <?php echo esc_attr( $is_active ); ?>">
						<?php echo esc_html( $providers[ $provider ]['title'] ); ?>
					</a>
					<?php
				}
				?>
			</div>
			<div class="classifai-setup-form">
				<form method="POST" action="">
					<?php
					// Load the appropriate step.
					switch ( $current_provider ) {
						case 'watson_nlu':
							Classifai\Admin\Onboarding::render_classifai_setup_settings( 'classifai_watson_nlu', array( 'url', 'username', 'password', 'toggle' ) );
							break;

						default:
							break;
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
