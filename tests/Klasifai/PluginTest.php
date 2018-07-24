<?php

namespace Klasifai;

class PluginTest extends \WP_UnitTestCase {

	public $plugin;

	function setUp() {
		parent::setUp();
	}

	function test_it_is_a_singleton() {
		$a = Plugin::get_instance();
		$b = Plugin::get_instance();

		$this->assertSame( $a, $b );
	}

}
