<?php
/**
 * Created by PhpStorm.
 * User: ryanwelcher
 * Date: 2019-03-22
 * Time: 16:54
 */

namespace Classifai\Azure;

use Classifai\Admin\ProviderSettings;

class AzureSettings extends ProviderSettings {


	public function __construct() {
		parent::__construct( 'azure_settings' );
	}

	/**
	 * Render the settings page.
	 */
	public function render_settings_page() {
		?>
		<div class="wrap">
			<h2><?php esc_html_e( 'Azure Settings', 'classifai' ); ?></h2>
			<p>Settings for the Service</p>
		</div>
		<?php
	}

	/**
	 * Register the actions required for the settings page.
	 */
	public function register() {
		add_action( 'admin_menu', [ $this, 'register_admin_menu_item' ], 11 );
	}

	public function register_admin_menu_item() {
		$is_setup = get_option( 'classifai_configured' );

		$title = esc_html__( 'Azure', 'classifai' );
		$menu_title = $title;
		add_submenu_page(
			'classifai_settings',
			$title,
			$menu_title,
			'manage_options',
			$this->menu_slug,
			[ $this, 'render_settings_page' ]
		);
	}
}
