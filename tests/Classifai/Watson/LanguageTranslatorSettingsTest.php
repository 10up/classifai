<?php
/**
 * Testing the NLU settings
 */
namespace Classifai\Tests\Watson;

use \WP_UnitTestCase;
use Classifai\Providers\Watson\LanguageTranslator;


/**
 * Class NLUSettingsTest
 * @package Classifai\Tests\Watson
 *
 * @group watson
 */
class LanguageTranslatorSettingsTest extends WP_UnitTestCase {

	protected $provider;
	protected $settings = [
		'crendentials' => [
			'watson_url' => 'url',
			'watson_username' => 'username',
			'watson_password' => 'password',
		]
	];

	/**
	 * setup method
	 */
	function setUp() {
		parent::setUp();
		// Add the settings
		update_option( 'classifai_watson_lt', $this->settings );

		$this->provider = new LanguageTranslator( 'service_name' );
	}

	/**
	 * Test the option name.
	 */
	public function test_option_name() {
		$this->assertSame( 'classifai_watson_lt', $this->provider->get_option_name() );
	}

	/**
	 * Retrieving the options.
	 */
	public function test_retrieving_options() {
		$options = get_option( $this->provider->get_option_name() );

		$this->assertEquals( $this->settings, $options );
	}

	/**
	 * Tests the function providing debug information.
	 */
	public function test_get_provider_debug_information() {
		$this->assertEquals(
			[
				'Configured',
				'API URL',
				'API username',
				'Languages',
			],
			array_keys( $this->provider->get_provider_debug_information() )
		);

		$this->assertEquals(
			[
				'Configured'   => 'yes',
				'API URL'      => 'my-watson-url.com',
				'API username' => 'my-watson-username',
				'Languages'    => '{"master":"en", "alternative":"en"}',
			],
			$this->provider->get_provider_debug_information(
				[
					'credentials' => [
						'watson_url'      => 'my-watson-url.com',
						'watson_username' => 'my-watson-username',
					],
					'languages'    => [ 'master' => 'en', 'alternative' => 'en' ],
				],
				true
			)
		);
	}
}
