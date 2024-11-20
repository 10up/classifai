<?php
/**
 * Plugin Name:       ClassifAI
 * Plugin URI:        https://github.com/10up/classifai
 * Update URI:        https://classifaiplugin.com
 * Description:       Enhance your WordPress content with Artificial Intelligence and Machine Learning services.
 * Version:           3.2.0-dev
 * Requires at least: 6.5
 * Requires PHP:      7.4
 * Author:            10up
 * Author URI:        https://10up.com
 * License:           GPLv2
 * License URI:       https://spdx.org/licenses/GPL-2.0-or-later.html
 * Text Domain:       classifai
 * Domain Path:       /languages
 */

/**
 * Get the minimum version of PHP required by this plugin.
 *
 * @return string Minimum version required.
 */
function classifai_minimum_php_requirement() {
	return '7.4';
}

/**
 * Whether PHP installation meets the minimum requirements
 *
 * @return bool True if meets minimum requirements, false otherwise.
 */
function classifai_site_meets_php_requirements() {
	return version_compare( phpversion(), classifai_minimum_php_requirement(), '>=' );
}

// Ensuring our PHP version requirement is met first before loading plugin.
if ( ! classifai_site_meets_php_requirements() ) {
	add_action(
		'admin_notices',
		function () {
			?>
			<div class="notice notice-error">
				<p>
					<?php
					echo wp_kses_post(
						sprintf(
							/* translators: %s: Minimum required PHP version */
							__( 'ClassifAI requires PHP version %s or later. Please upgrade PHP or disable the plugin.', 'classifai' ),
							esc_html( classifai_minimum_php_requirement() )
						)
					);
					?>
				</p>
			</div>
			<?php
		}
	);
	return;
}

/**
 * Small wrapper around PHP's define function.
 *
 * The defined constant is ignored if it has already
 * been defined. This allows these constants to be
 * overridden.
 *
 * @param string $name The constant name.
 * @param mixed  $value The constant value.
 */
function classifai_define( $name, $value ) {
	if ( ! defined( $name ) ) {
		define( $name, $value );
	}
}

require_once __DIR__ . '/config.php';

/**
 * Loads the autoloader if possible.
 *
 * @return bool True or false if autoloading was successful.
 */
function classifai_autoload() {
	if ( file_exists( CLASSIFAI_PLUGIN_DIR . '/vendor/autoload.php' ) ) {
		require_once CLASSIFAI_PLUGIN_DIR . '/vendor/autoload.php';

		return true;
	} else {
		error_log( // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			sprintf( 'Warning: Composer not setup in %s', CLASSIFAI_PLUGIN_DIR )
		);

		return false;
	}
}

/**
 * Gets the installation message error.
 *
 * Used both in a WP-CLI context and within an admin notice.
 *
 * @return string
 */
function get_error_install_message() {
	return esc_html__( 'Error: Please run $ composer install in the ClassifAI plugin directory.', 'classifai' );
}

/**
 * Plugin code entry point.
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
 * Run functionality on plugin activation.
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
