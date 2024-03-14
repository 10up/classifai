<?php
/**
 * OpenAI Text to Speech integration
 */

namespace Classifai\Providers\OpenAI;

use Classifai\Providers\Provider;
use Classifai\Features\TextToSpeech as FeatureTextToSpeech;

class TextToSpeech extends Provider {
	use OpenAI;

	const ID = 'openai_text-to-speech';

	/**
	 * OpenAI Text to Speech URL.
	 *
	 * @var string
	 */
	protected $api_url = 'https://api.openai.com/v1/audio/speech';

	/**
	 * OpenAI TextToSpeech constructor.
	 *
	 * @param \Classifai\Features\Feature $feature_instance The feature instance.
	 */
	public function __construct( $feature_instance = null ) {
		$this->feature_instance = $feature_instance;
	}

	/**
	 * Register settings for the provider.
	 */
	public function render_provider_fields(): void {
		$settings = $this->feature_instance->get_settings( static::ID );

		add_settings_field(
			static::ID . '_api_key',
			esc_html__( 'API Key', 'classifai' ),
			[ $this->feature_instance, 'render_input' ],
			$this->feature_instance->get_option_name(),
			$this->feature_instance->get_option_name() . '_section',
			[
				'option_index'  => static::ID,
				'label_for'     => 'api_key',
				'input_type'    => 'password',
				'default_value' => $settings['api_key'],
				'class'         => 'classifai-provider-field hidden provider-scope-' . static::ID,
				'description'   => sprintf(
					wp_kses(
						/* translators: %1$s is replaced with the OpenAI sign up URL */
						__( 'Don\'t have an OpenAI account yet? <a title="Sign up for an OpenAI account" href="%1$s">Sign up for one</a> in order to get your API key.', 'classifai' ),
						[
							'a' => [
								'href'  => [],
								'title' => [],
							],
						]
					),
					esc_url( 'https://platform.openai.com/signup' )
				),
			]
		);

		add_settings_field(
			static::ID . '_tts_model',
			esc_html__( 'TTS model', 'classifai' ),
			[ $this->feature_instance, 'render_select' ],
			$this->feature_instance->get_option_name(),
			$this->feature_instance->get_option_name() . '_section',
			[
				'option_index'  => static::ID,
				'label_for'     => 'tts_model',
				'options'       => [
					'tts-1'    => __( 'Text-to-speech 1 (Optimized for speed)', 'classifai' ),
					'tts-1-hd' => __( 'Text-to-speech 1 HD (Optimized for quality)', 'classifai' ),
				],
				'default_value' => $settings['tts_model'],
				'description'   => __( 'Select a model depending on your requirement.', 'classifai' ),
				'class'         => 'classifai-provider-field hidden provider-scope-' . static::ID,
			]
		);

		add_settings_field(
			static::ID . '_voice',
			esc_html__( 'Voice', 'classifai' ),
			[ $this->feature_instance, 'render_select' ],
			$this->feature_instance->get_option_name(),
			$this->feature_instance->get_option_name() . '_section',
			[
				'option_index'  => static::ID,
				'label_for'     => 'voice',
				'options'       => [
					'alloy'   => __( 'Alloy (male)', 'classifai' ),
					'echo'    => __( 'Echo (male)', 'classifai' ),
					'fable'   => __( 'Fable (male)', 'classifai' ),
					'onyx'    => __( 'Onyx (male)', 'classifai' ),
					'nova'    => __( 'Nova (female)', 'classifai' ),
					'shimmer' => __( 'Shimmer (female)', 'classifai' ),
				],
				'default_value' => $settings['voice'],
				'description'   => __( 'Select the speech voice.', 'classifai' ),
				'class'         => 'classifai-provider-field hidden provider-scope-' . static::ID,
			]
		);

		add_settings_field(
			static::ID . '_format',
			esc_html__( 'Audio format', 'classifai' ),
			[ $this->feature_instance, 'render_select' ],
			$this->feature_instance->get_option_name(),
			$this->feature_instance->get_option_name() . '_section',
			[
				'option_index'  => static::ID,
				'label_for'     => 'format',
				'options'       => [
					'mp3'  => __( '.mp3', 'classifai' ),
					'opus' => __( '.opus', 'classifai' ),
					'aac'  => __( '.aac', 'classifai' ),
					'flac' => __( '.flac', 'classifai' ),
					'wav'  => __( '.wav', 'classifai' ),
					'pcm'  => __( '.pcm', 'classifai' ),
				],
				'default_value' => $settings['format'],
				'description'   => __( 'Select the audio format.', 'classifai' ),
				'class'         => 'classifai-provider-field hidden provider-scope-' . static::ID,
			]
		);

		add_settings_field(
			static::ID . '_speed',
			esc_html__( 'Audio speed', 'classifai' ),
			[ $this->feature_instance, 'render_input' ],
			$this->feature_instance->get_option_name(),
			$this->feature_instance->get_option_name() . '_section',
			[
				'option_index'  => static::ID,
				'label_for'     => 'speed',
				'input_type'    => 'number',
				'min'           => 0.25,
				'max'           => 4,
				'step'          => 0.25,
				'default_value' => $settings['speed'],
				'description'   => __( 'Select the speed of the generated audio.', 'classifai' ),
				'class'         => 'classifai-provider-field hidden provider-scope-' . static::ID,
			]
		);
	}

