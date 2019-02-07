<?php

namespace Klasifai;

/**
 * The main Klasifai plugin object. Used as a singleton.
 */
class Plugin {

	/**
	 * singleton plugin instance
	 */
	static public $instance = null;

	/**
	 * Lazy initialize the plugin
	 */
	static public function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new Plugin();
		}

		return self::$instance;
	}

	/**
	 * Watson taxonomy factory
	 */
	public $taxonomy_factory;

	/**
	 * Triggers a classification with Watson
	 */
	public $save_post_handler;

	/**
	 * Setup WP hooks
	 */
	public function enable() {
		// NOTE: Must initialize before Fieldmanager ie:- priority = 99
		add_action( 'init', [ $this, 'init' ], 50 );
		add_action( 'admin_enqueue_scripts', [ $this, 'init_admin_scripts' ] );
	}

	/**
	 * Initializes the Klasifai plugin modules and support objects.
	 */
	function init() {
		do_action( 'before_klasifai_init' );

		$this->taxonomy_factory = new Taxonomy\TaxonomyFactory();
		$this->taxonomy_factory->build_all();

		$this->save_post_handler = new Admin\SavePostHandler();

		if ( $this->save_post_handler->can_register() ) {
			$this->save_post_handler->register();
		}

		if ( is_admin() ) {
			$this->init_admin_support();
		}

		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			$this->init_commands();
		}

		do_action( 'after_klasifai_init' );
	}

	/**
	 * Initializes Admin only support objects
	 */
	function init_admin_support() {
		$this->admin_support = [
			new Admin\SettingsSupport(),
		];

		foreach ( $this->admin_support as $support ) {
			if ( $support->can_register() ) {
				$support->register();
			}
		}
	}

	/**
	 * Adds Klasifai Gutenberg Support if on the Gutenberg editor page
	 */
	function init_admin_scripts() {
		if ( function_exists( 'is_gutenberg_page' ) && is_gutenberg_page() ) {
			wp_enqueue_script(
				'klasifai-gutenberg-support',
				KLASIFAI_PLUGIN_URL . 'assets/js/klasifai-gutenberg-support.js',
				[ 'editor' ],
				KLASIFAI_PLUGIN_VERSION,
				true
			);
		}
	}

	/**
	 * Initializes the Klasifai WP CLI integration
	 */
	function init_commands() {
		\WP_CLI::add_command(
			'klasifai', 'Klasifai\Command\KlasifaiCommand'
		);

		if ( defined( 'KLASIFAI_DEV' ) && KLASIFAI_DEV ) {
			\WP_CLI::add_command(
				'rss', 'Klasifai\Command\RSSImporterCommand'
			);
		}
	}

}
