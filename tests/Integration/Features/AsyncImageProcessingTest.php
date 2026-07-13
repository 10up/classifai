<?php
/**
 * Tests for async (Action Scheduler) image processing shared via the
 * AsyncImageProcessing trait, exercised through DescriptiveTextGenerator.
 */

namespace Classifai\Tests\Features;

use Classifai\Tests\TestCase;
use Classifai\Features\DescriptiveTextGenerator;
use Classifai\Services\ImageProcessing;
use WP_Error;

/**
 * @group features
 * @coversDefaultClass \Classifai\Features\DescriptiveTextGenerator
 */
class AsyncImageProcessingTest extends TestCase {

	const OPTION = 'classifai_feature_descriptive_text_generator';

	const ALT_TEXT = 'Generated descriptive text.';

	public function tear_down() {
		delete_option( self::OPTION );
		remove_all_filters( 'classifai_pre_fetch_feature_response' );
		parent::tear_down();
	}

	/**
	 * Configure and enable the feature for the current user.
	 *
	 * @param string $processing_mode Processing mode to store.
	 */
	private function enable_feature( string $processing_mode = 'automatic_async' ) {
		update_option(
			self::OPTION,
			[
				'status'                  => '1',
				'provider'                => 'ms_computer_vision',
				'ms_computer_vision'      => [ 'authenticated' => true ],
				'roles'                   => [ 'administrator' => 'administrator' ],
				'descriptive_text_fields' => [
					'alt'         => 'alt',
					'caption'     => 0,
					'description' => 0,
				],
				'processing_mode'         => $processing_mode,
			]
		);
	}

	/**
	 * Short-circuit the provider call so no HTTP request is made.
	 *
	 * @param mixed $response Value to return from run().
	 */
	private function mock_run( $response ) {
		add_filter(
			'classifai_pre_fetch_feature_response',
			static function () use ( $response ) {
				return $response;
			}
		);
	}

	/**
	 * Create an image attachment for testing.
	 *
	 * @return int
	 */
	private function create_image(): int {
		return self::factory()->attachment->create_upload_object(
			DIR_TESTDATA . '/images/canola.jpg'
		);
	}

	/**
	 * @covers ::sanitize_processing_mode
	 */
	public function test_sanitize_processing_mode_allowlist() {
		$feature = new DescriptiveTextGenerator();

		$this->assertSame( 'automatic', $feature->sanitize_processing_mode( 'automatic', 'manual' ) );
		$this->assertSame( 'automatic_async', $feature->sanitize_processing_mode( 'automatic_async', 'manual' ) );
		$this->assertSame( 'manual', $feature->sanitize_processing_mode( 'manual', 'automatic' ) );

		// Garbage falls back to the current value.
		$this->assertSame( 'automatic', $feature->sanitize_processing_mode( 'nonsense', 'automatic' ) );
		$this->assertSame( 'manual', $feature->sanitize_processing_mode( null, 'manual' ) );
	}

	/**
	 * @covers ::set_async_status
	 * @covers ::clear_async_status
	 */
	public function test_status_meta_is_keyed_by_feature() {
		$feature       = new DescriptiveTextGenerator();
		$attachment_id = self::factory()->post->create( [ 'post_type' => 'attachment' ] );

		$feature->set_async_status( $attachment_id, [ 'status' => 'scheduled', 'type' => 'descriptive_text' ] );

		$statuses = get_post_meta( $attachment_id, ImageProcessing::STATUS_META, true );
		$this->assertSame( 'scheduled', $statuses[ DescriptiveTextGenerator::ID ]['status'] );

		$feature->clear_async_status( $attachment_id );
		$this->assertSame( '', get_post_meta( $attachment_id, ImageProcessing::STATUS_META, true ) );
	}

