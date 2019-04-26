<?php

namespace Classifai;

use Classifai\Admin\SavePostHandler;
use Classifai\Taxonomy\TaxonomyFactory;

/**
 * The main ClassifAI plugin object. Used as a singleton.
 */
class Plugin {

	/**
	 * @var $instance Plugin Singleton plugin instance
	 */
	public static $instance = null;

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
	 * @var $taxonomy_factory TaxonomyFactory Watson taxonomy factory
	 */
	public $taxonomy_factory;

	/**
	 * @var $save_post_handler SavePostHandler Triggers a classification with Watson
	 */
	public $save_post_handler;

	/**
	 * Setup WP hooks
	 */
	public function enable() {
		add_action( 'init', [ $this, 'init' ], 20 );
		add_action( 'init', [ $this, 'i18n' ] );
		add_action( 'enqueue_block_editor_assets', [ $this, 'enqueue_editor_assets' ] );
	}

	/**
	 * Load translations.
	 */
	public function i18n() {
		load_plugin_textdomain( 'classifai', false, CLASSIFAI_PLUGIN_DIR . '/languages' );
	}

	/**
	 * Enqueue the editor scripts.
	 */
	public function enqueue_editor_assets() {
		wp_enqueue_script(
			'classifai-editor', // Handle.
			CLASSIFAI_PLUGIN_URL . '/dist/js/editor.min.js',
			array( 'wp-blocks', 'wp-i18n', 'wp-element', 'wp-editor', 'wp-edit-post' ),
			CLASSIFAI_PLUGIN_VERSION,
			true
		);
	}

	/**
	 * Initializes the ClassifAI plugin modules and support objects.
	 */
	public function init() {
		do_action( 'before_classifai_init' );

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

		$post_types = get_supported_post_types();
		foreach ( $post_types as $post_type ) {
			register_meta(
				$post_type,
				'_classifai_error',
				[
					'show_in_rest' => true,
				]
			);
		}

		do_action( 'after_classifai_init' );
	}

	/**
	 * Initializes Admin only support objects
	 */
	public function init_admin_support() {
		$this->admin_support = [
			new Admin\SettingsPage(),
			new Admin\Notifications(),
		];

		foreach ( $this->admin_support as $support ) {
			if ( $support->can_register() ) {
				$support->register();
			}
		}
	}

	/**
	 * Adds ClassifAI Gutenberg Support if on the Gutenberg editor page
	 */
	public function init_admin_scripts() {
		if ( function_exists( 'is_gutenberg_page' ) && is_gutenberg_page() ) {
			wp_enqueue_script(
				'classifai-gutenberg-support',
				CLASSIFAI_PLUGIN_URL . 'assets/js/classifai-gutenberg-support.js',
				[ 'editor' ],
				CLASSIFAI_PLUGIN_VERSION,
				true
			);
		}
	}

	/**
	 * Initializes the ClassifAI WP CLI integration
	 */
	public function init_commands() {
		\WP_CLI::add_command(
			'classifai',
			'Classifai\Command\ClassifaiCommand'
		);

		if ( defined( 'CLASSIFAI_DEV' ) && CLASSIFAI_DEV ) {
			\WP_CLI::add_command(
				'rss',
				'Classifai\Command\RSSImporterCommand'
			);
		}
	}

}
