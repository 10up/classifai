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
	 * @var array Array of provider instances.
	 */
	public $provider_classes;

	/**
	 * @var string[] array Array of feature classes for this service
	 */
	public $features = [];

	/**
	 * @var \Classifai\Features\Feature[] Array of feature instances.
	 */
	public $feature_classes = [];

	/**
	 * Service constructor.
	 *
	 * @param string $display_name Name that appears in menu item and page title.
	 * @param string $menu_slug    Slug for the settings page.
	 * @param array  $providers    Array of provider classes for this service
	 */
	public function __construct( string $display_name, string $menu_slug, array $providers ) {
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
					$this->provider_classes[] = new $provider();
				}
			}
			$this->register_providers();
		}

		/**
		 * Filter the list of features for the service.
		 *
		 * @since 3.0.0
		 * @hook {$this->menu_slug}_features
		 *
		 * @param {array} $this->features Array of available features for the service.
		 *
		 * @return {array} The filtered available features.
		 */
		$this->features = apply_filters( "{$this->menu_slug}_features", $this->features );

		if ( ! empty( $this->features ) && is_array( $this->features ) ) {
			foreach ( $this->features as $feature ) {
				if ( class_exists( $feature ) ) {
					$feature_instance        = new $feature();
					$this->feature_classes[] = $feature_instance;
					$feature_instance->setup();
				}
			}
		}

		add_filter( 'classifai_debug_information', [ $this, 'add_service_debug_information' ] );
	}

	/**
	 * Initializes the functionality for this services providers
	 */
	public function register_providers() {
		if ( ! empty( $this->provider_classes ) ) {
			foreach ( $this->provider_classes as $provider ) {
				if ( method_exists( $provider, 'register' ) ) {
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
	public function get_menu_slug(): string {
		return $this->menu_slug;
	}

	/**
	 * Get the display name
	 *
	 * @return string
	 */
	public function get_display_name(): string {
		return $this->display_name;
	}

	/**
	 * Render the start of a settings page. The rest is added by the providers
	 */
	public function render_settings_page() {
		$active_tab     = $this->provider_classes ? $this->provider_classes[0]->get_settings_section() : '';
		$active_tab     = isset( $_GET['provider'] ) ? sanitize_text_field( wp_unslash( $_GET['provider'] ) ) : $active_tab; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$base_url       = add_query_arg(
			array(
				'page' => 'classifai',
				'tab'  => $this->get_menu_slug(),
			),
			admin_url( 'tools.php' )
		);
		$active_feature = $this->feature_classes ? $this->feature_classes[0]::ID : '';
		$active_feature = isset( $_GET['feature'] ) ? sanitize_text_field( wp_unslash( $_GET['feature'] ) ) : $active_feature; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		?>
		<div class="classifai-content">
			<?php
			include_once CLASSIFAI_PLUGIN_DIR . '/includes/Classifai/Admin/templates/classifai-header.php';
			?>
			<div class="classifai-wrap wrap wrap--nlu">
				<h2><?php echo esc_html( $this->display_name ); ?></h2>

				<?php
				if ( empty( $this->feature_classes ) ) {
					echo '<p>' . esc_html__( 'No features available for this service.', 'classifai' ) . '</p>';
					echo '</div></div>';
					return;
				}
				?>

				<h2 class="nav-tab-wrapper">
					<?php foreach ( $this->feature_classes as $feature_class ) : ?>
						<a href="<?php echo esc_url( add_query_arg( 'feature', $feature_class::ID, $base_url ) ); ?>" class="nav-tab <?php echo $feature_class::ID === $active_feature ? 'nav-tab-active' : ''; ?>"><?php echo esc_html( $feature_class->get_label() ); ?></a>
					<?php endforeach; ?>
				</h2>

				<?php settings_errors(); ?>

				<div class="classifai-nlu-sections">
					<form method="post" action="options.php">
					<?php
						settings_fields( 'classifai_' . $active_feature );
						do_settings_sections( 'classifai_' . $active_feature );
						submit_button();
					?>
					</form>

					<?php
					/**
					 * Fires after the settings form for a feature.
					 *
					 * @since 3.0.0
					 * @hook classifai_after_feature_settings_form
					 *
					 * @param {array} $active_feature Array of active features.
					 */
					do_action( 'classifai_after_feature_settings_form', $active_feature );
					?>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Adds plugin debug information to be printed on the Site Health screen.
	 *
	 * @since 1.4.0
	 *
	 * @param array $debug_information Array of associative arrays corresponding to lines of debug information.
	 * @return array Array with lines added.
	 */
	public function add_service_debug_information( array $debug_information ): array {
		return array_merge( $debug_information, $this->get_service_debug_information() );
	}

	/**
	 * Provides debug information for the service.
	 *
	 * @since 1.4.0
	 *
	 * @return array Array of associative arrays representing lines of debug information.
	 */
	public function get_service_debug_information(): array {
		$make_line = function ( $feature ) {
			return [
				'label' => sprintf( '%s', $feature->get_label() ),
				'value' => $feature->get_debug_information(),
			];
		};

		return array_map( $make_line, $this->feature_classes );
	}
}
