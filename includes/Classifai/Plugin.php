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
		add_action( 'admin_init', [ $this, 'maybe_migrate_to_v3' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
		add_filter( 'plugin_action_links_' . CLASSIFAI_PLUGIN_BASENAME, array( $this, 'filter_plugin_action_links' ) );
		add_action( 'after_classifai_init', [ $this, 'load_action_scheduler' ] );
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

		// Initialize the services; each service handles their features.
		$this->init_services();

		if ( ! should_use_legacy_settings_panel() ) {
			// Initialize the ClassifAI Settings.
			$settings = new Admin\Settings();
			$settings->init();
		} else {
			// Initialize the ClassifAI Onboarding. This is only used for the legacy settings panel.
			$onboarding = new Admin\Onboarding();
			$onboarding->init();
		}

		// Initialize the ClassifAI User Profile.
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
	 * Initiates classes providing admin features.
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
	 * @since 2.4.0 Use get_asset_info to get the asset version and dependencies.
	 */
	public function enqueue_admin_assets() {
		$user_profile     = new Admin\UserProfile();
		$allowed_features = $user_profile->get_allowed_features( get_current_user_id() );

		wp_enqueue_style(
			'classifai-admin-style',
			CLASSIFAI_PLUGIN_URL . 'dist/admin.css',
			array( 'wp-components', 'wp-jquery-ui-dialog' ),
			get_asset_info( 'admin', 'version' ),
			'all'
		);

		wp_enqueue_script(
			'classifai-admin-script',
			CLASSIFAI_PLUGIN_URL . 'dist/admin.js',
			array_merge(
				get_asset_info( 'admin', 'dependencies' ),
				array(
					'jquery-ui-dialog',
					'heartbeat',
				)
			),
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
			'plugin_url'               => CLASSIFAI_PLUGIN_URL,
		];

		wp_localize_script(
			'classifai-admin-script',
			'ClassifAI',
			$localize_data
		);

		if ( wp_script_is( 'wp-commands', 'registered' ) ) {
			wp_enqueue_script(
				'classifai-plugin-commands-js',
				CLASSIFAI_PLUGIN_URL . 'dist/classifai-plugin-commands.js',
				get_asset_info( 'classifai-plugin-commands', 'dependencies' ),
				get_asset_info( 'classifai-plugin-commands', 'version' ),
				true
			);
		}
	}

	/**
	 * Add the action links to the plugin page.
	 *
	 * @param array $links The Action links for the plugin.
	 * @return array
	 */
	public function filter_plugin_action_links( $links ): array {

		if ( ! is_array( $links ) ) {
			return $links;
		}

		$setup_url = admin_url( 'tools.php?page=classifai#/language_processing?welcome_guide=1' );
		if ( should_use_legacy_settings_panel() ) {
			$setup_url = admin_url( 'admin.php?page=classifai_setup' );
		}

		return array_merge(
			array(
				'setup'    => sprintf(
					'<a href="%s"> %s </a>',
					esc_url( $setup_url ),
					esc_html__( 'Welcome', 'classifai' )
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

	/**
	 * Load the Action Scheduler library.
	 */
	public function load_action_scheduler() {
		$features                 = [ new \Classifai\Features\Classification(), new \Classifai\Features\TermCleanup() ];
		$is_feature_being_enabled = false;

		foreach ( $features as $feature ) {
			if ( isset( $_POST['classifai_feature_classification'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
				$is_feature_being_enabled = sanitize_text_field( wp_unslash( $_POST['classifai_feature_classification']['status'] ?? false ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
			}

			if ( ! ( $feature->is_enabled() || '1' === $is_feature_being_enabled ) ) {
				continue;
			}

			require_once CLASSIFAI_PLUGIN_DIR . '/vendor/woocommerce/action-scheduler/action-scheduler.php';
		}
	}

	/**
	 * Migrate the existing settings to v3 if necessary.
	 *
	 * @since 3.0.0
	 */
	public function maybe_migrate_to_v3() {
		$is_migrated = get_option( 'classifai_v3_migration_completed', false );

		if ( false !== $is_migrated ) {
			// Already migrated.
			return;
		}

		$features = array();

		// Get the existing settings.
		$nlu_settings          = get_option( 'classifai_watson_nlu', [] );
		$embeddings_settings   = get_option( 'classifai_openai_embeddings', [] );
		$whisper_settings      = get_option( 'classifai_openai_whisper', [] );
		$chatgpt_settings      = get_option( 'classifai_openai_chatgpt', [] );
		$tts_settings          = get_option( 'classifai_azure_text_to_speech', [] );
		$vision_settings       = get_option( 'classifai_computer_vision', [] );
		$dalle_settings        = get_option( 'classifai_openai_dalle', [] );
		$personalizer_settings = get_option( 'classifai_personalizer', [] );

		// If settings are there, migrate them.
		if ( ! empty( $nlu_settings ) || ! empty( $embeddings_settings ) ) {
			$features[] = \Classifai\Features\Classification::class;
		}

		if ( ! empty( $whisper_settings ) ) {
			$features[] = \Classifai\Features\AudioTranscriptsGeneration::class;
		}

		if ( ! empty( $chatgpt_settings ) ) {
			$features[] = \Classifai\Features\TitleGeneration::class;
			$features[] = \Classifai\Features\ExcerptGeneration::class;
			$features[] = \Classifai\Features\ContentResizing::class;
		}

		if ( ! empty( $tts_settings ) ) {
			$features[] = \Classifai\Features\TextToSpeech::class;
		}

		if ( ! empty( $vision_settings ) ) {
			$features[] = \Classifai\Features\DescriptiveTextGenerator::class;
			$features[] = \Classifai\Features\ImageTagsGenerator::class;
			$features[] = \Classifai\Features\ImageCropping::class;
			$features[] = \Classifai\Features\ImageTextExtraction::class;
			$features[] = \Classifai\Features\PDFTextExtraction::class;
		}

		if ( ! empty( $dalle_settings ) ) {
			$features[] = \Classifai\Features\ImageGeneration::class;
		}

		if ( ! empty( $personalizer_settings ) ) {
			$features[] = \Classifai\Features\RecommendedContent::class;
		}

		// Migrate settings.
		$migration_needed = ! empty( $features );
		foreach ( $features as $feature ) {
			$feature_instance = new $feature();
			$feature_id       = $feature_instance->get_option_name();

			if ( method_exists( $feature_instance, 'migrate_settings' ) ) {
				$migrated_settings = $feature_instance->migrate_settings();
				update_option( $feature_id, $migrated_settings );
			}
		}

		// Mark the migration as completed.
		update_option( 'classifai_v3_migration_completed', true, false );
		if ( $migration_needed ) {
			// This option will be used to display a notice only to users who have completed the migration process. This will help to avoid showing the notice to new users.
			update_option( 'classifai_display_v3_migration_notice', true, false );
		}
	}
}
