<?php
/**
 * IBM Watson NLU
 */

namespace Classifai\Providers\Watson;

use Classifai\Providers\Provider;
use Classifai\Taxonomy\TaxonomyFactory;
use Classifai\Features\Classification;
use Classifai\Features\Feature;
use Classifai\Providers\Watson\PostClassifier;
use WP_Error;
use WP_REST_Request;
use WP_REST_Server;

use function Classifai\get_asset_info;
use function Classifai\check_term_permissions;

class NLU extends Provider {

	const ID = 'ibm_watson_nlu';

	/**
	 * @var $taxonomy_factory TaxonomyFactory Watson taxonomy factory
	 */
	public $taxonomy_factory;

	/**
	 * @var $save_post_handler SavePostHandler Triggers a classification with Watson
	 */
	public $save_post_handler;

	/**
	 * @var $nlu_features array The list of NLU features
	 */
	protected $nlu_features = [];

	/**
	 * Watson NLU constructor.
	 *
	 * @param \Classifai\Features\Feature $feature Feature instance (Optional, only required in admin).
	 */
	public function __construct( $feature = null ) {
		parent::__construct(
			'IBM Watson',
			'Natural Language Understanding',
			'watson_nlu'
		);

		$this->nlu_features = [
			'category' => [
				'feature'           => __( 'Category', 'classifai' ),
				'threshold'         => __( 'Category Threshold (%)', 'classifai' ),
				'taxonomy'          => __( 'Category Taxonomy', 'classifai' ),
				'threshold_default' => WATSON_CATEGORY_THRESHOLD,
				'taxonomy_default'  => WATSON_CATEGORY_TAXONOMY,
			],
			'keyword'  => [
				'feature'           => __( 'Keyword', 'classifai' ),
				'threshold'         => __( 'Keyword Threshold (%)', 'classifai' ),
				'taxonomy'          => __( 'Keyword Taxonomy', 'classifai' ),
				'threshold_default' => WATSON_KEYWORD_THRESHOLD,
				'taxonomy_default'  => WATSON_KEYWORD_TAXONOMY,
			],
			'entity'   => [
				'feature'           => __( 'Entity', 'classifai' ),
				'threshold'         => __( 'Entity Threshold (%)', 'classifai' ),
				'taxonomy'          => __( 'Entity Taxonomy', 'classifai' ),
				'threshold_default' => WATSON_ENTITY_THRESHOLD,
				'taxonomy_default'  => WATSON_ENTITY_TAXONOMY,
			],
			'concept'  => [
				'feature'           => __( 'Concept', 'classifai' ),
				'threshold'         => __( 'Concept Threshold (%)', 'classifai' ),
				'taxonomy'          => __( 'Concept Taxonomy', 'classifai' ),
				'threshold_default' => WATSON_CONCEPT_THRESHOLD,
				'taxonomy_default'  => WATSON_CONCEPT_TAXONOMY,
			],
		];

		$this->feature_instance = $feature;

		add_action( 'rest_api_init', [ $this, 'register_endpoints' ] );
	}

