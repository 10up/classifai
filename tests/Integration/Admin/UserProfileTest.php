<?php
/**
 * Tests for the UserProfile opt-out admin handling.
 */

namespace Classifai\Tests\Admin;

use Classifai\Tests\TestCase;
use Classifai\Admin\UserProfile;
use Classifai\Features\TitleGeneration;

/**
 * @group admin
 * @coversDefaultClass \Classifai\Admin\UserProfile
 */
class UserProfileTest extends TestCase {

	const OPTION = 'classifai_feature_title_generation';

	public function tear_down() {
		delete_option( self::OPTION );
		unset( $_POST['classifai_out_out_features_nonce'], $_POST['classifai_opted_out_features'] );
		parent::tear_down();
	}

	/**
	 * Enable TitleGeneration with user-based opt-out for the administrator role.
	 */
	private function enable_with_opt_out() {
		update_option(
			self::OPTION,
			[
				'status'             => '1',
				'provider'           => 'openai_chatgpt',
				'openai_chatgpt'     => [ 'authenticated' => true ],
				'roles'              => [ 'administrator' => 'administrator' ],
				'user_based_opt_out' => '1',
			]
		);
	}

	/**
	 * @covers ::get_allowed_features
	 */
	public function test_get_allowed_features_lists_opt_out_enabled_features() {
		$user_id = $this->as_user_with_role( 'administrator' );
		$this->enable_with_opt_out();

		$features = ( new UserProfile() )->get_allowed_features( $user_id );

		$this->assertArrayHasKey( TitleGeneration::ID, $features );
	}

	/**
	 * @covers ::get_allowed_features
	 */
	public function test_get_allowed_features_excludes_when_opt_out_disabled() {
		$user_id = $this->as_user_with_role( 'administrator' );
		// Enabled/configured but opt-out OFF.
		update_option(
			self::OPTION,
			[
				'status'             => '1',
				'provider'           => 'openai_chatgpt',
				'openai_chatgpt'     => [ 'authenticated' => true ],
				'roles'              => [ 'administrator' => 'administrator' ],
				'user_based_opt_out' => 'no',
			]
		);

		$features = ( new UserProfile() )->get_allowed_features( $user_id );

		$this->assertArrayNotHasKey( TitleGeneration::ID, $features );
	}

	/**
	 * @covers ::save_user_settings
	 */
	public function test_save_user_settings_requires_nonce() {
		$user_id = $this->as_user_with_role( 'administrator' );

		// No nonce present.
		$_POST['classifai_opted_out_features'] = [ TitleGeneration::ID ];
		( new UserProfile() )->save_user_settings( $user_id );

		$this->assertSame( '', get_user_meta( $user_id, 'classifai_opted_out_features', true ) );
	}

	/**
	 * @covers ::save_user_settings
	 */
	public function test_save_user_settings_persists_with_valid_nonce() {
		$user_id = $this->as_user_with_role( 'administrator' );

		$_POST['classifai_out_out_features_nonce'] = wp_create_nonce( 'classifai_out_out_features' );
		$_POST['classifai_opted_out_features']     = [ TitleGeneration::ID ];

		( new UserProfile() )->save_user_settings( $user_id );

		$this->assertSame(
			[ TitleGeneration::ID ],
			get_user_meta( $user_id, 'classifai_opted_out_features', true )
		);
	}

	/**
	 * A saved opt-out actually flips Feature::has_access() to false.
	 *
	 * @covers ::save_user_settings
	 */
	public function test_saved_opt_out_revokes_feature_access() {
		$user_id = $this->as_user_with_role( 'administrator' );
		$this->enable_with_opt_out();

		// Before opting out the user has access.
		$this->assertTrue( ( new TitleGeneration() )->has_access() );

		$_POST['classifai_out_out_features_nonce'] = wp_create_nonce( 'classifai_out_out_features' );
		$_POST['classifai_opted_out_features']     = [ TitleGeneration::ID ];
		( new UserProfile() )->save_user_settings( $user_id );

		$this->assertFalse( ( new TitleGeneration() )->has_access(), 'Opt-out revokes access.' );
	}
}
