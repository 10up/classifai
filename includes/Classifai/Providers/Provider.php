<?php
/**
 *  Abtract class that defines the providers for a service.
 */

namespace Classifai\Providers;

abstract class Provider {

	/**
	 * @var string The display name for the provider. ie. Azure
	 */
	public $provider_name;


	/**
	 * @var string $provider_service_name The formal name of the service being provided. i.e Computer Vision, NLU, Rekongnition.
	 */
	public $provider_service_name;

	/**
	 * @var string $option_name Name of the option where the provider settings are stored.
	 */
	protected $option_name;


	/**
	 * @var string $service The name of the service this provider belongs to.
	 */
	protected $service;

	/**
	 * @var array $onboarding The onboarding options for this provider.
	 */
	public $onboarding_options;

	/**
	 * This variable holds an array that maps feature to their corresponding prefixes for saving role/user based access settings in feature options.
	 *
	 * @var array $access_prefix An associative array where the keys are feature and the values are their corresponding prefixes.
	 */
	protected $access_prefix = array(
		'classify_content'    => '',
		'titles'              => 'title_',
		'excerpt'             => '',
		'resize_content'      => 'resize_content_',
		'classification'      => '',
		'transcripts'         => '',
		'image_captions'      => 'image_caption_',
		'image_tagging'       => 'image_tagging_',
		'smart_cropping'      => 'smart_cropping_',
		'ocr'                 => 'ocr_',
		'read_pdf'            => 'read_pdf_',
		'image_gen'           => '',
		'recommended_content' => '',
	);

	/**
	 * Provider constructor.
	 *
	 * @param string $provider_name         The name of the Provider that will appear in the admin tab
	 * @param string $provider_service_name The name of the Service.
	 * @param string $option_name           Name of the option where the provider settings are stored.
	 * @param string $service               What service does this provider belong to.
	 */
	public function __construct( $provider_name, $provider_service_name, $option_name, $service ) {
		$this->provider_name         = $provider_name;
		$this->provider_service_name = $provider_service_name;
		$this->option_name           = $option_name;
		$this->service               = $service;
		$this->onboarding_options    = array();
	}

	/**
	 * Provides the provider name.
	 *
	 * @return string
	 */
	public function get_provider_name() {
		return $this->provider_name;
	}

	/** Returns the name of the settings section for this provider
	 *
	 * @return string
	 */
	public function get_settings_section() {
		return $this->option_name;
	}

	/**
	 * Get the option name.
	 *
	 * @return string
	 */
	public function get_option_name() {
		return 'classifai_' . $this->option_name;
	}

	/**
	 * Get the onboarding options.
	 *
	 * @return array
	 */
	public function get_onboarding_options() {
		if ( empty( $this->onboarding_options ) || ! isset( $this->onboarding_options['features'] ) ) {
			return array();
		}

		$settings      = $this->get_settings();
		$is_configured = $this->is_configured();

		foreach ( $this->onboarding_options['features'] as $key => $title ) {
			$enabled = isset( $settings[ $key ] ) ? 1 === absint( $settings[ $key ] ) : false;
			if ( count( explode( '__', $key ) ) > 1 ) {
				$keys    = explode( '__', $key );
				$enabled = isset( $settings[ $keys[0] ][ $keys[1] ] ) ? 1 === absint( $settings[ $keys[0] ][ $keys[1] ] ) : false;
			}
			// Handle enable_image_captions
			if ( 'enable_image_captions' === $key ) {
				$enabled = isset( $settings['enable_image_captions']['alt'] ) && 'alt' === $settings['enable_image_captions']['alt'];
			}
			$enabled = $enabled && $is_configured;

			$this->onboarding_options['features'][ $key ] = array(
				'title'   => $title,
				'enabled' => $enabled,
			);
		}

		return $this->onboarding_options;
	}

	/**
	 * Can the Provider be initialized?
	 */
	public function can_register() {
		return $this->is_configured();
	}

	/**
	 * Register the functionality for the Provider.
	 */
	abstract public function register();

	/**
	 * Resets the settings for this provider.
	 */
	abstract public function reset_settings();

	/**
	 * Initialization routine
	 */
	public function register_admin() {
		add_action( 'admin_init', [ $this, 'register_settings' ] );
		add_action( 'admin_init', [ $this, 'setup_fields_sections' ] );
	}