	/**
	 * Renders settings fields for this provider.
	 */
	public function render_provider_fields() {
		$settings = $this->feature_instance->get_settings( static::ID );

		add_settings_field(
			static::ID . '_endpoint_url',
			esc_html__( 'API URL', 'classifai' ),
			[ $this->feature_instance, 'render_input' ],
			$this->feature_instance->get_option_name(),
			$this->feature_instance->get_option_name() . '_section',
			[
				'option_index'  => static::ID,
				'label_for'     => 'endpoint_url',
				'default_value' => $settings['endpoint_url'],
				'input_type'    => 'text',
				'large'         => true,
				'class'         => 'classifai-provider-field hidden provider-scope-' . static::ID, // Important to add this.
			]
		);

		add_settings_field(
			static::ID . '_username',
			esc_html__( 'API Username', 'classifai' ),
			[ $this->feature_instance, 'render_input' ],
			$this->feature_instance->get_option_name(),
			$this->feature_instance->get_option_name() . '_section',
			[
				'option_index'  => static::ID,
				'label_for'     => 'username',
				'default_value' => $settings['username'],
				'input_type'    => 'text',
				'large'         => true,
				'class'         => 'classifai-provider-field ' . ( $this->use_username_password() ? 'hidden' : '' ) . ' provider-scope-' . static::ID, // Important to add this.
			]
		);

		add_settings_field(
			static::ID . '_password',
			esc_html__( 'API Key', 'classifai' ),
			[ $this->feature_instance, 'render_input' ],
			$this->feature_instance->get_option_name(),
			$this->feature_instance->get_option_name() . '_section',
			[
				'option_index'  => static::ID,
				'label_for'     => 'password',
				'default_value' => $settings['password'],
				'input_type'    => 'password',
				'large'         => true,
				'class'         => 'classifai-provider-field provider-scope-' . static::ID, // Important to add this.
				'description'   => sprintf(
					wp_kses(
						/* translators: %1$s is the link to register for an IBM Cloud account, %2$s is the link to setup the NLU service */
						__( 'Don\'t have an IBM Cloud account yet? <a title="Register for an IBM Cloud account" href="%1$s">Register for one</a> and set up a <a href="%2$s">Natural Language Understanding</a> Resource to get your API key.', 'classifai' ),
						[
							'a' => [
								'href'  => [],
								'title' => [],
							],
						]
					),
					esc_url( 'https://cloud.ibm.com/registration' ),
					esc_url( 'https://cloud.ibm.com/catalog/services/natural-language-understanding' )
				),
			]
		);

		add_settings_field(
			static::ID . '_toggle',
			'',
			function ( $args = [] ) {
				printf(
					'<a id="classifai-waston-cred-toggle" href="#" class="%s">%s</a>',
					$args['class'] ? esc_attr( $args['class'] ) : '', // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					$this->use_username_password() // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						? esc_html__( 'Use a username/password instead?', 'classifai' )
						: esc_html__( 'Use an API Key instead?', 'classifai' ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				);
			},
			$this->feature_instance->get_option_name(),
			$this->feature_instance->get_option_name() . '_section',
			[
				'class' => 'classifai-provider-field hidden provider-scope-' . static::ID, // Important to add this.
			]
		);

		add_settings_field(
			static::ID . '_classification_mode',
			esc_html__( 'Classification mode', 'classifai' ),
			[ $this->feature_instance, 'render_radio_group' ],
			$this->feature_instance->get_option_name(),
			$this->feature_instance->get_option_name() . '_section',
			[
				'option_index'  => static::ID,
				'label_for'     => 'classification_mode',
				'default_value' => $settings['classification_mode'],
				'class'         => 'classifai-provider-field hidden provider-scope-' . static::ID, // Important to add this.
				'options'       => array(
					'manual_review'            => __( 'Manual review', 'classifai' ),
					'automatic_classification' => __( 'Automatic classification', 'classifai' ),
				),
			]
		);

		add_settings_field(
			static::ID . '_classification_method',
			esc_html__( 'Classification method', 'classifai' ),
			[ $this->feature_instance, 'render_radio_group' ],
			$this->feature_instance->get_option_name(),
			$this->feature_instance->get_option_name() . '_section',
			[
				'option_index'  => static::ID,
				'label_for'     => 'classification_method',
				'default_value' => $settings['classification_method'],
				'class'         => 'classifai-provider-field hidden provider-scope-' . static::ID, // Important to add this.
				'options'       => array(
					'recommended_terms' => __( 'Recommend terms even if they do not exist on the site', 'classifai' ),
					'existing_terms'    => __( 'Only recommend terms that already exist on the site', 'classifai' ),
				),
			]
		);

		foreach ( $this->nlu_features as $classify_by => $labels ) {
			add_settings_field(
				static::ID . '_' . $classify_by,
				esc_html( $labels['feature'] ),
				[ $this, 'render_nlu_feature_settings' ],
				$this->feature_instance->get_option_name(),
				$this->feature_instance->get_option_name() . '_section',
				[
					'option_index'  => static::ID,
					'feature'       => $classify_by,
					'labels'        => $labels,
					'default_value' => $settings[ $classify_by ],
					'class'         => 'classifai-provider-field hidden provider-scope-' . static::ID, // Important to add this.
				]
			);
		}

		add_action( 'classifai_after_feature_settings_form', [ $this, 'render_previewer' ] );
	}

	/**
	 * Renders the previewer window for the feature.
	 *
	 * @param string $active_feature The active feature.
	 */
	public function render_previewer( string $active_feature ) {
		$feature = new Classification();

		if ( $feature::ID !== $active_feature || ! $feature->is_feature_enabled() ) {
			return;
		}
		?>

		<div id="classifai-post-preview-app">
			<?php
			$supported_post_statuses = get_supported_post_statuses();
			$supported_post_types    = get_supported_post_types();

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
					'enabled' => get_feature_enabled( 'category' ),
					'plural'  => 'categories',
				),
				'keyword'  => array(
					'name'    => esc_html__( 'Keyword', 'classifai' ),
					'enabled' => get_feature_enabled( 'keyword' ),
					'plural'  => 'keywords',
				),
				'entity'   => array(
					'name'    => esc_html__( 'Entity', 'classifai' ),
					'enabled' => get_feature_enabled( 'entity' ),
					'plural'  => 'entities',
				),
				'concept'  => array(
					'name'    => esc_html__( 'Concept', 'classifai' ),
					'enabled' => get_feature_enabled( 'concept' ),
					'plural'  => 'concepts',
				),
			);
			?>

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
				<?php
				foreach ( $features as $feature_slug => $feature ) :
					?>
					<div class="tax-row tax-row--<?php echo esc_attr( $feature['plural'] ); ?> <?php echo esc_attr( $feature['enabled'] ) ? '' : 'tax-row--hide'; ?>">
						<div class="tax-type"><?php echo esc_html( $feature['name'] ); ?></div>
					</div>
					<?php
				endforeach;
				?>
			</div>
		</div>

