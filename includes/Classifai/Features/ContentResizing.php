<?php

namespace Classifai\Features;

use \Classifai\Providers\OpenAI\ChatGPT;
use Classifai\Services\LanguageProcessing;

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

	public function __construct() {
		parent::__construct();

		$service_providers = LanguageProcessing::get_service_providers();
		$this->provider_instances = $this->get_provider_instances( $service_providers );
	}

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
	protected function get_providers() {
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

		foreach( array_keys( $this->get_providers() ) as $provider_id ) {
			$provider = $this->get_feature_provider_instance( $provider_id );

			if ( method_exists( $provider, 'render_provider_fields' ) ) {
				$provider->render_provider_fields();
			}
		}
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
	protected function get_default_settings() {
		$provider_settings = [];
		$feature_settings  = [
			'status'    => '0',
			'roles'     => $this->roles,
			'provider'  => \Classifai\Providers\OpenAI\ChatGPT::ID,
		];

		$provider_instance                = $this->get_feature_provider_instance( ChatGPT::ID );
		$provider_settings[ ChatGPT::ID ] = $provider_instance->get_default_provider_settings();

		return
			apply_filters(
				'classifai_' . static::ID . '_get_default_settings',
				array_merge(
					$feature_settings,
					$provider_settings
				)
			);
	}

	/**
	 * Sanitizes the settings before saving.
	 *
	 * @param array $new_settings The settings to be sanitized on save.
	 *
	 * @return array
	 */
	public function sanitize_settings( $new_settings ) {
		$settings = $this->get_settings();

		$new_settings['status']   = $new_settings['status'] ?? $settings['status'];
		$new_settings['roles']    = isset( $new_settings['roles'] ) ? array_map( 'sanitize_text_field', $new_settings['roles'] ) : $settings['roles'];
		$new_settings['provider'] = isset( $new_settings['provider'] ) ? sanitize_text_field( $new_settings['provider'] ) : $settings['provider'];

		$provider_instance = $this->get_feature_provider_instance( $new_settings['provider'] );
		$new_settings      = $provider_instance->sanitize_settings( $new_settings );

		return apply_filters(
			'classifai_' . static::ID . '_sanitize_settings',
			$new_settings,
			$settings
		);
	}
}
