<?php

class NotificationsTest extends \WP_UnitTestCase {

	public function setUp() {
		parent::setUp();
	}

	/**
	 * Can register will return the opposite bool of the option.
	 * If the plugin is configured, correctly we don't need the notifications.
	 *
	 * @dataProvider data_can_register
	 */
	public function test_can_register( $value, $expected ) {
		update_option( 'classifai_configured', $value );
		$class = new Classifai\Admin\Notifications();
		$this->assertSame( $expected, $class->can_register() );
	}

	public function data_can_register() {
		return [
			[
				true,
				false,
			],
			[
				false,
				true
			]
		];
	}

}
