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
	public function tear_down() {
		$this->remove_added_uploads();
		parent::tear_down();
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


	/**
	 * Ensure that settings returns default settings array if the `classifai_computer_vision` is not set.
	 */
	public function test_no_computer_vision_option_set() {
		delete_option( 'classifai_computer_vision' );

		$settings = $this->get_computer_vision()->get_settings();

		$this->assertSame( $settings, [
			'valid'                 => false,
			'url'                   => '',
			'api_key'               => '',
			'enable_image_captions' => array(
				'alt'         => 0,
				'caption'     => 0,
				'description' => 0,
			),
			'enable_image_tagging'  => true,
			'enable_smart_cropping' => false,
			'enable_ocr'            => false,
			'enable_read_pdf'       => false,
			'caption_threshold'     => 75,
			'tag_threshold'         => 70,
			'image_tag_taxonomy'    => 'classifai-image-tags',
		] );
	}

	/**
	 * Ensure that attachment meta is being set.
	 */
	public function test_set_image_meta_data() {
		// Create A settings object
		$settings = [
			'url'                   => '',
			'api_key'               => '',
			'enable_image_tagging'  => 'no',
			'enable_image_captions' => array(
				'alt'         => 0,
				'caption'     => 0,
				'description' => 0,
			),
		];
		// Add the settings.
		add_option( 'classifai_computer_vision', $settings );

		// Instantiate the hooks
		$this->get_computer_vision()->register();

		$attachment = $this->factory->attachment->create_upload_object( DIR_TESTDATA . '/images/33772.jpg' );
		$meta       = wp_get_attachment_metadata( $attachment );
		$this->assertNotFalse( $meta );
	}

	public function test_alt_text_option_reformatting() {
		add_option( 'classifai_computer_vision', array() );

		$options = array(
			'valid'                 => false,
			'url'                   => '',
			'api_key'               => '',
			'enable_image_captions' => '1',
			'enable_image_tagging'  => '1',
			'enable_smart_cropping' => 'no',
			'enable_ocr'            => 'no',
			'enable_read_pdf'       => 'no',
			'caption_threshold'     => 75,
			'tag_threshold'         => 70,
			'image_tag_taxonomy'    => 'classifai-image-tags',
		);

		// Test with `enable_image_captions` set to `1`.
		add_filter( 'pre_option_classifai_computer_vision', function() use( $options ) {
			return $options;
		} );

		$image_captions_settings = $this->get_computer_vision()->get_alt_text_settings();
		$this->assertSame(
			$image_captions_settings,
			array(
				'alt'         => 'alt',
				'caption'     => 0,
				'description' => 0,
			)
		);

		// Test with `enable_image_captions` set to `no`.
		$options['enable_image_captions'] = 'no';
		add_filter( 'pre_option_classifai_computer_vision', function() use( $options ) {
			return $options;
		} );

		$image_captions_settings = $this->get_computer_vision()->get_alt_text_settings();
		$this->assertSame(
			$image_captions_settings,
			array(
				'alt'         => 0,
				'caption'     => 0,
				'description' => 0,
			)
		);
	}
}
