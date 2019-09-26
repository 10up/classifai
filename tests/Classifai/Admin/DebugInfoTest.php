<?php
/**
 * Testing for the DebugInfo class
 */

namespace Classifai\Tests\Admin;

use \WP_UnitTestCase;
use Classifai\Admin\DebugInfo;

/**
 * Class DebugInfoTest
 * @package Classifai\Tests\Admin
 *
 * @group admin
 */
class DebugInfoTest extends WP_UnitTestCase {

	protected $debug_info;

	/**
	 * setup method
	 */
	function setUp() {
		parent::setUp();

		$this->debug_info = new DebugInfo();
	}

	/**
	 * Tests the add_classifai_debug_information function.
	 */
	function test_add_classifai_debug_information() {
		global $wp_filter;

		$saved_filters = $wp_filter['classifai_debug_information'] ?? null;
		unset( $wp_filter['classifai_debug_information'] );

		$information = $this->debug_info->add_classifai_debug_information( [] );

		$this->assertArrayHasKey( 'classifai', $information );
		$this->assertArrayHasKey( 'fields', $information['classifai'] );
		$this->assertEquals( 1, count( $information['classifai']['fields'] ) );

		if ( ! is_null( $saved_filters ) ) {
			$wp_filter['classifai_debug_information'] = $saved_filters;
		}
	}
}
