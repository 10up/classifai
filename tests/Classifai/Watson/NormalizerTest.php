<?php

namespace Classifai\Watson;

class NormalizeTest extends \WP_UnitTestCase {

	public $normalizer;

	function set_up() {
		parent::set_up();

		$this->normalizer = new Normalizer();
	}

	function test_it_includes_post_content_text() {
		$post_id = $this->factory->post->create( [
			'post_content' => 'lorem ipsum',
		] );

		$actual = $this->normalizer->normalize( $post_id );
		$this->assertContains( 'lorem ipsum', $actual );
	}

	function test_it_includes_post_title() {
		$post_id = $this->factory->post->create( [
			'post_title' => 'foo title',
		] );

		$actual = $this->normalizer->normalize( $post_id );
		$this->assertContains( 'foo title', $actual );
	}

	function test_it_strips_out_tags() {
		$post_id = $this->factory->post->create( [
			'post_content' => '<b>lorem</b> <i>ipsum</i>',
		] );

		$actual = $this->normalizer->normalize( $post_id );
		$this->assertNotContains( '<b>', $actual );
		$this->assertNotContains( '<i>', $actual );
		$this->assertContains( 'lorem ipsum', $actual );
	}

	function test_it_keeps_caption_text() {
		$post_id = $this->factory->post->create( [
			'post_content' => '[caption]foo bar[/caption]',
		] );

		$actual = $this->normalizer->normalize( $post_id );
		$this->assertNotContains( '[caption]', $actual );
		$this->assertContains( 'foo bar', $actual );
	}

	function test_it_removes_abbreviations() {
		$post_id = $this->factory->post->create( [
			'post_content' => 'VIM rocks!',
		] );

		$actual = $this->normalizer->normalize( $post_id );
		$this->assertNotContains( 'VIM', $actual );
		$this->assertContains( 'rocks', $actual );
	}

	function test_it_allows_custom_normalizations() {
		add_filter( 'classifai_normalize', function( $post_content, $post_id ) {
			return 'custom';
		}, 10, 2 );

		$post_id = $this->factory->post->create();
		$actual  = $this->normalizer->normalize( $post_id );

		$this->assertEquals( 'custom', $actual );
	}

}
