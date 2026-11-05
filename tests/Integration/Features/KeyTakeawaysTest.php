<?php
/**
 * Tests for the Key Takeaways on-demand front-end generation flow.
 */

namespace Classifai\Tests\Features;

use Classifai\Features\KeyTakeaways;
use Classifai\Providers\OpenAI\ChatGPT;
use WP_REST_Request;
use WP_UnitTestCase;

/**
 * Class KeyTakeawaysTest
 *
 * @package Classifai\Tests\Features;
 */
class KeyTakeawaysTest extends WP_UnitTestCase {

	/**
	 * Number of times the mocked provider HTTP endpoint was hit.
	 *
	 * @var int
	 */
	private $http_calls = 0;

	/**
	 * Tear down method.
	 */
	public function tear_down() {
		remove_all_filters( 'pre_option_classifai_feature_key_takeaways' );
		remove_all_filters( 'pre_http_request' );
		remove_all_filters( 'classifai_key_takeaways_on_demand_permission' );
		$this->http_calls = 0;

		parent::tear_down();
	}

	/**
	 * Mock the Key Takeaways feature settings.
	 *
	 * @param string $generation_timing Generation timing mode.
	 * @param array  $post_types        Allowed post types map.
	 */
	private function mock_settings( string $generation_timing = 'on_demand', array $post_types = array( 'post' => 'post' ) ): void {
		add_filter(
			'pre_option_classifai_feature_key_takeaways',
			function () use ( $generation_timing, $post_types ) {
				return array(
					'status'               => '1',
					'roles'                => array( 'administrator' ),
					'users'                => array(),
					'user_based_opt_out'   => '0',
					'provider'             => ChatGPT::ID,
					'generation_timing'    => $generation_timing,
					'post_types'           => $post_types,
					'render'               => 'list',
					'button_label'         => 'Key Takeaways',
					'key_takeaways_prompt' => array(),
					ChatGPT::ID            => array(
						'authenticated' => true,
					),
				);
			}
		);
	}

	/**
	 * Mock the OpenAI chat completions endpoint with a takeaways response.
	 */
	private function mock_openai_response(): void {
		add_filter(
			'pre_http_request',
			function ( $preempt, $parsed_args, $url ) {
				if ( false === strpos( $url, 'api.openai.com/v1/chat/completions' ) ) {
					return $preempt;
				}

				++$this->http_calls;

				return array(
					'headers'  => array( 'content-type' => 'application/json' ),
					'response' => array(
						'code'    => 200,
						'message' => 'OK',
					),
					'body'     => wp_json_encode(
						array(
							'choices' => array(
								array(
									'message' => array(
										'content' => wp_json_encode(
											array(
												'takeaways' => array(
													'First takeaway.',
													'Second takeaway.',
												),
											)
										),
									),
								),
							),
						)
					),
				);
			},
			10,
			3
		);
	}

	/**
	 * Build an on-demand REST request.
	 *
	 * @param int         $post_id Post ID.
	 * @param string|null $nonce   Optional nonce to attach.
	 * @return WP_REST_Request
	 */
	private function on_demand_request( int $post_id, $nonce = null ): WP_REST_Request {
		$request = new WP_REST_Request( 'POST', '/classifai/v1/key-takeaways-on-demand/' . $post_id );
		$request->set_param( 'id', $post_id );

		if ( null !== $nonce ) {
			$request->set_header( 'X-WP-Nonce', $nonce );
		}

		return $request;
	}

	/**
	 * Permission check passes for an anonymous visitor with a valid nonce.
	 */
	public function test_permission_allows_anonymous_with_valid_nonce() {
		$post_id = $this->factory->post->create( array( 'post_status' => 'publish' ) );
		$this->mock_settings();
		wp_set_current_user( 0 );

		$feature = new KeyTakeaways();
		$nonce   = wp_create_nonce( 'wp_rest' );

		$this->assertTrue( $feature->on_demand_takeaways_permissions_check( $this->on_demand_request( $post_id, $nonce ) ) );
	}

