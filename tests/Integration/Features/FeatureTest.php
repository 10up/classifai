<?php
/**
 * Tests for the Feature base class, exercised through TitleGeneration.
 */

namespace Classifai\Tests\Features;

use Classifai\Tests\TestCase;
use Classifai\Features\TitleGeneration;
use Classifai\Providers\CredentialObfuscator;

/**
 * @group features
 * @coversDefaultClass \Classifai\Features\Feature
 */
class FeatureTest extends TestCase {

	const OPTION = 'classifai_feature_title_generation';

	public function tear_down() {
		delete_option( self::OPTION );
		parent::tear_down();
	}

	/**
	 * Invoke the protected get_default_settings().
	 *
	 * @param TitleGeneration $feature Feature instance.
	 * @return array
	 */
	private function defaults( TitleGeneration $feature ): array {
		$method = new \ReflectionMethod( $feature, 'get_default_settings' );
		$method->setAccessible( true );
		return $method->invoke( $feature );
	}

	/**
	 * @covers ::get_option_name
	 */
	public function test_get_option_name() {
		$this->assertSame( self::OPTION, ( new TitleGeneration() )->get_option_name() );
	}

	/**
	 * @covers ::get_default_settings
	 */
	public function test_get_default_settings_shape() {
		$feature = new TitleGeneration();
		$feature->setup_roles(); // Normally hooked on admin_init; populate roles for the test.
		$defaults = $this->defaults( $feature );

		$this->assertSame( '0', $defaults['status'] );
		$this->assertSame( [], $defaults['users'] );
		$this->assertSame( 'no', $defaults['user_based_opt_out'] );
		$this->assertArrayHasKey( 'administrator', $defaults['roles'] );
		$this->assertArrayNotHasKey( 'subscriber', $defaults['roles'], 'Subscribers are excluded.' );

		// Feature defaults merged in.
		$this->assertSame( 'openai_chatgpt', $defaults['provider'] );
		// Provider defaults merged in (nested array per provider).
		$this->assertArrayHasKey( 'openai_chatgpt', $defaults );
		$this->assertArrayHasKey( 'authenticated', $defaults['openai_chatgpt'] );
	}

	/**
	 * @covers ::get_default_settings
	 */
	public function test_get_default_settings_filter() {
		$callback = function ( $defaults ) {
			$defaults['status'] = '1';
			return $defaults;
		};
		add_filter( 'classifai_feature_title_generation_get_default_settings', $callback );

		$this->assertSame( '1', $this->defaults( new TitleGeneration() )['status'] );

		remove_filter( 'classifai_feature_title_generation_get_default_settings', $callback );
	}

	/**
	 * @covers ::get_settings
	 */
	public function test_get_settings_merges_recursively_over_defaults() {
		update_option(
			self::OPTION,
			[
				'status'         => '1',
				'openai_chatgpt' => [ 'api_key' => 'sk-test' ],
			]
		);

		$settings = ( new TitleGeneration() )->get_settings();

		$this->assertSame( '1', $settings['status'] );
		$this->assertSame( 'sk-test', $settings['openai_chatgpt']['api_key'] );
		// Nested provider defaults are preserved, not clobbered.
		$this->assertArrayHasKey( 'authenticated', $settings['openai_chatgpt'] );
		// Missing top-level keys fall back to defaults.
		$this->assertSame( 'no', $settings['user_based_opt_out'] );
	}

	/**
	 * @covers ::get_settings
	 */
	public function test_get_settings_resets_unsupported_provider() {
		update_option( self::OPTION, [ 'provider' => 'not_a_supported_provider' ] );

		$this->assertSame( '', ( new TitleGeneration() )->get_settings()['provider'] );
	}

	/**
	 * @covers ::get_settings
	 */
	public function test_get_settings_index_access() {
		update_option( self::OPTION, [ 'status' => '1' ] );

		$this->assertSame( '1', ( new TitleGeneration() )->get_settings( 'status' ) );
	}

