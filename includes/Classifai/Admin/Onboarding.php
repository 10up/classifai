<?php

namespace Classifai\Admin;

use Classifai\Plugin;
use Classifai\Services\ServicesManager;

class Onboarding {

	/**
	 * @var string $setup_url The admin onboarding URL.
	 */
	protected $setup_url;

	/**
	 * @var array $features The list of features.
	 */
	public $features = array();

	/**
	 * Register the actions needed.
	 */
	public function __construct() {
		$this->setup_url = admin_url( 'admin.php?page=classifai_setup' );
	}

	/**
	 * Inintialize the class and register the actions needed.
	 */
	public function init() {
		add_action( 'admin_menu', [ $this, 'register_setup_page' ] );
		add_action( 'admin_init', [ $this, 'handle_step_submission' ] );
		add_action( 'admin_init', [ $this, 'prevent_direct_step_visits' ] );
		add_action( 'admin_post_classifai_skip_step', [ $this, 'handle_skip_setup_step' ] );
	}

	/**
	 * Registers a hidden sub menu page for the onboarding wizard.
	 */
	public function register_setup_page() {
		add_submenu_page(
			'admin.php',
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
		$current_step = isset( $_GET['step'] ) ? sanitize_text_field( wp_unslash( $_GET['step'] ) ) : '1'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

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
								$is_active   = ( $current_step === (string) $key ) ? 'is-active' : '';
								$is_complete = ( $current_step > $key ) ? 'is-complete' : '';
								$step_url    = add_query_arg( 'step', $key, $this->setup_url );
								?>
								<div class="classifai-setup__step <?php echo esc_attr( $is_active . ' ' . $is_complete ); ?>">
									<div class="classifai-setup__step__label">
										<?php if ( $is_complete ) { ?>
											<a href="<?php echo esc_url( $step_url ); ?>">
										<?php } ?>
											<span class="step-count">
												<?php
												if ( ! $is_complete ) {
													echo esc_html( $step['step'] );
												} else {
													?>
													<span class="dashicons dashicons-yes"></span>
													<?php
												}
												?>
											</span>
											<span class="step-title">
												<?php echo esc_html( $step['title'] ); ?>
											</span>
										<?php if ( $is_complete ) { ?>
											</a>
										<?php } ?>
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
				<div class="classifai-wrap wrap classifai-setup__wrapper">
					<div class="classifai-setup__content">
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
								require_once 'templates/onboarding-step-three.php';
								break;

							case '4':
								require_once 'templates/onboarding-step-four.php';
								break;

							default:
								break;
						}
						?>
					</div>
				</div>
			</div>

		</div>

		<?php
	}

