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
		$authenticated = $this->authenticate_credentials( $new_settings[ static::ID ]['api_key'] ?? '' );

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
	 * @param string $api_key Api Key.
	 * @return bool|WP_Error
	 */
	protected function authenticate_credentials( string $api_key = '' ) {
		// Check that we have credentials before hitting the API.
		if ( empty( $api_key ) ) {
			return new WP_Error( 'auth', esc_html__( 'Please enter your Google AI (Gemini) key.', 'classifai' ) );
		}

		// Make request to ensure credentials work.
		$request  = new APIRequest( $api_key );
		$response = $request->get( $this->model_url );

		return ! is_wp_error( $response ) ? true : $response;
	}
}
