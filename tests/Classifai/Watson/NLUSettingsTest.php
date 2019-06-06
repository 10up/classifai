<?php
/**
 * Testing the NLU settings
 */
namespace Classifai\Tests\Watson;

use \WP_UnitTestCase;
use \Classifai\Providers\Watson\NLU;


/**
 * Class NLUSettingsTest
 * @package Classifai\Tests\Watson
 *
 * @group watson
 */
class NLUSettingsTest extends WP_UnitTestCase {

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
		update_option( 'classifai_watson_nlu', $this->settings );

		$this->provider = new NLU( 'service_name' );
	}

	/**
	 * Test the option name.
	 */
	public function test_option_name() {
		$this->assertSame( 'classifai_watson_nlu', $this->provider->get_option_name() );
	}

	/**
	 * Retrieving the options.
	 */
	public function test_retrieving_options() {
		$options = get_option( $this->provider->get_option_name() );

		$this->assertEquals( $this->settings, $options );
	}

}
