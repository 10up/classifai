<?php
/**
 * IBM Watson NLU
 */

namespace Classifai\Providers\Watson;

use Classifai\Admin\SavePostHandler;
use Classifai\Admin\PreviewClassifierData;
use Classifai\Providers\Provider;
use Classifai\Taxonomy\TaxonomyFactory;
use function Classifai\get_plugin_settings;
use function Classifai\get_post_types_for_language_settings;
use function Classifai\get_post_statuses_for_language_settings;
use function Classifai\get_asset_info;
use WP_Error;

class NLU extends Provider {

	/**
	 * @var $taxonomy_factory TaxonomyFactory Watson taxonomy factory
	 */
	public $taxonomy_factory;

	/**
	 * @var $save_post_handler SavePostHandler Triggers a classification with Watson
	 */
	public $save_post_handler;

	/**
	 * Watson NLU constructor.
	 *
	 * @param string $service The service this class belongs to.
	 */
	public function __construct( $service ) {
		parent::__construct(
			'IBM Watson',
			'Natural Language Understanding',
			'watson_nlu',
			$service
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

		// Set the onboarding options.
		$this->onboarding_options = array(
			'title'    => __( 'IBM Watson NLU', 'classifai' ),
			'fields'   => array( 'url', 'username', 'password', 'toggle' ),
			'features' => array(),
		);

		$post_types = get_post_types_for_language_settings();
		foreach ( $post_types as $post_type ) {
			// translators: %s is the post type label.
			$this->onboarding_options['features'][ 'post_types__' . $post_type->name ] = sprintf( __( 'Automatically tag %s', 'classifai' ), $post_type->label );
		}

	}

	/**
	 * Resets the settings for the NLU provider.
	 */
	public function reset_settings() {
		$settings = [
			'post_types' => [
				'post',
				'page',
			],
			'features'   => [
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
			],
		];

		update_option( $this->get_option_name(), $settings );
	}

	/**
	 * Register what we need for the plugin.
	 */
	public function register() {
		add_action( 'enqueue_block_editor_assets', [ $this, 'enqueue_editor_assets' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );

		// Add classifai meta box to classic editor.
		add_action( 'add_meta_boxes', [ $this, 'add_classifai_meta_box' ], 10, 2 );
		add_action( 'save_post', [ $this, 'classifai_save_post_metadata' ], 5 );

		add_filter( 'rest_api_init', [ $this, 'add_process_content_meta_to_rest_api' ] );

		$this->taxonomy_factory = new TaxonomyFactory();
		$this->taxonomy_factory->build_all();

		$this->save_post_handler = new SavePostHandler();

		if ( $this->save_post_handler->can_register() ) {
			$this->save_post_handler->register();
		}

		new PreviewClassifierData();
	}

	/**
	 * Helper to get the settings and allow for settings default values.
	 *
	 * Overridden from parent to polyfill older settings storage schema.
	 *
	 * @param string|bool|mixed $index Optional. Name of the settings option index.
	 *
	 * @return array
	 */
	public function get_settings( $index = false ) {
		$defaults = [];
		$settings = get_option( $this->get_option_name(), [] );

		// If no settings have been saved, check for older storage to polyfill
		// These are pre-1.3 settings
		if ( empty( $settings ) ) {
			$old_settings = get_option( 'classifai_settings' );

			if ( isset( $old_settings['credentials'] ) ) {
				$defaults['credentials'] = $old_settings['credentials'];
			}

			if ( isset( $old_settings['post_types'] ) ) {
				$defaults['post_types'] = $old_settings['post_types'];
			}

			if ( isset( $old_settings['features'] ) ) {
				$defaults['features'] = $old_settings['features'];
			}
		}

		$settings = wp_parse_args( $settings, $defaults );

		if ( $index && isset( $settings[ $index ] ) ) {
			return $settings[ $index ];
		}

		return $settings;
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
			get_asset_info( 'gutenberg-plugin', 'dependencies' ),
			get_asset_info( 'gutenberg-plugin', 'version' ),
			true
		);

		wp_localize_script(
			'classifai-gutenberg-plugin',
			'classifaiPostData',
			[
				'NLUEnabled'           => \Classifai\language_processing_features_enabled(),
				'supportedPostTypes'   => \Classifai\get_supported_post_types(),
				'supportedPostStatues' => \Classifai\get_supported_post_statuses(),
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
			CLASSIFAI_PLUGIN_VERSION,
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
			function() {
				printf(
					wp_kses(
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

		// Create the Credentials Section.
		$this->do_credentials_section();

		// Create content tagging section
		$this->do_nlu_features_sections();
	}

	/**
	 * Helper method to create the credentials section
	 */
	protected function do_credentials_section() {
		add_settings_field(
			'url',
			esc_html__( 'API URL', 'classifai' ),
			[ $this, 'render_input' ],
			$this->get_option_name(),
			$this->get_option_name(),
			[
				'label_for'    => 'watson_url',
				'option_index' => 'credentials',
				'input_type'   => 'text',
				'large'        => true,
			]
		);
		add_settings_field(
			'username',
			esc_html__( 'API Username', 'classifai' ),
			[ $this, 'render_input' ],
			$this->get_option_name(),
			$this->get_option_name(),
			[
				'label_for'     => 'watson_username',
				'option_index'  => 'credentials',
				'input_type'    => 'text',
				'default_value' => 'apikey',
				'large'         => true,
				'class'         => $this->use_username_password() ? 'hidden' : '',
			]
		);
		add_settings_field(
			'password',
			esc_html__( 'API Key', 'classifai' ),
			[ $this, 'render_input' ],
			$this->get_option_name(),
			$this->get_option_name(),
			[
				'label_for'    => 'watson_password',
				'option_index' => 'credentials',
				'input_type'   => 'password',
				'large'        => true,
			]
		);
		add_settings_field(
			'toggle',
			'',
			function() {
				printf(
					'<a id="classifai-waston-cred-toggle" href="#">%s</a>',
					$this->use_username_password()
						? esc_html__( 'Use a username/password instead?', 'classifai' )
						: esc_html__( 'Use an API Key instead?', 'classifai' )
				);
			},
			$this->get_option_name(),
			$this->get_option_name()
		);
	}

	/**
	 * Check if a username/password is used instead of API key.
	 *
	 * @return bool
	 */
	protected function use_username_password() {
		$settings = $this->get_settings( 'credentials' );

		if ( empty( $settings['watson_username'] ) ) {
			return false;
		}

		return 'apikey' === $settings['watson_username'];
	}

	/**
	 * Helper method to create the watson features section
	 */
	protected function do_nlu_features_sections() {
		add_settings_field(
			'post-types',
			esc_html__( 'Post Types to Classify', 'classifai' ),
			[ $this, 'render_post_types_checkboxes' ],
			$this->get_option_name(),
			$this->get_option_name(),
			[
				'option_index' => 'post_types',
			]
		);

		add_settings_field(
			'post-statuses',
			esc_html__( 'Post Statuses to Classify', 'classifai' ),
			[ $this, 'render_post_statuses_checkboxes' ],
			$this->get_option_name(),
			$this->get_option_name(),
			[
				'option_index' => 'post_statuses',
			]
		);

		foreach ( $this->nlu_features as $feature => $labels ) {
			add_settings_field(
				$feature,
				esc_html( $labels['feature'] ),
				[ $this, 'render_nlu_feature_settings' ],
				$this->get_option_name(),
				$this->get_option_name(),
				[
					'option_index' => 'features',
					'feature'      => $feature,
					'labels'       => $labels,
				]
			);
		}
	}

	/**
	 * Generic text input field callback
	 *
	 * @param array $args The args passed to add_settings_field.
	 */
	public function render_input( $args ) {
		$setting_index = $this->get_settings( $args['option_index'] );
		$type          = $args['input_type'] ?? 'text';
		$value         = ( isset( $setting_index[ $args['label_for'] ] ) ) ? $setting_index[ $args['label_for'] ] : '';

		// Check for a default value
		$value = ( empty( $value ) && isset( $args['default_value'] ) ) ? $args['default_value'] : $value;
		$attrs = '';
		$class = '';

		switch ( $type ) {
			case 'text':
			case 'password':
				$attrs = ' value="' . esc_attr( $value ) . '"';
				$class = empty( $args['large'] ) ? 'regular-text' : 'large-text';
				break;
			case 'number':
				$attrs = ' value="' . esc_attr( $value ) . '"';
				$class = 'small-text';
				break;
			case 'checkbox':
				$attrs = ' value="1"' . checked( '1', $value, false );
				break;
		}
		?>
		<input
			type="<?php echo esc_attr( $type ); ?>"
			id="classifai-settings-<?php echo esc_attr( $args['label_for'] ); ?>"
			class="<?php echo esc_attr( $class ); ?>"
			name="classifai_<?php echo esc_attr( $this->option_name ); ?>[<?php echo esc_attr( $args['option_index'] ); ?>][<?php echo esc_attr( $args['label_for'] ); ?>]"
			<?php echo $attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> />
		<?php
		if ( ! empty( $args['description'] ) ) {
			echo '<br /><span class="description">' . wp_kses_post( $args['description'] ) . '</span>';
		}
	}

	/**
	 * Render the post types checkbox array.
	 *
	 * @param array $args Settings for the input
	 *
	 * @return void
	 */
	public function render_post_types_checkboxes( $args ) {
		echo '<ul>';
		$post_types = get_post_types_for_language_settings();
		foreach ( $post_types as $post_type ) {
			$args = [
				'label_for'    => $post_type->name,
				'option_index' => 'post_types',
				'input_type'   => 'checkbox',
			];

			echo '<li>';
			$this->render_input( $args );
			echo '<label for="classifai-settings-' . esc_attr( $post_type->name ) . '">' . esc_html( $post_type->label ) . '</label>';
			echo '</li>';
		}

		echo '</ul>';
	}


	/**
	 * Render the post statuses checkbox array.
	 *
	 * @param array $args Settings for the input
	 *
	 * @return void
	 */
	public function render_post_statuses_checkboxes( $args ) {
		echo '<ul>';
		$post_statuses = get_post_statuses_for_language_settings();
		foreach ( $post_statuses as $post_status_key => $post_status_label ) {
			$args = [
				'label_for'    => $post_status_key,
				'option_index' => 'post_statuses',
				'input_type'   => 'checkbox',
			];

			echo '<li>';
			$this->render_input( $args );
			echo '<label for="classifai-settings-' . esc_attr( $post_status_key ) . '">' . esc_html( $post_status_label ) . '</label>';
			echo '</li>';
		}

		echo '</ul>';
	}

	/**
	 * Render the NLU features settings.
	 *
	 * @param array $args Settings for the inputs
	 *
	 * @return void
	 */
	public function render_nlu_feature_settings( $args ) {
		$feature = $args['feature'];
		$labels  = $args['labels'];

		$taxonomies = $this->get_supported_taxonomies();
		$features   = $this->get_settings( 'features' );
		$taxonomy   = isset( $features[ "{$feature}_taxonomy" ] ) ? $features[ "{$feature}_taxonomy" ] : $labels['taxonomy_default'];

		// Enable classification type
		$feature_args = [
			'label_for'    => $feature,
			'option_index' => 'features',
			'input_type'   => 'checkbox',
		];

		$threshold_args = [
			'label_for'     => "{$feature}_threshold",
			'option_index'  => 'features',
			'input_type'    => 'number',
			'default_value' => $labels['threshold_default'],
		];
		?>

		<fieldset>
		<legend class="screen-reader-text"><?php esc_html_e( 'Watson Category Settings', 'classifai' ); ?></legend>

		<p>
			<?php $this->render_input( $feature_args ); ?>
			<label
				for="classifai-settings-<?php echo esc_attr( $feature ); ?>"><?php esc_html_e( 'Enable', 'classifai' ); ?></label>
		</p>

		<p>
			<label
				for="classifai-settings-<?php echo esc_attr( "{$feature}_threshold" ); ?>"><?php echo esc_html( $labels['threshold'] ); ?></label><br/>
			<?php $this->render_input( $threshold_args ); ?>
		</p>

		<p>
			<label
				for="classifai-settings-<?php echo esc_attr( "{$feature}_taxonomy" ); ?>"><?php echo esc_html( $labels['taxonomy'] ); ?></label><br/>
			<select id="classifai-settings-<?php echo esc_attr( "{$feature}_taxonomy" ); ?>"
				name="classifai_<?php echo esc_attr( $this->option_name ); ?>[features][<?php echo esc_attr( "{$feature}_taxonomy" ); ?>]">
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
	public function get_supported_taxonomies() {
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
	 *
	 * @return bool|WP_Error
	 */
	protected function nlu_authentication_check( $settings ) {

		// Check that we have credentials before hitting the API.
		if ( ! isset( $settings['credentials'] )
			|| empty( $settings['credentials']['watson_username'] )
			|| empty( $settings['credentials']['watson_password'] )
			|| empty( $settings['credentials']['watson_url'] )
		) {
			return new WP_Error( 'auth', esc_html__( 'Please enter your credentials.', 'classifai' ) );
		}

		$request           = new \Classifai\Watson\APIRequest();
		$request->username = $settings['credentials']['watson_username'];
		$request->password = $settings['credentials']['watson_password'];
		$base_url          = trailingslashit( $settings['credentials']['watson_url'] ) . 'v1/analyze';
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
		$response          = $request->post( $url, $options );

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
	 * @param array $settings Array of settings about to be saved.
	 *
	 * @return array The sanitized settings to be saved.
	 */
	public function sanitize_settings( $settings ) {
		$new_settings  = $this->get_settings();
		$authenticated = $this->nlu_authentication_check( $settings );

		if ( is_wp_error( $authenticated ) ) {
			$new_settings['authenticated'] = false;
			add_settings_error(
				'credentials',
				'classifai-auth',
				$authenticated->get_error_message(),
				'error'
			);
		} else {
			$new_settings['authenticated'] = true;
		}

		if ( isset( $settings['credentials']['watson_url'] ) ) {
			$new_settings['credentials']['watson_url'] = esc_url_raw( $settings['credentials']['watson_url'] );
		}

		if ( isset( $settings['credentials']['watson_username'] ) ) {
			$new_settings['credentials']['watson_username'] = sanitize_text_field( $settings['credentials']['watson_username'] );
		}

		if ( isset( $settings['credentials']['watson_password'] ) ) {
			$new_settings['credentials']['watson_password'] = sanitize_text_field( $settings['credentials']['watson_password'] );
		}

		// Sanitize the post type checkboxes
		$post_types = get_post_types( [ 'public' => true ], 'objects' );
		foreach ( $post_types as $post_type ) {
			if ( isset( $settings['post_types'][ $post_type->name ] ) ) {
				$new_settings['post_types'][ $post_type->name ] = absint( $settings['post_types'][ $post_type->name ] );
			} else {
				$new_settings['post_types'][ $post_type->name ] = null;
			}
		}

		// Sanitize the post statuses checkboxes
		$post_statuses = get_post_statuses_for_language_settings();
		foreach ( $post_statuses as $post_status_key => $post_status_value ) {
			if ( isset( $settings['post_statuses'][ $post_status_key ] ) ) {
				$new_settings['post_statuses'][ $post_status_key ] = absint( $settings['post_statuses'][ $post_status_key ] );
			} else {
				$new_settings['post_statuses'][ $post_status_key ] = null;
			}
		}

		$feature_enabled = false;

		foreach ( $this->nlu_features as $feature => $labels ) {
			// Set the enabled flag.
			if ( isset( $settings['features'][ $feature ] ) ) {
				$new_settings['features'][ $feature ] = absint( $settings['features'][ $feature ] );
				$feature_enabled                      = true;
			} else {
				$new_settings['features'][ $feature ] = null;
			}

			// Set the threshold
			if ( isset( $settings['features'][ "{$feature}_threshold" ] ) ) {
				$new_settings['features'][ "{$feature}_threshold" ] = min( absint( $settings['features'][ "{$feature}_threshold" ] ), 100 );
			}

			if ( isset( $settings['features'][ "{$feature}_taxonomy" ] ) ) {
				$new_settings['features'][ "{$feature}_taxonomy" ] = sanitize_text_field( $settings['features'][ "{$feature}_taxonomy" ] );
			}
		}

		// Show a warning if the NLU feature and Embeddings feature are both enabled.
		if ( $feature_enabled ) {
			$embeddings_settings = get_plugin_settings( 'language_processing', 'Embeddings' );

			if ( isset( $embeddings_settings['enable_classification'] ) && 1 === (int) $embeddings_settings['enable_classification'] ) {
				add_settings_error(
					'features',
					'conflict',
					esc_html__( 'OpenAI Embeddings classification is turned on. This may conflict with the NLU classification feature. It is possible to run both features but if they use the same taxonomies, one will overwrite the other.', 'classifai' ),
					'warning'
				);
			}
		}

		return $new_settings;
	}

	/**
	 * Provides debug information related to the provider.
	 *
	 * @param array|null $settings Settings array. If empty, settings will be retrieved.
	 * @param boolean    $configured Whether the provider is correctly configured. If null, the option will be retrieved.
	 * @return string|array
	 * @since 1.4.0
	 */
	public function get_provider_debug_information( $settings = null, $configured = null ) {
		if ( is_null( $settings ) ) {
			$settings = $this->sanitize_settings( $this->get_settings() );
		}

		if ( is_null( $configured ) ) {
			$configured = get_option( 'classifai_configured' );
		}

		$settings_post_types = $settings['post_types'] ?? [];
		$post_types          = array_filter(
			array_keys( $settings_post_types ),
			function( $post_type ) use ( $settings_post_types ) {
				return 1 === intval( $settings_post_types[ $post_type ] );
			}
		);

		$credentials = $settings['credentials'] ?? [];

		return [
			__( 'Configured', 'classifai' )      => $configured ? __( 'yes', 'classifai' ) : __( 'no', 'classifai' ),
			__( 'API URL', 'classifai' )         => $credentials['watson_url'] ?? '',
			__( 'API username', 'classifai' )    => $credentials['watson_username'] ?? '',
			__( 'Post types', 'classifai' )      => implode( ', ', $post_types ),
			__( 'Features', 'classifai' )        => preg_replace( '/,"/', ', "', wp_json_encode( $settings['features'] ?? '' ) ),
			__( 'Latest response', 'classifai' ) => $this->get_formatted_latest_response( get_transient( 'classifai_watson_nlu_latest_response' ) ),
		];
	}

	/**
	 * Format the result of most recent request.
	 *
	 * @param array|WP_Error $data Response data to format.
	 *
	 * @return string
	 */
	protected function get_formatted_latest_response( $data ) {
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
	 * @param string  $post_type Post Type.
	 * @param WP_Post $post      WP_Post object.
	 *
	 * @since 1.8.0
	 */
	public function add_classifai_meta_box( $post_type, $post ) {
		$supported_post_types = \Classifai\get_supported_post_types();
		$post_statuses        = \Classifai\get_supported_post_statuses();
		$post_status          = get_post_status( $post );
		if ( in_array( $post_type, $supported_post_types, true ) && in_array( $post_status, $post_statuses, true ) ) {
			add_meta_box(
				'classifai-nlu-meta-box',
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
	 * @param WP_Post $post WP_Post object.
	 *
	 * @since 1.8.0
	 */
	public function render_classifai_meta_box( $post ) {
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
				<?php esc_html_e( 'Process content on update', 'classifai' ); ?>
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
	 * @param int $post_id Post ID.
	 *
	 * @since 1.8.0
	 */
	public function classifai_save_post_metadata( $post_id ) {
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) || 'revision' === get_post_type( $post_id ) ) {
			return;
		}

		if ( empty( $_POST['classifai_language_processing_meta'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['classifai_language_processing_meta'] ) ), 'classifai_language_processing_meta_action' ) ) {
			return;
		}

		$supported_post_types = \Classifai\get_supported_post_types();
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
		$supported_post_types = \Classifai\get_supported_post_types();
		register_rest_field(
			$supported_post_types,
			'classifai_process_content',
			array(
				'get_callback'    => function( $object ) {
					$process_content = get_post_meta( $object['id'], '_classifai_process_content', true );
					return ( 'no' === $process_content ) ? 'no' : 'yes';
				},
				'update_callback' => function ( $value, $object ) {
					$value = ( 'no' === $value ) ? 'no' : 'yes';
					return update_post_meta( $object->ID, '_classifai_process_content', $value );
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
	public function is_configured() {
		$is_configured = parent::is_configured();

		if ( ! $is_configured ) {
			$is_configured = (bool) get_option( 'classifai_configured', false );
		}

		return $is_configured;
	}

}
