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
		$authenticated = $this->authenticate_credentials( $new_settings );

		$new_settings[ static::ID ]['authenticated'] = $settings[ static::ID ]['authenticated'];

		if ( is_wp_error( $authenticated ) ) {
			$new_settings[ static::ID ]['authenticated'] = false;
			$error_message                               = $authenticated->get_error_message();

			// Add an error message.
			add_settings_error(
				'api_key',
				'classifai-auth',
				$error_message,
				'error'
			);
		} else {
			$new_settings[ static::ID ]['authenticated'] = true;
		}

		$new_settings[ static::ID ]['api_key'] = sanitize_text_field( $new_settings[ static::ID ]['api_key'] ?? $settings[ static::ID ]['api_key'] );

		return $new_settings;
	}

	/**
	 * Authenticate our credentials.
	 *
	 * @param array $settings Settings being saved.
	 * @return bool|WP_Error
	 */
	public function authenticate_credentials( array $settings = [] ) {
		// Make request to ensure credentials work.
		$request  = new APIRequest( '', $this->feature_instance::ID, $this, $settings );
		$response = $request->get( $this->model_url, [ 'use_vip' => true ] );

		return ! is_wp_error( $response ) ? true : $response;
	}

	/**
	 * Get the available models.
	 *
	 * @param array $settings Settings being saved.
	 * @return array|WP_Error
	 */
	protected function get_models( array $settings = [] ) {
		// Make request to ensure credentials work.
		$request  = new APIRequest( '', $this->feature_instance::ID, $this, $settings );
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
