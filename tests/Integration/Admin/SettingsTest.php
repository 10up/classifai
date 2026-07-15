<?php
/**
 * Tests for the Admin Settings REST surface.
 */

namespace Classifai\Tests\Admin;

use Classifai\Tests\TestCase;
use Classifai\Admin\Settings;
use Classifai\Providers\CredentialObfuscator;

/**
 * @group admin
 * @coversDefaultClass \Classifai\Admin\Settings
 */
class SettingsTest extends TestCase {

	public function tear_down() {
		delete_option( 'classifai_feature_title_generation' );
		parent::tear_down();
	}

	/**
	 * Raw API keys must never be exposed by get_settings(); they are obfuscated.
	 *
	 * @covers ::get_settings
	 */
	public function test_get_settings_obfuscates_credentials() {
		update_option(
			'classifai_feature_title_generation',
			[
				'status'         => '1',
				'provider'       => 'openai_chatgpt',
				'openai_chatgpt' => [
					'api_key'       => 'sk-realsecret1234567890',
					'authenticated' => true,
				],
			]
		);

		$settings = ( new Settings() )->get_settings();

		$this->assertArrayHasKey( 'feature_title_generation', $settings );
		$api_key = $settings['feature_title_generation']['openai_chatgpt']['api_key'];

		$this->assertTrue( CredentialObfuscator::is_obfuscated( $api_key ) );
		$this->assertStringNotContainsString(
			'sk-realsecret1234567890',
			wp_json_encode( $settings ),
			'The raw API key must never appear in the settings payload.'
		);
	}

	/**
	 * @covers ::get_settings_permissions_check
	 * @covers ::update_settings_permissions_check
	 */
	public function test_permission_checks() {
		$settings = new Settings();

		$this->as_user_with_role( 'administrator' );
		$this->assertTrue( $settings->get_settings_permissions_check() );
		$this->assertTrue( $settings->update_settings_permissions_check() );

		$this->as_user_with_role( 'subscriber' );
		$this->assertFalse( $settings->get_settings_permissions_check() );
		$this->assertFalse( $settings->update_settings_permissions_check() );
	}
}
