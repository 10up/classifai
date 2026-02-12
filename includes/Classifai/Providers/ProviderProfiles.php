<?php
/**
 * Provider profile registry.
 *
 * Maps capability-specific Provider IDs (e.g. openai_chatgpt) to vendor-level
 * profiles (e.g. openai). Grouped profiles share one credential set across
 * multiple Providers; ungrouped profiles are 1:1 with a Provider.
 *
 * @since 3.8.0
 */

namespace Classifai\Providers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ProviderProfiles class.
 */
class ProviderProfiles {

	/**
	 * Profile definitions.
	 *
	 * Grouped profiles (OpenAI, Ollama, ElevenLabs, Google AI) share credentials.
	 * Ungrouped (e.g. Azure, AWS) use distinct endpoint/key per capability.
	 *
	 * @var array
	 */
	private static $profiles = [
		'openai'                  => [
			'provider_ids'      => [
				'openai_chatgpt',
				'openai_embeddings',
				'openai_moderation',
				'openai_dalle',
				'openai_whisper',
				'openai_text_to_speech',
			],
			'credential_fields' => [ 'api_key', 'authenticated' ],
			'sensitive_fields'  => [ 'api_key' ],
			'label'             => 'OpenAI',
		],
		'googleai'                => [
			'provider_ids'      => [ 'googleai_gemini_api', 'googleai_images' ],
			'credential_fields' => [ 'api_key', 'authenticated' ],
			'sensitive_fields'  => [ 'api_key' ],
			'label'             => 'Google AI',
		],
		'azure_openai'            => [
			'provider_ids'      => [ 'azure_openai' ],
			'credential_fields' => [ 'endpoint_url', 'api_key', 'deployment', 'authenticated' ],
			'sensitive_fields'  => [ 'api_key' ],
			'label'             => 'Azure OpenAI',
		],
		'azure_openai_embeddings' => [
			'provider_ids'      => [ 'azure_openai_embeddings' ],
			'credential_fields' => [ 'endpoint_url', 'api_key', 'deployment', 'authenticated' ],
			'sensitive_fields'  => [ 'api_key' ],
			'label'             => 'Azure OpenAI Embeddings',
		],
		'ms_computer_vision'      => [
			'provider_ids'      => [ 'ms_computer_vision' ],
			'credential_fields' => [ 'endpoint_url', 'api_key', 'authenticated' ],
			'sensitive_fields'  => [ 'api_key' ],
			'label'             => 'Azure AI Vision',
		],
		'ms_azure_text_to_speech' => [
			'provider_ids'      => [ 'ms_azure_text_to_speech' ],
			'credential_fields' => [ 'endpoint_url', 'api_key', 'authenticated' ],
			'sensitive_fields'  => [ 'api_key' ],
			'label'             => 'Azure Text to Speech',
		],
		'ibm_watson_nlu'          => [
			'provider_ids'      => [ 'ibm_watson_nlu' ],
			'credential_fields' => [ 'endpoint_url', 'apikey', 'username', 'password', 'authenticated' ],
			'sensitive_fields'  => [ 'apikey', 'password' ],
			'label'             => 'IBM Watson NLU',
		],
		'xai_grok'                => [
			'provider_ids'      => [ 'xai_grok' ],
			'credential_fields' => [ 'api_key', 'authenticated' ],
			'sensitive_fields'  => [ 'api_key' ],
			'label'             => 'XAI Grok',
		],
		'aws_polly'               => [
			'provider_ids'      => [ 'aws_polly' ],
			'credential_fields' => [ 'access_key_id', 'secret_access_key', 'aws_region', 'authenticated' ],
			'sensitive_fields'  => [ 'secret_access_key' ],
			'label'             => 'AWS Polly',
		],
		'elevenlabs'              => [
			'provider_ids'      => [ 'elevenlabs_text_to_speech', 'elevenlabs_speech_to_text' ],
			'credential_fields' => [ 'api_key', 'authenticated' ],
			'sensitive_fields'  => [ 'api_key' ],
			'label'             => 'ElevenLabs',
		],
		'togetherai_image'        => [
			'provider_ids'      => [ 'togetherai_image' ],
			'credential_fields' => [ 'api_key', 'authenticated' ],
			'sensitive_fields'  => [ 'api_key' ],
			'label'             => 'Together AI',
		],
		'stable_diffusion'        => [
			'provider_ids'      => [ 'stable_diffusion' ],
			'credential_fields' => [ 'endpoint_url', 'authenticated' ],
			'sensitive_fields'  => [],
			'label'             => 'Stable Diffusion',
		],
		'ollama'                  => [
			'provider_ids'      => [ 'ollama', 'ollama_embeddings', 'ollama_multimodal' ],
			'credential_fields' => [ 'endpoint_url', 'authenticated' ],
			'sensitive_fields'  => [],
			'label'             => 'Ollama',
		],
		'chrome_ai'               => [
			'provider_ids'      => [ 'chrome_ai' ],
			'credential_fields' => [],
			'sensitive_fields'  => [],
			'label'             => 'Chrome AI',
		],
	];

	/**
	 * Get all profile definitions.
	 *
	 * @return array Associative array of profile_id =>
	 * [ 'provider_ids', 'credential_fields' ].
	 */
	public static function get_all_profiles(): array {
		/**
		 * Filter the Provider profiles registry.
		 *
		 * @since 3.8.0
		 * @hook classifai_provider_profiles
		 *
		 * @param array $profiles Profile definitions.
		 *
		 * @return array Filtered profiles.
		 */
		return apply_filters( 'classifai_provider_profiles', self::$profiles );
	}

	/**
	 * Get the profile ID for a capability-specific Provider ID.
	 *
	 * @param string $provider_id Provider ID (e.g. openai_chatgpt, azure_openai).
	 * @return string|null Profile ID, or null if not found.
	 */
	public static function get_profile_for_provider( string $provider_id ): ?string {
		$profiles = self::get_all_profiles();
		foreach ( $profiles as $profile_id => $def ) {
			if ( in_array( $provider_id, $def['provider_ids'], true ) ) {
				return $profile_id;
			}
		}

		return null;
	}

	/**
	 * Get Provider IDs that belong to a profile.
	 *
	 * @param string $profile_id Profile ID.
	 * @return array List of Provider IDs.
	 */
	public static function get_provider_ids_for_profile( string $profile_id ): array {
		$profiles = self::get_all_profiles();
		return $profiles[ $profile_id ]['provider_ids'] ?? [];
	}

	/**
	 * Get credential field names for a profile (keys to store/merge).
	 *
	 * @param string $profile_id Profile ID.
	 * @return array List of field names.
	 */
	public static function get_credential_fields( string $profile_id ): array {
		$profiles = self::get_all_profiles();
		return $profiles[ $profile_id ]['credential_fields'] ?? [];
	}

	/**
	 * Get sensitive field names for a profile.
	 *
	 * @param string $profile_id Profile ID.
	 * @return array List of field names.
	 */
	public static function get_sensitive_fields( string $profile_id ): array {
		$profiles = self::get_all_profiles();
		return $profiles[ $profile_id ]['sensitive_fields'] ?? [];
	}
}
