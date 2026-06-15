<?php
/**
 * Tests for ProviderProfiles and CredentialResolver.
 */

namespace Classifai\Tests\Providers;

use Classifai\Tests\TestCase;
use Classifai\Providers\ProviderProfiles;
use Classifai\Providers\CredentialResolver;

/**
 * @group providers
 * @coversDefaultClass \Classifai\Providers\ProviderProfiles
 */
class ProviderProfilesTest extends TestCase {

	/**
	 * Every provider ID listed in a profile must reverse-resolve to that profile.
	 *
	 * @covers ::get_all_profiles
	 * @covers ::get_profile_for_provider
	 */
	public function test_every_provider_reverse_resolves() {
		$profiles = ProviderProfiles::get_all_profiles();
		$this->assertNotEmpty( $profiles );

		foreach ( $profiles as $profile_id => $definition ) {
			foreach ( $definition['provider_ids'] as $provider_id ) {
				$this->assertSame(
					$profile_id,
					ProviderProfiles::get_profile_for_provider( $provider_id ),
					"Provider '{$provider_id}' did not resolve to profile '{$profile_id}'."
				);
			}
		}
	}

	/**
	 * @covers ::get_profile_for_provider
	 */
	public function test_unknown_provider_returns_null() {
		$this->assertNull( ProviderProfiles::get_profile_for_provider( 'not_a_real_provider' ) );
	}

	/**
	 * Sensitive fields must always be a subset of the credential fields.
	 *
	 * @covers ::get_credential_fields
	 * @covers ::get_sensitive_fields
	 */
	public function test_sensitive_fields_are_subset_of_credential_fields() {
		foreach ( ProviderProfiles::get_all_profiles() as $profile_id => $definition ) {
			$credential_fields = ProviderProfiles::get_credential_fields( $profile_id );
			$sensitive_fields  = ProviderProfiles::get_sensitive_fields( $profile_id );

			$this->assertIsArray( $credential_fields );
			$this->assertEmpty(
				array_diff( $sensitive_fields, $credential_fields ),
				"Profile '{$profile_id}' has sensitive fields outside its credential fields."
			);
		}
	}

	/**
	 * @covers ::get_credential_fields
	 * @covers ::get_sensitive_fields
	 * @covers ::get_provider_ids_for_profile
	 */
	public function test_unknown_profile_returns_empty_arrays() {
		$this->assertSame( [], ProviderProfiles::get_credential_fields( 'nope' ) );
		$this->assertSame( [], ProviderProfiles::get_sensitive_fields( 'nope' ) );
		$this->assertSame( [], ProviderProfiles::get_provider_ids_for_profile( 'nope' ) );
	}

	/**
	 * @covers \Classifai\Providers\CredentialResolver::resolve
	 */
	public function test_resolve_keeps_only_credential_fields() {
		$resolved = CredentialResolver::resolve(
			'openai_chatgpt',
			[
				'api_key'       => 'sk-key',
				'authenticated' => true,
				'extra_field'   => 'ignored',
			]
		);

		$this->assertSame(
			[
				'api_key'       => 'sk-key',
				'authenticated' => true,
			],
			$resolved
		);
	}

	/**
	 * @covers \Classifai\Providers\CredentialResolver::resolve
	 */
	public function test_resolve_empty_settings() {
		$this->assertSame( [], CredentialResolver::resolve( 'openai_chatgpt', [] ) );
	}

	/**
	 * @covers \Classifai\Providers\CredentialResolver::resolve
	 */
	public function test_resolve_unknown_provider_passes_settings_through() {
		$settings = [ 'whatever' => 'value' ];
		$this->assertSame( $settings, CredentialResolver::resolve( 'unknown_provider', $settings ) );
	}

	/**
	 * A provider registered through the filter is recognized by the resolver.
	 *
	 * @covers ::get_all_profiles
	 * @covers ::get_profile_for_provider
	 */
	public function test_profiles_filter_override() {
		$callback = function ( $profiles ) {
			$profiles['custom_profile'] = [
				'provider_ids'      => [ 'custom_provider' ],
				'credential_fields' => [ 'token', 'authenticated' ],
				'sensitive_fields'  => [ 'token' ],
				'label'             => 'Custom',
			];
			return $profiles;
		};

		add_filter( 'classifai_provider_profiles', $callback );

		$this->assertSame( 'custom_profile', ProviderProfiles::get_profile_for_provider( 'custom_provider' ) );
		$this->assertSame( [ 'token', 'authenticated' ], ProviderProfiles::get_credential_fields( 'custom_profile' ) );

		remove_filter( 'classifai_provider_profiles', $callback );
	}
}
