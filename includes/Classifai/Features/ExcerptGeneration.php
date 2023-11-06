<?php

namespace Classifai\Features;

use \Classifai\Providers\OpenAI\ChatGPT;

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
				ChatGPT::ID => __( 'OpenAI ChatGPT', 'classifai' ),
			]
		);
	}

	public function setup_fields_sections() {
		$default_settings = $this->get_default_settings();

		add_settings_section(
			$this->get_option_name() . '_section',
			esc_html__( 'Feature settings', 'classifai' ),
			'__return_empty_string',
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

		$chat_gpt = new ChatGPT( null );
		$chat_gpt->add_api_key_field( $this );
		$chat_gpt->add_prompt_field(
			$this,
			[
				'id'                 => 'generate_excerpt_prompt',
				'prompt_placeholder' => esc_html__( 'Summarize the following message using a maximum of {{WORDS}} words. Ensure this summary pairs well with the following text: {{TITLE}}.', 'classifai' ),
				'description'        => esc_html__( "Enter your custom prompt. Note the following variables that can be used in the prompt and will be replaced with content: {{WORDS}} will be replaced with the desired excerpt length setting. {{TITLE}} will be replaced with the item's title.", 'classifai' )
			]
		);
	}

	public function get_default_settings() {
		return [
			'status'   => '0',
			'roles'    => $this->roles,
			'length'   => absint( apply_filters( 'excerpt_length', 55 ) ),
			'provider' => \Classifai\Providers\OpenAI\ChatGPT::ID,
			ChatGPT::ID => [
				'api_key' => '',
				'generate_excerpt_prompt' => array(
					array(
						'title'   => esc_html__( 'Default', 'classifai' ),
						'prompt'  => esc_html__( 'Summarize the following message using a maximum of {{WORDS}} words. Ensure this summary pairs well with the following text: {{TITLE}}.', 'classifai' ),
						'original' => 1,
					),
				)
			],
		];
	}

	public function sanitize_settings( $settings ) {
		$new_settings = $this->get_settings();
		$chat_gpt     = new ChatGPT( null );

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
			$new_settings['provider'] = ChatGPT::ID;
		}

		if ( isset( $settings['length'] ) && is_numeric( $settings['length'] ) && (int) $settings['length'] >= 0 ) {
			$new_settings['length'] = absint( $settings['length'] );
		} else {
			$new_settings['length'] = 55;
		}

		if ( isset( $settings[ ChatGPT::ID ] ) ) {
			$new_settings[ ChatGPT::ID ]['api_key'] = $chat_gpt->sanitize_api_key( $settings );
			$new_settings[ ChatGPT::ID ]['generate_excerpt_prompt'] = $chat_gpt->sanitize_prompts( 'generate_excerpt_prompt', $settings );
		}

		return $new_settings;
	}
}
