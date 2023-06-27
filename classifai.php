<?php
/**
 * Plugin Name:       ClassifAI
 * Plugin URI:        https://github.com/10up/classifai
 * Update URI:        https://classifaiplugin.com
 * Description:       Enhance your WordPress content with Artificial Intelligence and Machine Learning services.
 * Version:           2.2.2
 * Requires at least: 5.7
 * Requires PHP:      7.4
 * Author:            10up
 * Author URI:        https://10up.com
 * License:           GPLv2
 * License URI:       https://spdx.org/licenses/GPL-2.0-or-later.html
 * Text Domain:       classifai
 * Domain Path:       /languages
 */

/**
 * Require PHP version 7.4+ - throw an error if the plugin is activated on an older version.
 *
 * Note that this itself is only PHP5.3+ compatible because of the anonymous callback.
 */
register_activation_hook(
	__FILE__,
	function() {
		if ( version_compare( PHP_VERSION, '7.4.0', '<' ) ) {
			wp_die(
				sprintf(
					wp_kses(
						/* translators: PHP Update guide URL */
						__( 'ClassifAI requires PHP version 7.4. <a href="%s">Click here</a> to learn how to update your PHP version.', 'classifai' ),
						array(
							'a' => array( 'href' => array() ),
						)
					),
					esc_url( 'https://wordpress.org/support/update-php/' )
				),
				esc_html__( 'Error Activating', 'classifai' )
			);
		}
	}
);

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
	require_once __DIR__ . '/config.test.php';
}

if ( file_exists( __DIR__ . '/config.local.php' ) ) {
	require_once __DIR__ . '/config.local.php';
}

require_once __DIR__ . '/config.php';
classifai_define( 'CLASSIFAI_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Loads the CLASSIFAI PHP autoloader if possible.
 *
 * @return bool True or false if autoloading was successfull.
 */
function classifai_autoload() {
	if ( classifai_can_autoload() ) {
		require_once classifai_autoloader();

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
		error_log( // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
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
 * Gets the installation message error.
 *
 * This was put in a function specifically because it's used both in WP-CLI and within an admin notice if not using
 * WP-CLI.
 *
 * @return string
 */
function get_error_install_message() {
	return esc_html__( 'Error: Please run $ composer install in the classifai plugin directory.', 'classifai' );
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

		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			require_once CLASSIFAI_PLUGIN_DIR . '/includes/Classifai/Command/ClassifaiCommand.php';
		}
	} else {
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			try {
				\WP_CLI::error( get_error_install_message() );
			} catch ( \WP_CLI\ExitException $e ) {
				error_log( $e->getMessage() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			}
		}

		add_action( 'admin_notices', 'classifai_autoload_notice' );
	}
}


/**
 * Generate a notice if autoload fails.
 */
function classifai_autoload_notice() {
	printf( '<div class="%1$s"><p>%2$s</p></div>', 'notice notice-error', get_error_install_message() ); // @codingStandardsIgnoreLine Text is escaped in calling function already.
	error_log( get_error_install_message() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
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
if ( file_exists( __DIR__ . '/vendor/yahnis-elsts/plugin-update-checker/plugin-update-checker.php' ) ) {
	require_once __DIR__ . '/vendor/yahnis-elsts/plugin-update-checker/plugin-update-checker.php';
} else {
	add_action( 'admin_notices', 'classifai_dev_notice' );
	add_action( 'network_admin_notices', 'classifai_dev_notice' );
}

/**
 * Show dev version notice on ClassifAI pages if necessary.
 */
function classifai_dev_notice() {
	if ( 0 === strpos( get_current_screen()->parent_base, 'classifai' ) ) {
		?>
		<div class="notice notice-warning">
		<?php /* translators: %1$s: CLI install commands, %2$s: classifai url */ ?>
		<p><?php echo wp_kses_post( sprintf( __( 'You appear to be running a development version of ClassifAI. Certain features may not work correctly without running %1$s. If you&rsquo;re not sure what this means, you may want to <a href="%2$s">download and install</a> the stable version of ClassifAI instead.', 'classifai' ), '<code>composer install && npm install && npm run build</code>', 'https://classifaiplugin.com/' ) ); ?></p>
		</div>
		<?php
	}
}
