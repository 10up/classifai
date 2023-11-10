<?php

namespace Classifai\Features;

use \Classifai\Providers\OpenAI\ChatGPT;

/**
 * Class ContentResizing
 */
class ContentResizing extends Feature {
	/**
	 * ID of the current feature.
	 *
	 * @var string
	 */
	const ID = 'feature_content_resizing';

	/**
	 * Returns the label of the feature.
	 *
	 * @return string
	 */
	public function get_label() {
		return apply_filters(
			'classifai_' . static::ID . '_label',
			__( 'Content Resizing', 'classifai' )
		);
	}

	/**
	 * Returns the providers supported by the feature.
	 *
	 * @return array
	 */
	public function get_providers() {
		return apply_filters(
			'classifai_' . static::ID . '_providers',
			[
				ChatGPT::ID => __( 'OpenAI ChatGPT', 'classifai' ),
			]
		);
	}

	/**
	 * Sets up the fields and sections for the feature.
	 */
	public function setup_fields_sections() {
		$settings = $this->get_settings();

		add_settings_section(
			$this->get_option_name() . '_section',
			esc_html__( 'Feature settings', 'classifai' ),
			'__return_empty_string',
			$this->get_option_name()
		);

		add_settings_field(
			'status',
			esc_html__( 'Enable content resizing generation', 'classifai' ),
			[ $this, 'render_input' ],
			$this->get_option_name(),
			$this->get_option_name() . '_section',
			[
				'label_for'     => 'status',
				'input_type'    => 'checkbox',
				'default_value' => $settings['status'],
				'description'   => __( '"Condense this text" and "Expand this text" menu items will be added to the paragraph block\'s toolbar menu.', 'classifai' ),
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
				'default_values' => $settings['roles'],
				'description'    => __( 'Choose which roles are allowed to use this feature.', 'classifai' ),
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
				'default_value' => $settings['provider'],
			]
		);

		/*
		 * The following fields are specific to the OpenAI ChatGPT provider.
		 * These fields will only be displayed if the provider is selected, and will remain hidden otherwise.
		 *
		 * If the feature supports multiple providers, then the fields should be added for each provider.
		 */
		$chat_gpt = new ChatGPT( $this );
		$chat_gpt->add_api_key_field();
		$chat_gpt->add_number_of_responses_field(
			[
				'id'          => 'number_of_suggestions',
				'label'       => esc_html__( 'Number of suggestions', 'classifai' ),
				'description' => esc_html__( 'Number of suggestions that will be generated in one request.', 'classifai' ),
			]
		);
		$chat_gpt->add_prompt_field(
			[
				'id'                 => 'condense_text_prompt',
				'label'              => esc_html__( 'Condense text prompt', 'classifai' ),
				'prompt_placeholder' => esc_html__( 'Decrease the content length no more than 2 to 4 sentences.', 'classifai' ),
				'description'        => esc_html__( 'Enter a custom prompt, if desired.', 'classifai' ),
			]
		);
		$chat_gpt->add_prompt_field(
			[
				'id'                 => 'expand_text_prompt',
				'label'              => esc_html__( 'Expand text prompt' ),
				'prompt_placeholder' => esc_html__( 'Increase the content length no more than 2 to 4 sentences.', 'classifai' ),
				'description'        => esc_html__( 'Enter a custom prompt, if desired.', 'classifai' ),
			]
		);

		do_action( 'classifai_' . static::ID . 'provider_setup_fields_sections', $this );
	}

	/**
	 * Returns true if the feature meets all the criteria to be enabled. False otherwise.
	 *
	 * @return boolean
	 */
	public function is_feature_enabled() {
		$access          = false;
		$settings        = $this->get_settings();
		$provider_id     = $settings['provider'] ?? ChatGPT::ID;
		$user_roles      = wp_get_current_user()->roles ?? [];
		$feature_roles   = $settings['roles'] ?? [];

		$user_access     = ! empty( $feature_roles ) && ! empty( array_intersect( $user_roles, $feature_roles ) );
		$provider_access = $settings[ $provider_id ]['authenticated'] ?? false;
		$feature_status  = isset( $settings['status'] ) && '1' === $settings['status'];
		$access          = $user_access && $provider_access && $feature_status;

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

	/**
	 * Returns the default settings for the feature.
	 *
	 * The root-level keys are the setting keys that are independent of the provider.
	 * Provider specific settings should be nested under the provider key.
	 *
	 * @todo Add a filter hook to allow other plugins to add their own settings.
	 *
	 * @return array
	 */
	public function get_default_settings() {
		return [
			'status'    => '0',
			'roles'     => $this->roles,
			'provider'  => \Classifai\Providers\OpenAI\ChatGPT::ID,
			ChatGPT::ID => [
				'api_key'               => '',
				'authenticated'         => false,
				'number_of_suggestions' => 1,
				'condense_text_prompt'  => array(
					array(
						'title'    => esc_html__( 'Condense text prompt', 'classifai' ),
						'prompt'   => esc_html__( 'Decrease the content length no more than 2 to 4 sentences.', 'classifai' ),
						'original' => 1,
					),
				),
				'expand_text_prompt'    => array(
					array(
						'title'    => esc_html__( 'Expand text prompt', 'classifai' ),
						'prompt'   => esc_html__( 'Increase the content length no more than 2 to 4 sentences.', 'classifai' ),
						'original' => 1,
					),
				),
			],
		];
	}

	/**
	 * Sanitizes the settings before saving.
	 *
	 * @param array $settings The settings to be sanitized on save.
	 *
	 * @return array
	 */
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
			$new_settings['provider'] = ChatGPT::ID;
		}

		/*
		 * These are the sanitization methods specific to the OpenAI ChatGPT provider.
		 * They sanitize the settings for the provider and then merge them into the new settings array.
		 *
		 * When multiple providers are supported, the sanitization methods for each provider should be called here.
		 */
		if ( isset( $settings[ ChatGPT::ID ] ) ) {
			$provider_instance                                    = new ChatGPT( $this );
			$api_key_settings                                     = $provider_instance->sanitize_api_key_settings( $settings );
			$new_settings[ ChatGPT::ID ]['api_key']               = $api_key_settings[ ChatGPT::ID ]['api_key'];
			$new_settings[ ChatGPT::ID ]['authenticated']         = $api_key_settings[ ChatGPT::ID ]['authenticated'];
			$new_settings[ ChatGPT::ID ]['number_of_suggestions'] = $provider_instance->sanitize_number_of_responses_field( 'number_of_suggestions', $settings );
			$new_settings[ ChatGPT::ID ]['condense_text_prompt']  = $provider_instance->sanitize_prompts( 'condense_text_prompt', $settings );
			$new_settings[ ChatGPT::ID ]['expand_text_prompt']    = $provider_instance->sanitize_prompts( 'expand_text_prompt', $settings );
		}

		return apply_filters(
			'classifai_' . static::ID . '_sanitize_settings',
			$new_settings,
			$settings
		);
	}
}