	/**
	 * @covers ::sanitize_settings
	 */
	public function test_sanitize_settings_falls_back_to_current_values() {
		update_option( self::OPTION, [ 'status' => '1', 'provider' => 'openai_chatgpt' ] );

		$feature   = new TitleGeneration();
		$sanitized = $feature->sanitize_settings( [] );

		$this->assertSame( '1', $sanitized['status'], 'Missing status falls back to current.' );
		$this->assertSame( 'openai_chatgpt', $sanitized['provider'] );
	}

	/**
	 * @covers ::sanitize_settings
	 */
	public function test_sanitize_settings_users_accepts_array_or_csv() {
		$feature = new TitleGeneration();

		$from_array = $feature->sanitize_settings( [ 'users' => [ '5', 7 ] ] );
		$this->assertSame( [ 5, 7 ], $from_array['users'] );

		$from_csv = $feature->sanitize_settings( [ 'users' => '5,7,9' ] );
		$this->assertSame( [ 5, 7, 9 ], $from_csv['users'] );
	}

	/**
	 * @covers ::sanitize_settings
	 */
	public function test_sanitize_settings_user_based_opt_out_normalization() {
		$feature = new TitleGeneration();

		$this->assertSame( '1', $feature->sanitize_settings( [ 'user_based_opt_out' => 1 ] )['user_based_opt_out'] );
		$this->assertSame( 'no', $feature->sanitize_settings( [ 'user_based_opt_out' => 0 ] )['user_based_opt_out'] );
		$this->assertSame( 'no', $feature->sanitize_settings( [] )['user_based_opt_out'] );
	}

	/**
	 * The obfuscated-credential merge must run for every provider, not just the active one.
	 *
	 * @covers ::sanitize_settings
	 */
	public function test_sanitize_settings_preserves_inactive_provider_key() {
		update_option(
			self::OPTION,
			[
				'provider'      => 'openai_chatgpt',
				'azure_openai'  => [ 'api_key' => 'azure-real-secret' ],
			]
		);

		$feature   = new TitleGeneration();
		$sanitized = $feature->sanitize_settings(
			[
				'provider'     => 'openai_chatgpt',
				'azure_openai' => [ 'api_key' => CredentialObfuscator::obfuscate( 'azure-real-secret' ) ],
			]
		);

		$this->assertSame( 'azure-real-secret', $sanitized['azure_openai']['api_key'] );
	}

	/**
	 * @covers ::sanitize_settings
	 */
	public function test_sanitize_settings_filter() {
		$callback = function ( $new_settings ) {
			$new_settings['status'] = '1';
			return $new_settings;
		};
		add_filter( 'classifai_feature_title_generation_sanitize_settings', $callback );

		$this->assertSame( '1', ( new TitleGeneration() )->sanitize_settings( [ 'status' => '0' ] )['status'] );

		remove_filter( 'classifai_feature_title_generation_sanitize_settings', $callback );
	}

	/**
	 * @covers ::has_access
	 */
	public function test_has_access_allowed_role() {
		update_option( self::OPTION, [ 'roles' => [ 'editor' => 'editor' ] ] );
		$this->as_user_with_role( 'editor' );

		$this->assertTrue( ( new TitleGeneration() )->has_access() );
	}

	/**
	 * @covers ::has_access
	 */
	public function test_has_access_via_users_allowlist() {
		$user_id = $this->as_user_with_role( 'subscriber' );
		update_option(
			self::OPTION,
			[
				'roles' => [ 'administrator' => 'administrator' ],
				'users' => [ $user_id ],
			]
		);

		$this->assertTrue( ( new TitleGeneration() )->has_access(), 'Allowlisted user gets access despite role.' );
	}

