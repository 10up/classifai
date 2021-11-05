<?php
/**
 * Testing for the SavePostHandler class
 */

namespace Classifai\Tests\Admin;

use \WP_UnitTestCase;
use Classifai\Admin\SavePostHandler;

/**
 * Class SavePostHandlerTest
 * @package Classifai\Tests\Admin
 *
 * @group admin
 */
class SavePostHandlerTest extends WP_UnitTestCase {
	protected $save_post_handler;

	/**
	 * setup method
	 */
	function setUp() {
		parent::setUp();

		$this->save_post_handler = new SavePostHandler();
	}

	function test_is_rest_route() {
		global $wp_filter;

		$saved_filters = $wp_filter['classifai_rest_bases'] ?? null;
		unset( $wp_filter['classifai_rest_bases'] );

		$this->assertEquals( false, $this->save_post_handler->is_rest_route() );

		$_SERVER['REQUEST_URI'] = '/wp-json/wp/v2/users/me';
		$this->assertEquals( false, $this->save_post_handler->is_rest_route() );

		$_SERVER['REQUEST_URI'] = '/wp-json/wp/v2/posts/1';
		$this->assertEquals( true, $this->save_post_handler->is_rest_route() );

		$_SERVER['REQUEST_URI'] = '/wp-json/wp/v2/pages/1';
		$this->assertEquals( true, $this->save_post_handler->is_rest_route() );

		$_SERVER['REQUEST_URI'] = '/wp-json/wp/v2/custom/1';
		$this->assertEquals( false, $this->save_post_handler->is_rest_route() );

		if ( ! is_null( $saved_filters ) ) {
			$wp_filter['classifai_rest_bases'] = $saved_filters;
		}

		add_filter(
			'classifai_rest_bases',
			function( $bases ) {
				$bases[] = 'custom';
				return $bases;
			}
		);
		$this->assertEquals( true, $this->save_post_handler->is_rest_route() );
	}
}