	/**
	 * Jobs scheduled for another feature are ignored by the shared handler.
	 *
	 * @covers ::handle_async_image_job
	 */
	public function test_handle_job_ignores_other_feature() {
		$attachment_id = self::factory()->post->create( [ 'post_type' => 'attachment' ] );

		( new DescriptiveTextGenerator() )->handle_async_image_job( $attachment_id, 'feature_some_other', 'descriptive_text', 0 );

		$this->assertSame( '', get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ) );
		$this->assertSame( '', get_post_meta( $attachment_id, ImageProcessing::STATUS_META, true ) );
	}

	/**
	 * A deleted attachment is a no-op.
	 *
	 * @covers ::handle_async_image_job
	 */
	public function test_handle_job_skips_missing_attachment() {
		$this->mock_run( self::ALT_TEXT );

		// 999999 does not exist.
		( new DescriptiveTextGenerator() )->handle_async_image_job( 999999, DescriptiveTextGenerator::ID, 'descriptive_text', 0 );

		$this->assertSame( '', get_post_meta( 999999, '_wp_attachment_image_alt', true ) );
	}

	/**
	 * The handler runs the feature, saves the result and clears the status.
	 *
	 * @covers ::handle_async_image_job
	 */
	public function test_handle_job_saves_result_and_clears_status() {
		$this->as_user_with_role( 'administrator' );
		$this->enable_feature();
		$this->mock_run( self::ALT_TEXT );

		$attachment_id = $this->create_image();
		( new DescriptiveTextGenerator() )->set_async_status( $attachment_id, [ 'status' => 'scheduled' ] );

		( new DescriptiveTextGenerator() )->handle_async_image_job( $attachment_id, DescriptiveTextGenerator::ID, 'descriptive_text', get_current_user_id() );

		$this->assertSame( self::ALT_TEXT, get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ) );
		$this->assertSame( '', get_post_meta( $attachment_id, ImageProcessing::STATUS_META, true ), 'Status is cleared on success.' );
	}

	/**
	 * If the feature was disabled between enqueue and run, the status is
	 * cleared and nothing is saved.
	 *
	 * @covers ::handle_async_image_job
	 */
	public function test_handle_job_clears_status_when_feature_disabled() {
		$this->as_user_with_role( 'administrator' );
		// Feature option left unset => disabled.
		$this->mock_run( self::ALT_TEXT );

		$attachment_id = self::factory()->post->create( [ 'post_type' => 'attachment' ] );
		( new DescriptiveTextGenerator() )->set_async_status( $attachment_id, [ 'status' => 'scheduled' ] );

		( new DescriptiveTextGenerator() )->handle_async_image_job( $attachment_id, DescriptiveTextGenerator::ID, 'descriptive_text', get_current_user_id() );

		$this->assertSame( '', get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ) );
		$this->assertSame( '', get_post_meta( $attachment_id, ImageProcessing::STATUS_META, true ) );
	}

	/**
	 * A failed run records an error status.
	 *
	 * @covers ::handle_async_image_job
	 */
	public function test_handle_job_records_error_status() {
		$this->as_user_with_role( 'administrator' );
		$this->enable_feature();
		$this->mock_run( new WP_Error( 'boom', 'Provider failed.' ) );

		$attachment_id = $this->create_image();

		( new DescriptiveTextGenerator() )->handle_async_image_job( $attachment_id, DescriptiveTextGenerator::ID, 'descriptive_text', get_current_user_id() );

		$statuses = get_post_meta( $attachment_id, ImageProcessing::STATUS_META, true );
		$this->assertSame( 'error', $statuses[ DescriptiveTextGenerator::ID ]['status'] );
		$this->assertSame( 'Provider failed.', $statuses[ DescriptiveTextGenerator::ID ]['message'] );
		$this->assertSame( '', get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ) );
	}

	/**
	 * Manual mode performs no work on upload.
	 *
	 * @covers ::generate_image_alt_tags
	 */
	public function test_manual_mode_does_not_process_on_upload() {
		$this->as_user_with_role( 'administrator' );
		$this->enable_feature( 'manual' );
		$this->mock_run( self::ALT_TEXT );

		$attachment_id = $this->create_image();
		( new DescriptiveTextGenerator() )->generate_image_alt_tags( [], $attachment_id );

		$this->assertSame( '', get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ) );
		$this->assertSame( '', get_post_meta( $attachment_id, ImageProcessing::STATUS_META, true ) );
	}

	/**
	 * When Action Scheduler is unavailable, async mode falls back to processing
	 * synchronously so behavior is never silently dropped.
	 *
	 * @covers ::generate_image_alt_tags
	 * @covers ::enqueue_async_image_job
	 */
	public function test_async_mode_falls_back_to_sync_without_action_scheduler() {
		if ( function_exists( 'as_enqueue_async_action' ) ) {
			$this->markTestSkipped( 'Action Scheduler is loaded; fallback path not exercised.' );
		}

		$this->as_user_with_role( 'administrator' );
		$this->enable_feature( 'automatic_async' );
		$this->mock_run( self::ALT_TEXT );

		$attachment_id = $this->create_image();
		( new DescriptiveTextGenerator() )->generate_image_alt_tags( [], $attachment_id );

		$this->assertSame( self::ALT_TEXT, get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ) );
	}
}
