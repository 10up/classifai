<?php
/**
 * Verifies global Provider credentials by running the same checks
 * used during Feature-level sanitization. Used when saving from the
 * Providers tab.
 *
 * @since x.x.x
 */

namespace Classifai\Providers;

use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ProviderCredentialsVerifier class.
 */
class ProviderCredentialsVerifier {

	/**
	 * Verify credentials for a profile.
	 *
	 * @param string $profile_id Profile ID (e.g. openai, ollama).
	 * @param array  $config     Config with credential fields (e.g. api_key, endpoint_url).
	 * @return array{ authenticated: bool, error: WP_Error|null }
	 */
	public static function verify( string $profile_id, array $config ): array {
		switch ( $profile_id ) {
			case 'openai':
				return self::verify_openai( $config );
			case 'ollama':
				return self::verify_ollama( $config );
			default:
				return [
					'authenticated' => false,
					'error'         => null,
				];
		}
	}

	/**
	 * Verify OpenAI API key via /v1/models.
	 *
	 * @param array $config Must contain 'api_key'.
	 * @return array{ authenticated: bool, error: WP_Error|null }
	 */
	private static function verify_openai( array $config ): array {
		$api_key = isset( $config['api_key'] ) ? trim( (string) $config['api_key'] ) : '';

		if ( '' === $api_key ) {
			return [
				'authenticated' => false,
				'error'         => new WP_Error( 'auth', __( 'Please enter your OpenAI API key.', 'classifai' ) ),
			];
		}

		$response = wp_remote_get(
			'https://api.openai.com/v1/models',
			[
				'headers' => [
					'Authorization' => 'Bearer ' . $api_key,
				],
				'timeout' => 15,
			]
		);

		if ( is_wp_error( $response ) ) {
			return [
				'authenticated' => false,
				'error'         => $response,
			];
		}

		$code = (int) wp_remote_retrieve_response_code( $response );

		// 200 = OK; 429 = rate limit (credentials are valid).
		if ( 200 === $code || 429 === $code ) {
			return [
				'authenticated' => true,
				'error'         => null,
			];
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		$msg  = isset( $body['error']['message'] ) ? $body['error']['message'] : __( 'OpenAI API request failed.', 'classifai' );

		return [
			'authenticated' => false,
			'error'         => new WP_Error( 'auth', $msg ),
		];
	}

	/**
	 * Verify Ollama endpoint via /api/tags.
	 *
	 * @param array $config Must contain 'endpoint_url'.
	 * @return array{ authenticated: bool, error: WP_Error|null }
	 */
	private static function verify_ollama( array $config ): array {
		$url = isset( $config['endpoint_url'] ) ? trim( (string) $config['endpoint_url'] ) : '';

		if ( '' === $url ) {
			return [
				'authenticated' => false,
				'error'         => new WP_Error( 'auth', __( 'Please enter an endpoint URL.', 'classifai' ) ),
			];
		}

		$url      = trailingslashit( esc_url_raw( $url ) ) . 'api/tags';
		$response = wp_remote_get( $url, [ 'timeout' => 10 ] );

		if ( is_wp_error( $response ) ) {
			return [
				'authenticated' => false,
				'error'         => $response,
			];
		}

		$code = (int) wp_remote_retrieve_response_code( $response );

		if ( 200 !== $code ) {
			return [
				'authenticated' => false,
				'error'         => new WP_Error( 'auth', __( 'Could not reach the Ollama endpoint.', 'classifai' ) ),
			];
		}

		$body       = json_decode( wp_remote_retrieve_body( $response ), true );
		$has_models = ! empty( $body['models'] ) && is_array( $body['models'] );

		return [
			'authenticated' => $has_models,
			'error'         => $has_models ? null : new WP_Error( 'auth', __( 'Ollama responded but returned no models.', 'classifai' ) ),
		];
	}
}
