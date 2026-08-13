<?php
/**
 * Tests for the CredentialObfuscator class.
 */

namespace Classifai\Tests\Providers;

use Classifai\Tests\TestCase;
use Classifai\Providers\CredentialObfuscator;

/**
 * @group providers
 * @coversDefaultClass \Classifai\Providers\CredentialObfuscator
 */
class CredentialObfuscatorTest extends TestCase {

	/**
	 * @covers ::obfuscate
	 */
	public function test_obfuscate_empty_returns_empty() {
		$this->assertSame( '', CredentialObfuscator::obfuscate( '' ) );
	}

	/**
	 * @covers ::obfuscate
	 */
	public function test_obfuscate_very_short_values_fully_masked() {
		// Length <= MIN_ASTERISKS_TO_DETECT (3) collapses to exactly 3 asterisks.
		$this->assertSame( '***', CredentialObfuscator::obfuscate( 'a' ) );
		$this->assertSame( '***', CredentialObfuscator::obfuscate( 'ab' ) );
		$this->assertSame( '***', CredentialObfuscator::obfuscate( 'abc' ) );

		// Length > 3 but <= VISIBLE_PREFIX_LENGTH (8) → asterisks equal to length.
		$this->assertSame( '****', CredentialObfuscator::obfuscate( 'abcd' ) );
		$this->assertSame( '********', CredentialObfuscator::obfuscate( 'abcdefgh' ) );
	}

	/**
	 * @covers ::obfuscate
	 */
	public function test_obfuscate_long_values_keep_prefix() {
		// 15 chars → 8-char prefix + 7 asterisks.
		$this->assertSame( 'sk-abc12*******', CredentialObfuscator::obfuscate( 'sk-abc123xyz789' ) );
	}

	/**
	 * @covers ::obfuscate
	 */
	public function test_obfuscate_nine_char_value_enforces_min_asterisks() {
		// 9 chars → 8-char prefix + only 1 trailing char, padded up to the 3 asterisk minimum.
		$this->assertSame( 'abcdefgh***', CredentialObfuscator::obfuscate( 'abcdefghi' ) );
	}

	/**
	 * The load-bearing invariant: anything obfuscated must read back as obfuscated.
	 *
	 * @covers ::obfuscate
	 * @covers ::is_obfuscated
	 */
	public function test_obfuscate_is_always_detectable() {
		foreach ( [ 'a', 'abc', 'abcd', 'abcdefgh', 'abcdefghi', 'sk-abc123xyz789', str_repeat( 'x', 64 ) ] as $value ) {
			$this->assertTrue(
				CredentialObfuscator::is_obfuscated( CredentialObfuscator::obfuscate( $value ) ),
				"Obfuscated value for '{$value}' was not detected as obfuscated."
			);
		}
	}

	/**
	 * @covers ::is_obfuscated
	 */
	public function test_is_obfuscated() {
		$this->assertFalse( CredentialObfuscator::is_obfuscated( '' ) );
		$this->assertFalse( CredentialObfuscator::is_obfuscated( '**' ), 'Two asterisks should not count.' );
		$this->assertFalse( CredentialObfuscator::is_obfuscated( 'sk-abc123xyz789' ), 'A realistic raw key is not obfuscated.' );

		$this->assertTrue( CredentialObfuscator::is_obfuscated( '***' ) );
		$this->assertTrue( CredentialObfuscator::is_obfuscated( 'sk-abc***' ) );
		$this->assertTrue( CredentialObfuscator::is_obfuscated( 'ab***cd' ), 'Asterisks mid-string count.' );
	}

	/**
	 * @covers ::merge_credentials
	 */
	public function test_merge_credentials_preserves_existing_when_obfuscated() {
		$merged = CredentialObfuscator::merge_credentials(
			[ 'api_key' => 'sk-realo***' ],
			[ 'api_key' => 'sk-realold-value' ],
			'openai_chatgpt'
		);

		$this->assertSame( 'sk-realold-value', $merged['api_key'] );
	}

