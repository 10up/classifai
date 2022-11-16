<?php
/**
 * IBM Watson TTS
 */

namespace Classifai\Providers\Watson;

use Classifai\Admin\SavePostHandler;
use Classifai\Admin\PreviewClassifierData;
use Classifai\Providers\Provider;
use Classifai\Taxonomy\TaxonomyFactory;
use function Classifai\get_post_types_for_language_settings;
use function Classifai\get_post_statuses_for_language_settings;

class TTS extends Provider {

	/**
	 * @var $taxonomy_factory TaxonomyFactory Watson taxonomy factory
	 */
	public $taxonomy_factory;

	/**
	 * @var $save_post_handler SavePostHandler Triggers a classification with Watson
	 */
	public $save_post_handler;

	/**
	 * Watson TTS constructor.
	 *
	 * @param string $service The service this class belongs to.
	 */
	public function __construct( $service ) {
		parent::__construct(
			'IBM Watson',
			'Text To Speech',
			'watson_tts',
			$service
		);

		$this->tts_features = array(
			// 'category' => [
			// 'feature'           => __( 'Category', 'classifai' ),
			// 'threshold'         => __( 'Category Threshold (%)', 'classifai' ),
			// 'taxonomy'          => __( 'Category Taxonomy', 'classifai' ),
			// 'threshold_default' => WATSON_CATEGORY_THRESHOLD,
			// 'taxonomy_default'  => WATSON_CATEGORY_TAXONOMY,
			// ],
			// 'keyword'  => [
			// 'feature'           => __( 'Keyword', 'classifai' ),
			// 'threshold'         => __( 'Keyword Threshold (%)', 'classifai' ),
			// 'taxonomy'          => __( 'Keyword Taxonomy', 'classifai' ),
			// 'threshold_default' => WATSON_KEYWORD_THRESHOLD,
			// 'taxonomy_default'  => WATSON_KEYWORD_TAXONOMY,
			// ],
			// 'entity'   => [
			// 'feature'           => __( 'Entity', 'classifai' ),
			// 'threshold'         => __( 'Entity Threshold (%)', 'classifai' ),
			// 'taxonomy'          => __( 'Entity Taxonomy', 'classifai' ),
			// 'threshold_default' => WATSON_ENTITY_THRESHOLD,
			// 'taxonomy_default'  => WATSON_ENTITY_TAXONOMY,
			// ],
			// 'concept'  => [
			// 'feature'           => __( 'Concept', 'classifai' ),
			// 'threshold'         => __( 'Concept Threshold (%)', 'classifai' ),
			// 'taxonomy'          => __( 'Concept Taxonomy', 'classifai' ),
			// 'threshold_default' => WATSON_CONCEPT_THRESHOLD,
			// 'taxonomy_default'  => WATSON_CONCEPT_TAXONOMY,
			// ],
		);
	}

	/**
	 * Resets the settings for the TTS provider.
	 */
	public function reset_settings() {
		$settings = array(
			'post_types' => array(
				'post',
				'page',
			),
			'features'   => array(
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
			),
		);

		update_option( $this->get_option_name(), $settings );
	}

