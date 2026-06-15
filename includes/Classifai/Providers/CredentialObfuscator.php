<?php
/**
 * Helper class for credential obfuscation functionality.
 *
 * @package Classifai
 */

namespace Classifai\Providers;

use Classifai\Providers\ProviderProfiles;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * CredentialObfuscator class.
 *
 * Handles obfuscation of sensitive credentials when displayed in the admin UI
 * and preserves original values when obfuscated values are submitted.
 *
 * @since 3.8.0
 */
class CredentialObfuscator {

	/**
	 * Number of characters to show at the beginning of obfuscated values.
	 *
	 * @var int
	 */
	const VISIBLE_PREFIX_LENGTH = 8;

	/**
	 * Minimum number of consecutive asterisks to detect an obfuscated value.
	 *
	 * @var int
	 */
	const MIN_ASTERISKS_TO_DETECT = 3;

	/**
	 * Obfuscate a credential value.
	 *
	 * Returns first N characters followed by asterisks.
	 * Example: "sk-abc123xyz789" becomes "sk-abc************"
	 *
	 * @since 3.8.0
	 *
	 * @param string $value The credential value to obfuscate.
	 * @return string The obfuscated value, or original if too short.
	 */
	public static function obfuscate( string $value ): string {
		if ( empty( $value ) ) {
			return $value;
		}

		$length = strlen( $value );

		// If the value is too short, just return asterisks.
		if ( $length <= self::VISIBLE_PREFIX_LENGTH && $length <= self::MIN_ASTERISKS_TO_DETECT ) {
			return str_repeat( '*', self::MIN_ASTERISKS_TO_DETECT );
		} elseif ( $length <= self::VISIBLE_PREFIX_LENGTH ) {
			return str_repeat( '*', $length );
		}

		$prefix    = substr( $value, 0, self::VISIBLE_PREFIX_LENGTH );
		$asterisks = str_repeat( '*', $length - self::VISIBLE_PREFIX_LENGTH );

		// If we don't have enough asterisks, add more.
		if ( strlen( $asterisks ) < self::MIN_ASTERISKS_TO_DETECT ) {
			$asterisks = str_repeat( '*', self::MIN_ASTERISKS_TO_DETECT );
		}

		return $prefix . $asterisks;
	}

	/**
	 * Check if a value appears to be obfuscated.
	 *
	 * Detects values containing 3 or more consecutive asterisks.
	 *
	 * @since 3.8.0
	 *
	 * @param string $value The value to check.
	 * @return bool True if the value appears to be obfuscated.
	 */
	public static function is_obfuscated( string $value ): bool {
		if ( empty( $value ) ) {
			return false;
		}

		$pattern = '/\*{' . self::MIN_ASTERISKS_TO_DETECT . ',}/';
		return (bool) preg_match( $pattern, $value );
	}

	/**
	 * Check if a field should be obfuscated.
	 *
	 * Returns false for non-sensitive fields like authenticated, endpoint_url, etc.
	 *
	 * @since 3.8.0
	 *
	 * @param string $field The field name to check.
	 * @param string $profile_id The profile ID.
	 * @return bool True if the field should be obfuscated.
	 */
	public static function should_obfuscate_field( string $field, string $profile_id ): bool {
		$sensitive_fields = ProviderProfiles::get_sensitive_fields( $profile_id );
		return in_array( $field, $sensitive_fields, true );
	}

	/**
	 * Obfuscate credential fields for a specific Provider.
	 *
	 * Uses ProviderProfiles to determine which fields are credentials.
	 *
	 * @since 3.8.0
	 *
	 * @param string $provider_id The Provider ID (e.g., 'openai_chatgpt').
	 * @param array  $settings    The Provider settings array.
	 * @return array The settings with credentials obfuscated.
	 */
	public static function obfuscate_provider_settings( string $provider_id, array $settings ): array {
		$profile_id = ProviderProfiles::get_profile_for_provider( $provider_id );

		if ( ! $profile_id ) {
			return $settings;
		}

		$credential_fields = ProviderProfiles::get_credential_fields( $profile_id );

		foreach ( $credential_fields as $field ) {
			if (
				isset( $settings[ $field ] ) &&
				is_string( $settings[ $field ] ) &&
				self::should_obfuscate_field( $field, $profile_id )
			) {
				$settings[ $field ] = self::obfuscate( $settings[ $field ] );
			}
		}

		return $settings;
	}

