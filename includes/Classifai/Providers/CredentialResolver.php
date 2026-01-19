<?php
/**
 * Credential resolution for Providers.
 *
 * Determines the effective credentials for a Provider by preferring feature-level
 * overrides over global Provider config. Used at runtime when Providers need
 * api_key, endpoint_url, etc.
 *
 * @since x.x.x
 */

namespace Classifai\Providers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * CredentialResolver class.
 */
class CredentialResolver {

	/**
	 * Resolve effective credentials for a Provider in a Feature.
	 *
	 * If the Feature has non-empty credential values for this Provider, they are
	 * treated as an override and returned. Otherwise the global config for the
	 * profile is returned. Existing installs with Feature-level creds effectively
	 * keep using them as overrides.
	 *
	 * @param string $provider_id     Capability-specific Provider ID (e.g. openai_chatgpt).
	 * @param string $feature_id      Feature ID (unused for now; for future per-Feature overrides).
	 * @param array  $feature_settings Full feature settings including [ $provider_id => [ ... ] ].
	 * @return array Effective credentials for the Provider (e.g. [ 'api_key' => '...', 'authenticated' => true ]).
	 */
	public static function resolve( string $provider_id, string $feature_id, array $feature_settings ): array {
		$profile_id = ProviderProfiles::get_profile_for_provider( $provider_id );
		if ( null === $profile_id ) {
			return $feature_settings[ $provider_id ] ?? [];
		}

		$credential_fields = ProviderProfiles::get_credential_fields( $profile_id );
		$block             = $feature_settings[ $provider_id ] ?? [];

		// Treat as override if any credential field (excluding 'authenticated') has a non-empty value.
		$fields_to_check = array_diff( $credential_fields, [ 'authenticated' ] );
		$has_override    = false;
		foreach ( $fields_to_check as $field ) {
			$v = $block[ $field ] ?? null;
			if ( '' !== $v && null !== $v && false !== $v ) {
				$has_override = true;
				break;
			}
		}

		if ( $has_override ) {
			return $block;
		}

		$global = ProviderConfigStore::get( $profile_id );
		return is_array( $global ) ? $global : [];
	}
}
