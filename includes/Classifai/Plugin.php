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
	}

	/**
	 * Initializes the ClassifAI plugin modules and support objects.
	 */
	public function init() {
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
		$classifai_services = apply_filters(
			'classifai_services',
			[
				'language_processing' => 'Classifai\Services\LanguageProcessing',
				'image_processing'    => 'Classifai\Services\ImageProcessing',
			]
		);

		$this->services = [
			'service_manager' => new Services\ServicesManager( $classifai_services ),
		];

		foreach ( $this->services as $service ) {
			if ( $service->can_register() ) {
				$service->register();
			}
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
		];

		foreach ( $this->admin_helpers as $instance ) {
			if ( $instance->can_register() ) {
				$instance->register();
			}
		}
	}
}
