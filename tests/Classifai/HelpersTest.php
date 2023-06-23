<?php

namespace Classifai;

/**
 * @group helpers
 */
class HelpersTest extends \WP_UnitTestCase {

	/**
	 * Set up method.
	 */
	public function set_up() {
		register_post_status( 'unread', array(
			'label'                     => _x( 'Unread', 'post' ),
			'public'                    => true,
			'exclude_from_search'       => false,
			'show_in_admin_all_list'    => true,
			'show_in_admin_status_list' => true,
			'label_count'               => _n_noop( 'Unread <span class="count">(%s)</span>', 'Unread <span class="count">(%s)</span>' ),
		) );

		parent::set_up();
	}

	/**
	 * Tear down method.
	 */
	public function tear_down() {
		$this->remove_added_uploads();
		parent::tear_down();
	}

	function test_it_has_a_plugin_instance() {
		$actual = get_plugin();
		$this->assertInstanceOf( '\Classifai\Plugin', $actual );
	}

	function test_it_has_plugin_settings() {
		$this->markTestSkipped();
		update_option( 'classifai_settings', [ 'post_types' => [ 'foo' ] ] );

		$actual = get_plugin_settings();
		$this->assertEquals( [ 'foo' ], $actual['post_types'] );
	}

	function test_it_has_default_supported_post_types() {
		$actual = get_supported_post_types();
		$this->assertEquals( [], $actual );
	}

	function test_it_can_lookup_supported_post_types_from_option() {
		$this->markTestSkipped();
		update_option( 'classifai_settings', [ 'post_types' => [ 'post' => 1, 'page' => 1 ] ] );

		$actual = get_supported_post_types();
		$this->assertEquals( [ 'post', 'page' ], $actual );
	}

	function test_it_can_override_supported_post_types_with_filter() {
		add_filter( 'classifai_post_types', function() {
			return [ 'page' ];
		} );

		$actual = get_supported_post_types();
		$this->assertEquals( [ 'page' ], $actual );
	}

	function test_it_has_feature_thresholds() {
		$this->markTestSkipped();
		update_option( 'classifai_settings', [
			'features' => [
				'category_threshold' => 50,
			]
		] );

		$actual = get_feature_threshold( 'category' );
		$this->assertEquals( 0.50, $actual );
	}

	function test_it_can_change_plugin_settings() {
		$this->markTestSkipped();
		set_plugin_settings( [
			'features' => [
				'category_threshold' => 50,
			]
		] );

		$actual = get_feature_threshold( 'category' );
		$this->assertEquals( 0.50, $actual );
	}

	function test_it_knows_configured_username() {
		$this->markTestSkipped();
		set_plugin_settings( [
			'credentials' => [
				'watson_username' => 'foo',
			]
		] );

		$actual = get_watson_username();
		$this->assertEquals( 'foo', $actual );
	}

	function test_it_knows_configured_password() {
		$this->markTestSkipped();
		set_plugin_settings( [
			'credentials' => [
				'watson_password' => 'foo',
			]
		] );

		$actual = get_watson_password();
		$this->assertEquals( 'foo', $actual );
	}

	function test_it_has_default_feature_taxonomies() {
		$expected = [
			'category' => WATSON_CATEGORY_TAXONOMY,
			'keyword'  => WATSON_KEYWORD_TAXONOMY,
			'concept'  => WATSON_CONCEPT_TAXONOMY,
			'entity'   => WATSON_ENTITY_TAXONOMY,
		];

		foreach ( $expected as $feature => $taxonomy ) {
			$actual = get_feature_taxonomy( $feature );
			$this->assertEquals( $taxonomy, $actual );
		}
	}

	function test_it_knows_configured_feature_taxonomies() {
		$this->markTestSkipped();
		set_plugin_settings( [
			'features'              => [
				'category'          => true,
				'category_taxonomy' => 'a',

				'keyword'          => true,
				'keyword_taxonomy' => 'b',

				'concept'          => true,
				'concept_taxonomy' => 'c',

				'entity'          => true,
				'entity_taxonomy' => 'd',
			]
		] );

		$expected = [
			'category' => 'a',
			'keyword'  => 'b',
			'concept'  => 'c',
			'entity'   => 'd',
		];

		foreach ( $expected as $feature => $taxonomy ) {
			$actual = get_feature_taxonomy( $feature );
			$this->assertEquals( $taxonomy, $actual );
		}
	}

