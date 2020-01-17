<?php
/**
 * Testing for the ComputerVisition class
 */

namespace Classifai\Tests\Providers\Azure;

use \WP_UnitTestCase;
use Classifai\Providers\Azure\ComputerVision;

/**
 * Class ComputerVisionTest
 * @package Classifai\Tests\Providers\Azure;
 *
 * @group azure
 * @coversDefaultClass \Classifai\Providers\Azure\ComputerVision
 */
class ComputerVisionTest extends WP_UnitTestCase {
	/**
	 * Tear down method.
	 */
	public function tearDown() {
		parent::tearDown();

		$this->remove_added_uploads();
	}

	/**
	 * Provides a ComputerVision instance.
	 *
	 * @return ComputerVision
	 */
	public function get_computer_vision() : ComputerVision {
		return new ComputerVision( 'my_service' );
	}

	/**
	 * @covers ::smart_crop_image
	 */
	public function test_smart_crop_image() {
		$this->assertEquals(
			'non-array-data',
			$this->get_computer_vision()->smart_crop_image( 'non-array-data', 999999 )
		);
		$this->assertEquals( sprintf( 'http://example.org/wp-content/uploads/%s/%s/33772-1536x864.jpg', date( 'Y' ), date( 'm' ) ), $url );

		$this->assertEquals(
			[ 'no-smart-cropping' => 1 ],
			$this->get_computer_vision()->smart_crop_image(
				[ 'no-smart-cropping' => 1 ],
				999999
			)
		);

		add_filter( 'classifai_should_smart_crop_image', '__return_true' );

		$filter_file_system_method = function() {
			return 'not-direct';
		};

		add_filter( 'filesystem_method', $filter_file_system_method );
		$this->assertEquals(
			[ 'not-direct-file-system-method' => 1 ],
			$this->get_computer_vision()->smart_crop_image(
				[ 'not-direct-file-system-method' => 1 ],
				999999
			)
		);
		remove_filter( 'filesystem_method', $filter_file_system_method );

		// Test that SmartCropping is initiated and runs, as will be indicated in the coverage report, though it won't
		// actually do anything because the data and attachment are invalid.
		$this->assertEquals(
			[ 'my-data' => 1 ],
			$this->get_computer_vision()->smart_crop_image(
				[ 'my-data' => 1 ],
				999999
			)
		);

		remove_filter( 'classifai_should_smart_crop_image', '__return_true' );
	}
}
