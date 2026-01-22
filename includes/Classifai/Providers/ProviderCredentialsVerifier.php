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
use Classifai\Providers\Localhost\Ollama;
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
	 * Verify Ollama endpoint.
	 *
	 * @param array $config Provider config containing credentials.
	 * @return array{ authenticated: bool, error: WP_Error|null }
	 */
	private static function verify_ollama( array $config ): array {
		$provider = new Ollama();
		$models   = $provider->get_models( $config );

		if ( empty( $models ) ) {
			return [
				'authenticated' => false,
				'error'         => new WP_Error( 'auth', esc_html__( 'Error making request, please ensure the Ollama service is running', 'classifai' ), ),
			];
		}

		return [
			'authenticated' => true,
			'error'         => null,
		];
	}
}
