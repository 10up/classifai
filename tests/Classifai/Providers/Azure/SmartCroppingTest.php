<?php
/**
 * Testing for the ComputerVisition class
 */

namespace Classifai\Tests\Providers\Azure;

use WP_Filesystem_Direct;
use WP_UnitTestCase;
use Classifai\Providers\Azure\SmartCropping;

/**
 * @coversDefaultClass \Classifai\Providers\Azure\SmartCropping
 */
class SmartCroppingTest extends WP_UnitTestCase {
	/**
	 * Setup method.
	 */
	public function set_up() {
		parent::set_up();
	}

	/**
	 * Tear down method.
	 */
	public function tear_down() {
		parent::tear_down();

		$this->remove_added_uploads();
	}

	/**
	 * Provides a SmartCropping instance for testing.
	 *
	 * @param array $args Args to pass to the SmartCropping constructor.
	 * @return SmartCropping
	 */
	public function get_smart_cropping(
		array $args = [ 'endpoint_url' => 'my-api-url.com', 'api_key' => 'my-key' ]
	) : SmartCropping  {
		return new SmartCropping( $args );
	}

	/**
	 * Runs a callback with a filter overriding the smart cropping API request.
	 *
	 * @param callable $callback The function to run with the filter.
	 */
	public function with_http_request_filter( callable $callback )  {
		$filter = function( $response, array $parsed_args, string $url ) : array {
			$response = [
				'body' => file_get_contents( DIR_TESTDATA .'/images/33772.jpg' ),
				'response' => [
					'code' => 200,
					'message' => 'OK',
				]
			];

			if ( false !== strpos( $url, 'my-bad-url.com' ) ) {
				$response['response']['code'] = 400;
			}

			return $response;
		};
		add_filter( 'pre_http_request', $filter, 10, 3 );

		$callback();

		remove_filter( 'pre_http_request', $filter );
	}

	/**
	 * @covers ::__construct
	 * @covers ::get_wp_filesystem
	 */
	public function test_get_wp_filesystem() {
		$this->assertInstanceOf(
			WP_Filesystem_Direct::class,
			$this->get_smart_cropping()->get_wp_filesystem()
		);
	}

	/**
	 * @covers ::should_crop
	 */
	public function test_should_crop() {
		global $_wp_additional_image_sizes;
		$saved_additonal_image_sizes = $_wp_additional_image_sizes;;

		add_image_size( 'test-cropped-image-size', 600, 500, true );
		add_image_size( 'test-position-cropped-image-size', 600, 400, [ 'right', 'bottom' ] );

		$smart_cropping = $this->get_smart_cropping();

		$this->assertTrue( $smart_cropping->should_crop( 'thumbnail' ) );
		$this->assertFalse( $smart_cropping->should_crop( 'nonexistent-size' ) );
		$this->assertTrue( $smart_cropping->should_crop( 'test-cropped-image-size' ) );
		$this->assertFalse( $smart_cropping->should_crop( 'test-position-cropped-image-size' ) );

		// Reset.
		$_wp_additional_image_sizes = $saved_additonal_image_sizes;
	}

	/**
	 * @covers ::generate_attachment_metadata
	 */
	public function test_generate_attachment_metadata() {
		$attachment = $this->factory->attachment->create_upload_object( DIR_TESTDATA .'/images/33772.jpg' );

		// Test that nothing happens when the metadata contains no sizes entry.
		$this->assertEquals(
			[ 'no-sizes' => 1 ],
			$this->get_smart_cropping()->generate_attachment_metadata(
				[ 'no-sizes' => 1 ],
				$attachment
			)
		);

		$with_filter_cb = function() use ( $attachment ) {
			$filtered_data = $this->get_smart_cropping()->generate_attachment_metadata(
				wp_get_attachment_metadata( $attachment ),
				$attachment
			);

			$this->assertEquals(
				'33772-150x150.jpg',
				$filtered_data['sizes']['thumbnail']['file']
			);
		};

		$this->with_http_request_filter( $with_filter_cb );
	}

	/**
	 * @covers ::get_cropped_thumbnail
	 */
	public function test_get_cropped_thumbnail() {
		// Test invalid data returns false.
		$this->assertWPError( $this->get_smart_cropping()->get_cropped_thumbnail( 999999999, [] ) );

		$attachment = $this->factory->attachment->create_upload_object( DIR_TESTDATA .'/images/33772.jpg' );

		// Test bad request returns false.
		$this->assertWPError(
			$this->get_smart_cropping(
				[
					'url'     => 'my-bad-url.com',
					'api_key' => 'my-key',
				]
			)->get_cropped_thumbnail(
				$attachment,
				wp_get_attachment_metadata( $attachment )['sizes']['thumbnail']
			)
		);

		$with_filter_cb = function() use ( $attachment ) {

			// Get the uploaded image url
			$cropped_thumbnail_url = $this->get_smart_cropping()->get_cropped_thumbnail(
				$attachment,
				wp_get_attachment_metadata( $attachment )['sizes']['thumbnail']
			);
			// Strip out everything before /wp-content/ because it won't match.
			$prepped_url = substr( $cropped_thumbnail_url,  strpos( $cropped_thumbnail_url , '/wp-content/' ) );


			$this->assertEquals(
				sprintf( '%s/33772-150x150.jpg', wp_upload_dir()['path'] ),
				$cropped_thumbnail_url
			);

			// Test when file operations fail.
			add_filter( 'classifai_smart_crop_wp_filesystem', '__return_false' );
			$this->assertWPError(
				$this->get_smart_cropping()->get_cropped_thumbnail(
					$attachment,
					wp_get_attachment_metadata( $attachment )['sizes']['thumbnail']
				)
			);
			remove_filter( 'classifai_smart_crop_wp_filesystem', '__return_false' );
		};

		$this->with_http_request_filter( $with_filter_cb );
	}

	/**
	 * @covers ::get_api_url
	 */
	public function test_get_api_url() {
		$this->assertEquals(
			'my-api-url.com/vision/v3.1/generateThumbnail/',
			$this->get_smart_cropping()->get_api_url()
		);
	}

	/**
	 * @covers ::request_cropped_thumbnail
	 */
	public function test_request_cropped_thumbnail() {
		$with_filter_cb = function() {
			// Test successful request.
			$this->assertEquals(
				file_get_contents( DIR_TESTDATA .'/images/33772.jpg' ),
				$this->get_smart_cropping()->request_cropped_thumbnail(
					[
						'height' => 100,
						'width'  => 100,
						'url'    => 'my-image-url.jpeg',
					]
				)
			);

			// Test failed request.
			$this->assertWPError(
				$this->get_smart_cropping(
					[
						'url'     => 'my-bad-url.com',
						'api_key' => 'my-key',
					]
				)->request_cropped_thumbnail(
					[
						'height' => 100,
						'width'  => 100,
						'url'    => 'my-image-url.jpeg',
					]
				)
			);
		};

		$this->with_http_request_filter( $with_filter_cb );
	}
}
