<?php
/**
 *  Abtract class that defines the providers for a service.
 */

namespace Classifai\Providers;

abstract class Provider {
	/**
	 * Can the Provider be initalized?
	 */
	abstract public function can_register();

	/**
	 * Initialization routine
	 */
	abstract public function register();

	/**
	 * Settings items;
	 */
	abstract public function do_settings();
}
