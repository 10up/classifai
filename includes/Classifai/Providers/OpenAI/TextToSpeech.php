<?php
/**
 * OpenAI Text to Speech integration
 */

namespace Classifai\Providers\OpenAI;

use Classifai\Providers\Provider;
use Classifai\Features\TextToSpeech as FeatureTextToSpeech;
use WP_Error;

class TextToSpeech extends Provider {
	use OpenAI;

	const ID = 'openai_text_to_speech';

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
				'description'   => sprintf(
					wp_kses(
						__( 'Select a <a href="%s" title="OpenAI Text to Speech models" target="_blank">model</a> depending on your requirement.', 'classifai' ),
						[
							'a' => [
								'href'  => [],
								'title' => [],
							],
						],
					),
					esc_url( 'https://platform.openai.com/docs/models/tts' )
				),
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
				'description'   => sprintf(
					wp_kses(
						__( 'Select the speech <a href="%s" title="OpenAI Text to Speech models" target="_blank">voice</a>.', 'classifai' ),
						[
							'a' => [
								'href'  => [],
								'title' => [],
							],
						],
					),
					esc_url( 'https://platform.openai.com/docs/guides/text-to-speech/voice-options' )
				),
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
					'wav'  => __( '.wav', 'classifai' ),
				],
				'default_value' => $settings['format'],
				'description'   => __( 'Select the desired audio format.', 'classifai' ),
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
				'description'   => __( 'Select the desired speed of the generated audio.', 'classifai' ),
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

	/**
	 * Common entry point for all REST endpoints for this provider.
	 *
	 * @param int    $post_id       The post ID we're processing.
	 * @param string $route_to_call The name of the route we're going to be processing.
	 * @param array  $args          Optional arguments to pass to the route.
	 * @return array|string|WP_Error
	 */
	public function rest_endpoint_callback( $post_id, string $route_to_call = '', array $args = [] ) {
		if ( ! $post_id || ! get_post( $post_id ) ) {
			return new WP_Error( 'post_id_required', esc_html__( 'A valid post ID is required.', 'classifai' ) );
		}

		$route_to_call = strtolower( $route_to_call );
		$return        = '';

		// Handle all of our routes.
		switch ( $route_to_call ) {
			case 'synthesize':
				$return = $this->synthesize_speech( $post_id, $args );
				break;
		}

		return $return;
	}

	/**
	 * Synthesizes speech from a post item.
	 *
	 * @param int $post_id Post ID.
	 * @return string|WP_Error
	 */
	public function synthesize_speech( int $post_id ) {
		if ( empty( $post_id ) ) {
			return new WP_Error(
				'openai_text_to_speech_post_id_missing',
				esc_html__( 'Post ID missing.', 'classifai' )
			);
		}

		// We skip the user cap check if running under WP-CLI.
		if ( ! current_user_can( 'edit_post', $post_id ) && ( ! defined( 'WP_CLI' ) || ! WP_CLI ) ) {
			return new WP_Error(
				'openai_text_to_speech_user_not_authorized',
				esc_html__( 'Unauthorized user.', 'classifai' )
			);
		}

		$feature             = new FeatureTextToSpeech();
		$settings            = $feature->get_settings();
		$post_content        = $feature->normalize_post_content( $post_id );
		$content_hash        = get_post_meta( $post_id, FeatureTextToSpeech::AUDIO_HASH_KEY, true );
		$saved_attachment_id = (int) get_post_meta( $post_id, $feature::AUDIO_ID_KEY, true );

		// Don't regenerate the audio file it it already exists and the content hasn't changed.
		if ( $saved_attachment_id ) {

			// Check if the audio file exists.
			$audio_attachment_url = wp_get_attachment_url( $saved_attachment_id );

			if ( $audio_attachment_url && ! empty( $content_hash ) && ( md5( $post_content ) === $content_hash ) ) {
				return $saved_attachment_id;
			}
		}

		// Create the request body to synthesize speech from text.
		$request_body = array(
			'model'           => $settings[ static::ID ]['tts_model'],
			'voice'           => $settings[ static::ID ]['voice'],
			'response_format' => $settings[ static::ID ]['format'],
			'speed'           => (float) $settings[ static::ID ]['speed'],
			'input'           => $post_content,
		);

		// Request parameters.
		$request_params = array(
			'method'  => 'POST',
			'body'    => wp_json_encode( $request_body ),
			'timeout' => 60, // phpcs:ignore WordPressVIPMinimum.Performance.RemoteRequestTimeout.timeout_timeout
			'headers' => array(
				'Authorization' => 'Bearer ' . $settings[ static::ID ]['api_key'],
				'Content-Type'  => 'application/json',
			),
		);

		$response = wp_remote_post( $this->api_url, $request_params );

		if ( is_wp_error( $response ) ) {
			return new WP_Error(
				'openai_text_to_speech_http_error',
				esc_html( $response->get_error_message() )
			);
		}

		$code          = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );

		// return error if HTTP status code is not 200.
		if ( \WP_Http::OK !== $code ) {
			return new WP_Error(
				'openai_text_to_speech_unsuccessful_request',
				esc_html__( 'HTTP request unsuccessful.', 'classifai' )
			);
		}

		update_post_meta( $post_id, FeatureTextToSpeech::AUDIO_HASH_KEY, md5( $post_content ) );

		return $response_body;
	}
}
