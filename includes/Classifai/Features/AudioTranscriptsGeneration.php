<?php

namespace Classifai\Features;

use Classifai\Services\LanguageProcessing;
use Classifai\Providers\Azure\Speech;
use \Classifai\Providers\OpenAI\Whisper;

/**
 * Class AudioTranscriptsGeneration
 */
class AudioTranscriptsGeneration extends Feature {
	/**
	 * ID of the current feature.
	 *
	 * @var string
	 */
	const ID = 'feature_audio_transcripts_generation';

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct();

		/**
		 * Every feature must set the `provider_instances` variable with the list of provider instances
		 * that are registered to a service.
		 */
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
			__( 'Audio Transcripts Generation', 'classifai' )
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
				Whisper::ID => __( 'OpenAI Whisper', 'classifai' ),
			]
		);
	}

	/**
	 * Sets up the fields and sections for the feature.
	 */
	public function setup_fields_sections() {
		$settings = $this->get_settings();

		/* These are the feature-level fields that are
		 * independent of the provider.
		 */
		add_settings_section(
			$this->get_option_name() . '_section',
			esc_html__( 'Feature settings', 'classifai' ),
			'__return_empty_string',
			$this->get_option_name()
		);

		add_settings_field(
			'status',
			esc_html__( 'Enable audio transcription', 'classifai' ),
			[ $this, 'render_input' ],
			$this->get_option_name(),
			$this->get_option_name() . '_section',
			[
				'label_for'     => 'status',
				'input_type'    => 'checkbox',
				'default_value' => $settings['status'],
				'description'   => __( 'Enabling this will automatically generate transcripts for supported audio files.', 'classifai' ),
			]
		);

		// Add user/role-based access fields.
		$this->add_access_control_fields();

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

		/* The following renders the fields of all the providers
		 * that are registered to the feature.
		 */
		$this->render_provider_fields();
	}

	/**
	 * Returns true if the feature meets all the criteria to be enabled.
	 *
	 * @return boolean
	 */
	public function is_feature_enabled() {
		$access          = false;
		$settings        = $this->get_settings();
		$provider_id     = $settings['provider'] ?? Whisper::ID;
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
		$provider_settings = $this->get_provider_default_settings();
		$feature_settings  = [
			'provider' => Whisper::ID,
		];

		return
			apply_filters(
				'classifai_' . static::ID . '_get_default_settings',
				array_merge(
					parent::get_default_settings(),
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

		// Sanitization of the feature-level settings.
		$new_settings = parent::sanitize_settings( $new_settings );

		// Sanitization of the provider-level settings.
		$provider_instance = $this->get_feature_provider_instance( $new_settings['provider'] );
		$new_settings      = $provider_instance->sanitize_settings( $new_settings );

		return apply_filters(
			'classifai_' . static::ID . '_sanitize_settings',
			$new_settings,
			$settings
		);
	}

	public function run( ...$args ) {
		$settings          = $this->get_settings();
		$provider_id       = $settings['provider'] ?? Whisper::ID;
		$provider_instance = $this->get_feature_provider_instance( $provider_id );
		$result            = '';

		if ( Whisper::ID === $provider_instance::ID ) {
			/** @var Whisper $provider_instance */
			$result = call_user_func_array(
				[ $provider_instance, 'transcribe_audio' ],
				[ ...$args ]
			);
		}

		return apply_filters(
			'classifai_' . static::ID . '_run',
			$result,
			$provider_instance,
			$args,
			$this
		);
	}
}
