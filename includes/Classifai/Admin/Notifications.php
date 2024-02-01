<?php

namespace Classifai\Admin;

use Classifai\Features\DescriptiveTextGenerator;
use Classifai\Features\ImageTagsGenerator;

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
	 * Display a dismissable admin notice when a threshold may need updating.
	 *
	 * We used to recommend thresholds between 70-75% but in the latest
	 * version of the AI Vision API, seems 55% is a better threshold.
	 */
	public function thresholds_update_notice() {
		$features = [
			'feature_descriptive_text_generator' => 'Classifai\Features\DescriptiveTextGenerator',
			'feature_image_tags_generator'       => 'Classifai\Features\ImageTagsGenerator',
		];

		foreach ( $features as $name => $feature_class ) {
			if ( ! class_exists( $feature_class ) ) {
				continue;
			}

			$feature_instance = new $feature_class();

			if ( ! $feature_instance->is_feature_enabled() ) {
				continue;
			}

			// TODO: add a check where we don't show the notice if it's already been dismissed

			$settings = $feature_instance->get_settings( 'ms_computer_vision' );
			$key      = '';
			$message  = '';

			switch ( $feature_instance::ID ) {
				case DescriptiveTextGenerator::ID:
					$key     = 'descriptive_confidence_threshold';
					$message = __( 'The previous recommended threshold for descriptive text generation was 75% but we find better results now at around 55%.', 'classifai' );
					break;

				case ImageTagsGenerator::ID:
					$key     = 'tag_confidence_threshold';
					$message = __( 'The previous recommended threshold for image tagging was 70% but we find better results now at around 55%.', 'classifai' );
					break;
			}

			if ( $key && isset( $settings[ $key ] ) && $settings[ $key ] <= 55 ) {
				continue;
			}
			?>

			<div class='notice notice-warning is-dismissible'>
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
}
