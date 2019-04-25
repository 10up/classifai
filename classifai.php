<?php
/**
 * Plugin Name:     ClassifAI
 * Description:     AI-powered classification and machine learning for WordPress content
 * Author:          Darshan Sawardekar, 10up
 * Author URI:      https://10up.com
 * License:         MIT
 * Text Domain:     classifai
 * Domain Path:     /languages
 * Version:         1.2.0
 */

/**
 * Small wrapper around PHP's define function. The defined constant is
 * ignored if it has already been defined. This allows the
 * config.local.php to override any constant in config.php.
 *
 * @param string $name The constant name
 * @param mixed  $value The constant value
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
			sprintf( esc_html__( 'Fatal Error: Composer not setup in %', 'classifai' ), CLASSIFAI_PLUGIN_DIR )
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


/**
 * Generate a notice if autoload fails.
 */
function classifai_autoload_notice() {
	printf( '<div class="%1$s"><p>%2$s</p></div>', 'notice notice-error', esc_html__( 'Error: Please run $ composer install in the classifai plugin directory.', 'classifai' ) );
	error_log( esc_html__( 'Error: Please run $ composer install in the classifai plugin directory.', 'classifai' ) );
}


/**
 * Register an activation hook that we can hook into.
 */
function classifai_activation() {
	set_transient( 'classifai_activation_notice', 'classifai', HOUR_IN_SECONDS );
}
register_activation_hook( __FILE__, 'classifai_activation' );

classifai_autorun();

// Include in case we have composer issues.
require_once __DIR__ . '/vendor/yahnis-elsts/plugin-update-checker/plugin-update-checker.php';

if ( class_exists( 'Puc_v4_Factory' ) ) {
	/*
	 * Enable updates if we have a valid license
	 */
	$settings = \Classifai\get_plugin_settings();

	if ( isset( $settings['valid_license'] ) && $settings['valid_license'] ) {
		// @codingStandardsIgnoreStart
		$updateChecker = Puc_v4_Factory::buildUpdateChecker(
			'https://github.com/10up/classifai/',
			__FILE__,
			'classifai'
		);

		$updateChecker->addResultFilter(
			function( $plugin_info, $http_response = null ) {
				$plugin_info->icons = array(
					'svg' => plugins_url( '/assets/img/icon.svg', __FILE__ ),
				);
				return $plugin_info;
			}
		);
		// @codingStandardsIgnoreEnd
	}
}
