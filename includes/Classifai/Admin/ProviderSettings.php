<?php
/**
 * Created by PhpStorm.
 * User: ryanwelcher
 * Date: 2019-03-22
 * Time: 16:33
 */

namespace Classifai\Admin;


abstract class ProviderSettings {
	/**
	 * @var string
	 */
	protected $menu_slug;

	/**
	 * ProviderSettings constructor.
	 *
	 * @param string $menu_slug The menu slug for the page.
	 */
	public function __construct( string $menu_slug ) {
		$this->menu_slug = $menu_slug;
	}

	abstract function render_settings_page();

	abstract function register();
}
