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
 */
class ComputerVisionTest extends WP_UnitTestCase {
	protected $computer_vision;

	/**
	 * Setup method.
	 */
	public function setUp() {
		parent::setUp();

		$this->computer_vision = new ComputerVision( 'my_service' );
	}

	public function tearDown() {
		parent::tearDown();

		$this->remove_added_uploads();
	}

	/**
	 * Tests the get_largest_acceptable_image_url method.
	 */
	public function test_get_largest_acceptable_image_url() {
		$attachment = $this->factory->attachment->create_upload_object( DIR_TESTDATA .'/images/33772.jpg' ); // ~172KB image.

		$set_150kb_max_filesize = function() {
			return 150000;
		};
		add_filter( 'classifai_computervision_max_filesize', $set_150kb_max_filesize );

		$url = $this->computer_vision->get_largest_acceptable_image_url(
			get_attached_file( $attachment ),
			wp_get_attachment_url( $attachment, 'full' ),
			wp_get_attachment_metadata( $attachment )['sizes']
		);
		$this->assertEquals( sprintf( 'http://example.org/wp-content/uploads/%s/%s/33772-1024x576.jpg', date( 'Y' ), date( 'm' ) ), $url );

		$attachment = $this->factory->attachment->create_upload_object( DIR_TESTDATA .'/images/2004-07-22-DSC_0008.jpg' ); // ~109kb image.
		$url = $this->computer_vision->get_largest_acceptable_image_url(
			get_attached_file( $attachment ),
			wp_get_attachment_url( $attachment, 'full' ),
			wp_get_attachment_metadata( $attachment )['sizes']
		);
		$this->assertEquals( sprintf( 'http://example.org/wp-content/uploads/%s/%s/2004-07-22-DSC_0008.jpg', date( 'Y' ), date( 'm' ) ), $url );

		remove_filter( 'classifai_computervision_max_filesize', $set_150kb_max_filesize );

		$set_1kb_max_filesize = function() {
			return 1000;
		};
		add_filter( 'classifai_computervision_max_filesize', $set_1kb_max_filesize );

		$url = $this->computer_vision->get_largest_acceptable_image_url(
			get_attached_file( $attachment ),
			wp_get_attachment_url( $attachment, 'full' ),
			wp_get_attachment_metadata( $attachment )['sizes']
		);
		$this->assertNull( $url );

		remove_filter( 'classifai_computervision_max_filesize', $set_1kb_max_filesize );
	}
}