	/**
	 * Register the settings and sanitization callback method.
	 *
	 * It's very important that the option group matches the page slug.
	 */
	public function register_settings() {
		register_setting( $this->get_option_name(), $this->get_option_name(), [ $this, 'sanitize_settings' ] );
	}

	/**
	 * Helper to get the settings and allow for settings default values.
	 *
	 * @param string|bool|mixed $index Optional. Name of the settings option index.
	 *
	 * @return string|array|mixed
	 */
	public function get_settings( $index = false ) {
		$defaults = $this->get_default_settings();
		$settings = get_option( $this->get_option_name(), [] );
		$settings = wp_parse_args( $settings, $defaults );

		if ( $index && isset( $settings[ $index ] ) ) {
			return $settings[ $index ];
		}

		return $settings;
	}

	/**
	 * Returns the default settings.
	 *
	 * @return array
	 */
	public function get_default_settings() {
		return [];
	}

	/**
	 * Generic text input field callback
	 *
	 * @param array $args The args passed to add_settings_field.
	 */
	public function render_input( $args ) {
		$option_index  = isset( $args['option_index'] ) ? $args['option_index'] : false;
		$setting_index = $this->get_settings( $option_index );
		$type          = $args['input_type'] ?? 'text';
		$value         = ( isset( $setting_index[ $args['label_for'] ] ) ) ? $setting_index[ $args['label_for'] ] : '';

		// Check for a default value
		$value = ( empty( $value ) && isset( $args['default_value'] ) ) ? $args['default_value'] : $value;
		$attrs = '';
		$class = '';

		switch ( $type ) {
			case 'text':
			case 'password':
				$attrs = ' value="' . esc_attr( $value ) . '"';
				$class = 'regular-text';
				break;
			case 'number':
				$attrs = ' value="' . esc_attr( $value ) . '"';

				if ( isset( $args['max'] ) && is_numeric( $args['max'] ) ) {
					$attrs .= ' max="' . esc_attr( (float) $args['max'] ) . '"';
				}

				if ( isset( $args['min'] ) && is_numeric( $args['min'] ) ) {
					$attrs .= ' min="' . esc_attr( (float) $args['min'] ) . '"';
				}

				if ( isset( $args['step'] ) && is_numeric( $args['step'] ) ) {
					$attrs .= ' step="' . esc_attr( (float) $args['step'] ) . '"';
				}

				$class = 'small-text';
				break;
			case 'checkbox':
				$attrs = ' value="1"' . checked( '1', $value, false );
				break;
		}
		?>
		<input
			type="<?php echo esc_attr( $type ); ?>"
			id="<?php echo esc_attr( $args['label_for'] ); ?>"
			class="<?php echo esc_attr( $class ); ?>"
			name="classifai_<?php echo esc_attr( $this->option_name ); ?><?php echo $option_index ? '[' . esc_attr( $option_index ) . ']' : ''; ?>[<?php echo esc_attr( $args['label_for'] ); ?>]"
			<?php echo $attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> />
		<?php
		if ( ! empty( $args['description'] ) ) {
			echo '<span class="description classifai-input-description">' . wp_kses_post( $args['description'] ) . '</span>';
		}
	}

	/**
	 * Generic textarea field callback
	 *
	 * @param array $args The args passed to add_settings_field.
	 */
	public function render_textarea( $args ) {
		$option_index  = isset( $args['option_index'] ) ? $args['option_index'] : false;
		$setting_index = $this->get_settings( $option_index );
		$value         = ( isset( $setting_index[ $args['label_for'] ] ) ) ? $setting_index[ $args['label_for'] ] : '';
		$class         = isset( $args['class'] ) ? $args['class'] : 'large-text';
		$placeholder   = isset( $args['placeholder'] ) ? $args['placeholder'] : '';

		// Check for a default value
		$value = ( empty( $value ) && isset( $args['default_value'] ) ) ? $args['default_value'] : $value;
		?>
		<textarea
			id="<?php echo esc_attr( $args['label_for'] ); ?>"
			class="<?php echo esc_attr( $class ); ?>"
			rows="4"
			name="classifai_<?php echo esc_attr( $this->option_name ); ?><?php echo $option_index ? '[' . esc_attr( $option_index ) . ']' : ''; ?>[<?php echo esc_attr( $args['label_for'] ); ?>]"
			placeholder="<?php echo esc_attr( $placeholder ); ?>"
		><?php echo esc_textarea( $value ); ?></textarea>
		<?php
		if ( ! empty( $args['description'] ) ) {
			echo '<br /><span class="description classifai-input-description">' . wp_kses_post( $args['description'] ) . '</span>';
		}
	}