	/**
	 * Can the functionality be initialized?
	 *
	 * @return bool
	 */
	public function can_register() {
		if ( $this->tts_authentication_check_failed( $this->get_settings() ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Register what we need for the plugin.
	 */
	public function register() {
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_editor_assets' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );

		// Add classifai meta box to classic editor.
		add_action( 'add_meta_boxes', array( $this, 'add_classifai_meta_box' ), 10, 2 );
		add_action( 'save_post', array( $this, 'classifai_save_post_metadata' ), 5 );

		add_filter( 'rest_api_init', array( $this, 'add_process_content_meta_to_rest_api' ) );

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
		$defaults = array();
		$settings = get_option( $this->get_option_name(), array() );

		// If no settings have been saved, check for older storage to polyfill
		// These are pre-1.3 settings
		if ( empty( $settings ) ) {
			$old_settings = get_option( 'classifai_settings' );

			if ( isset( $old_settings['tts_credentials'] ) ) {
				$defaults['tts_credentials'] = $old_settings['tts_credentials'];
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
			'classifai-editor', // Handle.
			CLASSIFAI_PLUGIN_URL . 'dist/editor.js',
			array( 'wp-blocks', 'wp-i18n', 'wp-element', 'wp-editor', 'wp-edit-post' ),
			CLASSIFAI_PLUGIN_VERSION,
			true
		);

		if ( empty( $post ) ) {
			return;
		}

		wp_enqueue_script(
			'classifai-gutenberg-plugin',
			CLASSIFAI_PLUGIN_URL . 'dist/gutenberg-plugin.js',
			array( 'lodash', 'wp-blocks', 'wp-i18n', 'wp-element', 'wp-editor', 'wp-edit-post', 'wp-components', 'wp-data', 'wp-plugins' ),
			CLASSIFAI_PLUGIN_VERSION,
			true
		);

		wp_localize_script(
			'classifai-gutenberg-plugin',
			'classifaiPostData',
			array(
				'TTSEnabled'           => \Classifai\language_processing_features_enabled(),
				'supportedPostTypes'   => \Classifai\get_supported_post_types(),
				'supportedPostStatues' => \Classifai\get_supported_post_statuses(),
				'noPermissions'        => ! is_user_logged_in() || ! current_user_can( 'edit_post', $post->ID ),
			)
		);
	}

	/**
	 * Enqueue the admin scripts.
	 */
	public function enqueue_admin_assets() {
		wp_enqueue_script(
			'classifai-language-processing-script',
			CLASSIFAI_PLUGIN_URL . 'dist/language-processing.js',
			array(),
			CLASSIFAI_PLUGIN_VERSION,
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
						__( 'Don\'t have an IBM Cloud account yet? <a title="Register for an IBM Cloud account" href="%1$s">Register for one</a> and set up a <a href="%2$s">Text To Speech</a> Resource to get your API key.', 'classifai' ),
						array(
							'a' => array(
								'href'  => array(),
								'title' => array(),
							),
						)
					),
					esc_url( 'https://cloud.ibm.com/registration' ),
					esc_url( 'https://cloud.ibm.com/catalog/services/text-to-speech' )
				);

				$credentials = $this->get_settings( 'tts_credentials' );
				$watson_url  = $credentials['watson_url'] ?? '';

				if ( ! empty( $watson_url ) && strpos( $watson_url, 'watsonplatform.net' ) !== false ) {
					echo '<div class="notice notice-error"><p><strong>';
						printf(
							wp_kses(
								__( 'The `watsonplatform.net` endpoint URLs were retired on 26 May 2021. Please update the endpoint url. Check <a title="Deprecated Endpoint: watsonplatform.net" href="%s">here</a> for details.', 'classifai' ),
								array(
									'a' => array(
										'href'  => array(),
										'title' => array(),
									),
								)
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
		$this->do_tts_features_sections();
	}

	/**
	 * Helper method to create the credentials section
	 */
	protected function do_credentials_section() {
		add_settings_field(
			'url',
			esc_html__( 'API URL', 'classifai' ),
			array( $this, 'render_input' ),
			$this->get_option_name(),
			$this->get_option_name(),
			array(
				'label_for'    => 'watson_url',
				'option_index' => 'tts_credentials',
				'input_type'   => 'text',
				'large'        => true,
			)
		);
		add_settings_field(
			'username',
			esc_html__( 'API Username', 'classifai' ),
			array( $this, 'render_input' ),
			$this->get_option_name(),
			$this->get_option_name(),
			array(
				'label_for'     => 'watson_username',
				'option_index'  => 'tts_credentials',
				'input_type'    => 'text',
				'default_value' => 'apikey',
				'large'         => true,
				'class'         => $this->use_username_password() ? 'hidden' : '',
			)
		);
		add_settings_field(
			'password',
			esc_html__( 'API Key', 'classifai' ),
			array( $this, 'render_input' ),
			$this->get_option_name(),
			$this->get_option_name(),
			array(
				'label_for'    => 'watson_password',
				'option_index' => 'tts_credentials',
				'input_type'   => 'password',
				'large'        => true,
			)
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
		$settings = $this->get_settings( 'tts_credentials' );

		if ( empty( $settings['watson_username'] ) ) {
			return false;
		}

		return 'apikey' === $settings['watson_username'];
	}

	/**
	 * Helper method to create the watson features section
	 */
	protected function do_tts_features_sections() {
		add_settings_field(
			'post-types',
			esc_html__( 'Post Types to Convert', 'classifai' ),
			array( $this, 'render_post_types_checkboxes' ),
			$this->get_option_name(),
			$this->get_option_name(),
			array(
				'option_index' => 'post_types',
			)
		);

		// add_settings_field(
		// 'post-statuses',
		// esc_html__( 'Post Statuses to Convert', 'classifai' ),
		// [ $this, 'render_post_statuses_checkboxes' ],
		// $this->get_option_name(),
		// $this->get_option_name(),
		// [
		// 'option_index' => 'post_statuses',
		// ]
		// );

		foreach ( $this->tts_features as $feature => $labels ) {
			add_settings_field(
				$feature,
				esc_html( $labels['feature'] ),
				array( $this, 'render_tts_feature_settings' ),
				$this->get_option_name(),
				$this->get_option_name(),
				array(
					'option_index' => 'features',
					'feature'      => $feature,
					'labels'       => $labels,
				)
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
			$args = array(
				'label_for'    => $post_type->name,
				'option_index' => 'post_types',
				'input_type'   => 'checkbox',
			);

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
			$args = array(
				'label_for'    => $post_status_key,
				'option_index' => 'post_statuses',
				'input_type'   => 'checkbox',
			);

			echo '<li>';
			$this->render_input( $args );
			echo '<label for="classifai-settings-' . esc_attr( $post_status_key ) . '">' . esc_html( $post_status_label ) . '</label>';
			echo '</li>';
		}

		echo '</ul>';
	}

	/**
	 * Render the TTS features settings.
	 *
	 * @param array $args Settings for the inputs
	 *
	 * @return void
	 */
	public function render_tts_feature_settings( $args ) {
		$feature = $args['feature'];
		$labels  = $args['labels'];

		$taxonomies = $this->get_supported_taxonomies();
		$features   = $this->get_settings( 'features' );
		$taxonomy   = isset( $features[ "{$feature}_taxonomy" ] ) ? $features[ "{$feature}_taxonomy" ] : $labels['taxonomy_default'];

		// Enable classification type
		$feature_args = array(
			'label_for'    => $feature,
			'option_index' => 'features',
			'input_type'   => 'checkbox',
		);

		$threshold_args = array(
			'label_for'     => "{$feature}_threshold",
			'option_index'  => 'features',
			'input_type'    => 'number',
			'default_value' => $labels['threshold_default'],
		);
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
		$taxonomies = \get_taxonomies( array(), 'objects' );
		$supported  = array();

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
	 * @return bool
	 */
	protected function tts_authentication_check_failed( $settings ) {

		// Check that we have credentials before hitting the API.
		if ( ! isset( $settings['tts_credentials'] )
			|| empty( $settings['tts_credentials']['watson_username'] )
			|| empty( $settings['tts_credentials']['watson_password'] )
			|| empty( $settings['tts_credentials']['watson_url'] )
		) {
			return true;
		}

		$request           = new \Classifai\Watson\APIRequest();
		$request->username = $settings['tts_credentials']['watson_username'];
		$request->password = $settings['tts_credentials']['watson_password'];
		$base_url          = trailingslashit( $settings['tts_credentials']['watson_url'] ) . 'v1/synthesize';
		$url               = esc_url( add_query_arg( array( 'version' => WATSON_TTS_VERSION ), $base_url ) );
		$options           = array(
			'headers' => array(
				'Content-Type' => 'multipart/form-data',
				'Accept'       => 'audio/mp3',
			),
			'body'    => wp_json_encode(
				array(
					'text'   => 'Lorem ipsum dolor sit amet.',
					'accept' => 'audio/mp3',
				)
			),
		);
		$response          = $request->postAudio( $url, $options );

		$is_error = is_wp_error( $response );
		if ( ! $is_error ) {
			update_option( 'classifai_configured', true );
		} else {
			delete_option( 'classifai_configured' );
		}

		return $is_error;

	}


	/**
	 * Sanitization for the options being saved.
	 *
	 * @param array $settings Array of settings about to be saved.
	 *
	 * @return array The sanitized settings to be saved.
	 */
	public function sanitize_settings( $settings ) {
		$new_settings = $this->get_settings();
		if ( $this->tts_authentication_check_failed( $settings ) ) {
			add_settings_error(
				'tts_credentials',
				'classifai-auth',
				esc_html__( 'IBM Watson TTS Authentication Failed. Please check credentials.', 'classifai' ),
				'error'
			);
		}

		if ( isset( $settings['tts_credentials']['watson_url'] ) ) {
			$new_settings['tts_credentials']['watson_url'] = esc_url_raw( $settings['tts_credentials']['watson_url'] );
		}

		if ( isset( $settings['tts_credentials']['watson_username'] ) ) {
			$new_settings['tts_credentials']['watson_username'] = sanitize_text_field( $settings['tts_credentials']['watson_username'] );
		}

		if ( isset( $settings['tts_credentials']['watson_password'] ) ) {
			$new_settings['tts_credentials']['watson_password'] = sanitize_text_field( $settings['tts_credentials']['watson_password'] );
		}

		// Sanitize the post type checkboxes
		$post_types = get_post_types( array( 'public' => true ), 'objects' );
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

		foreach ( $this->tts_features as $feature => $labels ) {
			// Set the enabled flag.
			if ( isset( $settings['features'][ $feature ] ) ) {
				$new_settings['features'][ $feature ] = absint( $settings['features'][ $feature ] );
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

		$settings_post_types = $settings['post_types'] ?? array();
		$post_types          = array_filter(
			array_keys( $settings_post_types ),
			function( $post_type ) use ( $settings_post_types ) {
				return 1 === intval( $settings_post_types[ $post_type ] );
			}
		);

		$credentials = $settings['tts_credentials'] ?? array();

		return array(
			__( 'Configured', 'classifai' )      => $configured ? __( 'yes', 'classifai' ) : __( 'no', 'classifai' ),
			__( 'API URL', 'classifai' )         => $credentials['watson_url'] ?? '',
			__( 'API username', 'classifai' )    => $credentials['watson_username'] ?? '',
			__( 'Post types', 'classifai' )      => implode( ', ', $post_types ),
			__( 'Features', 'classifai' )        => preg_replace( '/,"/', ', "', wp_json_encode( $settings['features'] ?? '' ) ),
			__( 'Latest response', 'classifai' ) => $this->get_formatted_latest_response(),
		);
	}

	/**
	 * Format the result of most recent request.
	 *
	 * @return string
	 */
	private function get_formatted_latest_response() {
		$data = get_transient( 'classifai_watson_tts_latest_response' );

		if ( ! $data ) {
			return __( 'N/A', 'classifai' );
		}

		if ( is_wp_error( $data ) ) {
			return $data->get_error_message();
		}

		$formatted_data = array_intersect_key(
			$data,
			array(
				'usage'    => 1,
				'language' => 1,
			)
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
				'classifai-tts-meta-box',
				__( 'ClassifAI Text To Speech', 'classifai' ),
				array( $this, 'render_classifai_meta_box' ),
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
				'schema'          => array(
					'type'    => 'string',
					'context' => array( 'view', 'edit' ),
				),
			)
		);
	}
}
