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
	protected $services = [];

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
					'show_in_rest' => true,
					'single'       => true,
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
		$this->services = [
			new Services\ServicesManager(
				apply_filters(
					'classifai_services',
					[ 'Classifai\Services\LanguageProcessing', 'Classifai\Services\ImageProcessing' ]
				)
			),
			new Admin\Notifications(),
		];

		foreach ( $this->services as $service ) {
			if ( $service->can_register() ) {
				$service->register();
			}
		}
	}
}
