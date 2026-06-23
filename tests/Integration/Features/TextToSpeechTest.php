<?php
/**
 * Tests for TextToSpeech REST permissions and audio meta handling.
 */

namespace Classifai\Tests\Features;

use Classifai\Tests\TestCase;
use Classifai\Features\TextToSpeech;
use WP_REST_Request;

/**
 * @group features
 * @coversDefaultClass \Classifai\Features\TextToSpeech
 */
class TextToSpeechTest extends TestCase {

	const OPTION = 'classifai_feature_text_to_speech_generation';

	public function tear_down() {
		delete_option( self::OPTION );
		parent::tear_down();
	}

	/**
	 * Configure and enable the feature (Azure Speech provider) for the current user.
	 */
	private function enable_feature() {
		update_option(
			self::OPTION,
			[
				'status'                  => '1',
				'provider'                => 'ms_azure_text_to_speech',
				'ms_azure_text_to_speech' => [ 'authenticated' => true ],
				'roles'                   => [ 'administrator' => 'administrator' ],
				'post_types'              => [ 'post' => 'post' ],
			]
		);
	}

	/**
	 * Configure and enable the feature in on-demand (front-end) generation mode.
	 */
	private function enable_on_demand() {
		update_option(
			self::OPTION,
			[
				'status'                  => '1',
				'provider'                => 'ms_azure_text_to_speech',
				'ms_azure_text_to_speech' => [ 'authenticated' => true ],
				'roles'                   => [ 'administrator' => 'administrator' ],
				'post_types'              => [ 'post' => 'post' ],
				'generation_timing'       => 'on_demand',
			]
		);
	}

	/**
	 * @param mixed $post_id Post ID param.
	 * @return WP_REST_Request
	 */
	private function request( $post_id ): WP_REST_Request {
		$request = new WP_REST_Request( 'GET', '/classifai/v1/synthesize-speech/' . $post_id );
		$request->set_param( 'id', $post_id );
		return $request;
	}

	/**
	 * Build a request for the public on-demand synthesis route.
	 *
	 * @param mixed       $post_id Post ID param.
	 * @param string|null $nonce   `wp_rest` nonce to send as X-WP-Nonce, or null to omit.
	 * @return WP_REST_Request
	 */
	private function on_demand_request( $post_id, $nonce = null ): WP_REST_Request {
		$request = new WP_REST_Request( 'POST', '/classifai/v1/synthesize-speech-on-demand/' . $post_id );
		$request->set_param( 'id', $post_id );
		if ( null !== $nonce ) {
			$request->set_header( 'X-WP-Nonce', $nonce );
		}
		return $request;
	}

	/**
	 * @covers ::speech_synthesis_permissions_check
	 */
	public function test_denied_when_user_cannot_edit() {
		$post_id = self::factory()->post->create();
		$this->as_user_with_role( 'subscriber' );
		$this->enable_feature();

		$this->assertFalse( ( new TextToSpeech() )->speech_synthesis_permissions_check( $this->request( $post_id ) ) );
	}

	/**
	 * @covers ::speech_synthesis_permissions_check
	 */
	public function test_denied_for_unsupported_post_type() {
		$page_id = self::factory()->post->create( [ 'post_type' => 'page' ] );
		$this->as_user_with_role( 'administrator' );
		$this->enable_feature(); // Only 'post' is supported.

		$result = ( new TextToSpeech() )->speech_synthesis_permissions_check( $this->request( $page_id ) );
		$this->assertWPErrorCode( 'not_enabled', $result );
	}

	/**
	 * @covers ::speech_synthesis_permissions_check
	 */
	public function test_denied_when_feature_disabled() {
		$post_id = self::factory()->post->create();
		$this->as_user_with_role( 'administrator' );
		update_option( self::OPTION, [ 'status' => '0', 'post_types' => [ 'post' => 'post' ] ] );

		$result = ( new TextToSpeech() )->speech_synthesis_permissions_check( $this->request( $post_id ) );
		$this->assertWPErrorCode( 'not_enabled', $result );
	}

	/**
	 * @covers ::speech_synthesis_permissions_check
	 */
	public function test_allows_happy_path() {
		$post_id = self::factory()->post->create();
		$this->as_user_with_role( 'administrator' );
		$this->enable_feature();

		$this->assertTrue( ( new TextToSpeech() )->speech_synthesis_permissions_check( $this->request( $post_id ) ) );
	}

