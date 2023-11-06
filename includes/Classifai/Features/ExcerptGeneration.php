<?php

namespace Classifai\Features;

class ExcerptGeneration extends Feature {
	const ID = 'feature_excerpt_generation';

	public function get_label() {
		return apply_filters(
			'classifai_' . static::ID . '_label',
			__( 'Excerpt Generation', 'classifai' )
		);
	}

	public function get_providers() {
		return apply_filters(
			'classifai_' . static::ID . '_providers',
			[
				\Classifai\Providers\OpenAI\ChatGPT::ID => __( 'OpenAI ChatGPT', 'classifai' ),
			]
		);
	}

	public function setup_fields_sections() {
		$default_settings = $this->get_default_settings();

		add_settings_section(
			$this->get_option_name() . '_section',
			esc_html__( 'Feature settings', 'classifai' ),
			array( $this, 'render_section' ),
			$this->get_option_name()
		);

		add_settings_field(
			'status',
			esc_html__( 'Enable excerpt generation', 'classifai' ),
			[ $this, 'render_input' ],
			$this->get_option_name(),
			$this->get_option_name() . '_section',
			[
				'label_for'     => 'status',
				'input_type'    => 'checkbox',
				'default_value' => $default_settings['status'],
				'description'   => __( 'A button will be added to the status panel that can be used to generate titles.', 'classifai' ),
			]
		);

		add_settings_field(
			'roles',
			esc_html__( 'Allowed roles', 'classifai' ),
			[ $this, 'render_checkbox_group' ],
			$this->get_option_name(),
			$this->get_option_name() . '_section',
			[
				'label_for'      => 'roles',
				'options'        => $this->roles,
				'default_values' => $default_settings['roles'],
				'description'    => __( 'Choose which roles are allowed to generate excerpts.', 'classifai' ),
			]
		);

		add_settings_field(
			'length',
			esc_html__( 'Excerpt length', 'classifai' ),
			[ $this, 'render_input' ],
			$this->get_option_name(),
			$this->get_option_name() . '_section',
			[
				'label_for'     => 'length',
				'input_type'    => 'number',
				'min'           => 1,
				'step'          => 1,
				'default_value' => $default_settings['length'],
				'description'   => __( 'How many words should the excerpt be? Note that the final result may not exactly match this. In testing, ChatGPT tended to exceed this number by 10-15 words.', 'classifai' ),
			]
		);

		add_settings_field(
			'provider',
			esc_html__( 'Select a provider', 'classifai' ),
			[ $this, 'render_select' ],
			$this->get_option_name(),
			$this->get_option_name() . '_section',
			[
				'label_for'     => 'provider',
				'options'       => $this->get_providers(),
				'default_value' => $default_settings['provider'],
			]
		);

		$this->add_provider_settings_fields();
	}

	public function get_default_settings() {
		return [
			'status'   => '0',
			'roles'    => $this->roles,
			'provider' => \Classifai\Providers\OpenAI\ChatGPT::ID,
			'length'   => absint( apply_filters( 'excerpt_length', 55 ) ),
		];
	}

	public function render_section() {
		return;
	}

	public function sanitize_settings( $settings ) {
		$new_settings = $this->get_settings();

		if ( empty( $settings['status'] ) || 1 !== (int) $settings['status'] ) {
			$new_settings['status'] = 'no';
		} else {
			$new_settings['status'] = '1';
		}

		if ( isset( $settings['roles'] ) && is_array( $settings['roles'] ) ) {
			$new_settings['roles'] = array_map( 'sanitize_text_field', $settings['roles'] );
		} else {
			$new_settings['roles'] = array_keys( get_editable_roles() ?? [] );
		}

		if ( isset( $settings['provider'] ) ) {
			$new_settings['provider'] = sanitize_text_field( $settings['provider'] );
		} else {
			$new_settings['provider'] = \Classifai\Providers\OpenAI\ChatGPT::ID;
		}

		if ( isset( $settings['length'] ) && is_numeric( $settings['length'] ) && (int) $settings['length'] >= 0 ) {
			$new_settings['length'] = absint( $settings['length'] );
		} else {
			$new_settings['length'] = 55;
		}

		return $new_settings;
	}
}
