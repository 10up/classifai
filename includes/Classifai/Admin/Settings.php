<?php

namespace Classifai\Admin;

use Classifai\Features\Classification;
use Classifai\Services\ServicesManager;

use Classifai\Taxonomy\TaxonomyFactory;
use function Classifai\get_asset_info;
use function Classifai\get_plugin;
use function Classifai\get_post_types_for_language_settings;
use function Classifai\get_services_menu;
use function Classifai\get_post_statuses_for_language_settings;
use function Classifai\is_elasticpress_installed;

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
		$registration_settings = get_option( 'classifai_settings' );
		$page_title            = esc_attr__( 'ClassifAI', 'classifai' );
		$menu_title            = $page_title;

		if ( ! isset( $registration_settings['valid_license'] ) || ! $registration_settings['valid_license'] ) {
			/*
			 * Translators: Menu title.
			 */
			$menu_title = sprintf( __( 'ClassifAI %s', 'classifai' ), '<span class="update-plugins"><span class="update-count">!</span></span>' );
		}

		add_submenu_page(
			'tools.php',
			$page_title,
			$menu_title,
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
		<div style="display: none;">
			<hr class="wp-header-end" />
		</div>
		<div class="classifai-content classifai-settings" id="classifai-settings"></div>
		<?php
	}

	/**
	 * Enqueue the scripts and styles needed for the settings page.
	 *
	 * @param string $hook_suffix The current admin page.
	 */
	public function admin_enqueue_scripts( $hook_suffix ) {
		if ( ! in_array( $hook_suffix, array( 'tools_page_classifai' ), true ) ) {
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

		$post_types         = get_post_types_for_language_settings();
		$excerpt_post_types = array();
		$post_type_options  = array();
		foreach ( $post_types as $post_type ) {
			$post_type_options[ $post_type->name ] = $post_type->label;
			if ( post_type_supports( $post_type->name, 'excerpt' ) ) {
				$excerpt_post_types[ $post_type->name ] = $post_type->label;
			}
		}

		$data = array(
			'features'         => $this->get_features(),
			'services'         => get_services_menu(),
			'settings'         => $this->get_settings(),
			'dashboardUrl'     => admin_url( '/' ),
			'nonce'            => wp_create_nonce( 'classifai-previewer-action' ),
			'postTypes'        => $post_type_options,
			'excerptPostTypes' => $excerpt_post_types,
			'postStatuses'     => get_post_statuses_for_language_settings(),
			'isEPinstalled'    => is_elasticpress_installed(),
			'nluTaxonomies'    => $this->get_nlu_taxonomies(),
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
			array( 'wp-edit-blocks' ),
			get_asset_info( 'settings', 'version' ),
			'all'
		);
	}

	/**
	 * Get features for the settings page.
	 *
	 * @param bool $with_instance Whether to include the instance of the feature.
	 * @return array
	 */
	public function get_features( bool $with_instance = false ): array {
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

		$post_types = get_post_types_for_language_settings();
		foreach ( $service_manager->service_classes as $service ) {
			$services[ $service->get_menu_slug() ] = array();

			foreach ( $service->feature_classes as $feature ) {
				$services[ $service->get_menu_slug() ][ $feature::ID ] = array(
					'label'              => $feature->get_label(),
					'providers'          => $feature->get_providers(),
					'roles'              => $feature->get_roles(),
					'enable_description' => $feature->get_enable_description(),
					'taxonomies'         => $feature->get_taxonomies(),
				);

				// Add taxonomies under post types for language processing features to allow filtering by post type.
				if ( 'language_processing' === $service->get_menu_slug() && ! empty( $post_types ) ) {
					$post_types_taxonomies = array();
					foreach ( $post_types as $post_type ) {
						$post_types_taxonomies[ $post_type->name ] = $feature->get_taxonomies( [ $post_type->name ] );
					}
					$services[ $service->get_menu_slug() ][ $feature::ID ]['taxonomiesByPostTypes'] = $post_types_taxonomies;
				}
			}
		}

		return $services;
	}

	/**
	 * Return the list of NLU taxonomies for the Classification feature settings.
	 *
	 * @return array
	 */
	public function get_nlu_taxonomies(): array {
		$taxonomies       = [];
		$taxonomy_factory = new TaxonomyFactory();
		$nlu_taxonomies   = $taxonomy_factory->get_supported_taxonomies();
		foreach ( $nlu_taxonomies as $taxonomy ) {
			$taxonomy_instance = $taxonomy_factory->build( $taxonomy );
			if ( ! $taxonomy_instance ) {
				continue;
			}

			$taxonomies[ $taxonomy_instance->get_name() ] = $taxonomy_instance->get_singular_label();
		}

		/**
		 * Filter IBM Watson NLU taxonomies shown in settings.
		 *
		 * @since x.x.x
		 * @hook classifai_settings_ibm_watson_nlu_taxonomies
		 *
		 * @param {array} $taxonomies Array of IBM Watson NLU taxonomies.
		 *
		 * @return {array} Array of taxonomies.
		 */
		return apply_filters( 'classifai_settings_ibm_watson_nlu_taxonomies', $taxonomies );
	}


	/**
	 * Get the settings.
	 *
	 * @return array The settings.
	 */
	public function get_settings(): array {
		$features = $this->get_features( true );
		$settings = [];

		foreach ( $features as $feature ) {
			$settings[ $feature::ID ] = $feature->get_settings();
		}

		return $settings;
	}

	/**
	 * Register the REST API routes for the settings.
	 */
	public function register_routes() {
		register_rest_route(
			'classifai/v1',
			'settings',
			[
				[
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_settings_callback' ],
					'permission_callback' => [ $this, 'get_settings_permissions_check' ],
				],
				[
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => [ $this, 'update_settings_callback' ],
					'permission_callback' => [ $this, 'update_settings_permissions_check' ],
				],
			]
		);

		register_rest_route(
			'classifai/v1',
			'registration',
			[
				[
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_registration_settings_callback' ],
					'permission_callback' => [ $this, 'registration_settings_permissions_check' ],
				],
				[
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => [ $this, 'update_registration_settings_callback' ],
					'permission_callback' => [ $this, 'registration_settings_permissions_check' ],
				],
			]
		);

		register_rest_route(
			'classifai/v1',
			'embeddings_in_progress',
			[
				[
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => [ $this, 'check_embedding_generation_status' ],
					'permission_callback' => [ $this, 'get_settings_permissions_check' ],
				],
			]
		);
	}

	/**
	 * Callback for getting the settings.
	 *
	 * @return \WP_REST_Response
	 */
	public function get_settings_callback(): \WP_REST_Response {
		$settings = $this->get_settings();
		return rest_ensure_response( $settings );
	}

	/**
	 * Check if a given request has access to get settings.
	 *
	 * @return bool
	 */
	public function get_settings_permissions_check(): bool {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Update the settings.
	 *
	 * @param \WP_REST_Request $request Full data about the request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function update_settings_callback( \WP_REST_Request $request ) {
		$params   = $request->get_json_params();
		$settings = $params['settings'] ?? [];
		$is_setup = $params['is_setup'] ?? false;
		$step     = $params['step'] ?? '';
		$features = $this->get_features( true );

		// Load settings error functions.
		if ( ! function_exists( 'add_settings_error' ) ) {
			require_once ABSPATH . 'wp-admin/includes/template.php';
		}

		foreach ( $settings as $feature_key => $feature_settings ) {
			$feature = $features[ $feature_key ];

			if ( ! $feature ) {
				return new \WP_Error( 'invalid_feature', __( 'Invalid feature.', 'classifai' ), [ 'status' => 400 ] );
			}

			// Skip sanitizing settings for setup step 1.
			if ( true === $is_setup && 'enable_features' === $step ) {
				$current_settings = $feature->get_settings();

				// Update only status of the feature.
				$current_settings['status'] = sanitize_text_field( $feature_settings['status'] ?? $current_settings['status'] );
				$new_settings               = $current_settings;
			} else {
				$new_settings = $feature->sanitize_settings( $settings[ $feature_key ] );
				if ( is_wp_error( $new_settings ) ) {
					continue;
				}
			}

			// Update settings.
			$feature->update_settings( $new_settings );

			$setting_errors = get_settings_errors();
			$errors         = array();
			if ( ! empty( $setting_errors ) ) {
				foreach ( $setting_errors as $setting_error ) {
					if ( empty( $setting_error['message'] ) ) {
						continue;
					}

					$errors[] = array(
						'code'    => $setting_error['code'],
						'message' => wp_strip_all_tags( $setting_error['message'] ),
					);
				}
			}
		}

		$response = array(
			'success'  => true,
			'settings' => $this->get_settings(),
		);

		if ( ! empty( $errors ) ) {
			$response['success'] = false;
			$response['errors']  = $errors;
		}

		return rest_ensure_response( $response );
	}

	/**
	 * Check if a given request has access to update settings.
	 *
	 * @return bool
	 */
	public function update_settings_permissions_check(): bool {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Callback for getting the registration settings.
	 *
	 * @return \WP_REST_Response
	 */
	public function get_registration_settings_callback(): \WP_REST_Response {
		$service_manager = new ServicesManager();
		$settings        = $service_manager->get_settings();
		return rest_ensure_response( $settings );
	}

	/**
	 * Update the registration settings.
	 *
	 * @param \WP_REST_Request $request Full data about the request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function update_registration_settings_callback( \WP_REST_Request $request ) {
		// Load settings error functions.
		if ( ! function_exists( 'add_settings_error' ) ) {
			require_once ABSPATH . 'wp-admin/includes/template.php';
		}

		$service_manager = new ServicesManager();
		$settings        = $service_manager->get_settings();
		$new_settings    = $service_manager->sanitize_settings( $request->get_json_params() );

		// Update the settings with the new values.
		$new_settings = array_merge( $settings, $new_settings );
		update_option( 'classifai_settings', $new_settings );

		$setting_errors = get_settings_errors();
		$errors         = array();
		if ( ! empty( $setting_errors ) ) {
			foreach ( $setting_errors as $setting_error ) {
				if ( empty( $setting_error['message'] ) ) {
					continue;
				}

				$errors[] = array(
					'code'    => $setting_error['code'],
					'message' => wp_strip_all_tags( $setting_error['message'] ),
				);
			}
		}

		$response = array(
			'success'  => true,
			'settings' => $new_settings,
		);

		if ( ! empty( $errors ) ) {
			$response['success'] = false;
			$response['errors']  = $errors;
		}

		return rest_ensure_response( $response );
	}

	/**
	 * Check if a given request has access to get/update registration settings.
	 *
	 * @return bool
	 */
	public function registration_settings_permissions_check(): bool {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Callback for getting the registration settings.
	 *
	 * @return \WP_REST_Response
	 */
	public function check_embedding_generation_status(): \WP_REST_Response {
		$classification = new Classification();
		$response       = array(
			'classifAIEmbedInProgress' => $classification->is_embeddings_generation_in_progress(),
		);
		return rest_ensure_response( $response );
	}
}
