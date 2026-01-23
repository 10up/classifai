<?php
/**
 * Verifies global Provider credentials by running the same checks
 * used during Feature-level sanitization. Used when saving from the
 * Providers tab.
 *
 * @since x.x.x
 */

namespace Classifai\Providers;

use Classifai\Providers\AWS\AmazonPolly;
use Classifai\Providers\Azure\OpenAI as AzureOpenAI;
use Classifai\Providers\Azure\Embeddings as AzureEmbeddings;
use Classifai\Providers\Azure\ComputerVision;
use Classifai\Providers\Azure\Speech as AzureSpeech;
use Classifai\Providers\ElevenLabs\TextToSpeech as ElevenLabsTextToSpeech;
use Classifai\Providers\GoogleAI\GeminiAPI;
use Classifai\Providers\Localhost\Ollama;
use Classifai\Providers\Localhost\StableDiffusion;
use Classifai\Providers\OpenAI\ChatGPT;
use Classifai\Providers\Provider;
use Classifai\Providers\TogetherAI\Images as TogetherAIImages;
use Classifai\Providers\Watson\NLU;
use Classifai\Providers\XAI\Grok;
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
			case 'aws_polly':
				return self::verify_provider( new AmazonPolly(), $config );
			case 'azure_openai':
				return self::verify_provider( new AzureOpenAI(), $config );
			case 'azure_openai_embeddings':
				return self::verify_provider( new AzureEmbeddings(), $config );
			case 'elevenlabs':
				return self::verify_provider( new ElevenLabsTextToSpeech(), $config );
			case 'googleai':
				return self::verify_provider( new GeminiAPI(), $config );
			case 'ibm_watson_nlu':
				return self::verify_provider( new NLU(), $config );
			case 'ms_azure_text_to_speech':
				return self::verify_provider( new AzureSpeech(), $config );
			case 'ms_computer_vision':
				return self::verify_provider( new ComputerVision(), $config );
			case 'openai':
				return self::verify_provider( new ChatGPT(), $config );
			case 'ollama':
				return self::verify_provider( new Ollama(), $config );
			case 'stable_diffusion':
				return self::verify_provider( new StableDiffusion(), $config );
			case 'togetherai_image':
				return self::verify_provider( new TogetherAIImages(), $config );
			case 'xai_grok':
				return self::verify_provider( new Grok(), $config );
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
			'authenticated' => (bool) $authenticated,
			'error'         => (bool) $authenticated ? null : new WP_Error( 'auth', esc_html__( 'Error making request, please ensure the credentials are valid', 'classifai' ), ),
		];
	}
}
