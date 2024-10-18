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
		$this->assertWPError(
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
		$this->assertWPError( $this->get_computer_vision()->smart_crop_image(
			[ 'not-direct-file-system-method' => 1 ],
			999999
		) );
		remove_filter( 'filesystem_method', $filter_file_system_method );

		remove_filter( 'classifai_should_smart_crop_image', '__return_true' );
	}

	/**
	 * Ensure that settings returns default settings array if the `classifai_computer_vision` is not set.
	 */
	public function test_no_computer_vision_option_set() {
		delete_option( 'classifai_computer_vision' );

		$defaults = [];

		$expected = array_merge(
			$defaults,
			[
				'status' => '0',
				'roles' => [],
				'users' => [],
				'user_based_opt_out' => 'no',
				'descriptive_text_fields' => [
					'alt' => 'alt',
					'caption' => 0,
					'description' => 0,
				],
				'provider' => 'ms_computer_vision',
				'ms_computer_vision' => [
					'endpoint_url' => '',
					'api_key' => '',
					'authenticated' => false,
					'descriptive_confidence_threshold' => 55,
				],
			]
		);
		$settings = ( new \Classifai\Features\DescriptiveTextGenerator() )->get_settings();

		$this->assertSame( $expected, $settings );
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
		add_option( 'classifai_feature_descriptive_text_generator', array() );

		$options = array(
			'status'             => '1',
			'provider'           => 'ms_computer_vision',
			'ms_computer_vision' => array(
				'endpoint_url'                     => '',
				'api_key'                          => '',
				'descriptive_confidence_threshold' => '75',
				'authenticated'                    => true,
			),
			'descriptive_text_fields' => array(
				'alt'         => 'alt',
				'caption'     => '0',
				'description' => '0',
			),
		);

		// Test with `descriptive_text_fields` set to `alt`.
		add_filter( 'pre_option_classifai_feature_descriptive_text_generator', function() use( $options ) {
			return $options;
		} );

		$image_captions_settings = ( new \Classifai\Features\DescriptiveTextGenerator() )->get_alt_text_settings();
		$this->assertSame(
			$image_captions_settings,
			array( 'alt' )
		);

		// Test with `enable_image_captions` set to `no`.
		$options['descriptive_text_fields']['alt'] = '0';
		add_filter( 'pre_option_classifai_feature_descriptive_text_generator', function() use( $options ) {
			return $options;
		} );

		$image_captions_settings = ( new \Classifai\Features\DescriptiveTextGenerator() )->get_alt_text_settings();
		$this->assertSame(
			$image_captions_settings,
			array()
		);
	}
}
