<?php

namespace Classifai;

class PluginTest extends \WP_UnitTestCase {

	public $plugin;

	function set_up() {
		parent::set_up();
	}

	function test_it_is_a_singleton() {
		$a = Plugin::get_instance();
		$b = Plugin::get_instance();

		$this->assertSame( $a, $b );
	}

}
