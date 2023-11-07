<?php

namespace Classifai\Features;

use \Classifai\Providers\OpenAI\ChatGPT;

class TitleGeneration extends Feature {
	const ID = 'feature_title_generation';

	public function get_label() {
		return apply_filters(
			'classifai_' . static::ID . '_label',
			__( 'Title Generation', 'classifai' )
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

		$chat_gpt = new ChatGPT( $this );
		$chat_gpt->add_api_key_field();
		$chat_gpt->add_number_of_responses_field(
			[
				'id'          => 'number_of_titles',
				'label'       => esc_html__( 'Number of titles', 'classifai' ),
				'description' => esc_html__( 'Number of titles that will be generated in one request.', 'classifai' )
			]
		);
		$chat_gpt->add_prompt_field(
			[
				'id'                 => 'generate_title_prompt',
				'prompt_placeholder' => esc_html__( 'Write an SEO-friendly title for the following content that will encourage readers to clickthrough, staying within a range of 40 to 60 characters.', 'classifai' ),
				'description'        => esc_html__( "Enter a custom prompt, if desired.", 'classifai' )
			]
		);
	}

	public function is_feature_enabled() {
		$access        = false;
		$settings      = $this->get_settings();
		$user_roles    = wp_get_current_user()->roles ?? [];
		$feature_roles = $settings['roles' ] ?? [];

		// Check if user has access to the feature and the feature is turned on.
		if ( ! empty( $feature_roles ) && ! empty( array_intersect( $user_roles, $feature_roles ) ) ) {
			$access = true;
		}

		/**
		 * Filter to override permission to the generate title feature.
		 *
		 * @since 2.3.0
		 * @hook classifai_openai_chatgpt_{$feature}
		 *
		 * @param {bool}  $access Current access value.
		 * @param {array} $settings Current feature settings.
		 *
		 * @return {bool} Should the user have access?
		 */
		return apply_filters( 'classifai_' . static::ID . '_is_feature_enabled', $access, $settings );
	}

	public function get_default_settings() {
		return [
			'status'   => '0',
			'roles'    => $this->roles,
			'length'   => absint( apply_filters( 'excerpt_length', 55 ) ),
			'provider' => \Classifai\Providers\OpenAI\ChatGPT::ID,
			ChatGPT::ID => [
				'api_key' => '',
				'number_of_titles' => 1,
				'authenticated' => false,
				'generate_title_prompt' => array(
					array(
						'title'   => esc_html__( 'ClassifAI default', 'classifai' ),
						'prompt'  => esc_html__( 'Write an SEO-friendly title for the following content that will encourage readers to clickthrough, staying within a range of 40 to 60 characters.', 'classifai' ),
						'original' => 1,
					),
				)
			],
		];
	}

	public function sanitize_settings( $settings ) {
		$new_settings = $this->get_settings();
		$chat_gpt     = new ChatGPT( $this );

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
			$api_key_settings                                     = $chat_gpt->sanitize_api_key_settings( $settings );
			$new_settings[ ChatGPT::ID ]['api_key']               = $api_key_settings[ ChatGPT::ID ]['api_key'];
			$new_settings[ ChatGPT::ID ]['authenticated']         = $api_key_settings[ ChatGPT::ID ]['authenticated'];
			$new_settings[ ChatGPT::ID ]['number_of_titles']      = $chat_gpt->sanitize_number_of_responses_field( 'number_of_titles', $settings );
			$new_settings[ ChatGPT::ID ]['generate_title_prompt'] = $chat_gpt->sanitize_prompts( 'generate_title_prompt', $settings );
		}

		return $new_settings;
	}
}
