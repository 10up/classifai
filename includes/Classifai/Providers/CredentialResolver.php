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
	 * @param string $provider_id Capability-specific Provider ID (e.g. openai_chatgpt).
	 * @param string $feature_id Feature ID.
	 * @param array  $feature_settings Full feature settings including [ $provider_id => [ ... ] ].
	 * @return array Effective credentials for the Provider (e.g. [ 'api_key' => '...', 'authenticated' => true ]).
	 */
	public static function resolve( string $provider_id, string $feature_id, array $feature_settings ): array {
		$resolved = [];

		$profile_id = ProviderProfiles::get_profile_for_provider( $provider_id );
		if ( null === $profile_id ) {
			$resolved = $feature_settings[ $provider_id ] ?? [];

			/**
			 * Filter the resolved Provider credentials for a Feature.
			 *
			 * @since x.x.x
			 * @hook classifai_resolved_credentials
			 *
			 * @param array  $resolved        Resolved credentials (e.g. api_key, authenticated).
			 * @param string $provider_id     Provider ID (e.g. openai_chatgpt).
			 * @param string $feature_id      Feature ID (e.g. classification, title_generation).
			 * @param array  $feature_settings Full feature settings.
			 *
			 * @return array Filtered credentials.
			 */
			return apply_filters( 'classifai_resolved_credentials', $resolved, $provider_id, $feature_id, $feature_settings );
		}

		$credential_fields = ProviderProfiles::get_credential_fields( $profile_id );
		$block             = $feature_settings[ $provider_id ] ?? [];

		// Use Feature-level credentials when override is true; use only global when false.
		if ( array_key_exists( 'override', $block ) ) {
			if ( ! empty( $block['override'] ) ) {
				$resolved = $block;
			} else {
				$global   = ProviderConfigStore::get( $profile_id );
				$resolved = is_array( $global ) ? $global : [];
			}

			/**
			 * Filter documented above.
			 */
			return apply_filters( 'classifai_resolved_credentials', $resolved, $provider_id, $feature_id, $feature_settings );
		}

		// Infer override when any credential field (excluding 'authenticated') is non-empty.
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
			$resolved = $block;
		} else {
			$global   = ProviderConfigStore::get( $profile_id );
			$resolved = is_array( $global ) ? $global : [];
		}

		/**
		 * Filter documented above.
		 */
		return apply_filters( 'classifai_resolved_credentials', $resolved, $provider_id, $feature_id, $feature_settings );
	}
}
