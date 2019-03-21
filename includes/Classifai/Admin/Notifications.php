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
		$is_setup = get_option( 'classifai_configured' );
		return ( ! $is_setup );
	}

	/**
	 * Register the actions needed.
	 */
	public function register() {
		$this->message = esc_html__( 'ClassifAI requires setup', 'classifai' );
		add_action( 'classifai_activation_hook', [ $this, 'add_activation_notice' ] );
		add_action( 'admin_notices', [ $this, 'maybe_render_activation_notice' ] );
	}

	/**
	 * Respond to the activation hook.
	 */
	public function maybe_render_activation_notice() {
		$should_render = get_transient( 'classifai_activation_notice' );
		if ( $should_render ) {
			printf(
				'<div class="notice notice-warning"><p><a href="%s">' . esc_html( $this->message ) . '</a></p></div>',
				esc_url( admin_url( 'options-general.php?page=classifai_settings' ) )
			);
			delete_transient( 'classifai_activation_notice' );
		}
	}

}
