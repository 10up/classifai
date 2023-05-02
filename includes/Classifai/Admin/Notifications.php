<?php

namespace Classifai\Admin;

class Notifications {

	/**
	 * @var string $message The notice string.
	 */
	protected $message;

	/**
	 * Check to see if we can register this class.
	 *
	 * @return bool
	 */
	public function can_register() {
		return is_admin();
	}

	/**
	 * Register the actions needed.
	 */
	public function register() {
		add_action( 'classifai_activation_hook', [ $this, 'add_activation_notice' ] );
		add_action( 'admin_notices', [ $this, 'maybe_render_notices' ], 0 );
	}

	/**
	 * Respond to the activation hook.
	 */
	public function maybe_render_notices() {
		$registration_settings = get_option( 'classifai_settings' );
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';

		if (
			'classifai' === $page &&
			( ! isset( $registration_settings['valid_license'] ) || ! $registration_settings['valid_license'] )
		) {
			$notice_url = 'https://classifaiplugin.com/#cta';

			?>
			<div data-notice="auto-upgrade-disabled" class="notice notice-warning">
				<?php /* translators: %s: ClassifAI settings url */ ?>
				<p><?php echo wp_kses_post( sprintf( __( '<a href="%s">Register ClassifAI</a> to receive important plugin updates and other ClassifAI news.', 'classifai' ), esc_url( $notice_url ) ) ); ?></p>
			</div>
			<?php
		}

		$needs_setup = get_transient( 'classifai_activation_notice' );
		if ( $needs_setup ) {
			if ( Onboarding::is_onboarding_completed() ) {
				delete_transient( 'classifai_activation_notice' );
				return;
			}

			// Prevent showing the default WordPress "Plugin Activated" notice.
			unset( $_GET['activate'] ); // phpcs:ignore WordPress.Security.NonceVerification
			?>
			<div data-notice="plugin-activation" class="notice notice-success is-dismissible">
				<div id="classifai-activation-notice">
					<div class="classifai-logo">
						<img src="<?php echo esc_url( CLASSIFAI_PLUGIN_URL . 'assets/img/classifai.png' ); ?>" alt="<?php esc_attr_e( 'ClassifAI', 'classifai' ); ?>" />
					</div>
					<h3 class="classifai-activation-message">
						<?php esc_html_e( 'Congratulations, the ClassifAI plugin is now activated.', 'classifai' ); ?>
					</h3>
					<a class="classifai-button" href="<?php echo esc_url( admin_url( 'admin.php?page=classifai_setup' ) ); ?>">
						<?php esc_html_e( 'Start setup', 'classifai' ); ?>
					</a>
				</div>
			</div>
			<?php
			delete_transient( 'classifai_activation_notice' );
		}
	}
}
