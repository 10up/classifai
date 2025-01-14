<?php

namespace Classifai\Features;

use WP_REST_Request;
use WP_Error;
use function Classifai\find_provider_class;
use function Classifai\should_use_legacy_settings_panel;
use function Classifai\get_asset_info;

abstract class Feature {
	/**
	 * ID of the current feature.
	 *
	 * To be set in the subclass.
	 *
	 * @var string
	 */
	const ID = '';

	/**
	 * Plugin area script handle.
	 *
	 * Every feature that injects content into the plugin area
	 * should add this script as a dependency.
	 *
	 * @var string
	 */
	const PLUGIN_AREA_SCRIPT = 'classifai-plugin-fill-js';

	/**
	 * Feature label.
	 *
	 * @var string
	 */
	public $label = '';

	/**
	 * User role array.
	 *
	 * @var array
	 */
	public $roles = [];

	/**
	 * Array of provider classes.
	 *
	 * This contains all the providers that are registered to the service.
	 *
	 * @var \Classifai\Providers\Provider[]
	 */
	public $provider_instances = [];

	/**
	 * Array of providers supported by the feature.
	 *
	 * @var \Classifai\Providers\Provider[]
	 */
	public $supported_providers = [];

	/**
	 * Set up necessary hooks.
	 */
	public function setup() {
		add_action( 'admin_init', [ $this, 'setup_roles' ] );
		add_action( 'rest_api_init', [ $this, 'setup_roles' ] );
		if ( should_use_legacy_settings_panel() ) {
			add_action( 'admin_init', [ $this, 'register_setting' ] );
			add_action( 'admin_init', [ $this, 'setup_fields_sections' ] );
		}

		add_action( 'admin_enqueue_scripts', [ $this, 'register_plugin_area_script' ] );

		if ( $this->is_feature_enabled() ) {
			$this->feature_setup();
		}
	}

	/**
	 * Setup any hooks the feature needs.
	 *
	 * Only fires if the feature is enabled.
	 */
	public function feature_setup() {
	}

	/**
	 * Assigns user roles to the $roles array.
	 */
	public function setup_roles() {
		if ( ! function_exists( 'get_editable_roles' ) ) {
			require_once ABSPATH . 'wp-admin/includes/user.php';
		}

		$default_settings = $this->get_default_settings();
		$this->roles      = get_editable_roles() ?? [];
		$this->roles      = array_combine( array_keys( $this->roles ), array_column( $this->roles, 'name' ) );

		// Remove subscriber from the list of roles.
		unset( $this->roles['subscriber'] );

		/**
		 * Filter the allowed WordPress roles for a feature.
		 *
		 * @since 3.0.0
		 * @hook classifai_{feature}_roles
		 *
		 * @param {array} $roles            Array of arrays containing role information.
		 * @param {array} $default_settings Default setting values.
		 *
		 * @return {array} Roles array.
		 */
		$this->roles = apply_filters( 'classifai_' . static::ID . '_roles', $this->roles, $default_settings );
	}

	/**
	 * Returns the roles for the feature.
	 *
	 * @return array Array of roles.
	 */
	public function get_roles(): array {
		return $this->roles;
	}

	/**
	 * Returns the label of the feature.
	 *
	 * @return string
	 */
	public function get_label(): string {
		/**
		 * Filter the feature label.
		 *
		 * @since 3.0.0
		 * @hook classifai_{feature}_label
		 *
		 * @param {string} $label Feature label.
		 *
		 * @return {string} Filtered label.
		 */
		return apply_filters(
			'classifai_' . static::ID . '_label',
			$this->label
		);
	}

	/**
	 * Registers the plugin area script.
	 */
	public function register_plugin_area_script() {
		wp_register_script(
			self::PLUGIN_AREA_SCRIPT,
			CLASSIFAI_PLUGIN_URL . 'dist/classifai-plugin-fill.js',
			get_asset_info( 'classifai-plugin-fill', 'dependencies' ),
			get_asset_info( 'classifai-plugin-fill', 'version' ),
			true
		);
	}

	/**
	 * Set up the fields for each section.
	 *
	 * @internal
	 */
	public function setup_fields_sections() {
		$settings = $this->get_settings();

		add_settings_section(
			$this->get_option_name() . '_section',
			esc_html__( 'Feature settings', 'classifai' ),
			'__return_empty_string',
			$this->get_option_name()
		);

		// Add the enable field.
		add_settings_field(
			'status',
			esc_html__( 'Enable feature', 'classifai' ),
			[ $this, 'render_input' ],
			$this->get_option_name(),
			$this->get_option_name() . '_section',
			[
				'label_for'     => 'status',
				'input_type'    => 'checkbox',
				'default_value' => $settings['status'],
				'description'   => $this->get_enable_description(),
			]
		);

		// Add all the needed provider fields.
		$this->add_provider_fields();

		// Add any needed custom fields.
		$this->add_custom_settings_fields();

		// Add user/role-based access fields.
		$this->add_access_control_fields();
	}

