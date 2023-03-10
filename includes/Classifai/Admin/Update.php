<?php
/**
 * ClassifAI Auto Update Integration
 *
 * @package 10up/classifai
 */

namespace Classifai\Admin;

use Puc_v4_Factory;

/**
 * Plugin update class.
 */
class Update {

	/**
	 * The plugin repository URL for retrieving updates.
	 *
	 * @var string
	 */
	public static $repo_url = 'https://github.com/10up/classifai/';

	/**
	 * The update checker object.
	 *
	 * @var Puc_v4p13_Plugin_UpdateChecker
	 */
	protected $updater;

	/**
	 * Check to see if we can register this class.
	 *
	 * @return bool
	 */
	public function can_register() {
		return class_exists( '\Puc_v4_Factory' ) && self::license_check();
	}

	/**
	 * Prepare the updater and register hooks.
	 */
	public function register() {
		$this->init();

		add_filter( 'pre_set_site_transient_update_plugins', [ $this, 'maybe_update' ], 10, 1 );
	}

	/**
	 * Initialize the update checker object.
	 *
	 * @return void
	 */
	public function init() {
		$this->updater = Puc_v4_Factory::buildUpdateChecker(
			self::$repo_url,
			CLASSIFAI_PLUGIN,
			'classifai'
		);

		$this->updater->addResultFilter(
			function( $plugin_info, $http_response = null ) {
				$plugin_info->icons = array(
					'svg' => CLASSIFAI_PLUGIN_URL . 'assets/img/icon.svg',
				);
				return $plugin_info;
			}
		);
	}

	/**
	 * Initialize the auto update if an update is available.
	 *
	 * @param  object $transient The transient object.
	 * @return object            The modified transient object.
	 */
	public function maybe_update( $transient ) {
		// Check for an updated version
		$update = $this->updater->getUpdate();

		if ( $update ) {
			// If update is available, add it to the transient.
			$transient->response[ $update->filename ] = $update->toWpFormat();
		} else {
			// No update available, get current plugin info.
			$update = $this->updater->getUpdateState()->getUpdate();

			// Adding the plugin info to the `no_update` property is required
			// for the enable/disable auto-update links to appear correctly in the UI.
			$transient->no_update[ $update->filename ] = $update;
		}

		return $transient;
	}

	/**
	 * Verify the site has a valid license.
	 *
	 * @return boolean True if valid license, false otherwise.
	 */
	public static function license_check() {
		$service_manager = new \Classifai\Services\ServicesManager();
		$settings        = $service_manager->get_settings();

		return isset( $settings['valid_license'] ) && $settings['valid_license'];
	}
}
