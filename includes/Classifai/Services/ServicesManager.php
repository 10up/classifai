<?php
/**
 * This class manages the known services the ClassifAI provides
 */

namespace Classifai\Services;

class ServicesManager {

	/**
	 * @var array List of registered services
	 */
	protected $services;


	/**
	 * @var array List of class instances being managed.
	 */
	protected $service_classes;

	/**
	 * @var string Page title for the admin page
	 */
	protected $title;

	/**
	 * @var string Menu title of the admin page.
	 */
	protected $menu_title;

	/**
	 * ServicesManager constructor.
	 *
	 * @param array $services The list of services available.
	 */
	public function __construct( $services = [] ) {
		$this->services        = $services;
		$this->service_classes = [];
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
	 * Register the actions required for the settings page.
	 */
	public function register() {
		foreach ( $this->services as $service ) {
			if ( class_exists( $service ) ) {
				$this->service_classes[] = new $service();
			}
		}

		// Do the settings pages.
		$this->do_settings();

		// Register the functionality
		$this->register_services();
	}


	/**
	 * Create the settings pages.
	 *
	 * If there are more than a single service, we'll create a top level admin menu and add subsequent items there.
	 */
	public function do_settings() {
		if ( count( $this->service_classes ) > 1 ) {
			add_action( 'admin_menu', [ $this, 'register_top_level_admin_menu_item' ] );
		} else {
			add_action( 'admin_menu', [ $this, 'register_admin_menu_item' ] );
		}
	}

	/**
	 * Initialize the services.
	 */
	protected function register_services() {
		foreach ( $this->service_classes as $service_class ) {
			$service_class->register();
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
	 * Return the list of registered services.
	 *
	 * @return array
	 */
	public function get_services() {
		return $this->services;
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

		$this->init_services_settings();
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

		$this->init_services_settings();
	}

	/**
	 * Registers each of the service pages.
	 */
	public function init_services_settings() {

		foreach ( $this->service_classes as $service_class ) {
			add_submenu_page(
				'classifai_settings',
				$service_class->get_display_name(),
				$service_class->get_display_name(),
				'manage_options',
				$service_class->get_menu_slug(),
				[ $service_class, 'render_settings_page' ]
			);
		}
	}

	/**
	 * Render the main settings page for the Classifai plugin.
	 */
	public function render_settings_page() {

		if ( count( $this->services ) > 1 ) {

			?>
			<div class="wrap">
				<h2><?php esc_html_e( 'General Settings', 'classifai' ); ?></h2>
				<p>Render the settings for the overall plugin. I see this as the place to enable/disable Providers and Services.</p>
			</div>
			<?php
		} else {
			// Render settings page for the first ( and only ) settings page.
			$this->service_classes[0]->render_settings_page();
		}
	}
}
