<?php

namespace Classifai\Admin;

use Classifai\Services\ServicesManager;

use function Classifai\get_asset_info;
use function Classifai\get_plugin;
use function Classifai\get_services_menu;

class Settings {

	/**
	 * Register the actions needed.
	 */
	public function __construct() {}

	/**
	 * Inintialize the class and register the actions needed.
	 */
	public function init() {
		add_action( 'admin_menu', [ $this, 'register_settings_page' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'admin_enqueue_scripts' ] );
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	/**
	 * Registers a hidden sub menu page for the onboarding wizard.
	 */
	public function register_settings_page() {
		add_submenu_page(
			'tools.php',
			esc_attr__( 'ClassifAI', 'classifai' ),
			esc_attr__( 'ClassifAI', 'classifai' ),
			'manage_options',
			'classifai',
			[ $this, 'render_settings_page' ]
		);
	}

	/**
	 * Renders the ClassifAI settings page.
	 */
	public function render_settings_page() {
		?>
		<div class="classifai-content classifai-settings" id="classifai-settings"></div>
		<?php
	}

	/**
	 * Enqueue the scripts and styles needed for the settings page.
	 *
	 * @param string $hook_suffix The current admin page.
	 */
	public function admin_enqueue_scripts( $hook_suffix ) {
		if ( 'tools_page_classifai' !== $hook_suffix ) {
			return;
		}

		wp_enqueue_script(
			'classifai-settings',
			CLASSIFAI_PLUGIN_URL . 'dist/settings.js',
			get_asset_info( 'settings', 'dependencies' ),
			get_asset_info( 'settings', 'version' ),
			true
		);

		wp_set_script_translations( 'classifai-settings', 'classifai' );

		$data = array(
			'features' => $this->get_features(),
			'services' => get_services_menu(),
		);

		wp_add_inline_script(
			'classifai-settings',
			sprintf(
				'var classifAISettings = %s;',
				wp_json_encode( $data )
			),
			'before'
		);

		wp_enqueue_style(
			'classifai-settings',
			CLASSIFAI_PLUGIN_URL . 'dist/settings.css',
			array_filter(
				get_asset_info( 'settings', 'dependencies' ),
				function ( $style ) {
					return wp_style_is( $style, 'registered' );
				}
			),
			get_asset_info( 'settings', 'version' ),
			'all'
		);
	}

	/**
	 * Get features for the settings page.
	 *
	 * @param bool $with_instance Whether to include the instance of the feature.
	 */
	public function get_features( $with_instance = false ) {
		$services = get_plugin()->services;
		if ( empty( $services ) || empty( $services['service_manager'] ) || ! $services['service_manager'] instanceof ServicesManager ) {
			return [];
		}

		/** @var ServicesManager $service_manager Instance of the services manager class. */
		$service_manager = $services['service_manager'];
		$services        = [];

		if ( empty( $service_manager->service_classes ) ) {
			return [];
		}

		if ( $with_instance ) {
			foreach ( $service_manager->service_classes as $service ) {
				foreach ( $service->feature_classes as $feature ) {
					$services[ $feature::ID ] = $feature;
				}
			}
			return $services;
		}

		foreach ( $service_manager->service_classes as $service ) {
			$services[ $service->get_menu_slug() ] = array();

			foreach ( $service->feature_classes as $feature ) {
				$services[ $service->get_menu_slug() ][ $feature::ID ] = array(
					'label' => $feature->get_label(),
				);
			}
		}
		return $services;
	}

	/**
	 * Register the REST API routes for the settings.
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			'classifai/v1',
			'settings',
			[
				[
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_settings' ],
					'permission_callback' => [ $this, 'get_settings_permissions_check' ],
				],
				[
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => [ $this, 'update_settings' ],
					'permission_callback' => [ $this, 'update_settings_permissions_check' ],
				],
			]
		);
	}

	/**
	 * Get the settings.
	 *
	 * @return \WP_REST_Response
	 */
	public function get_settings() {
		$features = $this->get_features( true );
		$settings = [];

		foreach ( $features as $feature ) {
			$settings[ $feature::ID ] = $feature->get_settings();
		}

		return rest_ensure_response( $settings );
	}

	/**
	 * Check if a given request has access to get settings.
	 *
	 * @return bool|\WP_Error
	 */
	public function get_settings_permissions_check() {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Update the settings.
	 *
	 * @param \WP_REST_Request $request Full data about the request.
	 * @return \WP_REST_Response
	 */
	public function update_settings( $request ) {
		$settings = $request->get_json_params();

		$feature_key = key( $settings );
		$features    = $this->get_features( true );
		$feature     = $features[ $feature_key ];

		if ( ! $feature ) {
			return new \WP_Error( 'invalid_feature', __( 'Invalid feature.', 'classifai' ), [ 'status' => 400 ] );
		}

		$feature->update_settings( $settings[ $feature_key ] );

		return $this->get_settings();
	}

	/**
	 * Check if a given request has access to update settings.
	 *
	 * @return bool|\WP_Error
	 */
	public function update_settings_permissions_check() {
		return current_user_can( 'manage_options' );
	}
}
