<?php
/**
 * Credential resolution for Providers.
 *
 * Determines the effective credentials for a Provider by preferring Feature-level
 * overrides over global Provider config. Used at runtime when Providers need
 * api_key, endpoint_url, etc.
 *
 * @since x.x.x
 */

namespace Classifai\Providers;

use Classifai\Providers\ProviderConfigStore;
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
	 * If the Feature has non-empty credential values for this Provider, they are
	 * treated as an override and returned. Otherwise the global config for the
	 * profile is returned.
	 *
	 * @param string $provider_id Capability-specific Provider ID (e.g. openai_chatgpt).
	 * @param array  $feature_provider_settings The Provider specific Feature settings.
	 * @return array Effective credentials for the Provider.
	 */
	public static function resolve( string $provider_id, array $feature_provider_settings = [] ): array {
		$resolved = [];

		$profile_id = ProviderProfiles::get_profile_for_provider( $provider_id );
		if ( null === $profile_id ) {
			return $feature_provider_settings;
		}

		// Get the credential fields the profile supports.
		$credential_fields = ProviderProfiles::get_credential_fields( $profile_id );

		// Use Feature-level credentials when override is true; use only global when false.
		if ( array_key_exists( 'override', $feature_provider_settings ) ) {
			if ( ! empty( $feature_provider_settings['override'] ) ) {
				// Ensure the Feature-level Provider settings only has credential fields.
				$resolved = array_intersect_key( $feature_provider_settings, array_flip( $credential_fields ) );
			} else {
				$global   = ProviderConfigStore::get( $profile_id );
				$resolved = is_array( $global ) ? $global : [];
			}

			return $resolved;
		}

		// Infer override when any credential field (excluding 'authenticated') is non-empty.
		// TODO: Think about removing authenticated from the credential fields.
		$fields_to_check = array_diff( $credential_fields, [ 'authenticated' ] );
		$has_override    = false;
		foreach ( $fields_to_check as $field ) {
			$v = $feature_provider_settings[ $field ] ?? null;
			if ( '' !== $v && null !== $v && false !== $v ) {
				$has_override = true;
				break;
			}
		}

		if ( $has_override ) {
			// Ensure the Feature-level Provider settings only has credential fields.
			$resolved = array_intersect_key( $feature_provider_settings, array_flip( $credential_fields ) );
		} else {
			$global   = ProviderConfigStore::get( $profile_id );
			$resolved = is_array( $global ) ? $global : [];
		}

		return $resolved;
	}
}
