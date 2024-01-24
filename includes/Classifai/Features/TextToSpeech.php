<?php

namespace Classifai\Features;

use Classifai\Services\LanguageProcessing;
use Classifai\Providers\Azure\Speech;

/**
 * Class TextToSpeech
 */
class TextToSpeech extends Feature {
	/**
	 * ID of the current feature.
	 *
	 * @var string
	 */
	const ID = 'feature_text_to_speech_generation';

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->label = __( 'Text to Speech', 'classifai' );

		// Contains all providers that are registered to the service.
		$this->provider_instances = $this->get_provider_instances( LanguageProcessing::get_service_providers() );

		// Contains just the providers this feature supports.
		$this->supported_providers = [
			Speech::ID => __( 'Microsoft Azure AI Speech', 'classifai' ),
		];
	}

	/**
	 * Get the description for the enable field.
	 *
	 * @return string
	 */
	public function get_enable_description(): string {
		return esc_html__( 'A button will be added to the status panel that can be used to generate titles.', 'classifai' );
	}

	/**
	 * Add any needed custom fields.
	 */
	public function add_custom_settings_fields() {
		$settings          = $this->get_settings();
		$post_types        = \Classifai\get_post_types_for_language_settings();
		$post_type_options = array();

		foreach ( $post_types as $post_type ) {
			$post_type_options[ $post_type->name ] = $post_type->label;
		}

		add_settings_field(
			'post_types',
			esc_html__( 'Allowed post types', 'classifai' ),
			[ $this, 'render_checkbox_group' ],
			$this->get_option_name(),
			$this->get_option_name() . '_section',
			[
				'label_for'      => 'post_types',
				'options'        => $post_type_options,
				'default_values' => $settings['post_types'],
				'description'    => __( 'Choose which post types support this feature.', 'classifai' ),
			]
		);
	}

	/**
	 * Returns the select options for post types.
	 *
	 * @return array
	 */
	protected function get_post_types_select_options(): array {
		$post_types = \Classifai\get_post_types_for_language_settings();
		$options    = array();

		foreach ( $post_types as $post_type ) {
			$options[ $post_type->name ] = $post_type->label;
		}

		return $options;
	}

	/**
	 * The list of post types that TTS supports.
	 *
	 * @return array Supported Post Types.
	 */
	public function get_tts_supported_post_types(): array {
		$selected   = $this->get_settings( 'post_types' );
		$post_types = [];

		foreach ( $selected as $post_type => $enabled ) {
			if ( ! empty( $enabled ) ) {
				$post_types[] = $post_type;
			}
		}

		return $post_types;
	}

	/**
	 * Returns the default settings for the feature.
	 *
	 * @return array
	 */
	public function get_feature_default_settings(): array {
		return [
			'post_types' => [],
			'provider'   => Speech::ID,
		];
	}

	/**
	 * Sanitizes the default feature settings.
	 *
	 * @param array $new_settings Settings being saved.
	 * @return array
	 */
	public function sanitize_default_feature_settings( array $new_settings ): array {
		$settings   = $this->get_settings();
		$post_types = \Classifai\get_post_types_for_language_settings();

		foreach ( $post_types as $post_type ) {
			if ( ! isset( $new_settings['post_types'][ $post_type->name ] ) ) {
				$new_settings['post_types'][ $post_type->name ] = $settings['post_types'];
			} else {
				$new_settings['post_types'][ $post_type->name ] = sanitize_text_field( $new_settings['post_types'][ $post_type->name ] );
			}
		}

		return $new_settings;
	}

	/**
	 * Runs the feature.
	 *
	 * @param mixed ...$args Arguments required by the feature depending on the provider selected.
	 * @return mixed
	 */
	public function run( ...$args ) {
		$settings          = $this->get_settings();
		$provider_id       = $settings['provider'] ?? Speech::ID;
		$provider_instance = $this->get_feature_provider_instance( $provider_id );
		$result            = '';

		if ( Speech::ID === $provider_instance::ID ) {
			/** @var Speech $provider_instance */
			return call_user_func_array(
				[ $provider_instance, 'synthesize_speech' ],
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