	/**
	 * The save handler writes the audio attachment ID and timestamp meta.
	 *
	 * @covers ::save
	 */
	public function test_save_writes_audio_meta() {
		$post_id = self::factory()->post->create();

		$attachment_id = ( new TextToSpeech() )->save( 'fake-audio-bytes', $post_id );

		$this->assertIsInt( $attachment_id );
		$this->assertSame( $attachment_id, (int) get_post_meta( $post_id, TextToSpeech::AUDIO_ID_KEY, true ) );
		$this->assertNotEmpty( get_post_meta( $post_id, TextToSpeech::AUDIO_TIMESTAMP_KEY, true ) );
	}

	/**
	 * Re-saving replaces the previously generated audio attachment.
	 *
	 * @covers ::save
	 */
	public function test_save_replaces_existing_audio() {
		$feature = new TextToSpeech();
		$post_id = self::factory()->post->create();

		$first  = $feature->save( 'first-audio', $post_id );
		$second = $feature->save( 'second-audio', $post_id );

		$this->assertNotSame( $first, $second );
		$this->assertSame( $second, (int) get_post_meta( $post_id, TextToSpeech::AUDIO_ID_KEY, true ) );
		$this->assertNull( get_post( $first ), 'The old audio attachment is deleted.' );
	}

	/**
	 * On-demand generation is denied when the feature is not in on-demand mode.
	 *
	 * @covers ::on_demand_synthesis_permissions_check
	 */
	public function test_on_demand_denied_when_not_on_demand_mode() {
		$post_id = self::factory()->post->create( [ 'post_status' => 'publish' ] );
		$this->as_user_with_role( 'administrator' );
		$this->enable_feature(); // Defaults to automatic mode.

		$feature = new TextToSpeech();
		$nonce   = wp_create_nonce( 'wp_rest' );

		$this->assertFalse( $feature->on_demand_synthesis_permissions_check( $this->on_demand_request( $post_id, $nonce ) ) );
	}

	/**
	 * On-demand generation is denied for posts that aren't published.
	 *
	 * @covers ::on_demand_synthesis_permissions_check
	 */
	public function test_on_demand_denied_for_unpublished_post() {
		$post_id = self::factory()->post->create( [ 'post_status' => 'draft' ] );
		$this->enable_on_demand();

		$feature = new TextToSpeech();
		$nonce   = wp_create_nonce( 'wp_rest' );

		$this->assertFalse( $feature->on_demand_synthesis_permissions_check( $this->on_demand_request( $post_id, $nonce ) ) );
	}

	/**
	 * On-demand generation is denied without a valid nonce.
	 *
	 * @covers ::on_demand_synthesis_permissions_check
	 */
	public function test_on_demand_denied_with_invalid_nonce() {
		$post_id = self::factory()->post->create( [ 'post_status' => 'publish' ] );
		$this->enable_on_demand();

		$feature = new TextToSpeech();

		$this->assertFalse( $feature->on_demand_synthesis_permissions_check( $this->on_demand_request( $post_id, 'bogus-nonce' ) ) );
	}

	/**
	 * On-demand generation is allowed for an anonymous visitor with a valid nonce.
	 *
	 * @covers ::on_demand_synthesis_permissions_check
	 */
	public function test_on_demand_allows_anonymous_with_valid_nonce() {
		$post_id = self::factory()->post->create( [ 'post_status' => 'publish' ] );
		$this->enable_on_demand();
		wp_set_current_user( 0 ); // Anonymous visitor.

		$feature = new TextToSpeech();
		$nonce   = wp_create_nonce( 'wp_rest' );

		$this->assertTrue( $feature->on_demand_synthesis_permissions_check( $this->on_demand_request( $post_id, $nonce ) ) );
	}

	/**
	 * Generation is denied for a post opted out at the post level, even with a
	 * valid (global) nonce borrowed from another page.
	 *
	 * @covers ::on_demand_synthesis_permissions_check
	 */
	public function test_on_demand_denied_when_post_opted_out() {
		$post_id = self::factory()->post->create( [ 'post_status' => 'publish' ] );
		$this->enable_on_demand();
		update_post_meta( $post_id, TextToSpeech::DISABLE_ON_DEMAND_KEY, true );

		$feature = new TextToSpeech();
		$nonce   = wp_create_nonce( 'wp_rest' );

		$this->assertFalse( $feature->on_demand_synthesis_permissions_check( $this->on_demand_request( $post_id, $nonce ) ) );
	}

	/**
	 * Generation is denied when audio controls are hidden for the post.
	 *
	 * @covers ::on_demand_synthesis_permissions_check
	 */
	public function test_on_demand_denied_when_audio_display_hidden() {
		$post_id = self::factory()->post->create( [ 'post_status' => 'publish' ] );
		$this->enable_on_demand();
		update_post_meta( $post_id, TextToSpeech::DISPLAY_GENERATED_AUDIO, false );

		$feature = new TextToSpeech();
		$nonce   = wp_create_nonce( 'wp_rest' );

		$this->assertFalse( $feature->on_demand_synthesis_permissions_check( $this->on_demand_request( $post_id, $nonce ) ) );
	}

