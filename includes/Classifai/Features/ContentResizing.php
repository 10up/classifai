<?php

namespace Classifai\Features;

use \Classifai\Providers\OpenAI\ChatGPT;

class ContentResizing extends Feature {
	const ID = 'feature_content_resizing';

	public function get_label() {
		return apply_filters(
			'classifai_' . static::ID . '_label',
			__( 'Content Resizing', 'classifai' )
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
			esc_html__( 'Enable title generation', 'classifai' ),
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
		$chat_gpt->add_number_of_responses_field(
			$this,
			[
				'id'          => 'number_of_suggestions',
				'label'       => esc_html__( 'Number of suggestions', 'classifai' ),
				'description' => esc_html__( 'Number of suggestions that will be generated in one request.', 'classifai' )
			]
		);
		$chat_gpt->add_prompt_field(
			$this,
			[
				'id'                 => 'condense_text_prompt',
				'label'              => esc_html__( 'Condense text prompt', 'classifai' ),
				'prompt_placeholder' => esc_html__( 'Decrease the content length no more than 2 to 4 sentences.', 'classifai' ),
				'description'        => esc_html__( "Enter a custom prompt, if desired.", 'classifai' )
			]
		);
		$chat_gpt->add_prompt_field(
			$this,
			[
				'id'                 => 'expand_text_prompt',
				'label'              => esc_html__( 'Expand text prompt'),
				'prompt_placeholder' => esc_html__( 'Increase the content length no more than 2 to 4 sentences.', 'classifai' ),
				'description'        => esc_html__( "Enter a custom prompt, if desired.", 'classifai' )
			]
		);
	}

	public function get_default_settings() {
		return [
			'status'   => '0',
			'roles'    => $this->roles,
			'provider' => \Classifai\Providers\OpenAI\ChatGPT::ID,
			ChatGPT::ID => [
				'api_key' => '',
				'number_of_suggestions' => 1,
				'condense_text_prompt' => array(
					array(
						'title'   => esc_html__( 'Default', 'classifai' ),
						'prompt'  => '',
						'default' => 1,
					),
				),
				'expand_text_prompt' => array(
					array(
						'title'   => esc_html__( 'Default', 'classifai' ),
						'prompt'  => '',
						'default' => 1,
					),
				),
			],
		];
	}

	public function render_section() {
		return;
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

		if ( isset( $settings[ ChatGPT::ID ] ) ) {
			$new_settings[ ChatGPT::ID ]['api_key']               = $chat_gpt->sanitize_api_key( $settings );
			$new_settings[ ChatGPT::ID ]['number_of_suggestions'] = $chat_gpt->sanitize_number_of_responses_field( 'number_of_suggestions', $settings );
			$new_settings[ ChatGPT::ID ]['condense_text_prompt']  = $chat_gpt->sanitize_prompts( 'condense_text_prompt', $settings );
			$new_settings[ ChatGPT::ID ]['expand_text_prompt']    = $chat_gpt->sanitize_prompts( 'expand_text_prompt', $settings );
		}

		return $new_settings;
	}
}