	/**
	 * @covers ::merge_credentials
	 */
	public function test_merge_credentials_plain_value_wins() {
		// Key rotation: a non-obfuscated new value must overwrite the existing one.
		$merged = CredentialObfuscator::merge_credentials(
			[ 'api_key' => 'sk-brand-new-key' ],
			[ 'api_key' => 'sk-realold-value' ],
			'openai_chatgpt'
		);

		$this->assertSame( 'sk-brand-new-key', $merged['api_key'] );
	}

	/**
	 * @covers ::merge_credentials
	 */
	public function test_merge_credentials_obfuscated_without_existing_passes_through() {
		$merged = CredentialObfuscator::merge_credentials(
			[ 'api_key' => 'sk-real***' ],
			[],
			'openai_chatgpt'
		);

		$this->assertSame( 'sk-real***', $merged['api_key'] );
	}

	/**
	 * @covers ::merge_credentials
	 */
	public function test_merge_credentials_non_sensitive_fields_never_merged() {
		// endpoint_url is a credential field for azure_openai but not sensitive,
		// so it is never restored from existing settings even if it looks obfuscated.
		$merged = CredentialObfuscator::merge_credentials(
			[ 'endpoint_url' => 'https://new***' ],
			[ 'endpoint_url' => 'https://old.example.com' ],
			'azure_openai'
		);

		$this->assertSame( 'https://new***', $merged['endpoint_url'] );
	}

	/**
	 * @covers ::merge_credentials
	 */
	public function test_merge_credentials_unknown_provider_untouched() {
		$new = [ 'api_key' => 'sk-real***' ];

		$this->assertSame(
			$new,
			CredentialObfuscator::merge_credentials( $new, [ 'api_key' => 'real' ], 'does_not_exist' )
		);
	}

	/**
	 * The "switching providers wipes my key" regression: the inactive provider's
	 * obfuscated key must be restored from existing settings.
	 *
	 * @covers ::merge_all_provider_credentials
	 */
	public function test_merge_all_provider_credentials_restores_inactive_provider() {
		$new = [
			'provider'       => 'openai_chatgpt',
			'openai_chatgpt' => [ 'api_key' => 'sk-new-active-key' ],
			'azure_openai'   => [ 'api_key' => 'azure***' ],
		];

		$existing = [
			'openai_chatgpt' => [ 'api_key' => 'sk-old-active' ],
			'azure_openai'   => [ 'api_key' => 'azure-real-secret' ],
		];

		$merged = CredentialObfuscator::merge_all_provider_credentials( $new, $existing );

		$this->assertSame( 'sk-new-active-key', $merged['openai_chatgpt']['api_key'], 'Active plain key wins.' );
		$this->assertSame( 'azure-real-secret', $merged['azure_openai']['api_key'], 'Inactive obfuscated key restored.' );
	}

	/**
	 * @covers ::obfuscate_provider_settings
	 */
	public function test_obfuscate_provider_settings_only_touches_credentials() {
		$obfuscated = CredentialObfuscator::obfuscate_provider_settings(
			'openai_chatgpt',
			[
				'api_key'       => 'sk-secret-value-1234',
				'authenticated' => true,
			]
		);

		$this->assertTrue( CredentialObfuscator::is_obfuscated( $obfuscated['api_key'] ) );
		$this->assertTrue( $obfuscated['authenticated'], 'Non-string / non-sensitive fields untouched.' );
	}

	/**
	 * @covers ::obfuscate_provider_settings
	 */
	public function test_obfuscate_provider_settings_skips_non_string_values() {
		$obfuscated = CredentialObfuscator::obfuscate_provider_settings(
			'openai_chatgpt',
			[ 'api_key' => [ 'not', 'a', 'string' ] ]
		);

		$this->assertSame( [ 'not', 'a', 'string' ], $obfuscated['api_key'] );
	}

	/**
	 * @covers ::obfuscate_feature_settings
	 */
	public function test_obfuscate_feature_settings_leaves_non_provider_keys() {
		$obfuscated = CredentialObfuscator::obfuscate_feature_settings(
			[
				'status'         => '1',
				'openai_chatgpt' => [ 'api_key' => 'sk-secret-value-1234' ],
			]
		);

		$this->assertSame( '1', $obfuscated['status'], 'Non-provider keys untouched.' );
		$this->assertTrue( CredentialObfuscator::is_obfuscated( $obfuscated['openai_chatgpt']['api_key'] ) );
	}
}
