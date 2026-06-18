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
	 * @param mixed $post_id Post ID param.
	 * @return WP_REST_Request
	 */
	private function request( $post_id ): WP_REST_Request {
		$request = new WP_REST_Request( 'GET', '/classifai/v1/synthesize-speech/' . $post_id );
		$request->set_param( 'id', $post_id );
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
	 * When the provider returns a cached attachment ID (content unchanged), the
	 * existing audio must be left intact rather than overwritten with the
	 * numeric ID coerced to a string.
	 *
	 * @covers ::generate_text_to_speech_audio
	 */
	public function test_cached_audio_is_not_overwritten() {
		$feature = new TextToSpeech();
		$post_id = self::factory()->post->create( [ 'post_content' => 'Some content to read.' ] );
		$this->as_user_with_role( 'administrator' );
		$this->enable_feature();

		// Seed a previously generated audio attachment.
		$attachment_id = $feature->save( 'real-audio-bytes', $post_id );
		$audio_file    = get_attached_file( $attachment_id );

		// Mark the saved content hash so the provider's cached branch is taken.
		update_post_meta( $post_id, TextToSpeech::AUDIO_HASH_KEY, md5( $feature->normalize_post_content( $post_id ) ) );

		$feature->generate_text_to_speech_audio( $post_id );

		// The attachment must be untouched: same ID, same file, same bytes.
		$this->assertSame( $attachment_id, (int) get_post_meta( $post_id, TextToSpeech::AUDIO_ID_KEY, true ) );
		$this->assertNotNull( get_post( $attachment_id ), 'The cached audio attachment is preserved.' );
		$this->assertSame( 'real-audio-bytes', file_get_contents( $audio_file ) );
	}
}