	/**
	 * Get the description for the enable field.
	 *
	 * @return string
	 */
	public function get_enable_description(): string {
		return '';
	}

	/**
	 * Add any needed custom fields.
	 */
	public function add_custom_settings_fields() {
	}

	/**
	 * Returns the default settings for the feature.
	 *
	 * The root-level keys are the setting keys that are independent of the provider.
	 * Provider specific settings should be nested under the provider key.
	 *
	 * @internal
	 * @return array
	 */
	protected function get_default_settings(): array {
		$shared_defaults   = [
			'status'             => '0',
			'roles'              => array_combine( array_keys( $this->roles ), array_keys( $this->roles ) ),
			'users'              => [],
			'user_based_opt_out' => 'no',
		];
		$provider_settings = $this->get_provider_default_settings();
		$feature_settings  = $this->get_feature_default_settings();

		/**
		 * Filter the default settings for a feature.
		 *
		 * @since 3.0.0
		 * @hook classifai_{feature}_get_default_settings
		 *
		 * @param {array} $defaults Default feature settings.
		 * @param {object} $this Feature instance.
		 *
		 * @return {array} Filtered default feature settings.
		 */
		return apply_filters(
			'classifai_' . static::ID . '_get_default_settings',
			array_merge(
				$shared_defaults,
				$feature_settings,
				$provider_settings
			),
			$this
		);
	}

	/**
	 * Sanitizes the settings before saving.
	 *
	 * @internal
	 * @param array $settings The settings to be sanitized on save.
	 * @return array
	 */
	public function sanitize_settings( array $settings ): array {
		$new_settings     = $settings;
		$current_settings = $this->get_settings();

		// Sanitize the shared settings.
		$new_settings['status']   = $settings['status'] ?? $current_settings['status'];
		$new_settings['provider'] = isset( $settings['provider'] ) ? sanitize_text_field( $settings['provider'] ) : $current_settings['provider'];

		// Allowed roles.
		if ( isset( $settings['roles'] ) && is_array( $settings['roles'] ) ) {
			$new_settings['roles'] = array_map( 'sanitize_text_field', $settings['roles'] );
		} else {
			$new_settings['roles'] = $current_settings['roles'];
		}

		// Allowed users.
		if ( isset( $settings['users'] ) && ! empty( $settings['users'] ) ) {
			if ( is_array( $settings['users'] ) ) {
				$new_settings['users'] = array_map( 'absint', $settings['users'] );
			} else {
				$new_settings['users'] = array_map( 'absint', explode( ',', $settings['users'] ) );
			}
		} else {
			$new_settings['users'] = array();
		}

		// User-based opt-out.
		if ( empty( $settings['user_based_opt_out'] ) || 1 !== (int) $settings['user_based_opt_out'] ) {
			$new_settings['user_based_opt_out'] = 'no';
		} else {
			$new_settings['user_based_opt_out'] = '1';
		}

		// Sanitize the feature specific settings.
		$new_settings = $this->sanitize_default_feature_settings( $new_settings );

		// Sanitize the provider specific settings.
		$provider_instance = $this->get_feature_provider_instance( $new_settings['provider'] );
		$new_settings      = $provider_instance->sanitize_settings( $new_settings );

		/**
		 * Filter to change settings before they're saved.
		 *
		 * @since 3.0.0
		 * @hook classifai_{$feature}_sanitize_settings
		 *
		 * @param {array} $new_settings     Settings being saved.
		 * @param {array} $current_settings Existing settings.
		 *
		 * @return {array} Filtered settings.
		 */
		return apply_filters(
			'classifai_' . static::ID . '_sanitize_settings',
			$new_settings,
			$current_settings
		);
	}

	/**
	 * Sanitize the default feature settings.
	 *
	 * @param array $settings Settings to sanitize.
	 * @return array
	 */
	public function sanitize_default_feature_settings( array $settings ): array {
		return $settings;
	}

	/**
	 * Registers the settings for the feature.
	 */
	public function register_setting() {
		register_setting(
			$this->get_option_name(),
			$this->get_option_name(),
			[
				'sanitize_callback' => [ $this, 'sanitize_settings' ],
			]
		);
	}

	/**
	 * Returns the option name for the feature.
	 *
	 * @return string
	 */
	public function get_option_name(): string {
		return 'classifai_' . static::ID;
	}

	/**
	 * Returns the settings for the feature.
	 *
	 * @param string $index The index of the setting to return.
	 * @return array|mixed
	 */
	public function get_settings( $index = false ) {
		$defaults = $this->get_default_settings();
		$settings = get_option( $this->get_option_name(), [] );
		$settings = $this->merge_settings( (array) $settings, (array) $defaults );

		// If saved provider is not supported anymore, reset it.
		if ( ! in_array( $settings['provider'], array_keys( $this->get_providers() ), true ) ) {
			$settings['provider'] = '';
		}

		if ( $index && isset( $settings[ $index ] ) ) {
			return $settings[ $index ];
		}

		return $settings;
	}

