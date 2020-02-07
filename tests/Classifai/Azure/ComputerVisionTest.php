<?php
/**
 * Testing the Azure settings
 */
namespace Classifai\Tests\Azure;

use \WP_UnitTestCase;
use Classifai\Providers\Azure\ComputerVision;

/**
 * Class ComputerVisionTest
 * @package Classifai\Tests\Azure
 *
 * @group azure
 */
class ComputerVisionTest extends WP_UnitTestCase {

	protected $provider;

	/**
	 * setup method
	 */
	function setUp() {
		parent::setUp();

		$this->provider = new ComputerVision( 'service_name' );
	}

	/**
	 * Tests the function providing debug information.
	 */
	public function test_get_provider_debug_information() {
		$this->assertEquals(
			[
				'Authenticated',
				'API URL',
				'Caption threshold',
				'Latest response',
			],
			array_keys( $this->provider->get_provider_debug_information() )
		);

		$this->assertEquals(
			[
				'Authenticated'     => 'yes',
				'API URL'           => 'my-azure-url.com',
				'Caption threshold' => 77,
				'Latest response'   => 'N/A',
			],
			$this->provider->get_provider_debug_information(
				[
					'url'               => 'my-azure-url.com',
					'caption_threshold' => 77,
					'authenticated'     => true,
				],
				true
			)
		);
	}
}