	/**
	 * Obfuscate all Provider credentials in Feature settings.
	 *
	 * Iterates through all Provider settings in the Feature and obfuscates
	 * their credential fields.
	 *
	 * @since 3.8.0
	 *
	 * @param array $settings The complete Feature settings array.
	 * @return array The settings with all Provider credentials obfuscated.
	 */
	public static function obfuscate_feature_settings( array $settings ): array {
		$profiles = ProviderProfiles::get_all_profiles();

		// Get all Provider IDs from profiles.
		$all_provider_ids = array();
		foreach ( $profiles as $profile ) {
			$all_provider_ids = array_merge( $all_provider_ids, $profile['provider_ids'] );
		}

		// Obfuscate credentials for each Provider that has settings.
		foreach ( $settings as $key => $value ) {
			if ( is_array( $value ) && in_array( $key, $all_provider_ids, true ) ) {
				$settings[ $key ] = self::obfuscate_provider_settings( $key, $value );
			}
		}

		return $settings;
	}

	/**
	 * Merge new credentials with existing credentials, preserving originals when obfuscated.
	 *
	 * If a new value is obfuscated, use the existing value instead.
	 * This prevents obfuscated placeholder values from being saved to the database.
	 *
	 * @since 3.8.0
	 *
	 * @param array  $new_settings      The new settings being saved.
	 * @param array  $existing_settings The current saved settings.
	 * @param string $provider_id       The Provider ID.
	 * @return array The merged settings.
	 */
	public static function merge_credentials( array $new_settings, array $existing_settings, string $provider_id ): array {
		$profile_id = ProviderProfiles::get_profile_for_provider( $provider_id );

		if ( ! $profile_id ) {
			return $new_settings;
		}

		$credential_fields = ProviderProfiles::get_credential_fields( $profile_id );

		foreach ( $credential_fields as $field ) {
			// Skip non-sensitive fields.
			if ( ! self::should_obfuscate_field( $field, $profile_id ) ) {
				continue;
			}

			// If the new value is obfuscated, preserve the existing value.
			if (
				isset( $new_settings[ $field ] ) &&
				is_string( $new_settings[ $field ] ) &&
				self::is_obfuscated( $new_settings[ $field ] ) &&
				isset( $existing_settings[ $field ] )
			) {
				$new_settings[ $field ] = $existing_settings[ $field ];
			}
		}

		return $new_settings;
	}

	/**
	 * Merge credentials for all Providers in Feature settings.
	 *
	 * Iterates through all Provider settings and preserves existing credentials
	 * when obfuscated values are submitted. This ensures switching Providers
	 * doesn't save obfuscated values for inactive Providers.
	 *
	 * @since 3.8.0
	 *
	 * @param array $new_settings      The new Feature settings being saved.
	 * @param array $existing_settings The current saved Feature settings.
	 * @return array The settings with all Provider credentials properly merged.
	 */
	public static function merge_all_provider_credentials( array $new_settings, array $existing_settings ): array {
		$profiles = ProviderProfiles::get_all_profiles();

		// Get all Provider IDs from profiles.
		$all_provider_ids = array();
		foreach ( $profiles as $profile ) {
			$all_provider_ids = array_merge( $all_provider_ids, $profile['provider_ids'] );
		}

		// Merge credentials for each Provider that has settings.
		foreach ( $new_settings as $key => $value ) {
			if ( is_array( $value ) && in_array( $key, $all_provider_ids, true ) ) {
				$new_settings[ $key ] = self::merge_credentials(
					$value,
					$existing_settings[ $key ] ?? array(),
					$key
				);
			}
		}

		return $new_settings;
	}
}
