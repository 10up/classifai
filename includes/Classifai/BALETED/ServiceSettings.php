<?php
/**
 * Base class for services
 *
 * @package Classifai\Admin
 */

namespace Classifai\Admin;

abstract class ServiceSettings {

	/**
	 * ServiceSettings constructor.
	 *
	 * @param ProviderSettings $provider Instance of the ProviderSettings that this service belongs to.
	 */
	public function __construct( ProviderSettings $provider ) {
	}

	/**
	 * @return mixed
	 */
	abstract public function render_service_settings();
}
