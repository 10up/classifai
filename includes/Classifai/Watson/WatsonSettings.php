<?php

namespace Classifai\Watson;

use Classifai\Admin\ProviderSettings;

class WatsonSettings extends ProviderSettings {

	/**
	 * @var string $option Option that stores the classifai settings
	 */
	public $option = 'classifai_settings';

	/**
	 * @var array $features Array of Watson Features.
	 *
	 * This is populated upon construct because of translation functions
	 */
	public $nlu_features = [];

	/**
	 * The admin_support items require this method.
	 *
	 * @todo remove this requirement.
	 * @return bool
	 */
	public function can_register() {
		return true;
	}

	/**
	 * Object setup
	 */
	public function __construct() {
		parent::__construct( 'watson_settings' );

		$this->nlu_features = [
			'category' => [
				'feature' => __( 'Category', 'classifai' ),
				'threshold' => __( 'Category Threshold (%)', 'classifai' ),
				'taxonomy' => __( 'Category Taxonomy', 'classifai' ),
				'threshold_default' => WATSON_CATEGORY_THRESHOLD,
				'taxonomy_default' => WATSON_CATEGORY_TAXONOMY,
			],
			'keyword' => [
				'feature' => __( 'Keyword', 'classifai' ),
				'threshold' => __( 'Keyword Threshold (%)', 'classifai' ),
				'taxonomy' => __( 'Keyword Taxonomy', 'classifai' ),
				'threshold_default' => WATSON_KEYWORD_THRESHOLD,
				'taxonomy_default' => WATSON_KEYWORD_TAXONOMY,
			],
			'entity' => [
				'feature' => __( 'Entity', 'classifai' ),
				'threshold' => __( 'Entity Threshold (%)', 'classifai' ),
				'taxonomy' => __( 'Entity Taxonomy', 'classifai' ),
				'threshold_default' => WATSON_ENTITY_THRESHOLD,
				'taxonomy_default' => WATSON_ENTITY_TAXONOMY,
			],
			'concept' => [
				'feature' => __( 'Concept', 'classifai' ),
				'threshold' => __( 'Concept Threshold (%)', 'classifai' ),
				'taxonomy' => __( 'Concept Taxonomy', 'classifai' ),
				'threshold_default' => WATSON_CONCEPT_THRESHOLD,
				'taxonomy_default' => WATSON_CONCEPT_TAXONOMY,
			],
		];
	}

	/**
	 * Helper to get the settings and allow for settings default values.
	 *
	 * @param string|bool|mixed $index Optional. Name of the settings option index.
	 *
	 * @return array
	 */
	protected function get_settings( $index = false ) {
		$defaults = [];
		$settings = get_option( $this->option, [] );
		$settings = wp_parse_args( $settings, $defaults );

		if ( $index && isset( $settings[ $index ] ) ) {
			return $settings[ $index ];
		}

		return $settings;
	}


	/**
 * Register the actions required for the settings page.
 */
	public function register() {
		add_action( 'admin_menu', [ $this, 'register_admin_menu_item' ], 11 );
		add_action( 'admin_init', [ $this, 'setup_fields_sections' ] );
		add_action( 'admin_init', [ $this, 'register_settings' ] );
	}


	/**
	 * Adds the submenu item.
	 */
	public function register_admin_menu_item() {
		$is_setup = get_option( 'classifai_configured' );

		$title = esc_html__( 'IBM Watson NLU', 'classifai' );
		$menu_title = $title;

		add_submenu_page(
			'classifai_settings',
			$title,
			$menu_title,
			'manage_options',
			$this->menu_slug,
			[ $this, 'render_settings_page' ]
		);
	}


	/**
	 * Set up the fields for each section.
	 */
	public function setup_fields_sections() {
		// Create the Credentials Section.
		$this->do_credentials_section();

		// Create content tagging section
		$this->do_nlu_features_sections();

	}

	/**
	 * Helper method to create the credentials section
	 */
	protected function do_credentials_section() {
		add_settings_section( 'credentials', esc_html__( 'IBM Watson API Credentials', 'classifai' ), '', 'classifai-settings' );
		add_settings_field(
			'url',
			esc_html__( 'API URL', 'classifai' ),
			[ $this, 'render_input' ],
			'classifai-settings',
			'credentials',
			[
				'label_for'    => 'watson_url',
				'option_index' => 'credentials',
				'input_type'   => 'text',
			]
		);
		add_settings_field(
			'username',
			esc_html__( 'API Username', 'classifai' ),
			[ $this, 'render_input' ],
			'classifai-settings',
			'credentials',
			[
				'label_for'     => 'watson_username',
				'option_index'  => 'credentials',
				'input_type'    => 'text',
				'default_value' => 'apikey',
				'description'   => __( 'If your credentials do not include a username, it is typically apikey', 'classifai' ),
			]
		);
		add_settings_field(
			'password',
			esc_html__( 'API Key / Password', 'classifai' ),
			[ $this, 'render_input' ],
			'classifai-settings',
			'credentials',
			[
				'label_for'    => 'watson_password',
				'option_index' => 'credentials',
				'input_type'   => 'password',
			]
		);
	}