	/**
	 * Permission check fails without a valid nonce.
	 */
	public function test_permission_denies_missing_nonce() {
		$post_id = $this->factory->post->create( array( 'post_status' => 'publish' ) );
		$this->mock_settings();

		$feature = new KeyTakeaways();

		$this->assertFalse( $feature->on_demand_takeaways_permissions_check( $this->on_demand_request( $post_id ) ) );
	}

	/**
	 * Permission check fails when the feature is not in on-demand mode.
	 */
	public function test_permission_denies_wrong_mode() {
		$post_id = $this->factory->post->create( array( 'post_status' => 'publish' ) );
		$this->mock_settings( 'manual' );

		$feature = new KeyTakeaways();
		$nonce   = wp_create_nonce( 'wp_rest' );

		$this->assertFalse( $feature->on_demand_takeaways_permissions_check( $this->on_demand_request( $post_id, $nonce ) ) );
	}

	/**
	 * Permission check fails for an unpublished post.
	 */
	public function test_permission_denies_unpublished_post() {
		$post_id = $this->factory->post->create( array( 'post_status' => 'draft' ) );
		$this->mock_settings();

		$feature = new KeyTakeaways();
		$nonce   = wp_create_nonce( 'wp_rest' );

		$this->assertFalse( $feature->on_demand_takeaways_permissions_check( $this->on_demand_request( $post_id, $nonce ) ) );
	}

	/**
	 * Permission check fails for an unsupported post type.
	 */
	public function test_permission_denies_unsupported_post_type() {
		$post_id = $this->factory->post->create(
			array(
				'post_status' => 'publish',
				'post_type'   => 'page',
			)
		);
		$this->mock_settings( 'on_demand', array( 'post' => 'post' ) );

		$feature = new KeyTakeaways();
		$nonce   = wp_create_nonce( 'wp_rest' );

		$this->assertFalse( $feature->on_demand_takeaways_permissions_check( $this->on_demand_request( $post_id, $nonce ) ) );
	}

	/**
	 * The permission filter can override the default decision.
	 */
	public function test_permission_filter_override() {
		$post_id = $this->factory->post->create( array( 'post_status' => 'publish' ) );
		$this->mock_settings();
		add_filter( 'classifai_key_takeaways_on_demand_permission', '__return_false' );

		$feature = new KeyTakeaways();
		$nonce   = wp_create_nonce( 'wp_rest' );

		$this->assertFalse( $feature->on_demand_takeaways_permissions_check( $this->on_demand_request( $post_id, $nonce ) ) );
	}

	/**
	 * The handler generates, stores and returns takeaways.
	 */
	public function test_handler_generates_and_stores() {
		$author_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		$post_id   = $this->factory->post->create(
			array(
				'post_status'  => 'publish',
				'post_author'  => $author_id,
				'post_content' => 'This article explains structured outputs and why they help parsing.',
			)
		);
		$this->mock_settings();
		$this->mock_openai_response();

		$feature  = new KeyTakeaways();
		$response = $feature->generate_key_takeaways_on_demand( $this->on_demand_request( $post_id ) );
		$data     = $response->get_data();

		$this->assertTrue( $data['success'] );
		$this->assertSame( array( 'First takeaway.', 'Second takeaway.' ), $data['takeaways'] );
		$this->assertStringContainsString( 'First takeaway.', $data['html'] );
		$this->assertSame( array( 'First takeaway.', 'Second takeaway.' ), get_post_meta( $post_id, KeyTakeaways::TAKEAWAYS_META_KEY, true ) );
		$this->assertNotEmpty( get_post_meta( $post_id, KeyTakeaways::TAKEAWAYS_HASH_KEY, true ) );
		$this->assertSame( 1, $this->http_calls );
	}

