<?php
namespace Classifai;

class Plugin {

	/**
	 * @var $instance Plugin Singleton plugin instance
	 */
	public static $instance = null;

	/**
	 * @var array $services The known list of services.
	 */
	public $services = [];

	/**
	 * @var array $admin_helpers Class instances providing features in the admin UI.
	 */
	public $admin_helpers = [];

	/**
	 * Lazy initialize the plugin
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new Plugin();
		}

		return self::$instance;
	}

	/**
	 * Setup WP hooks
	 */
	public function enable() {
		add_action( 'init', [ $this, 'init' ], 20 );
		add_action( 'init', [ $this, 'i18n' ] );
		add_action( 'admin_init', [ $this, 'init_admin_helpers' ] );
		add_action( 'admin_init', [ $this, 'add_privacy_policy_content' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
		add_filter( 'plugin_action_links_' . CLASSIFAI_PLUGIN_BASENAME, array( $this, 'filter_plugin_action_links' ) );
	}

	/**
	 * Initializes the ClassifAI plugin modules and support objects.
	 */
	public function init() {
		/**
		 * Fires before ClassifAI services are loaded.
		 *
		 * @since 1.2.0
		 * @hook before_classifai_init
		 */
		do_action( 'before_classifai_init' );

		// Initialize the services, each services handles the providers
		$this->init_services();

		$post_types = get_supported_post_types();
		foreach ( $post_types as $post_type ) {
			register_meta(
				$post_type,
				'_classifai_error',
				[
					'show_in_rest'  => true,
					'single'        => true,
					'auth_callback' => '__return_true',
				]
			);
		}

		// Initialize the classifAI Onboarding.
		$onboarding = new Admin\Onboarding();
		$onboarding->init();

		// Initialize the classifAI User Profile.
		$user_profile = new Admin\UserProfile();
		$user_profile->init();

		/**
		 * Fires after ClassifAI services are loaded.
		 *
		 * @since 1.2.0
		 * @hook after_classifai_init
		 */
		do_action( 'after_classifai_init' );
	}

	/**
	 * Load translations.
	 */
	public function i18n() {
		load_plugin_textdomain( 'classifai', false, CLASSIFAI_PLUGIN_DIR . '/languages' );
	}

	/**
	 * Initialize the Services.
	 */
	public function init_services() {
		/**
		 * Filter available Services.
		 *
		 * @since 1.3.0
		 * @hook classifai_services
		 *
		 * @param {array} 'services' Associative array of service slugs and PHP class namespace.
		 *
		 * @return {array} The filtered list of services.
		 */
		$classifai_services = apply_filters(
			'classifai_services',
			[
				'language_processing' => 'Classifai\Services\LanguageProcessing',
				'image_processing'    => 'Classifai\Services\ImageProcessing',
				'personalizer'        => 'Classifai\Services\Personalizer',
			]
		);

		$this->services = [
			'service_manager' => new Services\ServicesManager( $classifai_services ),
		];

		foreach ( $this->services as $service ) {
			$service->register();
		}
	}

	/**
	 * Initiates classes providing admin feature sfor the plugin.
	 *
	 * @since 1.4.0
	 */
	public function init_admin_helpers() {
		if ( ! empty( $this->admin_helpers ) ) {
			return;
		}

		$this->admin_helpers = [
			'notifications' => new Admin\Notifications(),
			'debug_info'    => new Admin\DebugInfo(),
			'bulk_actions'  => new Admin\BulkActions(),
			'updater'       => new Admin\Update(),
		];

		foreach ( $this->admin_helpers as $instance ) {
			if ( $instance->can_register() ) {
				$instance->register();
			}
		}
	}

	/**
	 * Adds information to the privacy policy.
	 */
	public function add_privacy_policy_content() {
		$content  = '<p class="privacy-policy-tutorial">' . esc_html__( 'ClassifAI integrates with various AI service providers. We recommend that you are transparent with your users that these AI integrations are in use.', 'classifai' ) . '</p>';
		$content .= '<strong class="privacy-policy-tutorial">' . esc_html__( 'Suggested text:', 'classifai' ) . '</strong> ';
		$content .= esc_html__( 'This site makes use of Artificial Intelligence tools to help with tasks like language processing, image processing, and content recommendations.', 'classifai' );

		wp_add_privacy_policy_content( 'ClassifAI', wp_kses_post( wpautop( $content, false ) ) );
	}

	/**
	 * Enqueue the admin scripts.
	 *
	 * @param string $hook_suffix The current admin page.
	 * @since 2.4.0 Use get_asset_info to get the asset version and dependencies.
	 */
	public function enqueue_admin_assets( $hook_suffix ) {
		$user_profile     = new Admin\UserProfile();

		// @todo: rework on this part.
		// $allowed_features = $user_profile->get_allowed_features( get_current_user_id() );
		$allowed_features = [];

		wp_enqueue_style(
			'classifai-admin-style',
			CLASSIFAI_PLUGIN_URL . 'dist/admin.css',
			array( 'wp-components' ),
			get_asset_info( 'admin', 'version' ),
			'all'
		);

		wp_enqueue_script(
			'classifai-admin-script',
			CLASSIFAI_PLUGIN_URL . 'dist/admin.js',
			get_asset_info( 'admin', 'dependencies' ),
			get_asset_info( 'admin', 'version' ),
			true
		);

		$localize_data = [
			'api_password'             => __( 'API Password', 'classifai' ),
			'api_key'                  => __( 'API Key', 'classifai' ),
			'use_key'                  => __( 'Use an API Key instead?', 'classifai' ),
			'use_password'             => __( 'Use a username/password instead?', 'classifai' ),
			'ajax_nonce'               => wp_create_nonce( 'classifai' ),
			'opt_out_enabled_features' => array_keys( $allowed_features ),
			'profile_url'              => esc_url( get_edit_profile_url( get_current_user_id() ) . '#classifai-profile-features-section' ),
		];

		wp_localize_script(
			'classifai-admin-script',
			'ClassifAI',
			$localize_data
		);

		if ( wp_script_is( 'wp-commands', 'registered' ) ) {
			wp_enqueue_script(
				'classifai-commands',
				CLASSIFAI_PLUGIN_URL . 'dist/commands.js',
				get_asset_info( 'commands', 'dependencies' ),
				get_asset_info( 'commands', 'version' ),
				true
			);
		}
	}

	/**
	 * Add the action links to the plugin page.
	 *
	 * @param array $links The Action links for the plugin.
	 *
	 * @return array
	 */
	public function filter_plugin_action_links( $links ) {

		if ( ! is_array( $links ) ) {
			return $links;
		}

		return array_merge(
			array(
				'setup'    => sprintf(
					'<a href="%s"> %s </a>',
					esc_url( admin_url( 'admin.php?page=classifai_setup' ) ),
					esc_html__( 'Set up', 'classifai' )
				),
				'settings' => sprintf(
					'<a href="%s"> %s </a>',
					esc_url( admin_url( 'tools.php?page=classifai' ) ),
					esc_html__( 'Settings', 'classifai' )
				),
			),
			$links
		);
	}
}
