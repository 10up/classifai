<?php

namespace Classifai\Features;

/**
 * Content Resizing feature class
 */
class ContentResizing extends Feature {
	/**
	 * ID of the feature.
	 *
	 * @var string
	 */
	const ID = 'feature_content_resizing';

	/**
	 * Initialize required variables and hooks.
	 */
	public function init() {
		$this->title = __( 'Content Resizing', 'classifai' );
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
				'label'       => __( 'Enable Content Resizing Generation', 'classifai' ),
				'value'       => $this->feature_settings->get_setting( 'status' ) ?: 'off',
			],
			'roles'  => [
				'type'        => 'multiselect',
				'label'       => __( 'Roles', 'classifai' ),
				'options'     => $this->roles,
				'description' => __( 'Select the roles that can use this feature.', 'classifai' ),
				'value'       => $this->feature_settings->get_setting( 'roles' ) ?: [],
			],
			'provider' => [
				'type' => 'select',
				'label' => __( 'Provider', 'classifai' ),
				'options' => $this->get_providers(),
				'description' => __( 'Select a provider for this feature.', 'classifai' ),
				'value' => \Classifai\Providers\OpenAI\ChatGPT::ID,
				'provider_settings' => $this->get_provider_settings(),
			]
		];
	}
}
