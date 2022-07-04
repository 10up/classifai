<?php
/**
 * Testing for the SavePostHandler class
 */

namespace Classifai\Tests\Admin;

use \WP_UnitTestCase;
use Classifai\Admin\SavePostHandler;

/**
 * Class SavePostHandlerTest
 *
 * @package Classifai\Tests\Admin
 *
 * @group admin
 */
class SavePostHandlerTest extends WP_UnitTestCase {

	protected $save_post_handler;
	protected $settings = [
		'credentials' => [
			'watson_url'      => 'url',
			'watson_username' => 'username',
			'watson_password' => 'password',
		],
	];

	/**
	 * setup method
	 */
	function set_up() {
		parent::set_up();

		$this->save_post_handler = new SavePostHandler();
	}

	function add_options() {
		update_option( 'classifai_configured', true );
		update_option( 'classifai_watson_nlu', $this->settings );
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

	function test_rest_route_register() {

		$_SERVER['REQUEST_URI'] = '/wp-json/wp/v2/posts/1';

		$this->assertEquals( false, $this->save_post_handler->can_register() );

		$this->add_options();

		$this->assertEquals( true, $this->save_post_handler->can_register() );
	}

	function test_is_admin_register() {

		set_current_screen( 'edit.php' );

		$this->assertEquals( false, $this->save_post_handler->can_register() );

		$this->add_options();

		$this->assertEquals( true, $this->save_post_handler->can_register() );
	}

	function test_custom_register() {

		define( 'DOING_CRON', true );

		$this->assertEquals( false, $this->save_post_handler->can_register() );

		add_filter(
			'classifai_should_register_save_post_handler',
			function( $should_register ) {
				if ( defined( 'DOING_CRON' ) && DOING_CRON ) {
					return true;
				}
				return $should_register;
			}
		);

		$this->assertEquals( true, $this->save_post_handler->can_register() );
	}
}
