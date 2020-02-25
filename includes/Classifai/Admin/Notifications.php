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
		add_action( 'admin_notices', [ $this, 'maybe_render_notices' ] );
	}

	/**
	 * Respond to the activation hook.
	 */
	public function maybe_render_notices() {
		$registration_settings = get_option( 'classifai_settings' );

		if (
			'classifai_settings' === get_current_screen()->parent_base &&
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
			printf(
				'<div class="notice notice-warning"><p><a href="%s">' . esc_html__( 'ClassifAI requires setup', 'classifai' ) . '</a></p></div>',
				esc_url( admin_url( 'admin.php?page=classifai_settings' ) )
			);
			delete_transient( 'classifai_activation_notice' );
		}
	}
}
