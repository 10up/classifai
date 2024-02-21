<?php

namespace Classifai\Admin;

use Classifai\Features\DescriptiveTextGenerator;

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
	public function can_register(): bool {
		return is_admin();
	}

	/**
	 * Register the actions needed.
	 */
	public function register() {
		add_action( 'classifai_activation_hook', [ $this, 'add_activation_notice' ] );
		add_action( 'admin_notices', [ $this, 'maybe_render_notices' ], 0 );
		add_action( 'admin_notices', [ $this, 'thresholds_update_notice' ] );
		add_action( 'admin_notices', [ $this, 'v3_migration_completed_notice' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'add_dismiss_script' ] );
		add_action( 'wp_ajax_classifai_dismiss_notice', [ $this, 'ajax_maybe_dismiss_notice' ] );
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
			$onboarding = new Onboarding();
			if ( $onboarding->is_onboarding_completed() ) {
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

	/**
	 * Print out a script to dismiss a notice.
	 *
	 * This allows us to save that a user has dismissed a notice.
	 *
	 * Influenced by https://github.com/WPTT/admin-notices/blob/af52f563398b42cff82d38eefa55c8121d698ebe/src/Dismiss.php#L77
	 */
	public function add_dismiss_script() {
		$nonce          = wp_create_nonce( 'classifai_dismissible_notice' );
		$admin_ajax_url = esc_url( admin_url( 'admin-ajax.php' ) );

		$script = <<<EOD
jQuery( function() {
    const dismissBtn = document.querySelector( '.classifai-dismissible-notice' );

	if ( ! dismissBtn ) {
		return;
	}

    // Add an event listener to the dismiss button.
    dismissBtn.addEventListener( 'click', function( event ) {
		const id = dismissBtn.getAttribute( 'data-notice' );

		if ( ! id ) {
			return;
		}

		const httpRequest = new XMLHttpRequest();
    	let postData = '';

    	// Build the data to send in our request.
    	// Data has to be formatted as a string here.
    	postData += 'notice_id=' + id;
    	postData += '&action=classifai_dismiss_notice';
    	postData += '&nonce=$nonce';

    	httpRequest.open( 'POST', '$admin_ajax_url' );
    	httpRequest.setRequestHeader( 'Content-Type', 'application/x-www-form-urlencoded' )
    	httpRequest.send( postData );
    });
});
EOD;

		wp_add_inline_script( 'common', $script, 'after' );
	}

	/**
	 * Verify ajax request and dismiss the notice.
	 *
	 * Influenced by https://github.com/WPTT/admin-notices/blob/af52f563398b42cff82d38eefa55c8121d698ebe/src/Dismiss.php#L133
	 */
	public function ajax_maybe_dismiss_notice() {
		if ( ! isset( $_POST['action'] ) || 'classifai_dismiss_notice' !== $_POST['action'] ) {
			return;
		}

		if ( ! isset( $_POST['notice_id'] ) ) {
			return;
		}

		check_ajax_referer( 'classifai_dismissible_notice', 'nonce' );

		$notice_id = sanitize_text_field( wp_unslash( $_POST['notice_id'] ) );

		update_user_meta( get_current_user_id(), "classifai_dismissed_{$notice_id}", true );
	}

	/**
	 * Display a dismissable admin notice when a threshold may need updating.
	 *
	 * We used to recommend thresholds between 70-75% but in the latest
	 * version of the AI Vision API, seems 55% is a better threshold.
	 */
	public function thresholds_update_notice() {
		$features = [
			'feature_descriptive_text_generator' => 'Classifai\Features\DescriptiveTextGenerator',
		];

		foreach ( $features as $name => $feature_class ) {
			if ( ! class_exists( $feature_class ) ) {
				continue;
			}

			$feature_instance = new $feature_class();

			// Don't show the notice if the feature is not enabled.
			if ( ! $feature_instance->is_feature_enabled() ) {
				continue;
			}

			$settings = $feature_instance->get_settings( 'ms_computer_vision' );
			$key      = '';
			$message  = '';

			switch ( $feature_instance::ID ) {
				case DescriptiveTextGenerator::ID:
					$key     = 'descriptive_confidence_threshold';
					$message = __( 'The previous recommended threshold for descriptive text generation was 75% but we find better results now at around 55%.', 'classifai' );
					break;
			}

			// Don't show the notice if the user has already dismissed it.
			if ( get_user_meta( get_current_user_id(), "classifai_dismissed_{$key}", true ) ) {
				continue;
			}

			// Don't show the notice if the threshold is already at 55% or lower.
			if ( $key && isset( $settings[ $key ] ) && $settings[ $key ] <= 55 ) {
				continue;
			}
			?>

			<div class="notice notice-warning is-dismissible classifai-dismissible-notice" data-notice="<?php echo esc_attr( $key ); ?>">
				<p>
					<?php
					echo wp_kses_post(
						sprintf(
							// translators: %1$s: Feature specific message; %2$s: URL to Feature settings.
							__( 'ClassifAI has updated to the v3.2 of the Azure AI Vision API. %1$s <a href="%2$s">Click here to adjust those settings</a>.', 'classifai' ),
							esc_html( $message ),
							esc_url( admin_url( "tools.php?page=classifai&tab=image_processing&feature=$name" ) )
						)
					);
					?>
				</p>
			</div>

			<?php
		}
	}

	/**
	 * Displays the migration completed notice for feature-first refactor
	 * of the settings.
	 *
	 * @since 3.0.0
	 */
	public function v3_migration_completed_notice() {
		// Bail if no need to show the notice.
		$display_notice = get_option( 'classifai_display_v3_migration_notice', false );
		if ( ! $display_notice ) {
			return;
		}

		// Don't show the notice if the user has already dismissed it.
		$key = 'v3_migration_completed';
		if ( get_user_meta( get_current_user_id(), "classifai_dismissed_{$key}", true ) ) {
			return;
		}
		?>
		<div class="notice notice-info is-dismissible classifai-dismissible-notice classifai-migation-notice" data-notice="<?php echo esc_attr( $key ); ?>">
			<p>
				<?php
				echo wp_kses_post(
					sprintf(
						// translators: %1$s: <a> tag starting; %2$s: <a> tag closing.
						__( '%1$sClassifAI 3.0.0%2$s has changed how AI service providers are integrated with AI features which impacted how settings were handled.  Those settings have been migrated automatically, and you can %3$sview them here%4$s.', 'classifai' ),
						'<strong>',
						'</strong>',
						'<a href="' . esc_url( admin_url( 'tools.php?page=classifai' ) ) . '">',
						'</a>'
					)
				);
				?>
			</p>
		</div>
		<?php
	}
}