	/**
	 * Helper method to create the watson features section
	 */
	protected function do_nlu_features_sections() {
		// Add the settings section.
		add_settings_section( 'watson-content-classification', esc_html__( 'Content Classification with IBM Watson NLU', 'classifai' ), '', 'classifai-settings' );

		add_settings_field(
			'post-types',
			esc_html__( 'Post Types to Classify', 'classifai' ),
			[ $this, 'render_post_types_checkboxes' ],
			'classifai-settings',
			'watson-content-classification',
			[
				'option_index' => 'post_types',
			]
		);

		foreach ( $this->nlu_features as $feature => $labels ) {
			add_settings_field(
				$feature,
				esc_html( $labels['feature'] ),
				[ $this, 'render_nlu_feature_settings' ],
				'classifai-settings',
				'watson-content-classification',
				[
					'option_index' => 'features',
					'feature' => $feature,
					'labels' => $labels,
				]
			);
		}
	}


	/**
	 * Register the settings and sanitzation callback method.
	 */
	public function register_settings() {
		register_setting( 'classifai_settings', 'classifai_settings', [ $this, 'sanitize_settings' ] );
	}


	/**
	 * Render the settings page.
	 */
	public function render_settings_page() {
		?>
		<div class="wrap">
			<h2><?php esc_html_e( 'IBM Watson NLU Settings', 'classifai' ); ?></h2>

			<form action="options.php" method="post">

				<?php settings_fields( 'classifai_settings' ); ?>
				<?php do_settings_sections( 'classifai-settings' ); ?>

				<?php submit_button(); ?>

			</form>
		</div>
		<?php
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
			name="classifai_settings[<?php echo esc_attr( $args['option_index'] ); ?>][<?php echo esc_attr( $args['label_for'] ); ?>]"
			<?php echo $attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> />
		<?php
		if ( ! empty( $args['description'] ) ) {
			echo '<br /><span class="description">' . esc_html( $args['description'] ) . '</span>';
		}
	}

	/**
	 * Render the post types checkbox array.
	 *
	 * @param array $args Settings for the input
	 * @return void
	 */
	public function render_post_types_checkboxes( $args ) {
		echo '<ul>';
		$post_types = get_post_types( [ 'public' => true ], 'objects' );
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
	 * Render the NLU features settings.
	 *
	 * @param array $args Settings for the inputs
	 * @return void
	 */
	public function render_nlu_feature_settings( $args ) {
		$feature = $args['feature'];
		$labels = $args['labels'];

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

		<fieldset><legend class="screen-reader-text"><?php esc_html_e( 'Watson Category Settings', 'classifai' ); ?></legend>

			<p>
				<?php $this->render_input( $feature_args ); ?>
				<label for="classifai-settings-<?php echo esc_attr( $feature ); ?>"><?php esc_html_e( 'Enable', 'classifai' ); ?></label>
			</p>

		<p>
			<label for="classifai-settings-<?php echo esc_attr( "{$feature}_threshold" ); ?>"><?php echo esc_html( $labels['threshold'] ); ?></label><br />
			<?php $this->render_input( $threshold_args ); ?>
		</p>

		<p>
			<label for="classifai-settings-<?php echo esc_attr( "{$feature}_taxonomy" ); ?>"><?php echo esc_html( $labels['taxonomy'] ); ?></label><br />
			<select id="classifai-settings-<?php echo esc_attr( "{$feature}_taxonomy" ); ?>" name="classifai_settings[features][<?php echo esc_attr( "{$feature}_taxonomy" ); ?>]">
				<?php foreach ( $taxonomies as $name => $singular_name ) : ?>
					<option value="<?php echo esc_attr( $name ); ?>" <?php selected( $taxonomy, esc_attr( $name ) ); ?> ><?php echo esc_html( $singular_name ); ?></option>
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
	 * @return bool
	 */
	protected function authentication_check_failed( $settings ) {

		// Check that we have credentials before hitting the API.
		if ( ! isset( $settings['credentials'] )
			|| empty( $settings['credentials']['watson_username'] )
			|| empty( $settings['credentials']['watson_password'] )
			|| empty( $settings['credentials']['watson_url'] )
		) {
			return true;
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

		if ( $this->authentication_check_failed( $settings ) ) {
			add_settings_error(
				'credentials',
				'classifai-auth',
				esc_html__( 'Authentication Failed. Please check credentials.', 'classifai' ),
				'error'
			);
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

		foreach ( $this->nlu_features as $feature => $labels ) {
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

}
