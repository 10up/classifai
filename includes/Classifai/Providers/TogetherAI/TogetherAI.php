<?php
/**
 * Together AI shared functionality
 */

namespace Classifai\Providers\TogetherAI;

use Classifai\Providers\OpenAI\APIRequest;
use WP_Error;

trait TogetherAI {

	/**
	 * Together AI base API URL
	 *
	 * @var string
	 */
	protected $api_url = 'https://api.together.xyz/v1';

	/**
	 * Together AI model path
	 *
	 * @var string
	 */
	protected $model_path = 'models';

	/**
	 * Build the API URL.
	 *
	 * @param string $path The path to the API endpoint.
	 * @return string
	 */
	public function get_api_url( string $path = '' ): string {
		/**
		 * Filter the Together AI API URL.
		 *
		 * @since 3.6.0
		 * @hook classifai_togetherai_api_url
		 *
		 * @param string $url The default API URL.
		 * @param string $path The path to the API endpoint.
		 *
		 * @return string The API URL.
		 */
		return apply_filters( 'classifai_togetherai_api_url', trailingslashit( $this->api_url ) . $path, $path );
	}

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
		$new_settings[ static::ID ]['models']        = $settings[ static::ID ]['models'];

		if ( is_wp_error( $models ) ) {
			$new_settings[ static::ID ]['authenticated'] = false;
			$new_settings[ static::ID ]['models']        = [];
			$error_message                               = $models->get_error_message();

			// For response code 429, credentials are valid but rate limit is reached.
			if ( 429 === (int) $models->get_error_code() ) {
				$new_settings[ static::ID ]['authenticated'] = true;
				$new_settings[ static::ID ]['models']        = $settings[ static::ID ]['models'];
			}

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
	 *
	 * @param string $api_key The API key.
	 * @return array|WP_Error
	 */
	protected function get_models( string $api_key = '' ) {
		// Check that we have credentials before hitting the API.
		if ( empty( $api_key ) ) {
			return new WP_Error( 'auth', esc_html__( 'Please enter your Together AI API key.', 'classifai' ) );
		}

		$request  = new APIRequest( $api_key );
		$response = $request->get( $this->get_api_url( $this->model_path ), [ 'use_vip' => true ] );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		// Get the model data we need.
		$models = array_map(
			fn( $model ) => [
				'id'             => $model['id'] ?? '',
				'type'           => $model['type'] ?? '',
				'display_name'   => $model['display_name'] ?? '',
				'context_length' => $model['context_length'] ?? '',
			],
			$response
		);

		return $models;
	}
}