	/**
	 * @covers ::has_access
	 */
	public function test_has_access_denied_for_neither() {
		update_option( self::OPTION, [ 'roles' => [ 'administrator' => 'administrator' ], 'users' => [] ] );
		$this->as_user_with_role( 'subscriber' );

		$this->assertFalse( ( new TitleGeneration() )->has_access() );
	}

	/**
	 * @covers ::has_access
	 */
	public function test_has_access_opt_out() {
		$user_id = $this->as_user_with_role( 'editor' );
		update_option(
			self::OPTION,
			[
				'roles'              => [ 'editor' => 'editor' ],
				'user_based_opt_out' => '1',
			]
		);

		// Opted out of this feature → no access.
		update_user_meta( $user_id, 'classifai_opted_out_features', [ TitleGeneration::ID ] );
		$this->assertFalse( ( new TitleGeneration() )->has_access() );

		// Opted out of a different feature → still has access.
		update_user_meta( $user_id, 'classifai_opted_out_features', [ 'feature_some_other' ] );
		$this->assertTrue( ( new TitleGeneration() )->has_access() );
	}

	/**
	 * @covers ::has_access
	 */
	public function test_has_access_filter_override() {
		update_option( self::OPTION, [ 'roles' => [ 'administrator' => 'administrator' ] ] );
		$this->as_user_with_role( 'subscriber' );

		add_filter( 'classifai_feature_title_generation_has_access', '__return_true' );
		$this->assertTrue( ( new TitleGeneration() )->has_access() );
		remove_filter( 'classifai_feature_title_generation_has_access', '__return_true' );
	}

	/**
	 * @covers ::is_enabled
	 * @covers ::is_feature_enabled
	 * @covers ::is_configured
	 */
	public function test_is_enabled_vs_is_feature_enabled() {
		$this->as_user_with_role( 'administrator' );

		// Enabled + configured → both true.
		update_option(
			self::OPTION,
			[
				'status'         => '1',
				'provider'       => 'openai_chatgpt',
				'openai_chatgpt' => [ 'authenticated' => true ],
				'roles'          => [ 'administrator' => 'administrator' ],
			]
		);
		$feature = new TitleGeneration();
		$this->assertTrue( $feature->is_configured() );
		$this->assertTrue( $feature->is_enabled() );
		$this->assertTrue( $feature->is_feature_enabled() );

		// Enabled but NOT configured → is_enabled false.
		update_option( self::OPTION, [ 'status' => '1', 'provider' => 'openai_chatgpt' ] );
		$feature = new TitleGeneration();
		$this->assertFalse( $feature->is_configured() );
		$this->assertFalse( $feature->is_enabled() );
		$this->assertFalse( $feature->is_feature_enabled() );
	}

	/**
	 * Configured + enabled but the user lacks access: is_enabled() stays true while
	 * is_feature_enabled() is false.
	 *
	 * @covers ::is_enabled
	 * @covers ::is_feature_enabled
	 */
	public function test_is_enabled_true_without_access() {
		update_option(
			self::OPTION,
			[
				'status'         => '1',
				'provider'       => 'openai_chatgpt',
				'openai_chatgpt' => [ 'authenticated' => true ],
				'roles'          => [ 'administrator' => 'administrator' ],
				'users'          => [],
			]
		);
		$this->as_user_with_role( 'subscriber' );

		$feature = new TitleGeneration();
		$this->assertTrue( $feature->is_enabled() );
		$this->assertFalse( $feature->has_access() );
		$this->assertFalse( $feature->is_feature_enabled() );
	}

	/**
	 * @covers ::update_settings
	 * @covers ::reset_settings
	 */
	public function test_update_and_reset_settings() {
		$feature = new TitleGeneration();

		$feature->update_settings( [ 'status' => '1' ] );
		$this->assertSame( '1', get_option( self::OPTION )['status'] );

		$feature->reset_settings();
		$this->assertSame( '0', get_option( self::OPTION )['status'], 'Reset returns status to default.' );
	}
}
