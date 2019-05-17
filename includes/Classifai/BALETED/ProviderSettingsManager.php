<?php
/**
 * This class manages creating and initializing Settings pages for all of Service Providers
 */

namespace Classifai\Admin;

class ProviderSettingsManager {

	/**
	 * @var array Array of Classes that represent settings pages.
	 */
	protected $settings_pages;

	/**
	 * @var string The page title.
	 */
	protected $title;

	/**
	 * @var string The menu title
	 */
	protected $menu_title;

	/**
	 * @var array Array of provider Classes.
	 */
	protected $provider_instances = [];

	/**
	 * SettingsPageManager constructor.
	 *
	 * @param array $settings_pages Array of settings pages to init.
	 */
	public function __construct( $settings_pages = [] ) {
		$this->settings_pages = is_array( $settings_pages ) ? $settings_pages : [];
		$this->settings_pages = array_unique( array_filter( $this->settings_pages, [ $this, 'filter_settings_pages' ] ) );
		$this->get_menu_title();
	}

	/**
	 * The admin_support items require this method.
	 *
	 * @todo remove this requirement.
	 * @return bool
	 */
	public function can_register() {
		return true;
	}

	/**
	 * Filter the Provider settings pages to ensure that the class both exists and extends the correct base class.
	 *
	 * @param string $class The full namespaced class.
	 *
	 * @return bool
	 */
	protected function filter_settings_pages( $class ) {
		return ( class_exists( $class ) && in_array( 'Classifai\Admin\ProviderSettings', class_parents( $class ), true ) );
	}
	/**
	 * Register the actions required for the settings page.
	 */
	public function register() {
		if ( count( $this->settings_pages ) > 1 ) {
			add_action( 'admin_menu', [ $this, 'register_top_level_admin_menu_item' ] );
		} else {
			add_action( 'admin_menu', [ $this, 'register_admin_menu_item' ] );
		}
	}

	/**
	 * Helper to return the $menu title
	 */
	protected function get_menu_title() {
		$is_setup         = get_option( 'classifai_configured' );
		$this->title      = esc_html__( 'ClassifAI', 'classifai' );
		$this->menu_title = $this->title;

		if ( ! $is_setup ) {
			/*
			 * Translators: Main title.
			 */
			$this->menu_title = sprintf( __( 'ClassifAI %s', 'classifai' ), '<span class="update-plugins"><span class="update-count">!</span></span>' );
		}
	}


	/**
	 * Register a sub page.
	 */
	public function register_admin_menu_item() {
		add_submenu_page(
			'options-general.php',
			$this->title,
			$this->menu_title,
			'manage_options',
			'classifai_settings',
			[ $this, 'render_settings_page' ]
		);

		$this->init_provider_pages();

	}

	/**
	 * Register a top level page.
	 */
	public function register_top_level_admin_menu_item() {
		add_menu_page(
			$this->title,
			$this->menu_title,
			'manage_options',
			'classifai_settings',
			[ $this, 'render_settings_page' ]
		);
		$this->init_provider_pages();
	}

	/**
	 * Registers each of the provider pages.
	 */
	public function init_provider_pages() {
		foreach ( $this->settings_pages as $class ) {
			if ( class_exists( $class ) ) {
				$page = new $class();
				$page->register();
				$this->provider_instances[] = $page;
			}
		}
	}

	/**
	 * Render the main settings page for the Classifai plugin.
	 */
	public function render_settings_page() {

		if ( count( $this->settings_pages ) > 1 ) {

			?>
			<div class="wrap">
				<h2><?php esc_html_e( 'General Settings', 'classifai' ); ?></h2>
				<p>Render the settings for the overall plugin. I see this as the place to enable/disable Providers and Services.</p>
			</div>
			<?php
		} else {
			// Render settings page for the first ( and only ) settings page.
			$this->provider_instances[0]->render_settings_page();
		}
	}
}