	/**
	 * The permission filter can override the default decision.
	 *
	 * @covers ::on_demand_synthesis_permissions_check
	 */
	public function test_on_demand_permission_filter_override() {
		$post_id = self::factory()->post->create( [ 'post_status' => 'publish' ] );
		$this->enable_on_demand();

		$feature = new TextToSpeech();

		// Without a nonce the default decision is false; the filter forces true.
		add_filter( 'classifai_tts_on_demand_permission', '__return_true' );
		$this->assertTrue( $feature->on_demand_synthesis_permissions_check( $this->on_demand_request( $post_id ) ) );
	}

	/**
	 * The handler generates, stores and returns a playable URL on first listen.
	 *
	 * @covers ::synthesize_speech_on_demand
	 */
	public function test_on_demand_handler_generates_and_returns_url() {
		$post_id = self::factory()->post->create( [ 'post_status' => 'publish' ] );
		$this->enable_on_demand();

		// Bypass the provider HTTP call with canned audio bytes.
		add_filter( 'classifai_pre_fetch_feature_response', fn() => 'fake-audio-bytes' );

		$response = ( new TextToSpeech() )->synthesize_speech_on_demand( $this->on_demand_request( $post_id ) );
		$data     = $response->get_data();

		$this->assertTrue( $data['success'] );
		$this->assertNotEmpty( $data['url'] );
		$this->assertSame( $data['audio_id'], (int) get_post_meta( $post_id, TextToSpeech::AUDIO_ID_KEY, true ) );
	}

	/**
	 * The handler returns existing audio without regenerating.
	 *
	 * @covers ::synthesize_speech_on_demand
	 */
	public function test_on_demand_handler_reuses_existing_audio() {
		$feature = new TextToSpeech();
		$post_id = self::factory()->post->create( [ 'post_status' => 'publish' ] );
		$this->enable_on_demand();

		$existing_id = $feature->save( 'existing-audio', $post_id );

		// If generation runs, this counter increments — it must not.
		$called = 0;
		add_filter(
			'classifai_pre_fetch_feature_response',
			function () use ( &$called ) {
				$called++;
				return 'should-not-run';
			}
		);

		$response = $feature->synthesize_speech_on_demand( $this->on_demand_request( $post_id ) );
		$data     = $response->get_data();

		$this->assertSame( 0, $called, 'Generation should not run when audio already exists.' );
		$this->assertTrue( $data['success'] );
		$this->assertSame( $existing_id, $data['audio_id'] );
	}

	/**
	 * A per-post lock prevents a second concurrent generation.
	 *
	 * @covers ::synthesize_speech_on_demand
	 */
	public function test_on_demand_handler_respects_lock() {
		$post_id = self::factory()->post->create( [ 'post_status' => 'publish' ] );
		$this->enable_on_demand();

		set_transient( 'classifai_tts_on_demand_lock_' . $post_id, 1, MINUTE_IN_SECONDS );

		$called = 0;
		add_filter(
			'classifai_pre_fetch_feature_response',
			function () use ( &$called ) {
				$called++;
				return 'should-not-run';
			}
		);

		$response = ( new TextToSpeech() )->synthesize_speech_on_demand( $this->on_demand_request( $post_id ) );
		$data     = $response->get_data();

		$this->assertFalse( $data['success'] );
		$this->assertTrue( $data['inProgress'] );
		$this->assertSame( 0, $called, 'Generation should be skipped while locked.' );
		$this->assertEmpty( get_post_meta( $post_id, TextToSpeech::AUDIO_ID_KEY, true ) );
	}

	/**
	 * Stored audio is cleared when an on-demand post's content changes.
	 *
	 * @covers ::maybe_invalidate_on_demand_audio
	 */
	public function test_invalidate_clears_audio_on_content_change() {
		$feature = new TextToSpeech();
		$post_id = self::factory()->post->create( [ 'post_status' => 'publish' ] );
		$this->enable_on_demand();

		$feature->save( 'audio-bytes', $post_id );
		update_post_meta( $post_id, TextToSpeech::AUDIO_HASH_KEY, 'stale-hash' );

		$feature->maybe_invalidate_on_demand_audio( $post_id );

		$this->assertEmpty( get_post_meta( $post_id, TextToSpeech::AUDIO_ID_KEY, true ) );
		$this->assertEmpty( get_post_meta( $post_id, TextToSpeech::AUDIO_HASH_KEY, true ) );
	}

