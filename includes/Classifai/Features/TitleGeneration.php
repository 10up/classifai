<?php

namespace Classifai\Features;

/**
 * Title Generation feature class
 */
class TitleGeneration extends Feature {
	/**
	 * ID of the feature.
	 *
	 * @var string
	 */
	const ID = 'feature_title_generation';

	/**
	 * Initialize required variables and hooks.
	 */
	public function init() {
		$this->title = __( 'Title Generation', 'classifai' );
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
					'value' => \Classifai\Providers\OpenAI\ChatGPT::ID,
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
				'label'       => __( 'Enable Title Generation', 'classifai' ),
				'value'       => $this->get_setting( 'status' ) ?: 'off',
			],
			'roles'  => [
				'type'        => 'multiselect',
				'label'       => __( 'Roles', 'classifai' ),
				'options'     => $this->roles,
				'description' => __( 'Select the roles that can use this feature.', 'classifai' ),
				'value'       => $this->get_setting( 'roles' ) ?: [],
			],
			'provider' => [
				'type' => 'select',
				'label' => __( 'Provider', 'classifai' ),
				'options' => $this->get_providers(),
				'description' => __( 'Select a provider for this feature.', 'classifai' ),
				'value' => \Classifai\Providers\OpenAI\ChatGPT::ID,
			]
		];
	}
}
