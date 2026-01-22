<?php
/**
 * Verifies global Provider credentials by running the same checks
 * used during Feature-level sanitization. Used when saving from the
 * Providers tab.
 *
 * @since x.x.x
 */

namespace Classifai\Providers;

use Classifai\Providers\OpenAI\ChatGPT;
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
		// TODO: pass config through our credentials resolver so they can be filtered. Or this may happen when we verify credentials.
		switch ( $profile_id ) {
			case 'openai':
				return self::verify_openai( $config ); // TODO: finish this but would also be great to pull in verification from the Provider to avoid duplication. Or bring that all in here.
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
	 * Verify OpenAI API key.
	 *
	 * @param array $config Provider config containing credentials.
	 * @return array{ authenticated: bool, error: WP_Error|null }
	 */
	private static function verify_openai( array $config ): array {
		$provider      = new ChatGPT();
		$authenticated = $provider->authenticate_credentials( [ $provider::ID => $config ] );

		if ( is_wp_error( $authenticated ) ) {
			// For response code 429, credentials are valid but rate limit is reached.
			if ( 429 === (int) $authenticated->get_error_code() ) {
				return [
					'authenticated' => true,
					'error'         => new WP_Error( 'auth', str_replace( 'plan and billing details', '<a href="https://platform.openai.com/account/billing/overview" target="_blank" rel="noopener">plan and billing details</a>', $authenticated->get_error_message() ) ),
				];
			} else {
				return [
					'authenticated' => false,
					'error'         => new WP_Error( 'auth', str_replace( 'https://platform.openai.com/account/api-keys', '<a href="https://platform.openai.com/account/api-keys" target="_blank" rel="noopener">https://platform.openai.com/account/api-keys</a>', $authenticated->get_error_message() ) ),
				];
			}
		}

		return [
			'authenticated' => true,
			'error'         => null,
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