		<?php
	}

	/**
	 * Returns the default settings for this provider.
	 *
	 * @return array
	 */
	public function get_default_provider_settings(): array {
		$common_settings = [
			'endpoint_url'          => '',
			'apikey'                => '',
			'username'              => '',
			'password'              => '',
			'classification_mode'   => 'manual_review',
			'classification_method' => 'recommended_terms',
		];

		switch ( $this->feature_instance::ID ) {
			case Classification::ID:
				return array_merge(
					$common_settings,
					[
						'category'           => true,
						'category_threshold' => WATSON_CATEGORY_THRESHOLD,
						'category_taxonomy'  => WATSON_CATEGORY_TAXONOMY,

						'keyword'            => true,
						'keyword_threshold'  => WATSON_KEYWORD_THRESHOLD,
						'keyword_taxonomy'   => WATSON_KEYWORD_TAXONOMY,

						'concept'            => false,
						'concept_threshold'  => WATSON_CONCEPT_THRESHOLD,
						'concept_taxonomy'   => WATSON_CONCEPT_TAXONOMY,

						'entity'             => false,
						'entity_threshold'   => WATSON_ENTITY_THRESHOLD,
						'entity_taxonomy'    => WATSON_ENTITY_TAXONOMY,
					]
				);
		}

		return $common_settings;
	}

	/**
	 * Resets the settings for the NLU provider.
	 */
	public function reset_settings() {
		$settings = $this->get_default_settings() ?? [];
		update_option( $this->get_option_name(), $settings );
	}

	/**
	 * Register what we need for the plugin.
	 */
	public function register() {
		$feature = new Classification();

		if ( $feature->is_feature_enabled() && $feature->get_feature_provider_instance()::ID === static::ID ) {
			add_action( 'enqueue_block_editor_assets', [ $this, 'enqueue_editor_assets' ] );
			add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );

			// Add classifai meta box to classic editor.
			add_action( 'add_meta_boxes', [ $this, 'add_classifai_meta_box' ], 10, 2 );
			add_action( 'save_post', [ $this, 'classifai_save_post_metadata' ], 5 );

			add_filter( 'rest_api_init', [ $this, 'add_process_content_meta_to_rest_api' ] );

			$this->taxonomy_factory = new TaxonomyFactory();
			$this->taxonomy_factory->build_all();

			$this->save_post_handler = new SavePostHandler();
			$this->save_post_handler->register();

			new PreviewClassifierData();
		}
	}

	/**
	 * Enqueue the editor scripts.
	 */
	public function enqueue_editor_assets() {
		global $post;
		wp_enqueue_script(
			'classifai-editor',
			CLASSIFAI_PLUGIN_URL . 'dist/editor.js',
			get_asset_info( 'editor', 'dependencies' ),
			get_asset_info( 'editor', 'version' ),
			true
		);

		if ( empty( $post ) ) {
			return;
		}

		wp_enqueue_script(
			'classifai-gutenberg-plugin',
			CLASSIFAI_PLUGIN_URL . 'dist/gutenberg-plugin.js',
			array_merge( get_asset_info( 'gutenberg-plugin', 'dependencies' ), array( 'lodash' ) ),
			get_asset_info( 'gutenberg-plugin', 'dependencies' ),
			get_asset_info( 'gutenberg-plugin', 'version' ),
			true
		);

		wp_localize_script(
			'classifai-gutenberg-plugin',
			'classifaiPostData',
			[
				'NLUEnabled'           => ( new Classification() )->is_feature_enabled(),
				'supportedPostTypes'   => get_supported_post_types(),
				'supportedPostStatues' => get_supported_post_statuses(),
				'noPermissions'        => ! is_user_logged_in() || ! current_user_can( 'edit_post', $post->ID ),
			]
		);
	}

	/**
	 * Enqueue the admin scripts.
	 */
	public function enqueue_admin_assets() {
		wp_enqueue_script(
			'classifai-language-processing-script',
			CLASSIFAI_PLUGIN_URL . 'dist/language-processing.js',
			get_asset_info( 'language-processing', 'dependencies' ),
			get_asset_info( 'language-processing', 'version' ),
			true
		);

		wp_enqueue_style(
			'classifai-language-processing-style',
			CLASSIFAI_PLUGIN_URL . 'dist/language-processing.css',
			array(),
			get_asset_info( 'language-processing', 'version' ),
			'all'
		);
	}

	/**
	 * Setup fields
	 */
	public function setup_fields_sections() {
		// Add the settings section.
		add_settings_section(
			$this->get_option_name(),
			$this->provider_service_name,
			function () {
				printf(
					wp_kses(
						/* translators: %1$s is the link to register for an IBM Cloud account, %2$s is the link to setup the NLU service */
						__( 'Don\'t have an IBM Cloud account yet? <a title="Register for an IBM Cloud account" href="%1$s">Register for one</a> and set up a <a href="%2$s">Natural Language Understanding</a> Resource to get your API key.', 'classifai' ),
						[
							'a' => [
								'href'  => [],
								'title' => [],
							],
						]
					),
					esc_url( 'https://cloud.ibm.com/registration' ),
					esc_url( 'https://cloud.ibm.com/catalog/services/natural-language-understanding' )
				);

				$credentials = $this->get_settings( 'credentials' );
				$watson_url  = $credentials['watson_url'] ?? '';

				if ( ! empty( $watson_url ) && strpos( $watson_url, 'watsonplatform.net' ) !== false ) {
					echo '<div class="notice notice-error"><p><strong>';
						printf(
							wp_kses(
								/* translators: %s is the link to the IBM Watson documentation */
								__( 'The `watsonplatform.net` endpoint URLs were retired on 26 May 2021. Please update the endpoint url. Check <a title="Deprecated Endpoint: watsonplatform.net" href="%s">here</a> for details.', 'classifai' ),
								[
									'a' => [
										'href'  => [],
										'title' => [],
									],
								]
							),
							esc_url( 'https://cloud.ibm.com/docs/watson?topic=watson-endpoint-change' )
						);
					echo '</strong></p></div>';
				}
			},
			$this->get_option_name()
		);
	}

	/**
	 * Check if a username/password is used instead of API key.
	 *
	 * @return bool
	 */
	protected function use_username_password(): bool {
		$feature  = new Classification();
		$settings = $feature->get_settings( static::ID );

		if ( empty( $settings['username'] ) ) {
			return false;
		}

		return 'apikey' === $settings['username'];
	}

	/**
	 * Render the NLU features settings.
	 *
	 * @param array $args Settings for the inputs
	 */
	public function render_nlu_feature_settings( array $args ) {
		$feature      = $args['feature'];
		$labels       = $args['labels'];
		$option_index = $args['option_index'];

		$taxonomies = $this->get_supported_taxonomies();
		$features   = $this->feature_instance->get_settings( static::ID );
		$taxonomy   = isset( $features[ "{$feature}_taxonomy" ] ) ? $features[ "{$feature}_taxonomy" ] : $labels['taxonomy_default'];

		// Enable classification type
		$feature_args = [
			'label_for'    => $feature,
			'option_index' => $option_index,
			'input_type'   => 'checkbox',
		];

		$threshold_args = [
			'label_for'     => "{$feature}_threshold",
			'option_index'  => $option_index,
			'input_type'    => 'number',
			'default_value' => $labels['threshold_default'],
		];
		?>

		<fieldset>
		<legend class="screen-reader-text"><?php esc_html_e( 'Watson Category Settings', 'classifai' ); ?></legend>

		<p>
			<?php $this->feature_instance->render_input( $feature_args ); ?>
			<label
				for="classifai-settings-<?php echo esc_attr( $feature ); ?>"><?php esc_html_e( 'Enable', 'classifai' ); ?></label>
		</p>

		<p>
			<label
				for="classifai-settings-<?php echo esc_attr( "{$feature}_threshold" ); ?>"><?php echo esc_html( $labels['threshold'] ); ?></label><br/>
			<?php $this->feature_instance->render_input( $threshold_args ); ?>
		</p>

		<p>
			<label
				for="classifai-settings-<?php echo esc_attr( "{$feature}_taxonomy" ); ?>"><?php echo esc_html( $labels['taxonomy'] ); ?></label><br/>
			<select id="classifai-settings-<?php echo esc_attr( "{$feature}_taxonomy" ); ?>"
				name="<?php echo esc_attr( $this->feature_instance->get_option_name() ); ?>[<?php echo esc_attr( self::ID ); ?>][<?php echo esc_attr( "{$feature}_taxonomy" ); ?>]">
				<?php foreach ( $taxonomies as $name => $singular_name ) : ?>
					<option
						value="<?php echo esc_attr( $name ); ?>" <?php selected( $taxonomy, esc_attr( $name ) ); ?> ><?php echo esc_html( $singular_name ); ?></option>
				<?php endforeach; ?>
			</select>
		</p>
		<?php
	}

	/**
	 * Return the list of supported taxonomies
	 *
	 * @return array
	 */
	public function get_supported_taxonomies(): array {
		$taxonomies = \get_taxonomies( [], 'objects' );
		$supported  = [];

		foreach ( $taxonomies as $taxonomy ) {
			$supported[ $taxonomy->name ] = $taxonomy->labels->singular_name;
		}

		return $supported;
	}

	/**
	 * Helper to ensure the authentication works.
	 *
	 * @param array $settings The list of settings to be saved
	 * @return bool|WP_Error
	 */
	protected function nlu_authentication_check( array $settings ) {
		// Check that we have credentials before hitting the API.
		if ( empty( $settings[ static::ID ]['username'] )
			|| empty( $settings[ static::ID ]['password'] )
			|| empty( $settings[ static::ID ]['endpoint_url'] )
		) {
			return new WP_Error( 'auth', esc_html__( 'Please enter your credentials.', 'classifai' ) );
		}

		$request           = new APIRequest();
		$request->username = $settings[ static::ID ]['username'];
		$request->password = $settings[ static::ID ]['password'];
		$base_url          = trailingslashit( $settings[ static::ID ]['endpoint_url'] ) . 'v1/analyze';
		$url               = esc_url( add_query_arg( [ 'version' => WATSON_NLU_VERSION ], $base_url ) );
		$options           = [
			'body' => wp_json_encode(
				[
					'text'     => 'Lorem ipsum dolor sit amet.',
					'language' => 'en',
					'features' => [
						'keywords' => [
							'emotion' => false,
							'limit'   => 1,
						],
					],
				]
			),
		];

		$response = $request->post( $url, $options );

		if ( ! is_wp_error( $response ) ) {
			update_option( 'classifai_configured', true );
			return true;
		} else {
			delete_option( 'classifai_configured' );
			return $response;
		}
	}

	/**
	 * Sanitization for the options being saved.
	 *
	 * @param array $new_settings Array of settings about to be saved.
	 * @return array The sanitized settings to be saved.
	 */
	public function sanitize_settings( array $new_settings ): array {
		$settings      = $this->feature_instance->get_settings();
		$authenticated = $this->nlu_authentication_check( $new_settings );

		if ( is_wp_error( $authenticated ) ) {
			$new_settings[ static::ID ]['authenticated'] = false;
			add_settings_error(
				'classifai-credentials',
				'classifai-auth',
				$authenticated->get_error_message(),
				'error'
			);
		} else {
			$new_settings[ static::ID ]['authenticated'] = true;
		}

		$new_settings[ static::ID ]['classification_mode']   = sanitize_text_field( $new_settings[ static::ID ]['classification_mode'] ?? $settings[ static::ID ]['classification_mode'] );
		$new_settings[ static::ID ]['classification_method'] = sanitize_text_field( $new_settings[ static::ID ]['classification_method'] ?? $settings[ static::ID ]['classification_method'] );

		$new_settings[ static::ID ]['endpoint_url'] = esc_url_raw( $new_settings[ static::ID ]['endpoint_url'] ?? $settings[ static::ID ]['endpoint_url'] );
		$new_settings[ static::ID ]['username']     = sanitize_text_field( $new_settings[ static::ID ]['username'] ?? $settings[ static::ID ]['username'] );
		$new_settings[ static::ID ]['password']     = sanitize_text_field( $new_settings[ static::ID ]['password'] ?? $settings[ static::ID ]['password'] );

		$new_settings[ static::ID ]['category']           = absint( $new_settings[ static::ID ]['category'] ?? $settings[ static::ID ]['category'] );
		$new_settings[ static::ID ]['category_threshold'] = absint( $new_settings[ static::ID ]['category_threshold'] ?? $settings[ static::ID ]['category_threshold'] );
		$new_settings[ static::ID ]['category_taxonomy']  = sanitize_text_field( $new_settings[ static::ID ]['category_taxonomy'] ?? $settings[ static::ID ]['category_taxonomy'] );

		$new_settings[ static::ID ]['keyword']           = absint( $new_settings[ static::ID ]['keyword'] ?? $settings[ static::ID ]['keyword'] );
		$new_settings[ static::ID ]['keyword_threshold'] = absint( $new_settings[ static::ID ]['keyword_threshold'] ?? $settings[ static::ID ]['keyword_threshold'] );
		$new_settings[ static::ID ]['keyword_taxonomy']  = sanitize_text_field( $new_settings[ static::ID ]['keyword_taxonomy'] ?? $settings[ static::ID ]['keyword_taxonomy'] );

		$new_settings[ static::ID ]['entity']           = absint( $new_settings[ static::ID ]['entity'] ?? $settings[ static::ID ]['entity'] );
		$new_settings[ static::ID ]['entity_threshold'] = absint( $new_settings[ static::ID ]['entity_threshold'] ?? $settings[ static::ID ]['entity_threshold'] );
		$new_settings[ static::ID ]['entity_taxonomy']  = sanitize_text_field( $new_settings[ static::ID ]['entity_taxonomy'] ?? $settings[ static::ID ]['entity_taxonomy'] );

		$new_settings[ static::ID ]['concept']           = absint( $new_settings[ static::ID ]['concept'] ?? $settings[ static::ID ]['concept'] );
		$new_settings[ static::ID ]['concept_threshold'] = absint( $new_settings[ static::ID ]['concept_threshold'] ?? $settings[ static::ID ]['concept_threshold'] );
		$new_settings[ static::ID ]['concept_taxonomy']  = sanitize_text_field( $new_settings[ static::ID ]['concept_taxonomy'] ?? $settings[ static::ID ]['concept_taxonomy'] );

		return $new_settings;
	}

	/**
	 * Format the result of most recent request.
	 *
	 * @param array|WP_Error $data Response data to format.
	 * @return string
	 */
	protected function get_formatted_latest_response( $data ): string {
		if ( ! $data ) {
			return __( 'N/A', 'classifai' );
		}

		if ( is_wp_error( $data ) ) {
			return $data->get_error_message();
		}

		$formatted_data = array_intersect_key(
			$data,
			[
				'usage'    => 1,
				'language' => 1,
			]
		);

		foreach ( array_diff_key( $data, $formatted_data ) as $key => $value ) {
			$formatted_data[ $key ] = count( $value );
		}

		return preg_replace( '/,"/', ', "', wp_json_encode( $formatted_data ) );
	}

	/**
	 * Add metabox to enable/disable language processing on post/post types.
	 *
	 * @since 1.8.0
	 *
	 * @param string   $post_type Post Type.
	 * @param \WP_Post $post      WP_Post object.
	 */
	public function add_classifai_meta_box( string $post_type, \WP_Post $post ) {
		$supported_post_types = get_supported_post_types();
		$post_statuses        = get_supported_post_statuses();
		$post_status          = get_post_status( $post );
		if ( in_array( $post_type, $supported_post_types, true ) && in_array( $post_status, $post_statuses, true ) ) {
			add_meta_box(
				'classifai_language_processing_metabox',
				__( 'ClassifAI Language Processing', 'classifai' ),
				[ $this, 'render_classifai_meta_box' ],
				null,
				'side',
				'low',
				array( '__back_compat_meta_box' => true )
			);
		}
	}

	/**
	 * Render metabox content.
	 *
	 * @since 1.8.0
	 *
	 * @param \WP_Post $post WP_Post object.
	 */
	public function render_classifai_meta_box( \WP_Post $post ) {
		wp_nonce_field( 'classifai_language_processing_meta_action', 'classifai_language_processing_meta' );
		$classifai_process_content = get_post_meta( $post->ID, '_classifai_process_content', true );
		$classifai_process_content = ( 'no' === $classifai_process_content ) ? 'no' : 'yes';

		$post_type       = get_post_type_object( get_post_type( $post ) );
		$post_type_label = esc_html__( 'Post', 'classifai' );
		if ( $post_type ) {
			$post_type_label = $post_type->labels->singular_name;
		}
		?>
		<p>
			<label for="_classifai_process_content">
				<input type="checkbox" value="yes" id="_classifai_process_content" name="_classifai_process_content" <?php checked( $classifai_process_content, 'yes' ); ?> />
				<?php esc_html_e( 'Automatically tag content on update', 'classifai' ); ?>
			</label>
		</p>
		<div class="classifai-clasify-post-wrapper" style="display: none;">
			<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=classifai_classify_post&post_id=' . $post->ID ), 'classifai_classify_post_action', 'classifai_classify_post_nonce' ) ); ?>" class="button button-classify-post">
				<?php
				/* translators: %s Post type label */
				printf( esc_html__( 'Classify %s', 'classifai' ), esc_html( $post_type_label ) );
				?>
			</a>
		</div>
		<?php
	}

	/**
	 * Save language processing meta data on post/post types.
	 *
	 * @since 1.8.0
	 *
	 * @param int $post_id Post ID.
	 */
	public function classifai_save_post_metadata( int $post_id ) {
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) || 'revision' === get_post_type( $post_id ) ) {
			return;
		}

		if ( empty( $_POST['classifai_language_processing_meta'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['classifai_language_processing_meta'] ) ), 'classifai_language_processing_meta_action' ) ) {
			return;
		}

		$supported_post_types = get_supported_post_types();
		if ( ! in_array( get_post_type( $post_id ), $supported_post_types, true ) ) {
			return;
		}

		if ( isset( $_POST['_classifai_process_content'] ) && 'yes' === sanitize_text_field( wp_unslash( $_POST['_classifai_process_content'] ) ) ) {
			$classifai_process_content = 'yes';
		} else {
			$classifai_process_content = 'no';
		}

		update_post_meta( $post_id, '_classifai_process_content', $classifai_process_content );
	}

	/**
	 * Add `classifai_process_content` to rest API for view/edit.
	 */
	public function add_process_content_meta_to_rest_api() {
		$supported_post_types = get_supported_post_types();
		register_rest_field(
			$supported_post_types,
			'classifai_process_content',
			array(
				'get_callback'    => function ( $data ) {
					$process_content = get_post_meta( $data['id'], '_classifai_process_content', true );
					return ( 'no' === $process_content ) ? 'no' : 'yes';
				},
				'update_callback' => function ( $value, $data ) {
					$value = ( 'no' === $value ) ? 'no' : 'yes';
					return update_post_meta( $data->ID, '_classifai_process_content', $value );
				},
				'schema'          => [
					'type'    => 'string',
					'context' => [ 'view', 'edit' ],
				],
			)
		);
	}

	/**
	 * Returns whether the provider is configured or not.
	 *
	 * For backwards compat, we've maintained the use of the
	 * `classifai_configured` option. We default to looking for
	 * the `authenticated` setting though.
	 *
	 * @return bool
	 */
	public function is_configured(): bool {
		$is_configured = parent::is_configured();

		if ( ! $is_configured ) {
			$is_configured = (bool) get_option( 'classifai_configured', false );
		}

		return $is_configured;
	}

	/**
	 * Register REST endpoints.
	 */
	public function register_endpoints() {
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

		register_rest_route(
			'classifai/v1',
			'generate-tags/(?P<id>\d+)',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'generate_post_tags' ],
				'args'                => array(
					'id'        => array(
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
						'description'       => esc_html__( 'Post ID to generate tags.', 'classifai' ),
					),
					'linkTerms' => array(
						'type'        => 'boolean',
						'description' => esc_html__( 'Whether to link terms or not.', 'classifai' ),
						'default'     => true,
					),
				),
				'permission_callback' => [ $this, 'generate_post_tags_permissions_check' ],
			]
		);
	}

	/**
	 * Handle request to generate tags for given post ID.
	 *
	 * @param WP_REST_Request $request The full request object.
	 * @return array|bool|string|WP_Error
	 */
	public function generate_post_tags( WP_REST_Request $request ) {
		$post_id    = $request->get_param( 'id' );
		$link_terms = $request->get_param( 'linkTerms' );

		if ( empty( $post_id ) ) {
			return new WP_Error( 'post_id_required', esc_html__( 'Post ID is required to classify post.', 'classifai' ) );
		}

		$result = $this->rest_endpoint_callback(
			$post_id,
			'classify',
			[
				'link_terms' => $link_terms,
			]
		);

		return rest_ensure_response(
			array(
				'terms'              => $result,
				'feature_taxonomies' => $this->get_all_feature_taxonomies(),
			)
		);
	}

	/**
	 * Get all feature taxonomies.
	 *
	 * @since 2.5.0
	 *
	 * @return array|WP_Error
	 */
	public function get_all_feature_taxonomies() {
		// Get all feature taxonomies.
		$feature_taxonomies = [];
		foreach ( [ 'category', 'keyword', 'concept', 'entity' ] as $feature ) {
			if ( get_feature_enabled( $feature ) ) {
				$taxonomy   = get_feature_taxonomy( $feature );
				$permission = check_term_permissions( $taxonomy );

				if ( is_wp_error( $permission ) ) {
					return $permission;
				}

				if ( 'post_tag' === $taxonomy ) {
					$taxonomy = 'tags';
				}
				if ( 'category' === $taxonomy ) {
					$taxonomy = 'categories';
				}
				$feature_taxonomies[] = $taxonomy;
			}
		}

		return $feature_taxonomies;
	}

	/**
	 * Common entry point for all REST endpoints for this provider.
	 * This is called by the Service.
	 *
	 * @param int    $post_id The Post Id we're processing.
	 * @param string $route_to_call The route we are processing.
	 * @param array  $args Optional arguments to pass to the route.
	 * @return string|WP_Error
	 */
	public function rest_endpoint_callback( $post_id = 0, string $route_to_call = '', array $args = [] ) {
		$route_to_call = strtolower( $route_to_call );

		if ( ! $post_id || ! get_post( $post_id ) ) {
			return new WP_Error( 'post_id_required', esc_html__( 'A valid post ID is required to generate an excerpt.', 'classifai' ) );
		}

		$return = '';

		// Handle all of our routes.
		switch ( $route_to_call ) {
			case 'classify':
				$return = ( new Classification() )->run( $post_id, $args['link_terms'] ?? true );
				break;
		}

		return $return;
	}

	/**
	 * Handle request to generate tags for given post ID.
	 *
	 * @param int $post_id The Post Id we're processing.
	 * @return mixed
	 */
	public function classify_post( int $post_id ) {
		try {
			if ( empty( $post_id ) ) {
				return new WP_Error( 'post_id_required', esc_html__( 'Post ID is required to classify post.', 'classifai' ) );
			}

			$taxonomy_terms = [];
			$features       = [ 'category', 'keyword', 'concept', 'entity' ];

			// Process post content.
			$result = $this->classify( $post_id );

			if ( is_wp_error( $result ) ) {
				return $result;
			}

			foreach ( $features as $feature ) {
				$taxonomy = get_feature_taxonomy( $feature );
				$terms    = wp_get_object_terms( $post_id, $taxonomy );
				if ( ! is_wp_error( $terms ) ) {
					foreach ( $terms as $term ) {
						$taxonomy_terms[ $taxonomy ][] = $term->term_id;
					}
				}
			}

			// Return taxonomy terms.
			return rest_ensure_response( [ 'terms' => $taxonomy_terms ] );
		} catch ( \Exception $e ) {
			return new WP_Error( 'request_failed', $e->getMessage() );
		}
	}

	/**
	 * Check if a given request has access to generate tags
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|bool
	 */
	public function generate_post_tags_permissions_check( WP_REST_Request $request ) {
		$post_id = $request->get_param( 'id' );

		// Ensure we have a logged in user that can edit the item.
		if ( empty( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return false;
		}

		$post_type     = get_post_type( $post_id );
		$post_type_obj = get_post_type_object( $post_type );

		// Ensure the post type is allowed in REST endpoints.
		if ( ! $post_type || empty( $post_type_obj ) || empty( $post_type_obj->show_in_rest ) ) {
			return false;
		}

		// For all enabled features, ensure the user has proper permissions to add/edit terms.
		foreach ( [ 'category', 'keyword', 'concept', 'entity' ] as $feature ) {
			if ( ! get_feature_enabled( $feature ) ) {
				continue;
			}

			$taxonomy   = get_feature_taxonomy( $feature );
			$permission = check_term_permissions( $taxonomy );

			if ( is_wp_error( $permission ) ) {
				return $permission;
			}
		}

		$post_status   = get_post_status( $post_id );
		$supported     = get_supported_post_types();
		$post_statuses = get_supported_post_statuses();

		// Check if processing allowed.
		if ( ! in_array( $post_status, $post_statuses, true ) || ! in_array( $post_type, $supported, true ) || ! ( new Classification() )->is_feature_enabled() ) {
			return new WP_Error( 'not_enabled', esc_html__( 'Language Processing not enabled for current post.', 'classifai' ) );
		}

		return true;
	}

	/**
	 * Classifies the post specified with the PostClassifier object.
	 * Existing terms relationships are removed before classification.
	 *
	 * @param int  $post_id the post to classify & link
	 * @param bool $link_terms Whether to link the terms to the post.
	 * @return array|bool
	 */
	public function classify( int $post_id, bool $link_terms = true ) {
		/**
		 * Filter whether ClassifAI should classify a post.
		 *
		 * Default is true, return false to skip classifying a post.
		 *
		 * @since 1.2.0
		 * @hook classifai_should_classify_post
		 *
		 * @param {bool} $should_classify Whether the post should be classified. Default `true`, return `false` to skip
		 *                                classification for this post.
		 * @param {int}  $post_id         The ID of the post to be considered for classification.
		 *
		 * @return {bool} Whether the post should be classified.
		 */
		$classifai_should_classify_post = apply_filters( 'classifai_should_classify_post', true, $post_id );
		if ( ! $classifai_should_classify_post ) {
			return false;
		}

		$classifier = new PostClassifier();

		if ( $link_terms ) {
			if ( get_feature_enabled( 'category' ) ) {
				wp_delete_object_term_relationships( $post_id, get_feature_taxonomy( 'category' ) );
			}

			if ( get_feature_enabled( 'keyword' ) ) {
				wp_delete_object_term_relationships( $post_id, get_feature_taxonomy( 'keyword' ) );
			}

			if ( get_feature_enabled( 'concept' ) ) {
				wp_delete_object_term_relationships( $post_id, get_feature_taxonomy( 'concept' ) );
			}

			if ( get_feature_enabled( 'entity' ) ) {
				wp_delete_object_term_relationships( $post_id, get_feature_taxonomy( 'entity' ) );
			}
		}

		$output = $classifier->classify_and_link( $post_id, [], $link_terms );

		if ( is_wp_error( $output ) ) {
			update_post_meta(
				$post_id,
				'_classifai_error',
				wp_json_encode(
					[
						'code'    => $output->get_error_code(),
						'message' => $output->get_error_message(),
					]
				)
			);
		} else {
			// If there is no error, clear any existing error states.
			delete_post_meta( $post_id, '_classifai_error' );
		}

		return $output;
	}

	/**
	 * Returns the debug information for the provider settings.
	 *
	 * @return array
	 */
	public function get_debug_information(): array {
		$settings          = $this->feature_instance->get_settings();
		$provider_settings = $settings[ static::ID ];
		$debug_info        = [];

		if ( $this->feature_instance instanceof Classification ) {
			$debug_info[ __( 'Category (status)', 'classifai' ) ]    = Feature::get_debug_value_text( $provider_settings['category'], 1 );
			$debug_info[ __( 'Category (threshold)', 'classifai' ) ] = Feature::get_debug_value_text( $provider_settings['category_threshold'], 1 );
			$debug_info[ __( 'Category (taxonomy)', 'classifai' ) ]  = Feature::get_debug_value_text( $provider_settings['category_taxonomy'], 1 );

			$debug_info[ __( 'Keyword (status)', 'classifai' ) ]    = Feature::get_debug_value_text( $provider_settings['keyword'], 1 );
			$debug_info[ __( 'Keyword (threshold)', 'classifai' ) ] = Feature::get_debug_value_text( $provider_settings['keyword_threshold'], 1 );
			$debug_info[ __( 'Keyword (taxonomy)', 'classifai' ) ]  = Feature::get_debug_value_text( $provider_settings['keyword_taxonomy'], 1 );

			$debug_info[ __( 'Entity (status)', 'classifai' ) ]    = Feature::get_debug_value_text( $provider_settings['entity'], 1 );
			$debug_info[ __( 'Entity (threshold)', 'classifai' ) ] = Feature::get_debug_value_text( $provider_settings['entity_threshold'], 1 );
			$debug_info[ __( 'Entity (taxonomy)', 'classifai' ) ]  = Feature::get_debug_value_text( $provider_settings['entity_taxonomy'], 1 );

			$debug_info[ __( 'Concept (status)', 'classifai' ) ]    = Feature::get_debug_value_text( $provider_settings['concept'], 1 );
			$debug_info[ __( 'Concept (threshold)', 'classifai' ) ] = Feature::get_debug_value_text( $provider_settings['concept_threshold'], 1 );
			$debug_info[ __( 'Concept (taxonomy)', 'classifai' ) ]  = Feature::get_debug_value_text( $provider_settings['concept_taxonomy'], 1 );

			$debug_info[ __( 'Latest response', 'classifai' ) ] = $this->get_formatted_latest_response( get_transient( 'classifai_watson_nlu_latest_response' ) );
		}

		return apply_filters(
			'classifai_' . self::ID . '_debug_information',
			$debug_info,
			$settings,
			$this->feature_instance
		);
	}
}
