<?php

namespace Classifai\Features;

/**
 * Excerpt Generation feature class
 */
class ExcerptGeneration extends Feature {
	/**
	 * ID of the feature.
	 *
	 * @var string
	 */
	const ID = 'feature_excerpt_generation';

	/**
	 * Initialize required variables and hooks.
	 */
	public function init() {
		$this->title = __( 'Excerpt Generation', 'classifai' );
	}

	/**
	 * Returns an array of providers supported by the feature.
	 *
	 * @return array
	 */
	public function get_providers() {
		return apply_filters(
			'classifai_' . self::ID . '_providers',
			[
				[
					'label' => __( 'OpenAI ChatGPT' ),
					'value' => \Classifai\Providers\OpenAI\ChatGPT::class,
				]
			]
		);
	}

	/**
	 * Sanitization method for the feature settings.
	 *
	 * @return array
	 */
	public function sanitize_settings( $settings ) {
		return $settings;
	}

	/**
	 * Returns array of settings data structure.
	 * Used to render settings fields for the feature.
	 *
	 * @return array
	 */
	public function get_settings_data() {
		return [
			'status' => [
				'type'        => 'checkbox',
				'label'       => __( 'Enable Excerpt Generation', 'classifai' ),
				'value'       => $this->feature_settings->get_setting( 'status' ) ?: 'off',
			],
			'provider' => [
				'type' => 'select',
				'label' => __( 'Provider', 'classifai' ),
				'options' => $this->get_providers(),
				'description' => __( 'Select a provider for this feature.', 'classifai' ),
				'value' => \Classifai\Providers\OpenAI\ChatGPT::ID,
				'provider_settings' => $this->get_provider_settings(),
			],
			'roles'  => [
				'type'        => 'multiselect',
				'label'       => __( 'Roles', 'classifai' ),
				'options'     => $this->roles,
				'description' => __( 'Select the roles that can use this feature.', 'classifai' ),
				'value'       => $this->feature_settings->get_setting( 'roles' ) ?: [],
			],
			'length'  => [
				'type'        => 'number',
				'label'       => __( 'Excerpt Length', 'classifai' ),
				'options'     => $this->roles,
				'description' => __( 'How many words should the excerpt be? Note that the final result may not exactly match this. In testing, ChatGPT tended to exceed this number by 10-15 words.', 'classifai' ),
				'value'       => (int) $this->feature_settings->get_setting( 'length' ) ?: (int) apply_filters( 'excerpt_length', 55 ),
			]
		];
	}
}