	/**
	 * A second request serves stored takeaways without regenerating.
	 */
	public function test_handler_returns_cached_without_regenerating() {
		$author_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		$post_id   = $this->factory->post->create(
			array(
				'post_status'  => 'publish',
				'post_author'  => $author_id,
				'post_content' => 'This article explains structured outputs and why they help parsing.',
			)
		);
		$this->mock_settings();
		$this->mock_openai_response();

		$feature = new KeyTakeaways();
		$feature->generate_key_takeaways_on_demand( $this->on_demand_request( $post_id ) );
		$response = $feature->generate_key_takeaways_on_demand( $this->on_demand_request( $post_id ) );
		$data     = $response->get_data();

		$this->assertTrue( $data['success'] );
		$this->assertSame( array( 'First takeaway.', 'Second takeaway.' ), $data['takeaways'] );
		$this->assertSame( 1, $this->http_calls, 'Provider should only be hit once.' );
	}

	/**
	 * Render the on-demand button against the main loop for a post.
	 *
	 * @param int $post_id Post ID to view.
	 * @return string The filtered content.
	 */
	private function render_button_in_loop( int $post_id ): string {
		$feature = new KeyTakeaways();
		$output  = '';

		$this->go_to( get_permalink( $post_id ) );

		while ( have_posts() ) {
			the_post();
			$output = $feature->render_takeaways_button( 'POST_CONTENT' );
		}

		wp_reset_postdata();

		return $output;
	}

	/**
	 * The button renders on a supported post without the block.
	 */
	public function test_button_renders_without_block() {
		$post_id = $this->factory->post->create(
			array(
				'post_status'  => 'publish',
				'post_content' => 'Just some plain content.',
			)
		);
		$this->mock_settings();

		$output = $this->render_button_in_loop( $post_id );

		$this->assertStringContainsString( 'classifai-key-takeaways-toggle', $output );
	}

	/**
	 * The button is suppressed when the post already contains the block.
	 */
	public function test_button_suppressed_when_block_present() {
		$post_id = $this->factory->post->create(
			array(
				'post_status'  => 'publish',
				'post_content' => '<!-- wp:classifai/key-takeaways {"takeaways":["One."]} /-->',
			)
		);
		$this->mock_settings();

		$output = $this->render_button_in_loop( $post_id );

		$this->assertStringNotContainsString( 'classifai-key-takeaways-toggle', $output );
		$this->assertSame( 'POST_CONTENT', $output );
	}

	/**
	 * A concurrent request is collapsed by the per-post lock.
	 */
	public function test_handler_locks_concurrent_requests() {
		$post_id = $this->factory->post->create( array( 'post_status' => 'publish' ) );
		$this->mock_settings();
		$this->mock_openai_response();

		set_transient( 'classifai_key_takeaways_on_demand_lock_' . $post_id, 1, 5 * MINUTE_IN_SECONDS );

		$feature  = new KeyTakeaways();
		$response = $feature->generate_key_takeaways_on_demand( $this->on_demand_request( $post_id ) );
		$data     = $response->get_data();

		$this->assertFalse( $data['success'] );
		$this->assertTrue( $data['inProgress'] );
		$this->assertSame( 0, $this->http_calls );
	}

	/**
	 * Editing the post content invalidates stored takeaways.
	 */
	public function test_content_change_invalidates_takeaways() {
		$post_id = $this->factory->post->create(
			array(
				'post_status'  => 'publish',
				'post_content' => 'Original content.',
			)
		);
		$this->mock_settings();

		$feature = new KeyTakeaways();
		update_post_meta( $post_id, KeyTakeaways::TAKEAWAYS_META_KEY, array( 'Stored takeaway.' ) );
		update_post_meta( $post_id, KeyTakeaways::TAKEAWAYS_HASH_KEY, $feature->get_content_hash( $post_id ) );

		// Unchanged content keeps the stored takeaways.
		$feature->maybe_invalidate_on_demand_takeaways( $post_id );
		$this->assertNotEmpty( get_post_meta( $post_id, KeyTakeaways::TAKEAWAYS_META_KEY, true ) );

		// Editing the content clears them.
		wp_update_post(
			array(
				'ID'           => $post_id,
				'post_content' => 'Brand new content.',
			)
		);
		$feature->maybe_invalidate_on_demand_takeaways( $post_id );

		$this->assertEmpty( get_post_meta( $post_id, KeyTakeaways::TAKEAWAYS_META_KEY, true ) );
		$this->assertEmpty( get_post_meta( $post_id, KeyTakeaways::TAKEAWAYS_HASH_KEY, true ) );
	}

