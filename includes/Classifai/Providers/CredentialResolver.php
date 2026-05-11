<?php
/**
 * Credential resolution for Providers.
 *
 * Determines the effective credentials for a Provider by pulling from the
 * Feature-level config. Used at runtime when Providers need
 * api_key, endpoint_url, etc.
 *
 * @since 3.8.0
 */

namespace Classifai\Providers;

use Classifai\Providers\ProviderProfiles;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * CredentialResolver class.
 */
class CredentialResolver {

	/**
	 * Resolve credentials for a Provider.
	 *
	 * TODO: This will be updated in the future to support getting
	 * credentials from the global Provider scope.
	 *
	 * @param string $provider_id Capability-specific Provider ID (e.g. openai_chatgpt).
	 * @param array  $feature_provider_settings The Provider specific Feature settings.
	 * @return array Effective credentials for the Provider.
	 */
	public static function resolve( string $provider_id, array $feature_provider_settings = [] ): array {
		$profile_id = ProviderProfiles::get_profile_for_provider( $provider_id );
		if ( null === $profile_id ) {
			return $feature_provider_settings;
		}

		// Get the credential fields the profile supports.
		$credential_fields = ProviderProfiles::get_credential_fields( $profile_id );

		// Ensure the Feature-level Provider settings only has credential fields.
		return array_intersect_key( $feature_provider_settings, array_flip( $credential_fields ) );
	}
}
