<?php
/**
 * Tests for admin BulkActions registration.
 */

namespace Classifai\Tests\Admin;

use Classifai\Tests\TestCase;
use Classifai\Admin\BulkActions;
use Classifai\Features\ExcerptGeneration;

/**
 * @group admin
 * @coversDefaultClass \Classifai\Admin\BulkActions
 */
class BulkActionsTest extends TestCase {

	public function tear_down() {
		delete_option( 'classifai_feature_excerpt_generation' );
		unset( $GLOBALS['post'] );
		parent::tear_down();
	}

	/**
	 * Place a published post in the global scope so get_post_type() resolves.
	 *
	 * @return int Post ID.
	 */
	private function set_global_post(): int {
		$post_id        = self::factory()->post->create();
		$GLOBALS['post'] = get_post( $post_id );
		return $post_id;
	}

	/**
	 * @covers ::register_language_processing_actions
	 */
	public function test_action_registered_only_when_feature_enabled() {
		$this->as_user_with_role( 'administrator' );
		$this->set_global_post();

		$bulk = new BulkActions();

		// Disabled feature → action not added.
		update_option( 'classifai_feature_excerpt_generation', [ 'status' => '0' ] );
		$bulk->register_language_processing_hooks();
		$actions = $bulk->register_language_processing_actions( [] );
		$this->assertArrayNotHasKey( ExcerptGeneration::ID, $actions );

		// Enabled + configured + supported post type → action added.
		update_option(
			'classifai_feature_excerpt_generation',
			[
				'status'         => '1',
				'provider'       => 'openai_chatgpt',
				'openai_chatgpt' => [ 'authenticated' => true ],
				'roles'          => [ 'administrator' => 'administrator' ],
				'post_types'     => [ 'post' => 'post' ],
			]
		);
		$bulk->register_language_processing_hooks();
		$actions = $bulk->register_language_processing_actions( [] );

		$this->assertArrayHasKey( ExcerptGeneration::ID, $actions );
		$this->assertSame( 'Generate Excerpt', $actions[ ExcerptGeneration::ID ] );
	}
}
