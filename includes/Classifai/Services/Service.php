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
	public $provider_classes;

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
		 * Filter the list of providers for the service.
		 *
		 * @since 1.3.0
		 * @hook {$this->menu_slug}_providers
		 *
		 * @param {array} $this->providers Array of available providers for the service.
		 *
		 * @return {array} The filtered available providers.
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

		add_filter( 'classifai_debug_information', [ $this, 'add_service_debug_information' ] );
	}

	/**
	 * Initializes the functionality for this services providers
	 */
	public function register_providers() {
		if ( ! empty( $this->provider_classes ) ) {
			foreach ( $this->provider_classes as $provider ) {
				$provider->register_admin();
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
		$active_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : $this->provider_classes[0]->get_settings_section(); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		?>
		<div class="wrap wrap--nlu">
			<h2><?php echo esc_html( $this->display_name ); ?></h2>
			<?php if ( ! empty( $this->provider_classes ) ) : ?>
			<h2 class="nav-tab-wrapper">
				<?php foreach ( $this->provider_classes as $provider_class ) : ?>
					<a href="?page=<?php echo esc_attr( $this->get_menu_slug() ); ?>&tab=<?php echo esc_attr( $provider_class->get_settings_section() ); ?>" class="nav-tab <?php echo $provider_class->get_settings_section() === $active_tab ? 'nav-tab-active' : ''; ?>"><?php echo esc_html( $provider_class->provider_name ); ?></a>
				<?php endforeach; ?>
			</h2>
			<?php endif; ?>
			<?php settings_errors(); ?>
			<div class="classifai-nlu-sections">
				<form method="post" action="options.php">
				<?php
					settings_fields( 'classifai_' . $active_tab );
					do_settings_sections( 'classifai_' . $active_tab );
					submit_button();
				?>
				</form>
				<div id="classifai-post-preview-app">
					<?php
						$supported_post_statuses = \Classifai\get_supported_post_statuses();
						$supported_post_types    = \Classifai\get_supported_post_types();

						$posts_to_preview = get_posts(
							array(
								'post_type'      => $supported_post_types,
								'post_status'    => $supported_post_statuses,
								'posts_per_page' => 10,
							)
						);
					?>
					<div id="classifai-post-preview-controls">
						<select id="classifai-preview-post-selector">
							<?php foreach ( $posts_to_preview as $post ) : ?>
								<option value="<?php echo esc_attr( $post->ID ); ?>"><?php echo esc_html( $post->post_title ); ?></option>
							<?php endforeach; ?>
						</select>
						<button type="button" class="button" id="get-classifier-preview-data-btn">
							<span><?php esc_html_e( 'Preview', 'classifai' ); ?></span>
						</button>
					</div>
					<div id="classifai-post-preview-wrapper"></div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Adds plugin debug information to be printed on the Site Health screen.
	 *
	 * @param array $debug_information Array of associative arrays corresponding to lines of debug information.
	 * @return array Array with lines added.
	 * @since 1.4.0
	 */
	public function add_service_debug_information( $debug_information ) {
		return array_merge( $debug_information, $this->get_service_debug_information() );
	}

	/**
	 * Provides debug information for the service.
	 *
	 * @return array Array of associative arrays respresenting lines of debug information.
	 * @since 1.4.0
	 */
	public function get_service_debug_information() {
		$make_line = function( $provider ) {
			return [
				'label' => sprintf( '%s: %s', $this->get_display_name(), $provider->get_provider_name() ),
				'value' => $provider->get_provider_debug_information(),
			];
		};

		return array_map( $make_line, $this->provider_classes );
	}
}
