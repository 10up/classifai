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
				<div class="wrap classifai-setup__wrapper">
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

		$onboarding_options = array(
			'status' => 'inprogress',
		);

		switch ( $step ) {
			case 1:
				// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				$enabled_features = isset( $_POST['classifai-features'] ) ? $this->classifai_sanitize( $_POST['classifai-features'] ) : array();

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
				$providers           = $this->get_providers();
				foreach ( $configured_features as $provider_key => $data ) {
					$save_needed = false;
					$provider    = $providers[ $provider_key ];
					if ( empty( $provider ) ) {
						continue;
					}
					$settings = $provider->get_settings();

					foreach ( $data as $feature_key ) {
						$enabled = isset( $enabled_features[ $provider_key ][ $feature_key ] );
						$keys    = explode( '__', $feature_key );
						if ( count( $keys ) > 1 ) {
							$enabled = isset( $enabled_features[ $provider_key ][ $keys[0] ][ $keys[1] ] );
						}

						if ( ! $enabled ) {
							unset( $settings[ $feature_key ] );
							if ( count( $keys ) > 1 ) {
								unset( $settings[ $keys[0] ][ $keys[1] ] );
							}
							$save_needed = true;
						}
					}

					// Save settings
					if ( $save_needed ) {
						update_option( $provider->get_option_name(), $settings );
					}
				}

				// Skip step 2 if it is already configured.
				$registration_settings = get_option( 'classifai_settings', array() );
				if ( isset( $registration_settings['valid_license'] ) && $registration_settings['valid_license'] ) {
					$step = 2;
				}

				$onboarding_options['step_completed']       = $step;
				$onboarding_options['enabled_features']     = $enabled_features;
				$onboarding_options['configured_providers'] = array();
				break;

			case 2:
				// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				$classifai_settings = isset( $_POST['classifai_settings'] ) ? $this->classifai_sanitize( $_POST['classifai_settings'] ) : array();

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
				if ( empty( $_POST['classifai-setup-provider'] ) ) {
					return;
				}

				$providers       = $this->get_providers();
				$provider_option = sanitize_text_field( wp_unslash( $_POST['classifai-setup-provider'] ) );
				$provider        = $providers[ $provider_option ];

				if ( empty( $provider ) ) {
					return;
				}

				// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				$form_data = isset( $_POST[ $provider_option ] ) ? $this->classifai_sanitize( $_POST[ $provider_option ] ) : array();

				$settings     = $provider->get_settings();
				$options      = self::get_onboarding_options();
				$features     = $options['enabled_features'] ?? array();
				$feature_data = $features[ $provider_option ] ?? array();

				// Remove all features from the settings.
				foreach ( $this->get_features() as $value ) {
					$provider_features = $value['features'][ $provider_option ] ?? array();
					foreach ( $provider_features as $feature => $name ) {
						if ( ! isset( $settings[ $feature ] ) ) {
							continue;
						}
						unset( $settings[ $feature ] );
					}
				}

				// Update the settings with the new values.
				$settings = array_merge( $settings, $form_data, $feature_data );

				// Save the ClassifAI settings.
				update_option( $provider_option, $settings );

				$setting_errors = get_settings_errors();
				if ( ! empty( $setting_errors ) ) {
					// Stay on same setup step and display error.
					return;
				}

				$onboarding_options   = self::get_onboarding_options();
				$configured_providers = $onboarding_options['configured_providers'] ?? array();

				$onboarding_options['configured_providers'] = array_unique( array_merge( $configured_providers, array( $provider_option ) ) );
				// Save the options to use it later steps.
				$this->update_onboarding_options( $onboarding_options );

				// Redirect to next provider setup step.
				$next_provider = $this->get_next_provider( $provider_option );
				if ( ! empty( $next_provider ) ) {
					wp_safe_redirect(
						add_query_arg(
							array(
								'step' => $step,
								'tab'  => $next_provider,
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
	public static function render_classifai_setup_settings( $setting_name, $fields ) {
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
				<h2><?php echo esc_html( $section['title'] ); ?></h2>
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
	 * Get the Onboarding features from provider class to display on the setup wizard.
	 *
	 * @return array The Onboarding features.
	 */
	public function get_features() {
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

			if ( empty( $service->provider_classes ) ) {
				continue;
			}

			foreach ( $service->provider_classes as $provider_class ) {
				$options = $provider_class->get_onboarding_options();
				if ( ! empty( $options ) && ! empty( $options['features'] ) ) {
					$features[ $provider_class->get_option_name() ] = $options['features'];
				}
			}

			if ( ! empty( $features ) ) {
				$onboarding_features[ $service_slug ] = array(
					'title'    => $display_name,
					'features' => $features,
				);
			}
		}

		return $onboarding_features;
	}

	/**
	 * Get the list of providers.
	 *
	 * @return array Array of providers.
	 */
	public function get_providers() {
		$services = Plugin::$instance->services;
		if ( empty( $services ) || empty( $services['service_manager'] ) || ! $services['service_manager'] instanceof ServicesManager ) {
			return [];
		}

		/** @var ServicesManager $service_manager Instance of the services manager class. */
		$service_manager = $services['service_manager'];
		$providers       = [];

		foreach ( $service_manager->service_classes as $service ) {
			if ( empty( $service->provider_classes ) ) {
				continue;
			}

			foreach ( $service->provider_classes as $provider_class ) {
				$providers[ $provider_class->get_option_name() ] = $provider_class;
			}
		}

		return $providers;
	}

	/**
	 * Get Default features values.
	 *
	 * @return array The default feature values.
	 */
	public function get_default_features() {
		$features  = $this->get_features();
		$providers = $this->get_providers();
		$defaults  = array();

		foreach ( $features as $service_slug => $service ) {
			foreach ( $service['features'] as $provider_slug => $provider ) {
				$settings = $providers[ $provider_slug ]->get_settings();
				foreach ( $provider as $feature_slug => $feature ) {
					$value = $settings[ $feature_slug ] ?? 'no';
					if ( count( explode( '__', $feature_slug ) ) > 1 ) {
						$keys  = explode( '__', $feature_slug );
						$value = $settings[ $keys[0] ][ $keys[1] ] ?? 'no';
					} elseif ( 'enable_image_captions' === $feature_slug ) {
						$value = 'alt' === $settings['enable_image_captions']['alt'] ? 1 : 'no';
					}
					$defaults[ $provider_slug ][ $feature_slug ] = $value;
				}
			}
		}

		return $defaults;
	}

	/**
	 * Get onboarding options.
	 *
	 * @return array The onboarding options.
	 */
	public static function get_onboarding_options() {
		return get_option( 'classifai_onboarding_options', array() );
	}

	/**
	 * Check if onboarding is completed.
	 *
	 * @return bool True if onboarding is completed, false otherwise.
	 */
	public static function is_onboarding_completed() {
		$options = self::get_onboarding_options();
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

		$onboarding_options = self::get_onboarding_options();
		$onboarding_options = array_merge( $onboarding_options, $options );

		// Update options.
		update_option( 'classifai_onboarding_options', $onboarding_options );
	}

	/**
	 * Handle skip setup step.
	 */
	public function handle_skip_setup_step() {
		if ( ! empty( $_GET['classifai_skip_step_nonce'] ) && wp_verify_nonce( sanitize_text_field( $_GET['classifai_skip_step_nonce'] ), 'classifai_skip_step_action' ) ) {
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
	public function get_enabled_providers() {
		$providers          = $this->get_providers();
		$onboarding_options = self::get_onboarding_options();
		$enabled_features   = $onboarding_options['enabled_features'] ?? array();
		$enabled_providers  = [];

		foreach ( array_keys( $enabled_features ) as $provider ) {
			if ( ! empty( $providers[ $provider ] ) ) {
				$enabled_providers[ $provider ] = $providers[ $provider ]->get_onboarding_options();
			}
		}

		return $enabled_providers;
	}

	/**
	 * Get next provider to setup.
	 *
	 * @param string $current_provider Current provider.
	 * @return string|bool Next provider to setup or false if none.
	 */
	public function get_next_provider( $current_provider ) {
		$enabled_providers = $this->get_enabled_providers();
		$keys              = array_keys( $enabled_providers );
		$index             = array_search( $current_provider, $keys, true );

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
		$step               = absint( wp_unslash( $_GET['step'] ) );
		$onboarding_options = self::get_onboarding_options();
		$step_completed     = isset( $onboarding_options['step_completed'] ) ? absint( $onboarding_options['step_completed'] ) : 0;

		if ( ( $step_completed + 1 ) < $step ) {
			wp_die( esc_html__( 'You are not allowed to access this page.', 'classifai' ) );
		}
	}

	/**
	 * Check if any of the providers are configured.
	 *
	 * @return boolean
	 */
	public function has_configured_providers() {
		$providers = $this->get_providers();

		foreach ( $providers as $provider ) {
			if ( $provider->is_configured() ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get configured features.
	 *
	 * @return array
	 */
	public function get_configured_features() {
		$features            = $this->get_features();
		$configured_features = array();

		foreach ( $features as $feature ) {
			foreach ( $feature['features'] as $provider_key => $provider_features ) {
				foreach ( $provider_features as $feature_key => $feature_options ) {
					if ( $feature_options['enabled'] ) {
						$configured_features[ $provider_key ][] = $feature_key;
					}
				}
			}
		}

		return $configured_features;
	}

}
