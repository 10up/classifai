<?php

namespace Classifai\Tests\Integration\Embeddings;

use Classifai\Features\RecommendedContent;

/**
 * The Recommended Content results cache is keyed only by the source post, so a
 * post that stops being published must invalidate the whole cache (via a version
 * salt) or it lingers in other posts' recommendations until the TTL expires.
 *
 * Invalidation is owned by the RecommendedContent feature and only wired up when
 * the feature is active — if it is off there is no cache to keep clean.
 *
 * @group embeddings
 */
class RecommendedContentCacheTest extends \WP_UnitTestCase {

	public function set_up() {
		parent::set_up();
		wp_cache_delete( 'classifai_recommended_content_version' );
	}

	/**
	 * Register the feature's hooks the way feature_setup() does when the feature is
	 * enabled. WP_UnitTestCase restores hooks after each test, so this stays local.
	 */
	private function enable_feature_hooks(): void {
		( new RecommendedContent() )->feature_setup();
	}

	public function test_trashing_a_published_post_invalidates_the_cache() {
		$this->enable_feature_hooks();
		$post_id = self::factory()->post->create( [ 'post_status' => 'publish' ] );
		$before  = \Classifai\recommended_content_cache_key( $post_id );

		wp_trash_post( $post_id );

		$this->assertNotSame( $before, \Classifai\recommended_content_cache_key( $post_id ) );
	}

	public function test_unpublishing_a_post_invalidates_the_cache() {
		$this->enable_feature_hooks();
		$post_id = self::factory()->post->create( [ 'post_status' => 'publish' ] );
		$before  = \Classifai\recommended_content_cache_key( $post_id );

		wp_update_post(
			[
				'ID'          => $post_id,
				'post_status' => 'draft',
			]
		);

		$this->assertNotSame( $before, \Classifai\recommended_content_cache_key( $post_id ) );
	}

	public function test_force_deleting_a_published_post_invalidates_the_cache() {
		// Force delete skips trash (and thus transition_post_status); the
		// before_delete_post safety net must still invalidate the cache.
		$this->enable_feature_hooks();
		$post_id = self::factory()->post->create( [ 'post_status' => 'publish' ] );
		$before  = \Classifai\recommended_content_cache_key( $post_id );

		wp_delete_post( $post_id, true );

		$this->assertNotSame( $before, \Classifai\recommended_content_cache_key( $post_id ) );
	}

	public function test_editing_a_published_post_does_not_invalidate_the_cache() {
		$this->enable_feature_hooks();
		$post_id = self::factory()->post->create( [ 'post_status' => 'publish' ] );
		$before  = \Classifai\recommended_content_cache_key( $post_id );

		// A publish -> publish edit should not flush recommendation caches.
		wp_update_post(
			[
				'ID'         => $post_id,
				'post_title' => 'Edited title',
			]
		);

		$this->assertSame( $before, \Classifai\recommended_content_cache_key( $post_id ) );
	}

	public function test_cache_is_not_touched_when_feature_is_disabled() {
		// feature_setup() never runs, so no invalidation hooks are registered and
		// trashing a post leaves the cache version untouched.
		$post_id = self::factory()->post->create( [ 'post_status' => 'publish' ] );
		$before  = \Classifai\recommended_content_cache_key( $post_id );

		wp_trash_post( $post_id );

		$this->assertSame( $before, \Classifai\recommended_content_cache_key( $post_id ) );
	}
}
