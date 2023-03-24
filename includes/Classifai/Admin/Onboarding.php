<?php

namespace Classifai\Admin;

class Onboarding {
	/**
	 * Register the actions needed.
	 */
	public function __construct() {
		add_action( 'admin_menu', [ $this, 'register_setup_page' ] );
	}

	/**
	 * Registers a hidden sub menu page for the onboarding wizard.
	 */
	public function register_setup_page() {
		add_submenu_page(
			null,
			esc_html__( 'ClassifAI Setup', 'classifai' ),
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
		?>
		<div class="classifai-content">
			<?php
			include_once CLASSIFAI_PLUGIN_DIR . '/includes/Classifai/Admin/templates/classifai-header.php';
			?>
			<div class="classifai-wizard">
				<div class="classifai-wizard__header">
					<div class="classifai-wizard__step-wrapper">
						<div class="classifai-wizard__steps">
							<div class="classifai-wizard__step is-active">
								<div class="classifai-wizard__step__label">
									<span class="step-count">1</span>
									<span class="step-title">
										<?php esc_html_e( 'Enable Features', 'classifai' ); ?>
									</span>
								</div>
							</div>

							<div class="classifai-wizard__step-divider"></div>

							<div class="classifai-wizard__step">
								<div class="classifai-wizard__step__label">
									<span class="step-count">2</span>
									<span class="step-title">
										<?php esc_html_e( 'Register ClassifAI', 'classifai' ); ?>
									</span>
								</div>
							</div>

							<div class="classifai-wizard__step-divider"></div>
							<div class="classifai-wizard__step">
								<div class="classifai-wizard__step__label">
									<span class="step-count">3</span>
									<span class="step-title">
										<?php esc_html_e( 'Access AI', 'classifai' ); ?>
									</span>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>




			<div class="wrap classifai-setup-wrapper">

			</div>
		</div>
		<?php
	}
}
