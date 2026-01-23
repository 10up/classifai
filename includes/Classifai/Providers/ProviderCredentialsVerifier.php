<?php
/**
 * Verifies global Provider credentials by running the same checks
 * used during Feature-level sanitization. Used when saving from the
 * Providers tab.
 *
 * @since x.x.x
 */

namespace Classifai\Providers;

use Classifai\Providers\Provider;
use Classifai\Providers\OpenAI\ChatGPT;
use Classifai\Providers\ElevenLabs\TextToSpeech as ElevenLabsTextToSpeech;
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
			case 'elevenlabs':
				return self::verify_provider( new ElevenLabsTextToSpeech(), $config );
			case 'openai':
				return self::verify_provider( new ChatGPT(), $config ); // TODO: finish this but would also be great to pull in verification from the Provider to avoid duplication. Or bring that all in here.
			case 'ollama':
				return self::verify_provider( new Ollama(), $config );
			default:
				return [
					'authenticated' => false,
					'error'         => null,
				];
		}
	}

	/**
	 * Verify provider credentials.
	 *
	 * @param Provider $provider The provider.
	 * @param array    $config Provider config containing credentials.
	 * @return array{ authenticated: bool, error: WP_Error|null }
	 */
	private static function verify_provider( Provider $provider, array $config ): array {
		$authenticated = $provider->authenticate_credentials( [ $provider::ID => $config ] );

		if ( is_wp_error( $authenticated ) ) {
			$error = $authenticated;

			if ( 'openai_chatgpt' === $provider::ID ) {
				// For response code 429, credentials are valid but rate limit is reached.
				if ( 429 === (int) $authenticated->get_error_code() ) {
					return [
						'authenticated' => true,
						'error'         => $authenticated,
					];
				} else {
					return [
						'authenticated' => false,
						'error'         => $authenticated,
					];
				}
			}

			if ( 'ollama' === $provider::ID ) {
				$error = new WP_Error( $authenticated->get_error_code(), esc_html__( 'Error making request, please ensure the Ollama service is running', 'classifai' ), );
			}

			return [
				'authenticated' => false,
				'error'         => $error,
			];
		}

		return [
			'authenticated' => true,
			'error'         => null,
		];
	}
}
