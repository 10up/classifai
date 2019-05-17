<?php
/**
 * Abstract class for all services
 */

namespace Classifai\Services;

abstract class Service {

	/**
	 * @var string The settings page slug
	 */
	protected $menu_slug;

	/**
	 * @var string The display name for the service.
	 */
	protected $display_name;

	/**
	 * @var array Array of provider classes for this service
	 */
	protected $providers;

	/**
	 * @var array Array of class instances.
	 */
	protected $provider_classes;

	/**
	 * Service constructor.
	 *
	 * @param string $display_name Name that appears in menu item and page title.
	 * @param string $menu_slug    Slug for the settings page.
	 * @param array  $providers    Array of provider classes for this service
	 */
	public function __construct( $display_name, $menu_slug, $providers ) {
		$this->menu_slug    = $menu_slug;
		$this->display_name = $display_name;
		$this->providers    = $providers;
	}

	/**
	 * Init the Providers for this service.
	 */
	public function init() {
		/**
		 * Filter the list of providers
		 */
		$this->providers = apply_filters( "{$this->menu_slug}_providers", $this->providers );

		if ( ! empty( $this->providers ) && is_array( $this->providers ) ) {
			foreach ( $this->providers as $provider ) {
				if ( class_exists( $provider ) ) {
					$this->provider_classes[] = new $provider( $this->menu_slug );
				}
			}
			$this->register_providers();
		}
	}

	/**
	 * Initializes the functionality for this services providers
	 */
	public function register_providers() {
		if ( ! empty( $this->provider_classes ) ) {
			foreach ( $this->provider_classes as $provider ) {
				if ( $provider->can_register() ) {
					$provider->register();
				}
			}
		}
	}

	/**
	 * Get the menu slug
	 *
	 * @return string
	 */
	public function get_menu_slug() {
		return $this->menu_slug;
	}

	/**
	 * Get the display name
	 *
	 * @return string
	 */
	public function get_display_name() {
		return $this->display_name;
	}

	/**
	 * Render the start of a settings page. The rest is added by the providers
	 */
	public function render_settings_page() {
		$active_tab = isset( $_GET['tab'] ) ? $_GET['tab'] : $this->provider_classes[0]->get_settings_section();
		?>
		<div class="wrap">
			<h2><?php echo esc_html( $this->display_name ); ?></h2>
			<?php if ( ! empty( $this->provider_classes ) ) : ?>
			<h2 class="nav-tab-wrapper">
				<?php foreach ( $this->provider_classes as $provider_class ) : ?>
					<a href="?page=<?php echo esc_attr( $this->get_menu_slug() ); ?>&tab=<?php echo esc_attr( $provider_class->get_settings_section() ); ?>" class="nav-tab <?php echo $provider_class->get_settings_section() === $active_tab ? 'nav-tab-active' : ''; ?>"><?php echo esc_html( $provider_class->provider_name ); ?></a>
				<?php endforeach; ?>
			</h2>
			<?php endif; ?>
			<form method="post" action="options.php">
			<?php
				settings_fields( 'classifai_' . $active_tab );
				do_settings_sections( 'classifai_' . $active_tab );
				submit_button();
			?>
			</form>
		</div>
		<?php
	}
}
