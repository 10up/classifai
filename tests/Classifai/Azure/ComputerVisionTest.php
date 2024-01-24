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

		$this->provider = new ComputerVision( 'service_name' );
	}

	/**
	 * Tests the function providing debug information.
	 */
	public function test_get_debug_information() {
		$this->assertEquals(
			[
				'Authenticated',
				'API URL',
				'Caption threshold',
				'Latest response - Image Scan',
				'Latest response - Smart Cropping',
				'Latest response - OCR',
			],
			array_keys( $this->provider->test_get_debug_information() )
		);

		$this->assertEquals(
			[
				'Authenticated'                    => 'yes',
				'API URL'                          => 'my-azure-url.com',
				'Caption threshold'                => 77,
				'Latest response - Image Scan'     => 'N/A',
				'Latest response - Smart Cropping' => 'N/A',
				'Latest response - OCR'            => 'N/A',
			],
			$this->provider->test_get_debug_information(
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
