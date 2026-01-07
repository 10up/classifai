<?php
/**
 * ElevenLabs Text to Speech integration
 */

namespace Classifai\Providers\ElevenLabs;

use Classifai\Features\TextToSpeech as FeatureTextToSpeech;
use Classifai\Providers\Provider;
use WP_Error;

class TextToSpeech extends Provider {
	use ElevenLabs;

	/**
	 * ID of the current provider.
	 *
	 * @var string
	 */
	const ID = 'elevenlabs_text_to_speech';

	/**
	 * ElevenLabs Text to Speech API path.
	 *
	 * @var string
	 */
	protected $api_path = 'text-to-speech';

	/**
	 * ElevenLabs Text to Speech constructor.
	 *
	 * @param \Classifai\Features\Feature $feature_instance The feature instance.
	 */
	public function __construct( $feature_instance = null ) {
		$this->feature_instance = $feature_instance;
	}

	/**
	 * Get the model name.
	 *
	 * @return string
	 */
	public function get_model(): string {
		$settings = $this->feature_instance->get_settings();
		$model    = $settings[ static::ID ]['model'] ?? 'eleven_multilingual_v2';

		/**
		 * Filter the model name.
		 *
		 * Useful if you want to change the model for certain use cases.
		 *
		 * @since 3.7.0
		 * @hook classifai_elevenlabs_text_to_speech_model
		 *
		 * @param string $model The current model to use.
		 *
		 * @return string The model to use.
		 */
		return apply_filters( 'classifai_elevenlabs_text_to_speech_model', $model );
	}

	/**
	 * Get the default settings for this provider.
	 *
	 * @return array
	 */
	public function get_default_provider_settings(): array {
		return [
			'api_key'       => '',
			'authenticated' => false,
			'model'         => 'eleven_multilingual_v2',
			'voice'         => '',
			'models'        => [],
			'voices'        => [],
		];
	}

	/**
	 * Sanitize the settings for this provider.
	 *
	 * @param array $new_settings New settings.
	 * @return array
	 */
	public function sanitize_settings( array $new_settings ): array {
		$settings         = $this->feature_instance->get_settings();
		$api_key_settings = $this->sanitize_api_key_settings( $new_settings, $settings );

		$new_settings[ static::ID ]['api_key']       = $api_key_settings[ static::ID ]['api_key'];
		$new_settings[ static::ID ]['authenticated'] = $api_key_settings[ static::ID ]['authenticated'];

		$new_settings[ static::ID ]['models'] = $api_key_settings[ static::ID ]['models'] ?? [];
		$new_settings[ static::ID ]['voices'] = $api_key_settings[ static::ID ]['voices'] ?? [];

		$new_settings[ static::ID ]['model'] = sanitize_text_field( $new_settings[ static::ID ]['model'] ?? $settings[ static::ID ]['model'] );
		$new_settings[ static::ID ]['voice'] = sanitize_text_field( $new_settings[ static::ID ]['voice'] ?? $settings[ static::ID ]['voice'] );

		// Ensure the model being saved is valid. If not valid or we don't have one, use the first model.
		if ( ! empty( $new_settings[ static::ID ]['models'] ) && ! in_array( $new_settings[ static::ID ]['model'], array_column( $new_settings[ static::ID ]['models'], 'id' ), true ) ) {
			$new_settings[ static::ID ]['model'] = array_column( $new_settings[ static::ID ]['models'], 'id' )[0];
		}

		// Ensure the voice being saved is valid. If not valid or we don't have one, use the first voice.
		if ( ! empty( $new_settings[ static::ID ]['voices'] ) && ! in_array( $new_settings[ static::ID ]['voice'], array_column( $new_settings[ static::ID ]['voices'], 'id' ), true ) ) {
			$new_settings[ static::ID ]['voice'] = array_column( $new_settings[ static::ID ]['voices'], 'id' )[0];
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
				$return = $this->synthesize_speech( $post_id );
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
				'elevenlabs_text_to_speech_post_id_missing',
				esc_html__( 'Post ID missing.', 'classifai' )
			);
		}

		// We skip the user cap check if running under WP-CLI.
		if ( ! current_user_can( 'edit_post', $post_id ) && ( ! defined( 'WP_CLI' ) || ! WP_CLI ) ) {
			return new WP_Error(
				'elevenlabs_text_to_speech_user_not_authorized',
				esc_html__( 'Unauthorized user.', 'classifai' )
			);
		}

