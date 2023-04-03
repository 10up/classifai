<?php

namespace Classifai\Admin;

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
		add_action( 'admin_init', [ $this, 'handle_step_one_submission' ] );
		add_action( 'admin_init', [ $this, 'handle_step_two_submission' ] );
		add_action( 'admin_init', [ $this, 'handle_step_three_submission' ] );
		add_action( 'admin_init', [ $this, 'prevent_direct_step_visits' ] );
		add_action( 'admin_post_classifai_skip_step', [ $this, 'handle_skip_setup_step' ] );
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
	 * Handle the submission of the first step of the onboarding wizard.
	 *
	 * @return void
	 */
	public function handle_step_one_submission() {
		if ( ! isset( $_POST['classifai-setup-step-one-nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['classifai-setup-step-one-nonce'] ) ), 'classifai-setup-step-one-action' ) ) {
			return;
		}

		$enabled_features = isset( $_POST['classifai-features'] ) ? $this->classifai_sanitize( $_POST['classifai-features'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		if ( empty( $enabled_features ) ) {
			add_settings_error(
				'classifai-setup',
				'classifai-step-one-error',
				esc_html__( 'Please enable at least one feature to set up ClassifAI.', 'classifai' ),
				'error'
			);
			return;
		}

		$onboarding_options = array(
			'status'           => 'inprogress',
			'step_completed'   => 1,
			'enabled_features' => $enabled_features,
		);

		// Save the options to use it later steps.
		$this->update_onboarding_options( $onboarding_options );

		// Redirect to next setup step.
		wp_safe_redirect( add_query_arg( 'step', 2, $this->setup_url ) );
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

		// Save the ClassifAI settings.
		update_option( 'classifai_settings', $classifai_settings );

		$setting_errors = get_settings_errors( 'registration' );
		if ( ! empty( $setting_errors ) ) {
			// Stay on same setup step and display error.
			return;
		}

		$onboarding_options = array(
			'step_completed' => 2,
		);

		// Save the options to use it later steps.
		$this->update_onboarding_options( $onboarding_options );

		// Redirect to next setup step.
		wp_safe_redirect( add_query_arg( 'step', 3, $this->setup_url ) );
		exit();
	}

	/**
	 * Handle the submission of ClassifAI set up AI services.
	 *
	 * @return void
	 */
	public function handle_step_three_submission() {
		if ( ! isset( $_POST['classifai-setup-step-three-nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['classifai-setup-step-three-nonce'] ) ), 'classifai-setup-step-three-action' ) ) {
			return;
		}

		// Bail if no provider provided.
		if ( empty( $_POST['classifai-setup-provider'] ) ) {
			return;
		}

		$providers       = $this->get_setup_providers();
		$provider_option = sanitize_text_field( wp_unslash( $_POST['classifai-setup-provider'] ) );
		$provider        = $providers[ $provider_option ];
		$option_name     = 'classifai_' . $provider_option;

		if ( empty( $provider ) ) {
			return;
		}

		$form_data = isset( $_POST[ $option_name ] ) ? $this->classifai_sanitize( $_POST[ $option_name ] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		$settings = \Classifai\get_plugin_settings( $provider['service'], $provider['provider'] );
		$options  = self::get_onboarding_options();
		$features = isset( $options['enabled_features'] ) ? $options['enabled_features'] : array();

		// Update settings.
		switch ( $provider_option ) {
			case 'watson_nlu':
				$settings['credentials'] = $form_data['credentials'];
				if ( isset( $features['language']['classify'] ) ) {
					$settings['post_types'] = array();
					foreach ( $features['language']['classify'] as $enabled_type => $value ) {
						$settings['post_types'][ $enabled_type ] = '1';
					}
				}
				break;

			case 'openai_chatgpt':
				if ( isset( $features['language']['excerpt_generation'] ) ) {
					$settings['enable_excerpt'] = '1';
				}
				$settings['api_key'] = $form_data['api_key'];
				break;

			case 'computer_vision':
				$settings['url']     = $form_data['url'];
				$settings['api_key'] = $form_data['api_key'];

				$settings['enable_image_tagging']  = isset( $features['images']['image_tags'] ) ? 1 : 'no';
				$settings['enable_smart_cropping'] = isset( $features['images']['image_crop'] ) ? 1 : 'no';
				$settings['enable_ocr']            = isset( $features['images']['image_ocr'] ) ? 1 : 'no';
				$settings['enable_image_captions'] = array(
					'alt'         => isset( $features['images']['image_captions'] ) ? 'alt' : 0,
					'caption'     => 0,
					'description' => 0,
				);
				break;

			case 'personalizer':
				$settings['url']     = $form_data['url'];
				$settings['api_key'] = $form_data['api_key'];
				break;

			default:
				break;
		}

		// Save the ClassifAI settings.
		update_option( $option_name, $settings );

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
						'step' => 3,
						'tab'  => $next_provider,
					),
					$this->setup_url
				)
			);
			exit();
		}

		$onboarding_options = array(
			'status'         => 'completed',
			'step_completed' => 3,
		);

		// Save the options to use it later steps.
		$this->update_onboarding_options( $onboarding_options );

		// Redirect to next completion step.
		wp_safe_redirect( add_query_arg( 'step', 4, $this->setup_url ) );
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
	 * Get list of providers enabled for setup.
	 *
	 * @return array Array of providers.
	 */
	public static function get_setup_providers() {
		return array(
			'watson_nlu'      => array(
				'title'    => __( 'IBM Watson NLU', 'classifai' ),
				'fields'   => array( 'url', 'username', 'password', 'toggle' ),
				'service'  => 'language_processing',
				'provider' => 'Natural Language Understanding',
			),
			'openai_chatgpt'  => array(
				'title'    => __( 'OpenAI ChatGPT', 'classifai' ),
				'fields'   => array( 'api-key' ),
				'service'  => 'language_processing',
				'provider' => 'ChatGPT',
			),
			'computer_vision' => array(
				'title'    => __( 'Microsoft Azure Computer Vision', 'classifai' ),
				'fields'   => array( 'url', 'api-key' ),
				'service'  => 'image_processing',
				'provider' => 'Computer Vision',
			),
			'personalizer'    => array(
				'title'    => __( 'Microsoft Azure Personalizer', 'classifai' ),
				'fields'   => array( 'url', 'api-key' ),
				'service'  => 'personalizer',
				'provider' => 'Personalizer',
			),
		);
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
	 * This is a subset of the providers returned by get_setup_providers().
	 *
	 * @return array Array of enabled providers.
	 */
	public static function get_enabled_providers() {
		$providers          = self::get_setup_providers();
		$enabled_providers  = array();
		$onboarding_options = self::get_onboarding_options();
		$enabled_features   = $onboarding_options['enabled_features'] ?? array();

		foreach ( $enabled_features as $feature => $value ) {
			if ( 'language' === $feature ) {
				if ( ! empty( $value['classify'] ) ) {
					$enabled_providers['watson_nlu'] = $providers['watson_nlu'];
				}
				if ( ! empty( $value['excerpt_generation'] ) ) {
					$enabled_providers['openai_chatgpt'] = $providers['openai_chatgpt'];
				}
			} elseif ( 'images' === $feature ) {
				$enabled_providers['computer_vision'] = $providers['computer_vision'];
			} elseif ( 'recommended_content' === $feature ) {
				$enabled_providers['personalizer'] = $providers['personalizer'];
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
	public static function get_next_provider( $current_provider ) {
		$enabled_providers = self::get_enabled_providers();
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
}