	/**
	 * @covers \Classifai\sort_images_by_size_cb
	 */
	public function test_sort_images_by_size_cb() {
		$this->assertEquals(
			0,
			sort_images_by_size_cb(
				[
					'height' => 4,
					'width'  => 6,
				],
				[
					'height' => 2,
					'width'  => 8,
				]
			)
		);

		$this->assertEquals(
			-1,
			sort_images_by_size_cb(
				[
					'height' => 4,
					'width'  => 7,
				],
				[
					'height' => 2,
					'width'  => 8,
				]
			)
		);

		$this->assertEquals(
			1,
			sort_images_by_size_cb(
				[
					'height' => 4,
					'width'  => 6,
				],
				[
					'height' => 2,
					'width'  => 9,
				]
			)
		);
	}

	/**
	 * @covers \Classifai\get_largest_acceptable_image_url
	 */
	public function test_get_largest_acceptable_image_url() {
		$attachment = $this->factory->attachment->create_upload_object( DIR_TESTDATA .'/images/33772.jpg' ); // ~172KB image.

		$set_150kb_max_filesize = function() {
			return 150000;
		};
		add_filter( 'classifai_computer_vision_max_filesize', $set_150kb_max_filesize );

		$url = get_largest_acceptable_image_url(
			get_attached_file( $attachment ),
			wp_get_attachment_url( $attachment, 'full' ),
			wp_get_attachment_metadata( $attachment )['sizes'],
			computer_vision_max_filesize()
		);
		$this->assertEquals( sprintf( '%s/33772-1536x864.jpg', wp_upload_dir()['url'] ), $url );

		$attachment = $this->factory->attachment->create_upload_object( DIR_TESTDATA .'/images/2004-07-22-DSC_0008.jpg' ); // ~109kb image.
		$url = get_largest_acceptable_image_url(
			get_attached_file( $attachment ),
			wp_get_attachment_url( $attachment, 'full' ),
			wp_get_attachment_metadata( $attachment )['sizes'],
			computer_vision_max_filesize()
		);
		$this->assertEquals( sprintf( '%s/2004-07-22-DSC_0008.jpg', wp_upload_dir()['url'] ), $url );

		remove_filter( 'classifai_computer_vision_max_filesize', $set_150kb_max_filesize );

		$set_1kb_max_filesize = function() {
			return 1000;
		};
		add_filter( 'classifai_computer_vision_max_filesize', $set_1kb_max_filesize );

		$url = get_largest_acceptable_image_url(
			get_attached_file( $attachment ),
			wp_get_attachment_url( $attachment, 'full' ),
			wp_get_attachment_metadata( $attachment )['sizes'],
			computer_vision_max_filesize()
		);
		$this->assertNull( $url );

		remove_filter( 'classifai_computer_vision_max_filesize', $set_1kb_max_filesize );
	}

	public function test_clean_input() {
		$_POST['classify_test_string'] = '<h1>Hello, world!</h1>';
		$_GET['classify_test_int']     = -2.4;

		$sanitized_string = clean_input( 'classify_test_string' );
		$this->assertEquals( $sanitized_string, 'Hello, world!' );

		$sanitized_int = clean_input( 'classify_test_int', true, 'absint' );
		$this->assertEquals( $sanitized_int, 2 );
	}

	/**
	 * Tests for the get_post_statuses method.
	 */
	public function test_get_post_statuses() {
		$all_statuses  = get_all_post_statuses();
		$core_statuses = get_post_statuses();
		$statuses_diff = array_diff( $core_statuses, $all_statuses );
		$this->assertEquals( 0, count( $statuses_diff ) );
		$this->assertArrayHasKey( 'unread', $all_statuses );
	}
}
