<?php
/**
 * Global Provider configuration store.
 *
 * Persists and retrieves Provider credentials from the classifai_provider_configs
 * option. Used by the Providers tab and by CredentialResolver at runtime.
 *
 * @since x.x.x
 */

namespace Classifai\Providers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ProviderConfigStore class.
 */
class ProviderConfigStore {

	/**
	 * Option name for global Provider configs.
	 *
	 * @var string
	 */
	const OPTION_NAME = 'classifai_provider_configs';

	/**
	 * Get the full config array from the option.
	 *
	 * @return array Associative array of profile_id =>
	 * config (e.g. [ 'api_key' => '...', 'authenticated' => true ]).
	 */
	private static function get_option(): array {
		$val = get_option( self::OPTION_NAME, [] );
		return is_array( $val ) ? $val : [];
	}

	/**
	 * Get global config for a profile.
	 *
	 * @param string $profile_id Profile ID (e.g. openai, azure_openai).
	 * @return array|null Config array or null if not set.
	 */
	public static function get( string $profile_id ): ?array {
		$all = self::get_option();
		if ( ! isset( $all[ $profile_id ] ) || ! is_array( $all[ $profile_id ] ) ) {
			return null;
		}
		return $all[ $profile_id ];
	}

	/**
	 * Get all global Provider configs.
	 *
	 * @return array Associative array of profile_id => config.
	 */
	public static function get_all(): array {
		return self::get_option();
	}

	/**
	 * Set global config for a profile.
	 *
	 * @param string $profile_id Profile ID.
	 * @param array  $config     Config to save
	 * (e.g. [ 'api_key' => '...', 'authenticated' => true ]).
	 */
	public static function set( string $profile_id, array $config ): void {
		$all                = self::get_option();
		$all[ $profile_id ] = $config;
		update_option( self::OPTION_NAME, $all );
	}

	/**
	 * Remove global config for a profile.
	 *
	 * @param string $profile_id Profile ID.
	 */
	public static function delete( string $profile_id ): void {
		$all = self::get_option();
		unset( $all[ $profile_id ] );
		update_option( self::OPTION_NAME, $all );
	}
}