	/**
	 * The enable-toggle help text is worded for the configured timing mode.
	 *
	 * @covers ::get_audio_generation_help_text
	 */
	public function test_help_text_varies_by_mode() {
		$feature = new TextToSpeech();

		update_option( self::OPTION, [ 'generation_timing' => 'automatic' ] );
		$this->assertStringContainsString( 'published or updated', $feature->get_audio_generation_help_text( 'Post' ) );

		update_option( self::OPTION, [ 'generation_timing' => 'manual' ] );
		$this->assertStringContainsString( "won't be generated", $feature->get_audio_generation_help_text( 'Post' ) );

		update_option( self::OPTION, [ 'generation_timing' => 'on_demand' ] );
		$this->assertStringContainsString( 'first time a visitor', $feature->get_audio_generation_help_text( 'Post' ) );
	}

	/**
	 * The per-post toggle defaults on in on-demand mode and reflects the opt-out.
	 *
	 * @covers ::is_synthesize_speech_enabled
	 */
	public function test_on_demand_toggle_defaults_on_and_reflects_opt_out() {
		$feature = new TextToSpeech();
		$post_id = self::factory()->post->create( [ 'post_status' => 'publish' ] );
		$this->enable_on_demand();

		// No opt-out meta yet → toggle is on.
		$this->assertTrue( $feature->is_synthesize_speech_enabled( $post_id ) );

		update_post_meta( $post_id, TextToSpeech::DISABLE_ON_DEMAND_KEY, true );
		$this->assertFalse( $feature->is_synthesize_speech_enabled( $post_id ) );
	}

	/**
	 * Saving the classic meta box persists the opt-out without generating audio.
	 *
	 * @covers ::save_post_metadata
	 */
	public function test_on_demand_save_persists_opt_out() {
		$feature = new TextToSpeech();
		$post_id = self::factory()->post->create( [ 'post_status' => 'publish' ] );
		$this->as_user_with_role( 'administrator' );
		$this->enable_on_demand();

		$_POST['classifai_text_to_speech_meta'] = wp_create_nonce( 'classifai_text_to_speech_meta_action' );

		// Toggle off (checkbox not submitted) → opt-out persisted.
		unset( $_POST['classifai_synthesize_speech'] );
		$feature->save_post_metadata( $post_id );
		$this->assertTrue( (bool) get_post_meta( $post_id, TextToSpeech::DISABLE_ON_DEMAND_KEY, true ) );
		$this->assertEmpty( get_post_meta( $post_id, TextToSpeech::AUDIO_ID_KEY, true ), 'No audio is generated on save in on-demand mode.' );

		// Toggle back on → opt-out cleared.
		$_POST['classifai_synthesize_speech'] = '1';
		$feature->save_post_metadata( $post_id );
		$this->assertEmpty( get_post_meta( $post_id, TextToSpeech::DISABLE_ON_DEMAND_KEY, true ) );

		unset( $_POST['classifai_text_to_speech_meta'], $_POST['classifai_synthesize_speech'] );
	}

	/**
	 * The front-end player is hidden for a post opted out of on-demand audio.
	 *
	 * @covers ::render_post_audio_controls
	 */
	public function test_on_demand_player_hidden_when_opted_out() {
		$feature = new TextToSpeech();
		$post_id = self::factory()->post->create( [ 'post_status' => 'publish' ] );
		$this->enable_on_demand();

		$this->go_to( get_permalink( $post_id ) );

		// Opted in (default) → the player renders.
		$this->assertStringContainsString( 'class-post-audio-controls', $feature->render_post_audio_controls( 'CONTENT' ) );

		// Opted out → the content is returned untouched.
		update_post_meta( $post_id, TextToSpeech::DISABLE_ON_DEMAND_KEY, true );
		$this->assertSame( 'CONTENT', $feature->render_post_audio_controls( 'CONTENT' ) );
	}

	/**
	 * Stored audio is kept when content is unchanged.
	 *
	 * @covers ::maybe_invalidate_on_demand_audio
	 */
	public function test_invalidate_keeps_audio_when_unchanged() {
		$feature = new TextToSpeech();
		$post_id = self::factory()->post->create( [ 'post_status' => 'publish' ] );
		$this->enable_on_demand();

		$audio_id = $feature->save( 'audio-bytes', $post_id );
		update_post_meta( $post_id, TextToSpeech::AUDIO_HASH_KEY, md5( $feature->normalize_post_content( $post_id ) ) );

		$feature->maybe_invalidate_on_demand_audio( $post_id );

		$this->assertSame( $audio_id, (int) get_post_meta( $post_id, TextToSpeech::AUDIO_ID_KEY, true ) );
	}
}
