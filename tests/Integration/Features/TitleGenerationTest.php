<?php
/**
 * Tests for the TitleGeneration REST permission callback.
 */

namespace Classifai\Tests\Features;

use Classifai\Tests\TestCase;
use Classifai\Features\TitleGeneration;
use WP_REST_Request;

/**
 * @group features
 * @coversDefaultClass \Classifai\Features\TitleGeneration
 */
class TitleGenerationTest extends TestCase {

	const OPTION = 'classifai_feature_title_generation';

	public function tear_down() {
		delete_option( self::OPTION );
		parent::tear_down();
	}

	/**
	 * Configure and enable the feature with the current user allowed.
	 */
	private function enable_feature() {
		update_option(
			self::OPTION,
			[
				'status'         => '1',
				'provider'       => 'openai_chatgpt',
				'openai_chatgpt' => [ 'authenticated' => true ],
				'roles'          => [ 'administrator' => 'administrator' ],
			]
		);
	}

	/**
	 * Build a request targeting a post ID.
	 *
	 * @param mixed $post_id Post ID param.
	 * @return WP_REST_Request
	 */
	private function request( $post_id ): WP_REST_Request {
		$request = new WP_REST_Request( 'POST', '/classifai/v1/generate-title' );
		$request->set_param( 'id', $post_id );
		return $request;
	}

	/**
	 * @covers ::generate_title_permissions_check
	 */
	public function test_denied_when_no_post_id() {
		$this->as_user_with_role( 'administrator' );
		$this->enable_feature();

		$this->assertFalse( ( new TitleGeneration() )->generate_title_permissions_check( $this->request( 0 ) ) );
	}

	/**
	 * @covers ::generate_title_permissions_check
	 */
	public function test_denied_when_user_cannot_edit_post() {
		$post_id = self::factory()->post->create();
		$this->as_user_with_role( 'subscriber' );
		$this->enable_feature();

		$this->assertFalse( ( new TitleGeneration() )->generate_title_permissions_check( $this->request( $post_id ) ) );
	}

	/**
	 * @covers ::generate_title_permissions_check
	 */
	public function test_denied_for_unsupported_post_type() {
		register_post_type( 'classifai_no_rest', [ 'show_in_rest' => false ] );
		$post_id = self::factory()->post->create( [ 'post_type' => 'classifai_no_rest' ] );
		$this->as_user_with_role( 'administrator' );
		$this->enable_feature();

		$this->assertFalse( ( new TitleGeneration() )->generate_title_permissions_check( $this->request( $post_id ) ) );
	}

	/**
	 * @covers ::generate_title_permissions_check
	 */
	public function test_denied_when_feature_disabled() {
		$post_id = self::factory()->post->create();
		$this->as_user_with_role( 'administrator' );
		// Feature not configured/enabled.
		update_option( self::OPTION, [ 'status' => '0' ] );

		$result = ( new TitleGeneration() )->generate_title_permissions_check( $this->request( $post_id ) );
		$this->assertWPErrorCode( 'not_enabled', $result );
	}

	/**
	 * @covers ::generate_title_permissions_check
	 */
	public function test_allows_happy_path() {
		$post_id = self::factory()->post->create();
		$this->as_user_with_role( 'administrator' );
		$this->enable_feature();

		$this->assertTrue( ( new TitleGeneration() )->generate_title_permissions_check( $this->request( $post_id ) ) );
	}
}
