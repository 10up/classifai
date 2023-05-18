<?php
/**
 * Abstract class for all services
 */

namespace Classifai\Services;

use function Classifai\find_provider_class;
use WP_Error;

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
		$active_tab = $this->provider_classes ? $this->provider_classes[0]->get_settings_section() : '';
		$active_tab = isset( $_GET['provider'] ) ? sanitize_text_field( $_GET['provider'] ) : $active_tab; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$base_url   = add_query_arg(
			array(
				'page' => 'classifai',
				'tab'  => $this->get_menu_slug(),
			),
			admin_url( 'tools.php' )
		);
		?>
		<div class="classifai-content">
			<?php
			include_once CLASSIFAI_PLUGIN_DIR . '/includes/Classifai/Admin/templates/classifai-header.php';
			?>
			<div class="classifai-wrap wrap wrap--nlu">
				<h2><?php echo esc_html( $this->display_name ); ?></h2>

				<?php
				if ( empty( $this->provider_classes ) ) {
					echo '<p>' . esc_html__( 'No providers available for this service.', 'classifai' ) . '</p>';
					echo '</div></div>';
					return;
				}
				?>

				<h2 class="nav-tab-wrapper">
					<?php foreach ( $this->provider_classes as $provider_class ) : ?>
						<a href="<?php echo esc_url( add_query_arg( 'provider', $provider_class->get_settings_section(), $base_url ) ); ?>" class="nav-tab <?php echo $provider_class->get_settings_section() === $active_tab ? 'nav-tab-active' : ''; ?>"><?php echo esc_html( $provider_class->provider_name ); ?></a>
					<?php endforeach; ?>
				</h2>

				<?php settings_errors(); ?>

				<div class="classifai-nlu-sections">
					<form method="post" action="options.php">
					<?php
						settings_fields( 'classifai_' . $active_tab );
						do_settings_sections( 'classifai_' . $active_tab );
						submit_button();
					?>
					</form>
					<?php
					// Find the right provider class.
					$provider = find_provider_class( $this->provider_classes ?? [], 'Natural Language Understanding' );

					if ( ! is_wp_error( $provider ) && ! empty( $provider->can_register() ) ) :
						?>
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

							$features = array(
								'category' => array(
									'name'    => esc_html__( 'Category', 'classifai' ),
									'enabled' => \Classifai\get_feature_enabled( 'category' ),
									'plural'  => 'categories',
								),
								'keyword'  => array(
									'name'    => esc_html__( 'Keyword', 'classifai' ),
									'enabled' => \Classifai\get_feature_enabled( 'keyword' ),
									'plural'  => 'keywords',
								),
								'entity'   => array(
									'name'    => esc_html__( 'Entity', 'classifai' ),
									'enabled' => \Classifai\get_feature_enabled( 'entity' ),
									'plural'  => 'entities',
								),
								'concept'  => array(
									'name'    => esc_html__( 'Concept', 'classifai' ),
									'enabled' => \Classifai\get_feature_enabled( 'concept' ),
									'plural'  => 'concepts',
								),
							);
							?>

						<?php if ( 'watson_nlu' === $active_tab ) : ?>
						<h2><?php esc_html_e( 'Preview Language Processing', 'classifai' ); ?></h2>
						<div id="classifai-post-preview-controls">
							<select id="classifai-preview-post-selector">
								<?php foreach ( $posts_to_preview as $post ) : ?>
									<option value="<?php echo esc_attr( $post->ID ); ?>"><?php echo esc_html( $post->post_title ); ?></option>
								<?php endforeach; ?>
							</select>
							<?php wp_nonce_field( 'classifai-previewer-action', 'classifai-previewer-nonce' ); ?>
							<button type="button" class="button" id="get-classifier-preview-data-btn">
								<span><?php esc_html_e( 'Preview', 'classifai' ); ?></span>
							</button>
						</div>
						<div id="classifai-post-preview-wrapper">
							<?php foreach ( $features as $feature_slug => $feature ) : ?>
								<div class="tax-row tax-row--<?php echo esc_attr( $feature['plural'] ); ?> <?php echo esc_attr( $feature['enabled'] ) ? '' : 'tax-row--hide'; ?>">
									<div class="tax-type"><?php echo esc_html( $feature['name'] ); ?></div>
								</div>
							<?php endforeach; ?>
						</div>
						<?php endif; ?>
					</div>
					<?php endif; ?>
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

	/**
	 * Check if the current user has permission to create and assign terms.
	 *
	 * @param string $tax Taxonomy name.
	 * @return bool|WP_Error
	 */
	public function check_term_permissions( string $tax = '' ) {
		$taxonomy = get_taxonomy( $tax );

		if ( empty( $taxonomy ) || empty( $taxonomy->show_in_rest ) ) {
			return new WP_Error( 'invalid_taxonomy', esc_html__( 'Taxonomy not found. Double check your settings.', 'classifai' ) );
		}

		$create_cap = is_taxonomy_hierarchical( $taxonomy->name ) ? $taxonomy->cap->edit_terms : $taxonomy->cap->assign_terms;

		if ( ! current_user_can( $create_cap ) || ! current_user_can( $taxonomy->cap->assign_terms ) ) {
			return new WP_Error( 'rest_cannot_assign_term', esc_html__( 'Sorry, you are not alllowed to create or assign to this taxonomy.', 'classifai' ) );
		}

		return true;
	}

}
