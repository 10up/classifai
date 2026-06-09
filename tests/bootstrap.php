<?php
/**
 * PHPUnit bootstrap file
 *
 */

$_tests_dir = getenv( 'WP_TESTS_DIR' );

// Give access to tests_add_filter() function.
require_once $_tests_dir . '/includes/functions.php';

// Activate the plugin.
tests_add_filter(
	'muplugins_loaded',
	static function (): void {
		require dirname( __DIR__ ) . '/classifai.php';
	}
);

// Start up the WP testing environment.
require $_tests_dir . '/includes/bootstrap.php';
