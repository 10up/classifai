<?php
/**
 * Testing the NLU settings
 */
namespace Classifai\Tests\Watson;

use \WP_UnitTestCase;
use \Classifai\Providers\Watson\NLU;
use Classifai\Features\Classification;

/**
 * Class NLUSettingsTest
 * @package Classifai\Tests\Watson
 *
 * @group watson
 */
class NLUSettingsTest extends WP_UnitTestCase {

	protected $provider;
	protected $settings = [
		'credentials' => [
			'watson_url' => 'url',
			'watson_username' => 'username',
			'watson_password' => 'password',
		]
	];

	/**
	 * setup method
	 */
	function set_up() {
		parent::set_up();
		// Add the settings
		update_option( 'classifai_watson_nlu', $this->settings );

		$this->provider = new NLU( new Classification() );
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

	/**
	 * Tests the function providing debug information.
	 */
	public function test_get_debug_information() {
		$this->assertEquals(
			[
				'Configured',
				'API URL',
				'API username',
				'Post types',
				'Features',
				'Latest response',
			],
			array_keys( $this->provider->get_debug_information() )
		);

		$this->assertEquals(
			[
				'Configured'      => 'yes',
				'API URL'         => 'my-watson-url.com',
				'API username'    => 'my-watson-username',
				'Post types'      => 'post, attachment, event',
				'Features'        => '{"feature":true}',
				'Latest response' => 'N/A',
			],
			$this->provider->get_debug_information(
				[
					'credentials' => [
						'watson_url'      => 'my-watson-url.com',
						'watson_username' => 'my-watson-username',
					],
					'post_types'  => [
						'post'       => 1,
						'page'       => 0,
						'attachment' => 1,
						'event'      => 1,
						'list'       => 0,
					],
					'features'     => [ 'feature' => true ],
				],
				true
			)
		);
	}
}
