<?php
/**
 * Base class for provider pages
 *
 * @package Classifai\Admin
 */

namespace Classifai\Admin;

abstract class ProviderSettings {
	/**
	 * @var string
	 */
	protected $menu_slug;

	/**
	 * @var string $provider Name slug for the provider
	 */
	protected $provider;

	/**
	 * @var array $_services List of services that will be rendered in the settings page.
	 */
	protected $services;

	/**
	 * ProviderSettings constructor.
	 *
	 * @param string $provider  The name slug for the provider.
	 * @param string $menu_slug The menu slug for the page.
	 */
	public function __construct( string $provider, string $menu_slug ) {
		$this->provider         = $provider;
		$this->menu_slug        = $menu_slug;
		$this->option_group     = "{$this->provider}_settings";
		$this->settings_section = "{$this->provider}-settings";
		$this->services         = [];
	}

	/**
	 * Add a Service Settings class to be rendered.
	 *
	 * @param ServiceSettings $service A class that extends the ServiceSettings class.
	 */
	public function add_service( ServiceSettings $service ) {
		$this->services[] = $service;
	}

	/**
	 * Retrieve the list of services for this provider.
	 *
	 * @return mixed|void
	 */
	public function get_services() {
		return apply_filters( "classifai_{$this->provider}_services", $this->services );
	}

	/**
	 * Register the settings and sanitzation callback method.
	 */
	public function register_settings() {
		register_setting( $this->option_group, $this->option_group );
	}

	/**
	 * Required render callback that is called by the PageSettingsManager class to render the settings page.
	 */
	abstract public function render_settings_page();

	/**
	 * Required register method.
	 */
	protected function register() {
		add_action( 'admin_init', [ $this, 'register_settings' ] );
	}
}