	/**
	 * Returns the default settings for the provider selected for the feature.
	 *
	 * @return array
	 */
	public function get_provider_default_settings(): array {
		$provider_settings = [];

		foreach ( array_keys( $this->get_providers() ) as $provider_id ) {
			$provider = $this->get_feature_provider_instance( $provider_id );

			if ( $provider && method_exists( $provider, 'get_default_provider_settings' ) ) {
				$provider_settings[ $provider_id ] = $provider->get_default_provider_settings();
			}
		}

		return $provider_settings;
	}

	/**
	 * Returns the default settings for the feature.
	 *
	 * @return array
	 */
	abstract public function get_feature_default_settings(): array;

	/**
	 * Add the provider fields.
	 *
	 * Will add a field to choose the provider and any
	 * fields the selected provider has registered.
	 */
	public function add_provider_fields() {
		$settings = $this->get_settings();

		add_settings_field(
			'provider',
			esc_html__( 'Select a provider', 'classifai' ),
			[ $this, 'render_select' ],
			$this->get_option_name(),
			$this->get_option_name() . '_section',
			[
				'label_for'     => 'provider',
				'options'       => $this->get_providers(),
				'default_value' => $settings['provider'],
			]
		);

		foreach ( array_keys( $this->get_providers() ) as $provider_id ) {
			$provider = $this->get_feature_provider_instance( $provider_id );

			if ( $provider && method_exists( $provider, 'render_provider_fields' ) ) {
				$provider->render_provider_fields();
			}
		}
	}

	/**
	 * Merges the data settings with the default settings recursively.
	 *
	 * @internal
	 *
	 * @param array $settings  Settings data from the database.
	 * @param array $defaults  Default feature and providers settings data.
	 * @return array
	 */
	protected function merge_settings( array $settings = [], array $defaults = [] ): array {
		foreach ( $defaults as $key => $value ) {
			if ( ! array_key_exists( $key, $settings ) ) {
				$settings[ $key ] = $defaults[ $key ];
			} elseif ( is_array( $value ) ) {
				if ( is_array( $settings[ $key ] ) ) {
					$settings[ $key ] = $this->merge_settings( $settings[ $key ], $defaults[ $key ] );
				} else {
					$settings[ $key ] = $defaults[ $key ];
				}
			}
		}

		return $settings;
	}

	/**
	 * Returns the providers supported by the feature.
	 *
	 * @return array
	 */
	public function get_providers(): array {
		/**
		 * Filter the feature providers.
		 *
		 * @since 3.0.0
		 * @hook classifai_{feature}_providers
		 *
		 * @param {array} $providers Feature providers.
		 *
		 * @return {array} Filtered providers.
		 */
		return apply_filters(
			'classifai_' . static::ID . '_providers',
			$this->supported_providers
		);
	}

	/**
	 * Resets settings for the provider.
	 */
	public function reset_settings() {
		update_option( $this->get_option_name(), $this->get_default_settings() );
	}

	/**
	 * Updates the settings for the feature.
	 *
	 * @param array $new_settings New settings to update.
	 */
	public function update_settings( array $new_settings ) {
		$settings = $this->get_settings();
		if ( empty( $new_settings ) ) {
			return;
		}

		// Update the settings with the new values.
		$new_settings = array_merge( $settings, $new_settings );
		update_option( $this->get_option_name(), $new_settings );
	}

	/**
	 * Add settings fields for Role/User based access.
	 */
	protected function add_access_control_fields() {
		$settings = $this->get_settings();

		add_settings_field(
			'roles',
			esc_html__( 'Allowed roles', 'classifai' ),
			[ $this, 'render_checkbox_group' ],
			$this->get_option_name(),
			$this->get_option_name() . '_section',
			[
				'label_for'      => 'roles',
				'options'        => $this->roles,
				'default_values' => $settings['roles'],
				'description'    => __( 'Choose which roles are allowed to access this feature.', 'classifai' ),
				'class'          => 'allowed_roles_row',
			]
		);

		add_settings_field(
			'users',
			esc_html__( 'Allowed users', 'classifai' ),
			[ $this, 'render_allowed_users' ],
			$this->get_option_name(),
			$this->get_option_name() . '_section',
			[
				'label_for'     => 'users',
				'default_value' => $settings['users'],
				'description'   => __( 'Users who have access to this feature.', 'classifai' ),
				'class'         => 'allowed_users_row',
			]
		);

		add_settings_field(
			'user_based_opt_out',
			esc_html__( 'Enable user-based opt-out', 'classifai' ),
			[ $this, 'render_input' ],
			$this->get_option_name(),
			$this->get_option_name() . '_section',
			[
				'label_for'     => 'user_based_opt_out',
				'input_type'    => 'checkbox',
				'default_value' => $settings['user_based_opt_out'],
				'description'   => __( 'Enables ability for users to opt-out from their user profile page.', 'classifai' ),
				'class'         => 'classifai-user-based-opt-out',
			]
		);
	}