	/**
	 * Renders a select menu
	 *
	 * @param array $args The args passed to add_settings_field.
	 */
	public function render_select( $args ) {
		$setting_index = $this->get_settings();
		$saved         = ( isset( $setting_index[ $args['label_for'] ] ) ) ? $setting_index[ $args['label_for'] ] : '';

		// Check for a default value
		$saved   = ( empty( $saved ) && isset( $args['default_value'] ) ) ? $args['default_value'] : $saved;
		$options = isset( $args['options'] ) ? $args['options'] : [];
		?>

		<select
			id="<?php echo esc_attr( $args['label_for'] ); ?>"
			name="classifai_<?php echo esc_attr( $this->option_name ); ?>[<?php echo esc_attr( $args['label_for'] ); ?>]"
			>
			<?php foreach ( $options as $value => $name ) : ?>
				<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $saved, $value ); ?>>
					<?php echo esc_attr( $name ); ?>
				</option>
			<?php endforeach; ?>
		</select>

		<?php
		if ( ! empty( $args['description'] ) ) {
			echo '<br /><span class="description">' . wp_kses_post( $args['description'] ) . '</span>';
		}
	}

	/**
	 * Render a group of checkboxes.
	 *
	 * @param array $args The args passed to add_settings_field
	 */
	public function render_checkbox_group( array $args = array() ) {
		$setting_index = $this->get_settings();

		// Iterate through all of our options.
		foreach ( $args['options'] as $option_value => $option_label ) {
			$value       = '';
			$default_key = array_search( $option_value, $args['default_values'], true );

			// Get saved value, if any.
			if ( isset( $setting_index[ $args['label_for'] ] ) ) {
				$value = $setting_index[ $args['label_for'] ][ $option_value ] ?? '';
			}

			// If no saved value, check if we have a default value.
			if ( empty( $value ) && '0' !== $value && isset( $args['default_values'][ $default_key ] ) ) {
				$value = $args['default_values'][ $default_key ];
			}

			// Render checkbox.
			printf(
				'<p>
					<label for="%1$s_%2$s_%3$s">
						<input type="hidden" name="classifai_%1$s[%2$s][%3$s]" value="0" />
						<input type="checkbox" id="%1$s_%2$s_%3$s" name="classifai_%1$s[%2$s][%3$s]" value="%3$s" %4$s />
						%5$s
					</label>
				</p>',
				esc_attr( $this->option_name ),
				esc_attr( $args['label_for'] ),
				esc_attr( $option_value ),
				checked( $value, $option_value, false ),
				esc_html( $option_label )
			);
		}

		// Render description, if any.
		if ( ! empty( $args['description'] ) ) {
			printf(
				'<span class="description classifai-input-description">%s</span>',
				esc_html( $args['description'] )
			);
		}
	}

	/**
	 * Renders the checkbox group for 'Generate descriptive text' setting.
	 *
	 * @param array $args The args passed to add_settings_field.
	 */
	public function render_auto_caption_fields( $args ) {
		$setting_index = $this->get_settings();

		$default_value = '';

		if ( isset( $setting_index['enable_image_captions'] ) ) {
			if ( ! is_array( $setting_index['enable_image_captions'] ) ) {
				if ( '1' === $setting_index['enable_image_captions'] ) {
					$default_value = 'alt';
				} elseif ( 'no' === $setting_index['enable_image_captions'] ) {
					$default_value = '';
				}
			}
		}

		$checkbox_options = array(
			'alt'         => esc_html__( 'Alt text', 'classifai' ),
			'caption'     => esc_html__( 'Image caption', 'classifai' ),
			'description' => esc_html__( 'Image description', 'classifai' ),
		);

		foreach ( $checkbox_options as $option_value => $option_label ) {
			if ( isset( $setting_index['enable_image_captions'] ) ) {
				if ( ! is_array( $setting_index['enable_image_captions'] ) ) {
					$default_value = '1' === $setting_index['enable_image_captions'] ? 'alt' : '';
				} else {
					$default_value = $setting_index['enable_image_captions'][ $option_value ];
				}
			}

			printf(
				'<p>
					<label for="%1$s_%2$s_%3$s">
						<input type="hidden" name="classifai_%1$s[%2$s][%3$s]" value="0" />
						<input type="checkbox" id="%1$s_%2$s_%3$s" name="classifai_%1$s[%2$s][%3$s]" value="%3$s" %4$s />
						%5$s
					</label>
				</p>',
				esc_attr( $this->option_name ),
				esc_attr( $args['label_for'] ),
				esc_attr( $option_value ),
				checked( $default_value, $option_value, false ),
				esc_html( $option_label )
			);
		}

		// Render description, if any.
		if ( ! empty( $args['description'] ) ) {
			printf(
				'<span class="description classifai-input-description">%s</span>',
				esc_html( $args['description'] )
			);
		}
	}

	/**
	 * Set up the fields for each section.
	 */
	abstract public function setup_fields_sections();

	/**
	 * Sanitization
	 *
	 * @param array $settings The settings being saved.
	 */
	abstract public function sanitize_settings( $settings );

	/**
	 * Provides debug information related to the provider.
	 *
	 * @return string|array Debug info to display on the Site Health screen. Accepts a string or key-value pairs.
	 * @since 1.4.0
	 */
	abstract public function get_provider_debug_information();

	/**
	 * Common entry point for all REST endpoints for this provider.
	 * This is called by the Service.
	 *
	 * @param int    $post_id       The Post Id we're processing.
	 * @param string $route_to_call The name of the route we're going to be processing.
	 * @param array  $args          Optional arguments to pass to the route.
	 *
	 * @return mixed
	 */
	public function rest_endpoint_callback( $post_id, $route_to_call, $args = [] ) {
		return null;
	}

	/**
	 * Format the result of most recent request.
	 *
	 * @param array|WP_Error $data Response data to format.
	 *
	 * @return string
	 */
	protected function get_formatted_latest_response( $data ) {
		if ( ! $data ) {
			return __( 'N/A', 'classifai' );
		}

		if ( is_wp_error( $data ) ) {
			return $data->get_error_message();
		}

		return preg_replace( '/,"/', ', "', wp_json_encode( $data ) );
	}

	/**
	 * Returns whether the provider is configured or not.
	 *
	 * @return bool
	 */
	public function is_configured() {
		$settings = $this->get_settings();

		$is_configured = false;
		if ( ! empty( $settings ) && ! empty( $settings['authenticated'] ) ) {
			$is_configured = true;
		}

		return $is_configured;
	}

	/**
	 * Add settings fields for Role/User based access.
	 *
	 * @param string $feature Feature.
	 * @param string $section Settings section.
	 * @return void
	 */
	public function add_access_settings( $feature = '', $section = '' ) {
		$feature_name     = $this->get_provider_name();
		$editable_roles   = get_editable_roles() ?? [];
		$default_settings = $this->get_default_settings();
		$settings         = $this->get_settings();

		$prefix = '';
		if ( ! empty( $feature ) && isset( $this->access_prefix[ $feature ] ) ) {
			$prefix = $this->access_prefix[ $feature ];
		}

		if ( empty( $section ) ) {
			$prefix = $this->get_option_name();
		}

		$role_based_access_key = $prefix . 'role_based_access';
		$roles_key             = $prefix . 'roles';

		$default_settings = array_merge(
			$default_settings,
			array(
				$role_based_access_key => '1',
				$roles_key             => array_keys( $editable_roles ),
			)
		);

		$roles = array_combine( array_keys( $editable_roles ), array_column( $editable_roles, 'name' ) );

		/**
		 * Filter the allowed WordPress roles for ClassifAI
		 *
		 * @since 2.5.0
		 * @hook classifai_chatgpt_allowed_roles
		 *
		 * @param {array}  $roles            Array of arrays containing role information.
		 * @param {string} $feature_name     Feature name.
		 * @param {string} $option_name      Option name.
		 * @param {array}  $default_settings Default setting values.
		 *
		 * @return {array} Roles array.
		 */
		$roles = apply_filters( 'classifai_allowed_roles', $roles, $feature, $this->get_option_name(), $default_settings );

		add_settings_field(
			$role_based_access_key,
			esc_html__( 'Enable role-based access', 'classifai' ),
			[ $this, 'render_input' ],
			$this->get_option_name(),
			$section,
			[
				'label_for'     => $role_based_access_key,
				'input_type'    => 'checkbox',
				'default_value' => $default_settings[ $role_based_access_key ],
				/* translators: %s - Feature name */
				'description'   => sprintf( __( 'Enable ability to select which role can access %s', 'classifai' ), $feature_name ),
				'class'         => 'classifai-role-based-access',
			]
		);

		// Add hidden class if role-based access is disabled.
		$class = 'allowed_roles_row';
		if ( ! isset( $settings[ $role_based_access_key ] ) || '1' !== $settings[ $role_based_access_key ] ) {
			$class .= ' hidden';
		}

		add_settings_field(
			$roles_key,
			esc_html__( 'Allowed roles', 'classifai' ),
			[ $this, 'render_checkbox_group' ],
			$this->get_option_name(),
			$section,
			[
				'label_for'      => $roles_key,
				'options'        => $roles,
				'default_values' => $default_settings[ $roles_key ],
				/* translators: %s - Feature name */
				'description'    => sprintf( __( 'Choose which roles are allowed to %s', 'classifai' ), $feature_name ),
				'class'          => $class,
			]
		);
	}

	/**
	 * Sanitization for the roles/users access options being saved.
	 *
	 * @param array  $settings Array of settings about to be saved.
	 * @param string $feature  Feature key.
	 *
	 * @return array The sanitized settings to be saved.
	 */
	public function sanitize_access_settings( $settings, $feature = '' ) {
		$prefix = '';
		if ( ! empty( $feature ) && isset( $this->access_prefix[ $feature ] ) ) {
			$prefix = $this->access_prefix[ $feature ];
		}

		$role_based_access_key = $prefix . 'role_based_access';
		$roles_key             = $prefix . 'roles';

		$new_settings = [];

		if ( empty( $settings[ $role_based_access_key ] ) || 1 !== (int) $settings[ $role_based_access_key ] ) {
			$new_settings[ $role_based_access_key ] = 'no';
		} else {
			$new_settings[ $role_based_access_key ] = '1';
		}

		// Allowed roles.
		if ( isset( $settings[ $roles_key ] ) && is_array( $settings[ $roles_key ] ) ) {
			$new_settings[ $roles_key ] = array_map( 'sanitize_text_field', $settings[ $roles_key ] );
		} else {
			$new_settings[ $roles_key ] = array_keys( get_editable_roles() ?? [] );
		}
		return $new_settings;
	}

	/**
	 * Determine if the current user has access of the feature
	 *
	 * @param string $feature Feature to check.
	 * @return bool
	 */
	protected function has_access( string $feature = '' ) {
		$access     = false;
		$settings   = $this->get_settings();
		$user_roles = wp_get_current_user()->roles ?? [];
		$prefix     = '';
		if ( ! empty( $feature ) && isset( $this->access_prefix[ $feature ] ) ) {
			$prefix = $this->access_prefix[ $feature ];
		}

		$role_based_access_key = $prefix . 'role_based_access';
		$roles_key             = $prefix . 'roles';
		$feature_roles         = $settings[ $roles_key ] ?? [];

		/*
		 * Checks:
		 * - User is logged in.
		 * - Role-based access is enabled and user role has access to the feature.
		 */
		if (
			is_user_logged_in() &&
			( 1 !== (int) $settings[ $role_based_access_key ] || ( ! empty( $feature_roles ) && ! empty( array_intersect( $user_roles, $feature_roles ) ) ) )
		) {
			$access = true;
		}

		/**
		 * Filter to override user access to a ClassifAI feature.
		 *
		 * @since 2.5.0
		 * @hook classifai_has_access_{$this->option_name}_{$feature}
		 *
		 * @param {bool}  $access Current access value.
		 * @param {array} $settings Current feature settings.
		 *
		 * @return {bool} Should the user have access?
		 */
		return apply_filters( "classifai_has_access_{$this->option_name}_{$feature}", $access, $settings );
	}
}
