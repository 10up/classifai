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
	 * Setup WP hooks
	 */
	public function enable() {
		// NOTE: Must initialize before Fieldmanager ie:- priority = 99
		add_action( 'init', [ $this, 'init' ], 50 );
	}

	/**
	 * Initializes the Klasifai plugin modules and support objects.
	 */
	function init() {
		do_action( 'before_klasifai_init' );

		$this->taxonomy_factory = new Taxonomy\TaxonomyFactory();
		$this->taxonomy_factory->build_all();

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
			new Admin\SavePostHandler(),
			new Admin\SettingsPage(),
		];

		foreach ( $this->admin_support as $support ) {
			if ( $support->can_register() ) {
				$support->register();
			}
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
