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
	private static $profiles = array(
		'openai'                  => array(
			'provider_ids'      => array(
				'openai_chatgpt',
				'openai_embeddings',
				'openai_moderation',
				'openai_dalle',
				'openai_whisper',
				'openai_text_to_speech',
			),
			'credential_fields' => array( 'api_key', 'authenticated' ),
			'sensitive_fields'  => array( 'api_key' ),
			'label'             => 'OpenAI',
		),
		'googleai'                => array(
			'provider_ids'      => array( 'googleai_gemini_api', 'googleai_images' ),
			'credential_fields' => array( 'api_key', 'authenticated' ),
			'sensitive_fields'  => array( 'api_key' ),
			'label'             => 'Google AI',
		),
		'azure_openai'            => array(
			'provider_ids'      => array( 'azure_openai' ),
			'credential_fields' => array( 'endpoint_url', 'api_key', 'deployment', 'authenticated' ),
			'sensitive_fields'  => array( 'api_key' ),
			'label'             => 'Azure OpenAI',
		),
		'azure_openai_embeddings' => array(
			'provider_ids'      => array( 'azure_openai_embeddings' ),
			'credential_fields' => array( 'endpoint_url', 'api_key', 'deployment', 'authenticated' ),
			'sensitive_fields'  => array( 'api_key' ),
			'label'             => 'Azure OpenAI Embeddings',
		),
		'ms_computer_vision'      => array(
			'provider_ids'      => array( 'ms_computer_vision' ),
			'credential_fields' => array( 'endpoint_url', 'api_key', 'authenticated' ),
			'sensitive_fields'  => array( 'api_key' ),
			'label'             => 'Azure AI Vision',
		),
		'ms_azure_text_to_speech' => array(
			'provider_ids'      => array( 'ms_azure_text_to_speech' ),
			'credential_fields' => array( 'endpoint_url', 'api_key', 'authenticated' ),
			'sensitive_fields'  => array( 'api_key' ),
			'label'             => 'Azure Text to Speech',
		),
		'ibm_watson_nlu'          => array(
			'provider_ids'      => array( 'ibm_watson_nlu' ),
			'credential_fields' => array( 'endpoint_url', 'apikey', 'username', 'password', 'authenticated' ),
			'sensitive_fields'  => array( 'apikey', 'password' ),
			'label'             => 'IBM Watson NLU',
		),
		'xai_grok'                => array(
			'provider_ids'      => array( 'xai_grok' ),
			'credential_fields' => array( 'api_key', 'authenticated' ),
			'sensitive_fields'  => array( 'api_key' ),
			'label'             => 'XAI Grok',
		),
		'aws_polly'               => array(
			'provider_ids'      => array( 'aws_polly' ),
			'credential_fields' => array( 'access_key_id', 'secret_access_key', 'aws_region', 'authenticated' ),
			'sensitive_fields'  => array( 'secret_access_key' ),
			'label'             => 'AWS Polly',
		),
		'elevenlabs'              => array(
			'provider_ids'      => array( 'elevenlabs_text_to_speech', 'elevenlabs_speech_to_text' ),
			'credential_fields' => array( 'api_key', 'authenticated' ),
			'sensitive_fields'  => array( 'api_key' ),
			'label'             => 'ElevenLabs',
		),
		'togetherai_image'        => array(
			'provider_ids'      => array( 'togetherai_image' ),
			'credential_fields' => array( 'api_key', 'authenticated' ),
			'sensitive_fields'  => array( 'api_key' ),
			'label'             => 'Together AI',
		),
		'stable_diffusion'        => array(
			'provider_ids'      => array( 'stable_diffusion' ),
			'credential_fields' => array( 'endpoint_url', 'authenticated' ),
			'sensitive_fields'  => array(),
			'label'             => 'Stable Diffusion',
		),
		'ollama'                  => array(
			'provider_ids'      => array( 'ollama', 'ollama_embeddings', 'ollama_multimodal' ),
			'credential_fields' => array( 'endpoint_url', 'authenticated' ),
			'sensitive_fields'  => array(),
			'label'             => 'Ollama',
		),
		'chrome_ai'               => array(
			'provider_ids'      => array( 'chrome_ai' ),
			'credential_fields' => array(),
			'sensitive_fields'  => array(),
			'label'             => 'Chrome AI',
		),
		'api_usage_tracking'      => array(
			'provider_ids'      => array( 'openai_usage_tracking' ),
			'credential_fields' => array( 'api_key', 'authenticated' ),
			'sensitive_fields'  => array( 'api_key' ),
			'label'             => 'OpenAI Usage Tracking',
		),
	);

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
		return $profiles[ $profile_id ]['provider_ids'] ?? array();
	}

	/**
	 * Get credential field names for a profile (keys to store/merge).
	 *
	 * @param string $profile_id Profile ID.
	 * @return array List of field names.
	 */
	public static function get_credential_fields( string $profile_id ): array {
		$profiles = self::get_all_profiles();
		return $profiles[ $profile_id ]['credential_fields'] ?? array();
	}

	/**
	 * Get sensitive field names for a profile.
	 *
	 * @param string $profile_id Profile ID.
	 * @return array List of field names.
	 */
	public static function get_sensitive_fields( string $profile_id ): array {
		$profiles = self::get_all_profiles();
		return $profiles[ $profile_id ]['sensitive_fields'] ?? array();
	}
}