	/**
	 * Generic text input field callback
	 *
	 * @param array $args The args passed to add_settings_field.
	 */
	public function render_input( array $args ) {
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
				?>
				<input
					type="hidden"
					name="<?php echo esc_attr( $this->get_option_name() ); ?><?php echo $option_index ? '[' . esc_attr( $option_index ) . ']' : ''; ?>[<?php echo esc_attr( $args['label_for'] ); ?>]"
					value="0"
				/>
				<?php
				break;
		}
		?>

		<input
			type="<?php echo esc_attr( $type ); ?>"
			id="<?php echo esc_attr( $args['label_for'] ); ?>"
			class="<?php echo esc_attr( $class ); ?>"
			name="<?php echo esc_attr( $this->get_option_name() ); ?><?php echo $option_index ? '[' . esc_attr( $option_index ) . ']' : ''; ?>[<?php echo esc_attr( $args['label_for'] ); ?>]"
			<?php echo $this->get_data_attribute( $args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php echo $attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> />

		<?php
		if ( ! empty( $args['description'] ) ) {
			echo '<span class="description classifai-input-description">' . wp_kses_post( $args['description'] ) . '</span>';
		}
	}

	/**
	 * Generic prompt repeater field callback
	 *
	 * @since 2.4.0
	 *
	 * @param array $args The args passed to add_settings_field.
	 */
	public function render_prompt_repeater_field( array $args ) {
		$option_index      = $args['option_index'] ?? false;
		$setting_index     = $this->get_settings( $option_index );
		$prompts           = $setting_index[ $args['label_for'] ] ?? [];
		$class             = $args['class'] ?? 'large-text';
		$placeholder       = $args['placeholder'] ?? '';
		$field_name_prefix = sprintf(
			'%1$s%2$s[%3$s]',
			$this->get_option_name(),
			$option_index ? "[$option_index]" : '',
			$args['label_for']
		);

		$prompts = empty( $prompts ) && isset( $args['default_value'] ) ? $args['default_value'] : $prompts;

		$prompt_count = count( $prompts );
		$field_index  = 0;
		?>

		<?php foreach ( $prompts as $prompt ) : ?>
			<?php
			$is_default_prompt  = ( isset( $prompt['default'] ) && 1 === (int) $prompt['default'] ) || 1 === $prompt_count;
			$is_original_prompt = isset( $prompt['original'] ) && 1 === (int) $prompt['original'];
			?>

			<fieldset class="classifai-field-type-prompt-setting">
				<?php if ( $is_original_prompt ) : ?>
					<p class="classifai-original-prompt">
						<?php
						printf(
							/* translators: %1$s is replaced with <strong>; %2$s with </strong>; %3$s with prompt. */
							esc_html__( '%1$sClassifAI default prompt%2$s: %3$s', 'classifai' ),
							'<strong>',
							'</strong>',
							esc_html( $placeholder )
						);
						?>
					</p>
				<?php endif; ?>

				<input type="hidden"
					name="<?php echo esc_attr( $field_name_prefix . "[$field_index][default]" ); ?>"
					value="<?php echo esc_attr( $prompt['default'] ?? '' ); ?>"
					class="js-setting-field__default">
				<input type="hidden"
					name="<?php echo esc_attr( $field_name_prefix . "[$field_index][original]" ); ?>"
					value="<?php echo esc_attr( $prompt['original'] ?? '' ); ?>">
				<label>
					<?php esc_html_e( 'Title', 'classifai' ); ?>&nbsp;*
					<span class="dashicons dashicons-editor-help"
						title="<?php esc_attr_e( 'Short description of prompt to use for identification', 'classifai' ); ?>"></span>
					<input type="text"
						name="<?php echo esc_attr( $field_name_prefix . "[$field_index][title]" ); ?>"
						placeholder="<?php esc_attr_e( 'Prompt title', 'classifai' ); ?>"
						value="<?php echo esc_attr( $prompt['title'] ?? '' ); ?>"
						<?php echo $is_original_prompt ? 'readonly' : ''; ?>
						required>
				</label>

				<label>
					<?php esc_html_e( 'Prompt', 'classifai' ); ?>
					<textarea
						class="<?php echo esc_attr( $class ); ?>"
						rows="4"
						name="<?php echo esc_attr( $field_name_prefix . "[$field_index][prompt]" ); ?>"
						placeholder="<?php echo esc_attr( $placeholder ); ?>"
						<?php echo $is_original_prompt ? 'readonly' : ''; ?>
					><?php echo esc_textarea( $prompt['prompt'] ?? '' ); ?></textarea>
				</label>

				<div class="actions-rows">
					<a href="#" class="action__set_default <?php echo $is_default_prompt ? 'selected' : ''; ?>">
						<?php if ( $is_default_prompt ) : ?>
							<?php esc_html_e( 'Default prompt', 'classifai' ); ?>
						<?php else : ?>
							<?php esc_html_e( 'Set as default prompt', 'classifai' ); ?>
						<?php endif; ?>
					</a>
					<a href="#" class="action__remove_prompt" style="<?php echo 1 === $prompt_count || $is_original_prompt ? 'display:none;' : ''; ?>">
						<?php esc_html_e( 'Trash', 'classifai' ); ?>
					</a>
				</div>
			</fieldset>
			<?php ++$field_index; ?>
		<?php endforeach; ?>

		<button
			class="button-secondary js-classifai-add-prompt-fieldset">
			<?php esc_html_e( 'Add new prompt', 'classifai' ); ?>
		</button>

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
	public function render_select( array $args ) {
		$option_index  = isset( $args['option_index'] ) ? $args['option_index'] : false;
		$setting_index = $this->get_settings( $option_index );
		$saved         = ( isset( $setting_index[ $args['label_for'] ] ) ) ? $setting_index[ $args['label_for'] ] : '';

		// Check for a default value
		$saved   = ( empty( $saved ) && isset( $args['default_value'] ) ) ? $args['default_value'] : $saved;
		$options = isset( $args['options'] ) ? $args['options'] : [];
		?>

		<select
			id="<?php echo esc_attr( $args['label_for'] ); ?>"
			name="<?php echo esc_attr( $this->get_option_name() ); ?><?php echo $option_index ? '[' . esc_attr( $option_index ) . ']' : ''; ?>[<?php echo esc_attr( $args['label_for'] ); ?>]"
			<?php echo $this->get_data_attribute( $args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
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
		$option_index  = isset( $args['option_index'] ) ? $args['option_index'] : false;
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
					<label for="%1$s_%3$s_%4$s">
						<input type="hidden" name="%1$s%2$s[%3$s][%4$s]" value="0" />
						<input type="checkbox" id="%1$s_%3$s_%4$s" name="%1$s%2$s[%3$s][%4$s]" value="%4$s" %5$s />
						%6$s
					</label>
				</p>',
				esc_attr( $this->get_option_name() ),
				$option_index ? '[' . esc_attr( $option_index ) . ']' : '',
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
	public function render_auto_caption_fields( array $args ) {
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
						<input type="hidden" name="%1$s[%2$s][%3$s]" value="0" />
						<input type="checkbox" id="%1$s_%2$s_%3$s" name="%1$s[%2$s][%3$s]" value="%3$s" %4$s />
						%5$s
					</label>
				</p>',
				esc_attr( $this->get_option_name() ),
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
	 * Render a group of radio.
	 *
	 * @param array $args The args passed to add_settings_field
	 */
	public function render_radio_group( array $args = array() ) {
		$option_index  = isset( $args['option_index'] ) ? $args['option_index'] : false;
		$setting_index = $this->get_settings( $option_index );
		$value         = $setting_index[ $args['label_for'] ] ?? '';
		$options       = $args['options'] ?? [];

		if ( ! is_array( $options ) ) {
			return;
		}

		// Iterate through all of our options.
		foreach ( $options as $option_value => $option_label ) {
			// Render radio button.
			printf(
				'<p>
					<label for="%1$s_%3$s_%4$s">
						<input type="radio" id="%1$s_%3$s_%4$s" name="%1$s%2$s[%3$s]" value="%4$s" %5$s />
						%6$s
					</label>
				</p>',
				esc_attr( $this->get_option_name() ),
				$option_index ? '[' . esc_attr( $option_index ) . ']' : '',
				esc_attr( $args['label_for'] ),
				esc_attr( $option_value ),
				checked( $value, $option_value, false ),
				esc_html( $option_label )
			);
		}

		// Render description, if any.
		if ( ! empty( $args['description'] ) ) {
			printf(
				'<span class="description">%s</span>',
				esc_html( $args['description'] )
			);
		}
	}

	/**
	 * Render allowed users input field.
	 *
	 * @param array $args The args passed to add_settings_field
	 */
	public function render_allowed_users( array $args = array() ) {
		$setting_index = $this->get_settings();
		$value         = $setting_index[ $args['label_for'] ] ?? array();
		?>
		<div class="classifai-search-users-container">
			<div class="classifai-user-selector" data-id="<?php echo esc_attr( $args['label_for'] ); ?>" id="<?php echo esc_attr( $args['label_for'] ); ?>-container"></div>
			<input
				id="<?php echo esc_attr( $args['label_for'] ); ?>"
				class="classifai-search-users"
				type="hidden"
				name="<?php echo esc_attr( $this->get_option_name() ); ?>[<?php echo esc_attr( $args['label_for'] ); ?>]"
				value="<?php echo esc_attr( implode( ',', $value ) ); ?>"
			/>
		</div>
		<?php
		if ( ! empty( $args['description'] ) ) {
			echo '<span class="description">' . wp_kses_post( $args['description'] ) . '</span>';
		}
	}

	/**
	 * Determine if the current user has access to the feature
	 *
	 * @return bool
	 */
	public function has_access(): bool {
		$access        = false;
		$user_id       = get_current_user_id();
		$user          = get_user_by( 'id', $user_id );
		$user_roles    = $user->roles ?? [];
		$settings      = $this->get_settings();
		$feature_roles = $settings['roles'] ?? [];
		$feature_users = array_map( 'absint', $settings['users'] ?? [] );

		$user_based_opt_out_enabled = isset( $settings['user_based_opt_out'] ) && 1 === (int) $settings['user_based_opt_out'];

		/*
		 * Checks if the user role has access to the feature.
		 */
		// For super admins that don't have a specific role on a site, treat them as admins.
		if ( is_multisite() && is_super_admin( $user_id ) && empty( $user_roles ) ) {
			$user_roles = [ 'administrator' ];
		}

		$access = ( ! empty( $feature_roles ) && ! empty( array_intersect( $user_roles, $feature_roles ) ) );

		/*
		 * Checks if has access to the feature.
		 */
		if ( ! $access ) {
			$access = ( ! empty( $feature_users ) && ! empty( in_array( $user_id, $feature_users, true ) ) );
		}

		/*
		 * Checks if User-based opt-out is enabled and user has opted out from the feature.
		 */
		if ( $access && $user_based_opt_out_enabled ) {
			$opted_out_features = (array) get_user_meta( $user_id, 'classifai_opted_out_features', true );
			$access             = ( ! in_array( static::ID, $opted_out_features, true ) );
		}

		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			$access = true;
		}

		/**
		 * Filter to override user access to a ClassifAI feature.
		 *
		 * @since 3.0.0
		 * @hook classifai_{$feature}_has_access
		 *
		 * @param {bool}  $access   Current access value.
		 * @param {array} $settings Feature settings.
		 *
		 * @return {bool} Should the user have access?
		 */
		return apply_filters( 'classifai_' . static::ID . '_has_access', $access, $settings );
	}

	/**
	 * Determine if a feature is enabled.
	 *
	 * Returns true if the feature meets all the criteria to
	 * be enabled. False otherwise.
	 *
	 * Criteria:
	 *  - Provider is configured.
	 *  - User has access to the feature.
	 *  - Feature is turned on.
	 *
	 * @return bool
	 */
	public function is_feature_enabled(): bool {
		$is_feature_enabled = false;
		$settings           = $this->get_settings();

		// Check if provider is configured, user has access to the feature and the feature is turned on.
		if (
			$this->is_configured() &&
			$this->has_access() &&
			$this->is_enabled()
		) {
			$is_feature_enabled = true;
		}

		/**
		 * Filter to override permission to a specific classifai feature.
		 *
		 * @since 3.0.0
		 * @hook classifai_{$feature}_is_feature_enabled
		 *
		 * @param {bool}  $is_feature_enabled Is the feature enabled?
		 * @param {array} $settings           Current feature settings.
		 *
		 * @return {bool} Returns true if the user has access and the feature is enabled, false otherwise.
		 */
		return apply_filters( 'classifai_' . static::ID . '_is_feature_enabled', $is_feature_enabled, $settings );
	}

	/**
	 * Determine if the feature is turned on.
	 *
	 * Note: This function does not check if the user has access to the feature.
	 *
	 * - Use `is_feature_enabled()` to check if the user has access to the feature and feature is turned on.
	 * - Use `has_access()` to check if the user has access to the feature.
	 *
	 * @return bool
	 */
	public function is_enabled(): bool {
		$settings = $this->get_settings();

		// Check if feature is turned on.
		$feature_status = ( isset( $settings['status'] ) && 1 === (int) $settings['status'] );
		$is_configured  = $this->is_configured();
		$is_enabled     = $feature_status && $is_configured;

		/**
		 * Filter to override a specific classifai feature enabled.
		 *
		 * @since 3.0.0
		 * @hook classifai_{$feature}_is_enabled
		 *
		 * @param {bool}  $is_enabled Is the feature enabled?
		 * @param {array} $settings   Current feature settings.
		 *
		 * @return {bool} Returns true if the feature is enabled, false otherwise.
		 */
		return apply_filters( 'classifai_' . static::ID . '_is_enabled', $is_enabled, $settings );
	}

	/**
	 * The list of post types that are supported.
	 *
	 * @return array
	 */
	public function get_supported_post_types(): array {
		$settings   = $this->get_settings();
		$post_types = [];

		if ( isset( $settings['post_types'] ) && is_array( $settings['post_types'] ) ) {
			foreach ( $settings['post_types'] as $post_type => $enabled ) {
				if ( ! empty( $enabled ) ) {
					$post_types[] = $post_type;
				}
			}
		}

		/**
		 * Filter post types supported for a feature.
		 *
		 * @since 3.0.0
		 * @hook classifai_{feature}_post_types
		 *
		 * @param {array} $post_types Array of post types to be classified.
		 *
		 * @return {array} Array of post types.
		 */
		$post_types = apply_filters( 'classifai_' . static::ID . '_post_types', $post_types );

		return $post_types;
	}

	/**
	 * The list of post statuses that are supported.
	 *
	 * @return array
	 */
	public function get_supported_post_statuses(): array {
		$settings      = $this->get_settings();
		$post_statuses = [];

		if ( ! empty( $settings ) && isset( $settings['post_statuses'] ) ) {
			foreach ( $settings['post_statuses'] as $post_status => $enabled ) {
				if ( ! empty( $enabled ) ) {
					$post_statuses[] = $post_status;
				}
			}
		}

		/**
		 * Filter post statuses supported for a feature.
		 *
		 * @since 3.0.0
		 * @hook classifai_{feature}_post_statuses
		 *
		 * @param {array} $post_types Array of post statuses to be classified.
		 *
		 * @return {array} Array of post statuses.
		 */
		$post_statuses = apply_filters( 'classifai_' . static::ID . '_post_statuses', $post_statuses );

		return $post_statuses;
	}

	/**
	 * Return the list of taxonomies for the feature settings.
	 *
	 * @param array $post_types Array of post types to filter taxonomies by, leave empty to get all taxonomies.
	 * @return array
	 */
	public function get_taxonomies( array $post_types = [] ): array {
		$taxonomies = get_taxonomies( [], 'objects' );
		$taxonomies = array_filter( $taxonomies, 'is_taxonomy_viewable' );
		$supported  = [];

		foreach ( $taxonomies as $taxonomy ) {
			// Remove this taxonomy if it doesn't support at least one of our post types.
			if (
				(
					! empty( $post_types ) &&
					empty( array_intersect( $post_types, $taxonomy->object_type ) )
				) ||
				'post_format' === $taxonomy->name
			) {
				continue;
			}

			$supported[ $taxonomy->name ] = $taxonomy->labels->singular_name;
		}

		/**
		 * Filter taxonomies shown in settings.
		 *
		 * @since 3.0.0
		 * @hook classifai_{feature}_setting_taxonomies
		 *
		 * @param {array} $supported Array of supported taxonomies.
		 * @param {object} $this Current instance of the class.
		 *
		 * @return {array} Array of taxonomies.
		 */
		return apply_filters( 'classifai_' . static::ID . '_setting_taxonomies', $supported, $this );
	}

	/**
	 * Returns array of instances of provider classes registered for the service.
	 *
	 * @internal
	 *
	 * @param array $services Array of provider classes.
	 * @return array
	 */
	protected function get_provider_instances( array $services ): array {
		$provider_instances = [];

		foreach ( $services as $provider_class ) {
			$provider_instances[] = new $provider_class();
		}

		return $provider_instances;
	}

	/**
	 * Returns the instance of the provider set for the feature.
	 *
	 * @param string $provider_id The ID of the provider.
	 * @return \Classifai\Providers
	 */
	public function get_feature_provider_instance( string $provider_id = '' ) {
		$provider_id       = $provider_id ? $provider_id : $this->get_settings( 'provider' );
		$provider_instance = find_provider_class( $this->provider_instances ?? [], $provider_id );

		if ( is_wp_error( $provider_instance ) ) {
			return null;
		}

		$provider_class    = get_class( $provider_instance );
		$provider_instance = new $provider_class( $this );

		return $provider_instance;
	}

	/**
	 * Returns whether the provider is configured or not.
	 *
	 * @return bool
	 */
	public function is_configured(): bool {
		$settings      = $this->get_settings();
		$provider_id   = $settings['provider'];
		$is_configured = false;

		if ( ! empty( $settings ) && ! empty( $settings[ $provider_id ]['authenticated'] ) ) {
			$is_configured = true;
		}

		return $is_configured;
	}

	/**
	 * Returns whether the feature is configured with the specified provider or not.
	 *
	 * @param string $provider The specified provider.
	 *
	 * @return bool
	 */
	public function is_configured_with_provider( string $provider ): bool {
		$settings      = $this->get_settings();
		$provider_id   = $settings['provider'];
		$is_configured = false;

		if (
			! empty( $settings ) &&
			$provider_id === $provider &&
			! empty( $settings[ $provider_id ]['authenticated'] )
		) {
			$is_configured = true;
		}

		return $is_configured;
	}

	/**
	 * Can the feature be initialized?
	 *
	 * @return bool
	 */
	public function can_register(): bool {
		return $this->is_configured();
	}

	/**
	 * Get the debug value text.
	 *
	 * @param mixed   $setting_value The value of the setting.
	 * @param integer $type The type of debug value to return.
	 * @return string
	 */
	public static function get_debug_value_text( $setting_value, $type = 0 ): string {
		$debug_value = '';

		if ( empty( $setting_value ) ) {
			$boolean = false;
		} elseif ( 'no' === $setting_value ) {
			$boolean = false;
		} else {
			$boolean = true;
		}

		switch ( $type ) {
			case 0:
				$debug_value = $boolean ? __( 'Yes', 'classifai' ) : __( 'No', 'classifai' );
				break;
			case 1:
				$debug_value = $boolean ? __( 'Enabled', 'classifai' ) : __( 'Disabled', 'classifai' );
				break;
		}

		return $debug_value;
	}

	/**
	 * Returns an array of feature-level debug info.
	 *
	 * @return array
	 */
	public function get_debug_information(): array {
		$feature_settings = $this->get_settings();
		$provider         = $this->get_feature_provider_instance();

		$roles = array_filter(
			$feature_settings['roles'],
			function ( $role ) {
				return '0' !== $role;
			}
		);

		$common_debug_info = [
			__( 'Authenticated', 'classifai' )          => self::get_debug_value_text( $this->is_configured() ),
			__( 'Status', 'classifai' )                 => self::get_debug_value_text( $feature_settings['status'], 1 ),
			__( 'Allowed roles (titles)', 'classifai' ) => implode( ', ', $roles ?? [] ),
			__( 'Allowed users (titles)', 'classifai' ) => implode( ', ', $feature_settings['users'] ?? [] ),
			__( 'User based opt-out', 'classifai' )     => self::get_debug_value_text( $feature_settings['user_based_opt_out'], 1 ),
			__( 'Provider', 'classifai' )               => $feature_settings['provider'],
		];

		if ( $provider && method_exists( $provider, 'get_debug_information' ) ) {
			$all_debug_info = array_merge(
				$common_debug_info,
				$provider->get_debug_information()
			);
		} else {
			$all_debug_info = $common_debug_info;
		}

		/**
		 * Filter to add feature-level debug information.
		 *
		 * @since 3.0.0
		 * @hook classifai_{feature}_debug_information
		 *
		 * @param {array} $all_debug_info Debug information
		 * @param {object} $this Current feature class.
		 *
		 * @return {array} Returns debug information.
		 */
		return apply_filters(
			'classifai_' . self::ID . '_debug_information',
			$all_debug_info,
			$this,
		);
	}

	/**
	 * Returns the data attribute string for an input.
	 *
	 * @param array $args The args passed to add_settings_field.
	 * @return string
	 */
	protected function get_data_attribute( array $args ): string {
		$data_attr     = $args['data_attr'] ?? [];
		$data_attr_str = '';

		foreach ( $data_attr as $attr_key => $attr_value ) {
			if ( is_scalar( $attr_value ) ) {
				$data_attr_str .= 'data-' . $attr_key . '="' . esc_attr( $attr_value ) . '"';
			} else {
				$data_attr_str .= 'data-' . $attr_key . '="' . esc_attr( wp_json_encode( $attr_value ) ) . '"';
			}
		}

		return $data_attr_str;
	}

	/**
	 * Register any needed endpoints.
	 */
	public function register_endpoints() {}

	/**
	 * Generic callback that can be used for all custom endpoints.
	 *
	 * @param WP_REST_Request $request The full request object.
	 * @return \WP_REST_Response|WP_Error
	 */
	public function rest_endpoint_callback( WP_REST_Request $request ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
		return rest_ensure_response( new WP_Error( 'invalid_route', esc_html__( 'Invalid route.', 'classifai' ) ) );
	}

	/**
	 * Runs the feature.
	 *
	 * @param mixed ...$args Arguments required by the feature depending on the provider selected.
	 * @return mixed
	 */
	public function run( ...$args ) {
		$settings          = $this->get_settings();
		$provider_id       = $settings['provider'];
		$provider_instance = $this->get_feature_provider_instance( $provider_id );

		if ( ! is_callable( [ $provider_instance, 'rest_endpoint_callback' ] ) ) {
			return new WP_Error( 'invalid_route', esc_html__( 'The selected provider does not have a valid callback in place.', 'classifai' ) );
		}

		/**
		 * Filter the results of running the feature.
		 *
		 * @since 3.0.0
		 * @hook classifai_{feature}_run
		 *
		 * @param {mixed} $result Result of running the feature.
		 * @param {Providers} $provider_instance Provider used.
		 * @param {mixed} $args Arguments used by the feature.
		 * @param {Feature} $this Current feature class.
		 *
		 * @return {mixed} Results.
		 */
		return apply_filters(
			'classifai_' . static::ID . '_run',
			$provider_instance->rest_endpoint_callback( ...$args ),
			$provider_instance,
			$args,
			$this
		);
	}
}
