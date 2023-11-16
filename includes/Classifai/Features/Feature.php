<?php

namespace Classifai\Features;

use function Classifai\find_provider_class;

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
	 * User role array.
	 *
	 * @var array
	 */
	public $roles = [];

	/**
	 * Array of provider classes.
	 *
	 * @var \Classifai\Providers\Provider[]
	 */
	public $provider_instances = [];

	/**
	 * Feature constructor.
	 *
	 * @param \Classifai\Providers\Provider[] $provider_instances Array of provider instances.
	 */
	public function __construct( $provider_instances = [] ) {
		$this->provider_instances = $provider_instances;
		add_action( 'admin_init', [ $this, 'setup_roles' ] );
		add_action( 'admin_init', [ $this, 'register_setting' ] );
		add_action( 'admin_init', [ $this, 'setup_fields_sections' ] );
	}

	/**
	 * Assigns user roles to the $roles array.
	 */
	public function setup_roles() {
		$default_settings = $this->get_default_settings();
		$this->roles      = get_editable_roles() ?? [];
		$this->roles      = array_combine( array_keys( $this->roles ), array_column( $this->roles, 'name' ) );

		/**
		 * Filter the allowed WordPress roles for ChatGTP
		 *
		 * @since 2.3.0
		 * @hook classifai_chatgpt_allowed_roles
		 *
		 * @param {array} $roles            Array of arrays containing role information.
		 * @param {array} $default_settings Default setting values.
		 *
		 * @return {array} Roles array.
		 */
		$this->roles = apply_filters( 'classifai_chatgpt_allowed_roles', $this->roles, $default_settings );
	}

	/**
	 * Returns the label of the feature.
	 *
	 * @return string
	 */
	abstract public function get_label();

	/**
	 * Set up the fields for each section.
	 */
	abstract public function setup_fields_sections();

	/**
	 * Returns the default settings for the feature.
	 *
	 * @return array
	 */
	abstract protected function get_default_settings();

	/**
	 * Returns the providers supported by the feature.
	 *
	 * @return array
	 */
	abstract protected function get_providers();

	/**
	 * Sanitizes the settings before saving.
	 *
	 * @param array $settings The settings to be sanitized on save.
	 *
	 * @return array
	 */
	abstract protected function sanitize_settings( $settings );

	/**
	 * Returns true if the feature meets all the criteria to be enabled. False otherwise.
	 *
	 * @return boolean|\WP_Error
	 */
	abstract public function is_feature_enabled();

	/**
	 * Calls the register method for the provider set for the feature.
	 * The Provider::register() method usually loads the JS assets.
	 */
	public function register() {
		if ( ! $this->can_register() ) {
			return;
		}

		$provider_instance = $this->get_feature_provider_instance();
		$provider_instance->register();
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
	public function get_option_name() {
		return 'classifai_' . static::ID;
	}

	/**
	 * Returns the settings for the feature.
	 *
	 * @param string $index The index of the setting to return.
	 *
	 * @return array|string
	 */
	public function get_settings( $index = false ) {
		$defaults = $this->get_default_settings();
		$settings = get_option( $this->get_option_name(), [] );
		$settings = $this->merge_settings( $settings, $defaults );

		if ( $index && isset( $settings[ $index ] ) ) {
			return $settings[ $index ];
		}

		return $settings;
	}

	/**
	 * Merges the data settings with the default settings recursively,
	 *
	 * @internal
	 *
	 * @param array $settings Settings data from the database.
	 * @param array $default  Default feature and providers settings data.
	 *
	 * @return array
	 */
	protected function merge_settings( $settings = [], $default = [] ) {
		foreach ( $default as $key => $value ) {
			if ( ! isset( $settings[ $key ] ) ) {
				$settings[ $key ] = $default[ $key ];
			} else {
				if ( is_array( $value ) ) {
					$settings[ $key ] = $this->merge_settings( $settings[ $key ], $default[ $key ] );
				}
			}
		}

		return $settings;
	}

	/**
	 * Returns the instance of the provider set for the feature.
	 *
	 * @param string $provider_id The ID of the provider.
	 *
	 * @return \Classifai\Providers
	 */
	protected function get_feature_provider_instance( $provider_id = '' ) {
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
	public function is_configured() {
		$settings      = $this->get_settings();
		$provider_id   = $settings['provider'];
		$is_configured = false;

		if ( ! empty( $settings ) && ! empty( $settings[ $provider_id ]['authenticated'] ) ) {
			$is_configured = true;
		}

		return $is_configured;
	}

	/**
	 * Can the feature be initialized?
	 */
	public function can_register() {
		return $this->is_configured();
	}

	/**
	 * Returns the data attribute string for an input.
	 *
	 * @param array $args The args passed to add_settings_field.
	 * @return string
	 */
	protected function get_data_attribute( $args ) {
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
	 * Resets settings for the provider.
	 */
	public function reset_settings() {
		update_option( $this->get_option_name(), $this->get_default_settings() );
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
	public function render_prompt_repeater_field( array $args ): void {
		$option_index      = $args['option_index'] ?? false;
		$setting_index     = $this->get_settings( $option_index );
		$prompts           = $setting_index[ $args['label_for'] ] ?? '';
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
			$is_default_prompt  = ( isset( $prompt['default'] ) && 1 === $prompt['default'] ) || 1 === $prompt_count;
			$is_original_prompt = isset( $prompt['original'] ) && 1 === $prompt['original'];
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
	public function render_select( $args ) {
		$option_index  = isset( $args['option_index'] ) ? $args['option_index'] : false;
		$setting_index = $this->get_settings( $option_index );
		$saved         = ( isset( $setting_index[ $args['label_for'] ] ) ) ? $setting_index[ $args['label_for'] ] : '';
		$data_attr     = isset( $args['data_attr'] ) ?: [];

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
					<label for="%1$s_%2$s_%3$s">
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
}
