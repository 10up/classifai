<?php
/**
 * Google AI shared functionality
 */

namespace Classifai\Providers\GoogleAI;

use Classifai\Providers\GoogleAI\APIRequest;
use WP_Error;

trait GoogleAI {

	/**
	 * OpenAI model URL
	 *
	 * @var string
	 */
	protected $model_url = 'https://generativelanguage.googleapis.com/v1beta/models';

	/**
	 * Sanitize the API key, showing an error message if needed.
	 *
	 * @param array $new_settings Incoming settings, if any.
	 * @param array $settings     Current settings, if any.
	 * @return array
	 */
	public function sanitize_api_key_settings( array $new_settings = [], array $settings = [] ): array {
		$models = $this->get_models( $new_settings[ static::ID ]['api_key'] ?? '' );

		$new_settings[ static::ID ]['authenticated'] = $settings[ static::ID ]['authenticated'];

		if ( is_wp_error( $models ) ) {
			$new_settings[ static::ID ]['authenticated'] = false;
			$error_message                               = $models->get_error_message();

			// Add an error message.
			add_settings_error(
				'api_key',
				'classifai-auth',
				$error_message,
				'error'
			);
		} else {
			$new_settings[ static::ID ]['authenticated'] = true;
			$new_settings[ static::ID ]['models']        = $models;
		}

		$new_settings[ static::ID ]['api_key'] = sanitize_text_field( $new_settings[ static::ID ]['api_key'] ?? $settings[ static::ID ]['api_key'] );

		return $new_settings;
	}

	/**
	 * Get the available models.
	 * This function also authenticates the credentials.
	 *
	 * @param string $api_key Api Key.
	 * @return array|WP_Error
	 */
	protected function get_models( string $api_key = '' ) {
		// Check that we have credentials before hitting the API.
		if ( empty( $api_key ) ) {
			return new WP_Error( 'auth', esc_html__( 'Please enter your Google AI (Gemini) key.', 'classifai' ) );
		}

		// Make request to ensure credentials work.
		$request  = new APIRequest( $api_key );
		$response = $request->get( $this->model_url, [ 'use_vip' => true ] );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$models = [];
		foreach ( $response['models'] as $model ) {
			if ( 'googleai_gemini_api' === self::ID && is_array( $model['supportedGenerationMethods'] ) && in_array( 'generateContent', $model['supportedGenerationMethods'], true ) ) {
				$models[ $model['name'] ] = $model['displayName'];
			}
		}

		/**
		 * Filter the models returned by the Google AI API.
		 *
		 * @since 3.6.0
		 * @hook classifai_googleai_models
		 *
		 * @param {array} $models The models.
		 * @param {string} $id The provider ID.
		 *
		 * @return {array} The models.
		 */
		return apply_filters( 'classifai_googleai_models', $models, self::ID );
	}
}