	/**
	 * Returns the default settings for the provider.
	 *
	 * @return array
	 */
	public function get_default_provider_settings(): array {
		$common_settings = [
			'api_key'       => '',
			'authenticated' => false,
		];

		switch ( $this->feature_instance::ID ) {
			case FeatureTextToSpeech::ID:
				return array_merge(
					$common_settings,
					[
						'tts_model' => 'tts-1',
						'voice'     => 'voice',
						'format'    => 'mp3',
						'speed'     => 1,
					]
				);
		}

		return $common_settings;
	}

	/**
	 * Sanitization for the options being saved.
	 *
	 * @param array $new_settings Array of settings about to be saved.
	 * @return array The sanitized settings to be saved.
	 */
	public function sanitize_settings( array $new_settings ): array {
		$settings                                    = $this->feature_instance->get_settings();
		$api_key_settings                            = $this->sanitize_api_key_settings( $new_settings, $settings );
		$new_settings[ static::ID ]['api_key']       = $api_key_settings[ static::ID ]['api_key'];
		$new_settings[ static::ID ]['authenticated'] = $api_key_settings[ static::ID ]['authenticated'];

		if ( $this->feature_instance instanceof FeatureTextToSpeech ) {
			if ( in_array( $new_settings[ static::ID ]['tts_model'], [ 'tts-1', 'tts-1-hd' ], true ) ) {
				$new_settings[ static::ID ]['tts_model'] = sanitize_text_field( $new_settings[ static::ID ]['tts_model'] );
			}

			if ( in_array( $new_settings[ static::ID ]['voice'], [ 'alloy', 'echo', 'fable', 'onyx', 'nova', 'shimmer' ], true ) ) {
				$new_settings[ static::ID ]['voice'] = sanitize_text_field( $new_settings[ static::ID ]['voice'] );
			}

			if ( in_array( $new_settings[ static::ID ]['format'], [ 'mp3', 'opus', 'aac', 'flac', 'wav', 'pcm' ], true ) ) {
				$new_settings[ static::ID ]['format'] = sanitize_text_field( $new_settings[ static::ID ]['format'] );
			}

			$speed = filter_var( $new_settings[ static::ID ]['speed'] ?? 1.0, FILTER_SANITIZE_NUMBER_FLOAT );

			if ( 0.25 <= $speed || 4.00 >= $speed ) {
				$new_settings[ static::ID ]['speed'] = sanitize_text_field( $new_settings[ static::ID ]['speed'] );
			}
		}

		return $new_settings;
	}
}