	/**
	 * Build an editor (block) generation request.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $run     Either `auto` or `manual`.
	 * @return WP_REST_Request
	 */
	private function block_request( int $post_id, string $run = 'auto' ): WP_REST_Request {
		$request = new WP_REST_Request( 'POST', '/classifai/v1/key-takeaways/' );
		$request->set_param( 'id', $post_id );
		$request->set_param( 'content', 'This article explains structured outputs and why they help parsing.' );
		$request->set_param( 'title', 'Structured Outputs' );
		$request->set_param( 'render', 'list' );
		$request->set_param( 'run', $run );

		return $request;
	}

	/**
	 * The block reuses stored takeaways on auto load instead of regenerating.
	 */
	public function test_block_auto_load_reuses_stored_meta() {
		$post_id = $this->factory->post->create( array( 'post_status' => 'publish' ) );
		$this->mock_settings();
		$this->mock_openai_response();

		update_post_meta( $post_id, KeyTakeaways::TAKEAWAYS_META_KEY, array( 'Stored takeaway.' ) );

		$feature  = new KeyTakeaways();
		$response = $feature->rest_endpoint_callback( $this->block_request( $post_id, 'auto' ) );

		$this->assertSame( array( 'Stored takeaway.' ), $response->get_data() );
		$this->assertSame( 0, $this->http_calls, 'Provider should not be hit when meta exists.' );
	}

	/**
	 * Block generation persists takeaways to the shared post meta.
	 */
	public function test_block_generation_persists_meta() {
		$author_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		$post_id   = $this->factory->post->create(
			array(
				'post_status' => 'publish',
				'post_author' => $author_id,
			)
		);
		$this->mock_settings();
		$this->mock_openai_response();
		wp_set_current_user( $author_id );

		$feature  = new KeyTakeaways();
		$response = $feature->rest_endpoint_callback( $this->block_request( $post_id, 'manual' ) );

		$this->assertSame( array( 'First takeaway.', 'Second takeaway.' ), $response->get_data() );
		$this->assertSame( array( 'First takeaway.', 'Second takeaway.' ), get_post_meta( $post_id, KeyTakeaways::TAKEAWAYS_META_KEY, true ) );
		$this->assertNotEmpty( get_post_meta( $post_id, KeyTakeaways::TAKEAWAYS_HASH_KEY, true ) );
	}

	/**
	 * An explicit manual refresh regenerates even when takeaways are stored.
	 */
	public function test_block_manual_run_regenerates_despite_stored_meta() {
		$author_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		$post_id   = $this->factory->post->create(
			array(
				'post_status' => 'publish',
				'post_author' => $author_id,
			)
		);
		$this->mock_settings();
		$this->mock_openai_response();
		wp_set_current_user( $author_id );

		update_post_meta( $post_id, KeyTakeaways::TAKEAWAYS_META_KEY, array( 'Old takeaway.' ) );

		$feature  = new KeyTakeaways();
		$response = $feature->rest_endpoint_callback( $this->block_request( $post_id, 'manual' ) );

		$this->assertSame( array( 'First takeaway.', 'Second takeaway.' ), $response->get_data() );
		$this->assertSame( 1, $this->http_calls );
		$this->assertSame( array( 'First takeaway.', 'Second takeaway.' ), get_post_meta( $post_id, KeyTakeaways::TAKEAWAYS_META_KEY, true ) );
	}
}
