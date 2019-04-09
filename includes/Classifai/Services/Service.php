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
		$this->providers    = apply_filters( "{$this->menu_slug}_providers", $providers );

		if ( ! empty( $this->providers ) ) {
			foreach ( $this->providers as $provider ) {
				if ( class_exists( $provider ) ) {
					$this->provider_classes[] = new $provider();
				}
			}
		}
	}

	/**
	 * Initializes the functionality for this services providers
	 */
	public function register() {
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
		?>
		<div class="wrap">
			<h2><?php echo esc_html( $this->display_name ); ?></h2>
			<?php
			if ( ! empty( $this->provider_classes ) ) {
				foreach ( $this->provider_classes as $provider_class ) {
					$provider_class->do_settings();
				}
			}
			?>
		</div>
		<?php
	}
}