		$feature              = new FeatureTextToSpeech();
		$settings             = $feature->get_settings();
		$settings             = $settings[ static::ID ];
		$post_content         = $feature->normalize_post_content( $post_id );
		$content_hash         = get_post_meta( $post_id, FeatureTextToSpeech::AUDIO_HASH_KEY, true );
		$saved_attachment_id  = (int) get_post_meta( $post_id, $feature::AUDIO_ID_KEY, true );
		$voice_id             = $settings['voice'] ?? $settings['voices'][0]['id'] ?? '';
		$max_characters_limit = $this->get_max_characters_limit();
		if ( mb_strlen( $post_content ) > $max_characters_limit ) {
			return new WP_Error(
				'elevenlabs_text_to_speech_content_too_long',
				// translators: %s is the max characters limit.
				sprintf( esc_html__( 'Character length should not exceed beyond %s characters.', 'classifai' ), $max_characters_limit )
			);
		}

		// Don't regenerate the audio file it it already exists and the content hasn't changed.
		if ( $saved_attachment_id ) {

			// Check if the audio file exists.
			$audio_attachment_url = wp_get_attachment_url( $saved_attachment_id );

			if ( $audio_attachment_url && ! empty( $content_hash ) && ( md5( $post_content ) === $content_hash ) ) {
				return $saved_attachment_id;
			}
		}

		/**
		 * Filter the request body before sending to ElevenLabs.
		 *
		 * @since 3.7.0
		 *
		 * @param array  $body         Request body that will be sent to ElevenLabs.
		 * @param int    $post_id      Post ID.
		 * @param string $post_content Post content.
		 *
		 * @return array Request body.
		 */
		$body = apply_filters(
			'classifai_elevenlabs_text_to_speech_request_body',
			[
				'text'     => $post_content,
				'model_id' => $this->get_model(),
			],
			$post_id,
			$post_content
		);

		// Make our API request.
		$response = $this->request(
			$this->get_api_url( $this->api_path . '/' . $voice_id ),
			$settings['api_key'] ?? '',
			'post',
			[
				'body'    => wp_json_encode( $body ),
				'headers' => [
					'Content-Type' => 'application/json',
				],
			]
		);

		if ( is_wp_error( $response ) ) {
			return new WP_Error(
				'elevenlabs_text_to_speech_http_error',
				wp_kses_post( $response->get_error_message() )
			);
		}

		$response_body = wp_remote_retrieve_body( $response );

		update_post_meta( $post_id, FeatureTextToSpeech::AUDIO_HASH_KEY, md5( $post_content ) );

		return $response_body;
	}

	/**
	 * Returns max text/character limit based on selected model.
	 *
	 * @return int Number of characters.
	 */
	private function get_max_characters_limit() {
		if ( empty( $this->feature_instance ) ) {
			$this->feature_instance = new FeatureTextToSpeech();
		}

		$settings = $this->feature_instance->get_settings();
		$model_id = $this->get_model();
		$models   = $settings[ static::ID ]['models'] ?? [];

		$selected_model = array_filter(
			$models,
			function ( $model ) use ( $model_id ) {
				return $model['id'] === $model_id;
			}
		);
		$selected_model = current( $selected_model );

		if ( ! empty( $selected_model ) && isset( $selected_model['max_text_length'] ) ) {
			return $selected_model['max_text_length'];
		}

		// Return default lowest text length from available models (Eleven v3).
		return 3000;
	}

	/**
	 * Returns the debug information for the provider settings.
	 *
	 * @return array
	 */
	public function get_debug_information(): array {
		$settings          = $this->feature_instance->get_settings();
		$provider_settings = $settings[ static::ID ];
		$debug_info        = [];

		if ( $this->feature_instance instanceof FeatureTextToSpeech ) {
			$debug_info[ __( 'Model', 'classifai' ) ] = $provider_settings['model'] ?? '';
			$debug_info[ __( 'Voice', 'classifai' ) ] = $provider_settings['voice'] ?? '';

			// We don't save the response transient because WP does not support serialized binary data to be inserted to the options.
		}

		return apply_filters(
			'classifai_' . self::ID . '_debug_information',
			$debug_info,
			$settings,
			$this->feature_instance
		);
	}
}
