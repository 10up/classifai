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
	function set_up() {
		parent::set_up();

		$this->provider = new ComputerVision( new \Classifai\Features\DescriptiveTextGenerator() );
	}

	/**
	 * Tests the function providing debug information.
	 */
	public function test_get_debug_information() {
		$this->assertEquals(
			[
				'Generate descriptive text',
				'Confidence threshold',
				'Latest response:',
			],
			array_keys( $this->provider->get_debug_information() )
		);

		$this->assertEquals(
			[
				'Generate descriptive text' => '0, 0, 0',
				'Confidence threshold'      => 70,
				'Latest response:'          => 'N/A',
			],
			$this->provider->get_debug_information(
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