	/**
	 * Handle the submission of the step of the onboarding wizard.
	 *
	 * @return void
	 */
	public function handle_step_submission() {
		if ( ! isset( $_POST['classifai-setup-step-nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['classifai-setup-step-nonce'] ) ), 'classifai-setup-step-action' ) ) {
			return;
		}

		$step = isset( $_POST['classifai-setup-step'] ) ? absint( wp_unslash( $_POST['classifai-setup-step'] ) ) : '';
		if ( empty( $step ) ) {
			return;
		}

		$features           = $this->get_features( false );
		$onboarding_options = array(
			'status' => 'inprogress',
		);

		switch ( $step ) {
			case 1:
				// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				$enabled_features = isset( $_POST['classifai-features'] ) ? $this->classifai_sanitize( wp_unslash( $_POST['classifai-features'] ) ) : array();

				if ( empty( $enabled_features ) ) {
					add_settings_error(
						'classifai-setup',
						'classifai-step-one-error',
						esc_html__( 'Please enable at least one feature to set up ClassifAI.', 'classifai' ),
						'error'
					);
					return;
				}

				// Disable unchecked features.
				$configured_features = $this->get_configured_features();
				foreach ( $configured_features as $feature_key ) {
					$enabled = isset( $enabled_features[ $feature_key ] );

					if ( ! $enabled ) {
						$feature_class = $features[ $feature_key ] ?? null;
						if ( ! $feature_class instanceof \Classifai\Features\Feature ) {
							continue;
						}

						$settings = $feature_class->get_settings();
						// Disable the feature.
						$settings['status'] = '0';
						update_option( $feature_class->get_option_name(), $settings );
					}
				}

				// Skip step 2 if it is already configured.
				$registration_settings = get_option( 'classifai_settings', array() );
				if ( isset( $registration_settings['valid_license'] ) && $registration_settings['valid_license'] ) {
					$step = 2;
				}

				$onboarding_options['step_completed']      = $step;
				$onboarding_options['enabled_features']    = $enabled_features;
				$onboarding_options['configured_features'] = array();
				break;

			case 2:
				// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				$classifai_settings = isset( $_POST['classifai_settings'] ) ? $this->classifai_sanitize( wp_unslash( $_POST['classifai_settings'] ) ) : array();

				// Save the ClassifAI settings.
				update_option( 'classifai_settings', $classifai_settings );

				$setting_errors = get_settings_errors( 'registration' );
				if ( ! empty( $setting_errors ) ) {
					// Stay on same setup step and display error.
					return;
				}
				$onboarding_options['step_completed'] = $step;
				break;

			case 3:
				// Bail if no provider provided.
				if ( empty( $_POST['classifai-setup-feature'] ) ) {
					return;
				}

				$features    = $this->get_features( false );
				$feature_key = sanitize_text_field( wp_unslash( $_POST['classifai-setup-feature'] ) );
				$feature     = $features[ $feature_key ] ?? null;

				if ( ! $feature instanceof \Classifai\Features\Feature ) {
					return;
				}

				$feature_option = $feature->get_option_name();

				// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				$form_data = isset( $_POST[ $feature_option ] ) ? $this->classifai_sanitize( wp_unslash( $_POST[ $feature_option ] ) ) : array();

				$settings         = $feature->get_settings();
				$options          = $this->get_onboarding_options();
				$enabled_features = $options['enabled_features'] ?? array();
				$is_enabled       = isset( $enabled_features[ $feature_key ] );

				if ( $is_enabled ) {
					// Enable the feature.
					$settings['status'] = '1';
				}

				// Update the settings with the new values.
				$settings = array_merge( $settings, $form_data );

				// Save the ClassifAI settings.
				update_option( $feature_option, $settings );

				$setting_errors = get_settings_errors();
				if ( ! empty( $setting_errors ) ) {
					// Stay on same setup step and display error.
					return;
				}

				$onboarding_options  = $this->get_onboarding_options();
				$configured_features = $onboarding_options['configured_features'] ?? array();

				$onboarding_options['configured_features'] = array_unique( array_merge( $configured_features, array( $feature_key ) ) );
				// Save the options to use it later steps.
				$this->update_onboarding_options( $onboarding_options );

				// Redirect to next provider setup step.
				$next_feature = $this->get_next_feature( $feature_key );
				if ( ! empty( $next_feature ) ) {
					wp_safe_redirect(
						add_query_arg(
							array(
								'step' => $step,
								'tab'  => $next_feature,
							),
							$this->setup_url
						)
					);
					exit();
				}

				$onboarding_options['step_completed'] = $step;
				$onboarding_options['status']         = 'completed';
				break;

			default:
				break;
		}

		// Save the options to use it later steps.
		$this->update_onboarding_options( $onboarding_options );

		// Redirect to next setup step.
		wp_safe_redirect( add_query_arg( 'step', ( $step + 1 ), $this->setup_url ) );
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

	/**
	 * Render classifai setup settings with the given fields.
	 *
	 * @param string   $setting_name The name of the setting.
	 * @param string[] $fields       The fields to render.
	 * @return void
	 */
	public function render_classifai_setup_settings( $setting_name, $fields = array() ) {
		global $wp_settings_sections, $wp_settings_fields;

		if ( ! isset( $wp_settings_fields[ $setting_name ][ $setting_name ] ) ) {
			return;
		}

		// Render the section.
		if ( isset( $wp_settings_sections[ $setting_name ] ) && ! empty( $wp_settings_sections[ $setting_name ][ $setting_name ] ) && 'classifai_settings' !== $setting_name ) {
			$section = $wp_settings_sections[ $setting_name ][ $setting_name ];
			if ( '' !== $section['before_section'] ) {
				if ( '' !== $section['section_class'] ) {
					echo wp_kses_post( sprintf( $section['before_section'], esc_attr( $section['section_class'] ) ) );
				} else {
					echo wp_kses_post( $section['before_section'] );
				}
			}

			if ( $section['title'] ) {
				?>
				<h2>
					<?php echo esc_html( $section['title'] ); ?>
				</h2>
				<?php
			}

			if ( $section['callback'] ) {
				call_user_func( $section['callback'], $section );
			}
		}

		// Render the fields.
		$setting_fields = $wp_settings_fields[ $setting_name ][ $setting_name ];
		foreach ( $fields as $field_name ) {
			if ( ! isset( $setting_fields[ $field_name ] ) ) {
				continue;
			}

			$field = $setting_fields[ $field_name ];
			if ( ! isset( $field['callback'] ) || ! is_callable( $field['callback'] ) ) {
				continue;
			}

			if ( 'toggle' === $field_name ) {
				call_user_func( $field['callback'], $field['args'] );
				continue;
			}
			?>
			<div class="classifai-setup-form-field">
				<label for="<?php echo esc_attr( $field['args']['label_for'] ); ?>">
					<?php echo esc_html( $field['title'] ); ?>
				</label>
				<?php
				call_user_func( $field['callback'], $field['args'] );
				?>
			</div>
			<?php
		}
	}

	/**
	 * Render classifai setup feature settings.
	 *
	 * @param string $feature $feature to setup.
	 * @return void
	 */
	public function render_classifai_setup_feature( $feature ) {
		global $wp_settings_fields;
		$features      = $this->get_features( false );
		$feature_class = $features[ $feature ] ?? null;
		if ( ! $feature_class instanceof \Classifai\Features\Feature ) {
			return;
		}

		$setting_name = $feature_class->get_option_name();
		$section_name = $feature_class->get_option_name() . '_section';
		if ( ! isset( $wp_settings_fields[ $setting_name ][ $section_name ] ) ) {
			return;
		}

		// Render the fields.
		$skip           = true;
		$setting_fields = $wp_settings_fields[ $setting_name ][ $section_name ];
		foreach ( $setting_fields as $field_name => $field ) {
			if ( 'provider' === $field_name ) {
				$skip = false;
			}

			if ( $skip ) {
				continue;
			}

			if ( ! isset( $field['callback'] ) || ! is_callable( $field['callback'] ) ) {
				continue;
			}

			$label_for = $field['args']['label_for'] ?? '';
			$class     = $field['args']['class'] ?? '';

			if ( 'ibm_watson_nlu_toggle' === $field_name ) {
				?>
				<tr>
					<td class="classifai-setup-form-field <?php echo esc_attr( $class ); ?>">
						<?php
						call_user_func( $field['callback'], $field['args'] );
						?>
					</td>
				</tr>
				<?php
				continue;
			}
			?>
			<tr>
				<th scope="row" class="classifai-setup-form-field-label <?php echo esc_attr( $class ); ?>">
					<label for="<?php echo esc_attr( $label_for ); ?>">
						<?php echo esc_html( $field['title'] ); ?>
					</label>
				</th>
			</tr>
			<tr>
				<td class="classifai-setup-form-field <?php echo esc_attr( $class ); ?>">
					<?php
					call_user_func( $field['callback'], $field['args'] );
					?>
				</td>
			</tr>
			<?php
		}
	}

	/**
	 * Get the Onboarding features from service class to display on the setup wizard.
	 *
	 * @param bool $nested Whether to return the features in a nested array or not.
	 * @return array The Onboarding features.
	 */
	public function get_features( $nested = true ) {
		if ( empty( $this->features ) ) {
			$services = Plugin::$instance->services;
			if ( empty( $services ) || empty( $services['service_manager'] ) || ! $services['service_manager'] instanceof ServicesManager ) {
				return [];
			}

			/** @var ServicesManager $service_manager Instance of the services manager class. */
			$service_manager     = $services['service_manager'];
			$onboarding_features = [];

			foreach ( $service_manager->service_classes as $service ) {
				$display_name = $service->get_display_name();
				$service_slug = $service->get_menu_slug();
				$features     = array();
				if ( empty( $service->feature_classes ) ) {
					continue;
				}

				foreach ( $service->feature_classes as $feature_class ) {
					if ( ! empty( $feature_class ) ) {
						$features[ $feature_class::ID ] = $feature_class;
					}
				}

				$onboarding_features[ $service_slug ] = array(
					'title'    => $display_name,
					'features' => $features,
				);
			}

			$this->features = $onboarding_features;
		}

		if ( $nested ) {
			return $this->features;
		}

		if ( empty( $this->features ) ) {
			return [];
		}

		$features = [];
		foreach ( $this->features as $service_slug => $service ) {
			foreach ( $service['features'] as $feature_slug => $feature ) {
				$features[ $feature_slug ] = $feature;
			}
		}
		return $features;
	}

	/**
	 * Get onboarding options.
	 *
	 * @return array The onboarding options.
	 */
	public function get_onboarding_options() {
		return get_option( 'classifai_onboarding_options', array() );
	}

	/**
	 * Check if onboarding is completed.
	 *
	 * @return bool True if onboarding is completed, false otherwise.
	 */
	public function is_onboarding_completed() {
		$options = $this->get_onboarding_options();
		return isset( $options['status'] ) && 'completed' === $options['status'];
	}

	/**
	 * Update onboarding options.
	 *
	 * @param array $options The options to update.
	 */
	public function update_onboarding_options( $options ) {
		if ( ! is_array( $options ) ) {
			return;
		}

		$onboarding_options = $this->get_onboarding_options();
		$onboarding_options = array_merge( $onboarding_options, $options );

		// Update options.
		update_option( 'classifai_onboarding_options', $onboarding_options );
	}

	/**
	 * Handle skip setup step.
	 */
	public function handle_skip_setup_step() {
		if ( ! empty( $_GET['classifai_skip_step_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['classifai_skip_step_nonce'] ) ), 'classifai_skip_step_action' ) ) {
			$step = isset( $_GET['step'] ) ? absint( $_GET['step'] ) : 1;

			$onboarding_options = array(
				'step_completed' => $step,
			);

			if ( 3 === $step ) {
				$onboarding_options['status'] = 'completed';
			}

			$this->update_onboarding_options( $onboarding_options );

			// Redirect to next step.
			wp_safe_redirect( add_query_arg( 'step', ( $step + 1 ), $this->setup_url ) );
			exit();
		} else {
			wp_die( esc_html__( 'You don\'t have permission to perform this operation.', 'classifai' ) );
		}
	}

	/**
	 * Get list of providers enabled for setup in step 1.
	 *
	 * @return array Array of enabled providers.
	 */
	public function get_enabled_features() {
		$features           = $this->get_features( false );
		$onboarding_options = $this->get_onboarding_options();
		$enabled_features   = $onboarding_options['enabled_features'] ?? array();
		foreach ( $enabled_features as $feature_key => $value ) {
			if ( ! isset( $features[ $feature_key ] ) ) {
				unset( $enabled_features[ $feature_key ] );
				continue;
			}
			$enabled_features[ $feature_key ] = $features[ $feature_key ] ?? null;
		}

		return $enabled_features;
	}

	/**
	 * Get next feature to setup.
	 *
	 * @param string $current_feature Current feature.
	 * @return string|bool Next provider to setup or false if none.
	 */
	public function get_next_feature( $current_feature ) {
		$enabled_features = $this->get_enabled_features();
		$keys = array_keys( $enabled_features );
		$index = array_search( $current_feature, $keys, true );

		if ( false === $index ) {
			return false;
		}

		if ( isset( $keys[ $index + 1 ] ) ) {
			return $keys[ $index + 1 ];
		}

		return false;
	}

	/**
	 * Prevents users from accessing the onboarding wizard steps directly.
	 */
	public function prevent_direct_step_visits() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $_GET['page'] ) || ! isset( $_GET['step'] ) || 'classifai_setup' !== sanitize_text_field( wp_unslash( $_GET['page'] ) ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$step = absint( wp_unslash( $_GET['step'] ) );
		$onboarding_options = $this->get_onboarding_options();
		$step_completed     = isset( $onboarding_options['step_completed'] ) ? absint( $onboarding_options['step_completed'] ) : 0;

		if ( ( $step_completed + 1 ) < $step ) {
			wp_die( esc_html__( 'You are not allowed to access this page.', 'classifai' ) );
		}
	}

	/**
	 * Get configured features.
	 *
	 * @return array
	 */
	public function get_configured_features() {
		$features            = $this->get_features( false );
		$configured_features = array();

		foreach ( $features as $feature_key => $feature_class ) {
			if ( ! $feature_class instanceof \Classifai\Features\Feature ) {
				continue;
			}
			$settings = $feature_class->get_settings();
			if ( '1' === $settings['status'] ) {
				$configured_features[] = $feature_key;
			}
		}

		return $configured_features;
	}

}
