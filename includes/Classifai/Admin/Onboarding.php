<?php

namespace Classifai\Admin;

class Onboarding {
	/**
	 * Register the actions needed.
	 */
	public function __construct() {
		add_action( 'admin_menu', [ $this, 'register_setup_page' ] );
		add_action( 'admin_init', [ $this, 'handle_step_one_submission' ] );
		add_action( 'admin_init', [ $this, 'handle_step_two_submission' ] );
	}

	/**
	 * Registers a hidden sub menu page for the onboarding wizard.
	 */
	public function register_setup_page() {
		add_submenu_page(
			null,
			esc_attr__( 'ClassifAI Setup', 'classifai' ),
			'',
			'manage_options',
			'classifai_setup',
			[ $this, 'render_setup_page' ]
		);
	}

	/**
	 * Renders the ClassifAI setup page.
	 */
	public function render_setup_page() {
		$current_step     = isset( $_GET['step'] ) ? sanitize_text_field( wp_unslash( $_GET['step'] ) ) : '1'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$onboarding_steps = array(
			'1' => array(
				'step'  => __( '1', 'classifai' ),
				'title' => __( 'Enable Features', 'classifai' ),
			),
			'2' => array(
				'step'  => __( '2', 'classifai' ),
				'title' => __( 'Register ClassifAI', 'classifai' ),
			),
			'3' => array(
				'step'  => __( '3', 'classifai' ),
				'title' => __( 'Access AI', 'classifai' ),
			),
		);
		?>
		<div class="classifai-content classifai-setup-page">
			<?php
			include_once 'templates/classifai-header.php';
			?>
			<div class="classifai-setup">
				<div class="classifai-setup__header">
					<div class="classifai-setup__step-wrapper">
						<div class="classifai-setup__steps">
							<?php
							foreach ( $onboarding_steps as $key => $step ) {
								?>
								<div class="classifai-setup__step <?php echo ( $current_step === (string) $key ) ? 'is-active' : ''; ?>">
									<div class="classifai-setup__step__label">
										<span class="step-count"><?php echo esc_html( $step['step'] ); ?></span>
										<span class="step-title">
											<?php echo esc_html( $step['title'] ); ?>
										</span>
									</div>
								</div>
								<?php
								if ( array_key_last( $onboarding_steps ) !== $key ) {
									?>
									<div class="classifai-setup__step-divider"></div>
									<?php
								}
							}
							?>
						</div>
					</div>
				</div>
				<div class="wrap classifai-setup__content">
					<h1 class="classifai-setup-heading">
						<?php esc_html_e( 'Welcome to ClassifAI', 'classifai' ); ?>
					</h1>
					<?php
					// Load the appropriate step.
					switch ( $current_step ) {
						case '1':
							require_once 'templates/onboarding-step-one.php';
							break;

						case '2':
							require_once 'templates/onboarding-step-two.php';
							break;

						case '3':
							break;

						default:
							break;
					}
					?>
				</div>
			</div>

		</div>
		<?php
	}

	/**
	 * Handle the submission of the first step of the onboarding wizard.
	 *
	 * @return void
	 */
	public function handle_step_one_submission() {
		if ( ! isset( $_POST['classifai-setup-step-one-nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['classifai-setup-step-one-nonce'] ) ), 'classifai-setup-step-one-action' ) ) {
			return;
		}

		$enabled_features = isset( $_POST['classifai-features'] ) ? $this->classifai_sanitize( $_POST['classifai-features'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		$onboarding_options = get_option( 'classifai_onboarding_options', array() );

		$onboarding_options['enabled_features'] = $enabled_features;

		// Save the options to use it later steps.
		update_option( 'classifai_onboarding_options', $onboarding_options );

		// Redirect to next setup step.
		wp_safe_redirect( admin_url( 'admin.php?page=classifai_setup&step=2' ) );
		exit();
	}

	/**
	 * Handle the submission of the Register ClassifAI step of the onboarding wizard.
	 *
	 * @return void
	 */
	public function handle_step_two_submission() {
		if ( ! isset( $_POST['classifai-setup-step-two-nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['classifai-setup-step-two-nonce'] ) ), 'classifai-setup-step-two-action' ) ) {
			return;
		}

		$classifai_settings = isset( $_POST['classifai_settings'] ) ? $this->classifai_sanitize( $_POST['classifai_settings'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		// Save the options to use it later steps.
		update_option( 'classifai_settings', $classifai_settings );

		$setting_errors = get_settings_errors( 'registration' );
		if ( ! empty( $setting_errors ) ) {
			// Stay on same setup step and display error.
			return;
		}

		// Redirect to next setup step.
		wp_safe_redirect( admin_url( 'admin.php?page=classifai_setup&step=3' ) );
		exit();
	}

	/**
	 * Sanitize variables using sanitize_text_field and wp_unslash. Arrays are cleaned recursively.
	 * Non-scalar values are ignored.
	 *
	 * @param string|array $var Data to sanitize.
	 * @return string|array
	 */
	public function classifai_sanitize( $var ) {
		if ( is_array( $var ) ) {
			return array_map( array( $this, 'classifai_sanitize' ), $var );
		} else {
			return is_scalar( $var ) ? sanitize_text_field( wp_unslash( $var ) ) : $var;
		}
	}
}
