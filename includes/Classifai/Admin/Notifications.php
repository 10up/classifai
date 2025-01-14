<?php
/* phpcs:disable PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage */

namespace Classifai\Admin;

use Classifai\Features\DescriptiveTextGenerator;
use Classifai\Features\Classification;
use function Classifai\should_use_legacy_settings_panel;

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
		add_action( 'admin_notices', [ $this, 'maybe_render_notices' ], 0 );
		add_action( 'admin_enqueue_scripts', [ $this, 'add_dismiss_script' ] );
		add_action( 'wp_ajax_classifai_dismiss_notice', [ $this, 'ajax_maybe_dismiss_notice' ] );
	}

	/**
	 * Render any needed admin notices.
	 */
	public function maybe_render_notices() {
		// Only show these notices to admins.
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$this->render_registration_notice();
		$this->render_activation_notice();
		$this->thresholds_update_notice();
		$this->v3_migration_completed_notice();
		$this->render_embeddings_notice();
		$this->render_notices();
	}

	/**
	 * Render a registration notice, if needed.
	 */
	public function render_registration_notice() {
		$registration_settings = get_option( 'classifai_settings' );
		$page                  = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

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
	}

	/**
	 * Render an activation notice, if needed.
	 */
	public function render_activation_notice() {
		$needs_setup = get_transient( 'classifai_activation_notice' );

		if ( ! $needs_setup ) {
			return;
		}

		$onboarding = new Onboarding();
		if ( $onboarding->is_onboarding_completed() ) {
			delete_transient( 'classifai_activation_notice' );
			return;
		}

		$setup_url = admin_url( 'tools.php?page=classifai#/language_processing?welcome_guide=1' );
		if ( should_use_legacy_settings_panel() ) {
			$setup_url = admin_url( 'admin.php?page=classifai_setup' );
		}

		// Prevent showing the default WordPress "Plugin Activated" notice.
		unset( $_GET['activate'] ); // phpcs:ignore WordPress.Security.NonceVerification
		?>

		<div data-notice="plugin-activation" class="notice notice-success is-dismissible">
			<div id="classifai-activation-notice">
				<div class="classifai-logo">
					<img src="<?php echo esc_url( CLASSIFAI_PLUGIN_URL . 'assets/img/classifai.png' ); ?>" alt="<?php esc_attr_e( 'ClassifAI', 'classifai' ); ?>" />
				</div>
				<div class="classifai-activation-message">
					<p><?php esc_html_e( 'Thanks for downloading ClassifAI.', 'classifai' ); ?></p>
					<p><?php esc_html_e( 'Choose your Features, connect your AI Provider accounts, and you’re ready to go.', 'classifai' ); ?></p>
				</div>
				<a class="components-button is-primary" href="<?php echo esc_url( $setup_url ); ?>">
					<?php esc_html_e( 'Get started', 'classifai' ); ?>
				</a>
			</div>
		</div>

		<?php
		delete_transient( 'classifai_activation_notice' );
	}

	/**
	 * Display a dismissable admin notice when a threshold may need updating.
	 *
	 * We used to recommend thresholds between 50-55% but in the latest
	 * version of the AI Vision API, seems 70% is a better threshold.
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
					$message = __( 'The previous recommended threshold for descriptive text generation was 55% but we find better results now at around 70%.', 'classifai' );
					break;
			}

			// Don't show the notice if the user has already dismissed it.
			if ( get_user_meta( get_current_user_id(), "classifai_dismissed_{$key}", true ) ) {
				continue;
			}

			// Don't show the notice if the threshold is already at 70% or higher.
			if ( $key && isset( $settings[ $key ] ) && $settings[ $key ] >= 70 ) {
				continue;
			}
			?>

			<div class="notice notice-warning is-dismissible classifai-dismissible-notice" data-notice="<?php echo esc_attr( $key ); ?>">
				<p>
					<?php
					echo wp_kses_post(
						sprintf(
							// translators: %1$s: Feature specific message; %2$s: URL to Feature settings.
							__( 'ClassifAI has updated to the v4.0 of the Azure AI Vision API. %1$s <a href="%2$s">Click here to adjust those settings</a>.', 'classifai' ),
							esc_html( $message ),
							esc_url( admin_url( "tools.php?page=classifai#/image_processing/$name" ) )
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
						__( '%1$sClassifAI 3.0.0%2$s has changed how AI providers are integrated with individual features. This changes how settings are stored and requires that existing settings be migrated. This migration has happened automatically and you can %3$sverify your settings here%4$s.', 'classifai' ),
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

	/**
	 * Render a notice about needing to regenerate embeddings.
	 */
	public function render_embeddings_notice() {
		// Bail if no need to show the notice.
		if ( get_option( 'classifai_hide_embeddings_notice', false ) ) {
			return;
		}

		// Ensure the feature exists.
		if ( ! class_exists( 'Classifai\Features\Classification' ) ) {
			return;
		}

		$feature_instance = new Classification();

		// Don't show the notice if the feature is not enabled.
		if ( ! $feature_instance->is_feature_enabled() ) {
			return;
		}

		// Don't show the notice if the provider is not OpenAI Embeddings.
		$provider = $feature_instance->get_settings( 'provider' );
		if ( 'openai_embeddings' !== $provider ) {
			return;
		}

		$key = 'embedding_regen_completed';

		// Don't show the notice if the user has already dismissed it.
		if ( get_user_meta( get_current_user_id(), "classifai_dismissed_{$key}", true ) ) {
			return;
		}
		?>

		<div class="notice notice-warning is-dismissible classifai-dismissible-notice" data-notice="<?php echo esc_attr( $key ); ?>">
			<p>
				<?php
				echo wp_kses_post(
					sprintf(
						// translators: %1$s: Feature specific message; %2$s: URL to Feature settings.
						__( 'ClassifAI has updated to the <code>text-embedding-3-small</code> embeddings model. <br>This requires regenerating any stored embeddings for functionality to work properly. <br><a href="%1$s">Click here to do that</a>, noting this will make multiple API requests to OpenAI.', 'classifai' ),
						wp_nonce_url( admin_url( 'admin-post.php?action=classifai_regen_embeddings' ), 'regen_embeddings', 'embeddings_nonce' )
					)
				);
				?>
			</p>
		</div>

		<?php
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

		/* phpcs:disable Squiz.PHP.Heredoc.NotAllowed */
		$script = <<<EOD
jQuery( function() {
	const dismissNotices = document.querySelectorAll( '.classifai-dismissible-notice' );

	if ( ! dismissNotices.length ) {
		return;
	}

	// Add an event listener to the dismiss buttons.
	dismissNotices.forEach( function( dismissNotice ) {
		let dismissBtn = dismissNotice.querySelector( '.notice-dismiss' );
		dismissBtn.addEventListener( 'click', function( event ) {
			const id = dismissNotice.getAttribute( 'data-notice' );

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
});
EOD;
		/* phpcs:enable Squiz.PHP.Heredoc.NotAllowed */

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
	 * Render any saved notices to display.
	 */
	public function render_notices() {
		$notices = $this->get_notices();
		if ( empty( $notices ) ) {
			return;
		}

		foreach ( $notices as $notice ) {
			if ( ! empty( $notice['message'] ) ) {
				?>
				<div class="notice notice-<?php echo esc_attr( $notice['type'] ); ?> is-dismissible">
					<p><?php echo esc_html( $notice['message'] ); ?></p>
				</div>
				<?php
			}
		}
	}

	/**
	 * Get any saved notices to display.
	 *
	 * @return mixed
	 */
	public function get_notices() {
		$notices = get_transient( 'classifai_notices' );
		delete_transient( 'classifai_notices' );

		return $notices;
	}

	/**
	 * Set a notice to be displayed.
	 *
	 * This will be displayed on the next page load.
	 * The notice will be stored in a transient.
	 *
	 * @param string $message The notice message.
	 * @param string $type    The notice type.
	 */
	public function set_notice( string $message, string $type = 'info' ) {
		$notices = get_transient( 'classifai_notices' );
		if ( ! is_array( $notices ) ) {
			$notices = [];
		}

		$notices[] = [
			'type'    => $type,
			'message' => $message,
		];
		set_transient( 'classifai_notices', $notices );
	}
}
