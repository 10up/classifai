<?php
/**
 * Plugin Name:     ClassifAI
 * Description:     AI-powered classification and machine learning for WordPress content
 * Author:          Darshan Sawardekar, 10up
 * Author URI:      https://10up.com
 * Text Domain:     classifai
 * Domain Path:     /languages
 * Version:         1.1.0
 */

/**
 * Small wrapper around PHP's define function. The defined constant is
 * ignored if it has already been defined. This allows the
 * config.local.php to override any constant in config.php.
 *
 * @param string $name The constant name
 * @param mixed $value The constant value
 * @return void
 */
function classifai_define( $name, $value ) {
	if ( ! defined( $name ) ) {
		define( $name, $value );
	}
}

if ( file_exists( __DIR__ . '/config.test.php' ) && defined( 'PHPUNIT_RUNNER' ) ) {
	require_once( __DIR__ . '/config.test.php' );
}

if ( file_exists( __DIR__ . '/config.local.php' ) ) {
	require_once( __DIR__ . '/config.local.php' );
}

require_once( __DIR__ . '/config.php' );

/**
 * Loads the CLASSIFAI PHP autoloader if possible.
 *
 * @return bool True or false if autoloading was successfull.
 */
function classifai_autoload() {
	if ( classifai_can_autoload() ) {
		require_once( classifai_autoloader() );

		return true;
	} else {
		return false;
	}
}

/**
 * In server mode we can autoload if autoloader file exists. For
 * test environments we prevent autoloading of the plugin to prevent
 * global pollution and for better performance.
 */
function classifai_can_autoload() {
	if ( file_exists( classifai_autoloader() ) ) {
		return true;
	} else {
		error_log(
			"Fatal Error: Composer not setup in " . CLASSIFAI_PLUGIN_DIR
		);

		return false;
	}
}

/**
 * Default is Composer's autoloader
 */
function classifai_autoloader() {
	if ( file_exists( CLASSIFAI_PLUGIN_DIR . '/vendor/autoload.php' ) ) {
		return CLASSIFAI_PLUGIN_DIR . '/vendor/autoload.php';
	} else {
		return CLASSIFAI_PLUGIN_DIR . '/autoload.php';
	}
}

/**
 * Plugin code entry point. Singleton instance is used to maintain a common single
 * instance of the plugin throughout the current request's lifecycle.
 *
 * If autoloading failed an admin notice is shown and logged to
 * the PHP error_log.
 */
function classifai_autorun() {
	if ( classifai_autoload() ) {
		$plugin = \Classifai\Plugin::get_instance();
		$plugin->enable();
	} else {
		add_action( 'admin_notices', 'classifai_autoload_notice' );
	}
}

function classifai_autoload_notice() {
	$class   = 'notice notice-error';
	$message = 'Error: Please run $ composer install in the classifai plugin directory.';

	printf( '<div class="%1$s"><p>%2$s</p></div>', $class, $message );
	error_log( $message );
}

classifai_autorun();
