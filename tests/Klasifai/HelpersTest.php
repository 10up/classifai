<?php

namespace Klasifai;

class HelpersTest extends \WP_UnitTestCase {

	function setUp() {
		parent::setUp();
	}

	function test_it_has_a_plugin_instance() {
		$actual = get_plugin();
		$this->assertInstanceOf( '\Klasifai\Plugin', $actual );
	}

	function test_it_has_plugin_settings() {
		update_option( 'klasifai_settings', [ 'post_types' => [ 'foo' ] ] );

		$actual = get_plugin_settings();
		$this->assertEquals( [ 'foo' ], $actual['post_types'] );
	}

	function test_it_has_default_supported_post_types() {
		$actual = get_supported_post_types();
		$this->assertEquals( [ 'post' ], $actual );
	}

	function test_it_can_lookup_supported_post_types_from_option() {
		update_option( 'klasifai_settings', [ 'post_types' => [ 'post' => 1, 'page' => 1 ] ] );

		$actual = get_supported_post_types();
		$this->assertEquals( [ 'post', 'page' ], $actual );
	}

	function test_it_can_override_supported_post_types_with_filter() {
		add_filter( 'klasifai_post_types', function() {
			return [ 'page' ];
		} );

		$actual = get_supported_post_types();
		$this->assertEquals( [ 'page' ], $actual );
	}

	function test_it_has_feature_thresholds() {
		update_option( 'klasifai_settings', [
			'features' => [
				'category_threshold' => 50,
			]
		] );

		$actual = get_feature_threshold( 'category' );
		$this->assertEquals( 0.50, $actual );
	}

	function test_it_can_change_plugin_settings() {
		set_plugin_settings( [
			'features' => [
				'category_threshold' => 50,
			]
		] );

		$actual = get_feature_threshold( 'category' );
		$this->assertEquals( 0.50, $actual );
	}

	function test_it_knows_configured_username() {
		set_plugin_settings( [
			'credentials' => [
				'watson_username' => 'foo',
			]
		] );

		$actual = get_watson_username();
		$this->assertEquals( 'foo', $actual );
	}

	function test_it_knows_configured_password() {
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
}
