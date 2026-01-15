<?php
/**
 * Helper class for credential management functionality.
 *
 * @package Classifai\Helpers
 */

namespace Classifai\Helpers;

/**
 * Credentials class.
 *
 * Centralized credential management for all Providers.
 * Allows programmatic override of credentials via filters for integration
 * with external secret management services.
 *
 * @since x.x.x
 */
class Credentials {

	/**
	 * Get credentials for a Provider.
	 *
	 * @param string $provider_id The Provider ID (e.g., 'azure_openai', 'openai_chatgpt').
	 * @param string $feature_id  The Feature ID (e.g., 'feature_title_generation').
	 * @param array  $settings    The Provider settings from the database.
	 * @return array Filtered credentials array.
	 */
	public static function get_credentials( string $provider_id, string $feature_id, array $settings ): array {
		/**
		 * Filter Provider credentials before making an API request.
		 *
		 * This is the primary hook for integrating external secret management
		 * services like Azure Key Vault, AWS Secrets Manager, or HashiCorp Vault.
		 *
		 * @since x.x.x
		 * @hook classifai_provider_credentials
		 *
		 * @param array  $credentials The credentials array from settings.
		 * @param string $provider_id The Provider ID (e.g., 'azure_openai', 'openai_chatgpt').
		 * @param string $feature_id  The ID of the Feature making the request.
		 *
		 * @return array Filtered credentials array.
		 */
		return apply_filters(
			'classifai_provider_credentials',
			$settings,
			$provider_id,
			$feature_id
		);
	}

	/**
	 * Get a specific credential value.
	 *
	 * @param string $provider_id    The Provider ID.
	 * @param string $feature_id     The Feature ID.
	 * @param array  $settings       The Provider settings.
	 * @param string $credential_key The specific credential key (e.g., 'api_key').
	 * @return mixed The credential value.
	 */
	public static function get_credential(
		string $provider_id,
		string $feature_id,
		array $settings,
		string $credential_key
	) {
		$credentials = self::get_credentials( $provider_id, $feature_id, $settings );
		return $credentials[ $credential_key ] ?? '';
	}
}
