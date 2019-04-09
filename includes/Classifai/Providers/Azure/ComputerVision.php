<?php
/**
 * Azure Computer vision
 */

namespace Classifai\Providers\Azure;

use Classifai\Providers\Provider;

class ComputerVision extends Provider {

	/**
	 * Can the functionality be inited?
	 *
	 * @return bool
	 */
	public function can_register() {
		// TODO: Implement can_register() method.
		return true;
	}

	/**
	 * Init functionality.
	 */
	public function register() {
		// TODO: Implement register() method.
	}

	/**
	 * Renders the settings.
	 */
	public function do_settings() {
		echo 'SETTINGS FOR AZURE COMPUTER VISION';
	}
}
