<?php

namespace Classifai\Features;

abstract class Feature {
	const ID = '';

	public $roles = [];

	public function __construct( $provider_instances = [] ) {
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

		add_action( 'admin_init', [ $this, 'register_setting' ] );
		add_action( 'admin_init', [ $this, 'setup_fields_sections' ] );
	}

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
	 * Set up the fields for each section.
	 */
	abstract public function setup_fields_sections();

	abstract public function get_default_settings() ;

	abstract public function get_providers();

	abstract public function sanitize_settings( $settings );

	public function get_option_name() {
		return 'classifai_' . static::ID;
	}

	public function get_settings( $index = false ) {
		$defaults = $this->get_default_settings();
		$settings = get_option( $this->get_option_name(), [] );
		$settings = wp_parse_args( $settings, $defaults );

		if ( $index && isset( $settings[ $index ] ) ) {
			return $settings[ $index ];
		}

		return $settings;
	}

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
		$value             = $setting_index[ $args['label_for'] ] ?? '';
		$class             = $args['class'] ?? 'large-text';
		$placeholder       = $args['placeholder'] ?? '';
		$field_name_prefix = sprintf(
			'%1$s%2$s[%3$s]',
			$this->get_option_name(),
			$option_index ? "[$option_index]" : '',
			$args['label_for']
		);

		$value = ( empty( $value ) && isset( $args['default_value'] ) ) ? $args['default_value'] : $value;

		$prompt_count = count( $value );
		$field_index  = 0;
		?>

		<?php foreach ( $value as $prompt ) : ?>
			<?php $is_default_prompt = 1 === $prompt['default']; ?>

			<fieldset class="classifai-field-type-prompt-setting">
				<input type="hidden"
					name="<?php echo esc_attr( $field_name_prefix . "[$field_index][default]" ); ?>"
					value="<?php echo esc_attr( $prompt['default'] ?? '' ); ?>"
					class="js-setting-field__default">
				<label>
					<?php esc_html_e( 'Title', 'classifai' ); ?>&nbsp;*
					<span class="dashicons dashicons-editor-help"
						title="<?php esc_attr_e( 'Short description of prompt to use for identification', 'classifai' ); ?>"></span>
					<input type="text"
						name="<?php echo esc_attr( $field_name_prefix . "[$field_index][title]" ); ?>"
						placeholder="<?php esc_attr_e( 'Prompt title', 'classifai' ); ?>"
						value="<?php echo esc_attr( $prompt['title'] ?? '' ); ?>"
						required>
				</label>

				<label>
					<?php esc_html_e( 'Prompt', 'classifai' ); ?>
					<textarea
						class="<?php echo esc_attr( $class ); ?>"
						rows="4"
						name="<?php echo esc_attr( $field_name_prefix . "[$field_index][prompt]" ); ?>"
						placeholder="<?php echo esc_attr( $placeholder ); ?>"
					><?php echo esc_textarea( $prompt['prompt'] ?? '' ); ?></textarea>
				</label>

				<div class="actions-rows">
					<a href="#" class="action__set_default <?php echo $is_default_prompt ? 'selected' : ''; ?>">
						<?php if ( $is_default_prompt ) : ?>
							<?php esc_html_e( 'Default Prompt', 'classifai' ); ?>
						<?php else : ?>
							<?php esc_html_e( 'Set as default prompt', 'classifai' ); ?>
						<?php endif; ?>
					</a>
					<a href="#" class="action__remove_prompt" style="<?php echo 1 === $prompt_count ? 'display:none;' : ''; ?>">
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
		$setting_index = $this->get_settings();
		$saved         = ( isset( $setting_index[ $args['label_for'] ] ) ) ? $setting_index[ $args['label_for'] ] : '';
		$data_attr     = isset( $args['data_attr'] ) ?: [];

		// Check for a default value
		$saved   = ( empty( $saved ) && isset( $args['default_value'] ) ) ? $args['default_value'] : $saved;
		$options = isset( $args['options'] ) ? $args['options'] : [];
		?>

		<select
			id="<?php echo esc_attr( $args['label_for'] ); ?>"
			name="<?php echo esc_attr( $this->get_option_name() ); ?>[<?php echo esc_attr( $args['label_for'] ); ?>]"
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
						<input type="hidden" name="%1$s[%2$s][%3$s]" value="0" />
						<input type="checkbox" id="%1$s_%2$s_%3$s" name="%1$s[%2$s][%3$s]" value="%3$s" %4$s />
						%5$s
					</label>
				</p>',
				esc_attr( $this->get_option_name() ),
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
