<?php

namespace Classifai\Admin;

use Classifai\Plugin;

class SettingsPage {

	/**
	 * @var string $option Option that stores the classifai settings
	 */
	public $option = 'classifai_settings';

	/**
	 * @var array $features of Watson Features.
	 */
	public $features = [
		'category',
		'keyword',
		'entity',
	];

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
		add_action( 'admin_menu', [ $this, 'register_admin_menu_item' ] );
		add_action( 'admin_init', [ $this, 'setup_fields_sections' ] );
		add_action( 'admin_init', [ $this, 'register_settings' ] );
	}


	/**
	 * Adds the submenu item.
	 */
	public function register_admin_menu_item() {
		add_submenu_page(
			'options-general.php',
			esc_html__( 'ClassifAI', 'classifai' ),
			esc_html__( 'ClassifAI', 'classifai' ),
			'manage_options',
			'classifai_settings',
			[ $this, 'render_settings_page' ]
		);
	}


	/**
	 * Set up the fields for each section.
	 */
	public function setup_fields_sections() {
		// Create the Credentials Section.
		$this->do_credentials_section();

		// Create the post types section
		$this->do_post_types_section();

		// Create features section
		$this->do_watson_features_section();

	}

	/**
	 * Helper method to create the credentials section
	 */
	protected function do_credentials_section() {
		add_settings_section( 'credentials', esc_html__( 'IBM Watson API Credentials', 'classifai' ), '', 'classifai-settings' );
		add_settings_field(
			'username',
			esc_html__( 'Username', 'classifai' ),
			[ $this, 'render_input' ],
			'classifai-settings',
			'credentials',
			[
				'label_for'    => 'watson_username',
				'option_index' => 'credentials',
				'input_type'   => 'text',
			]
		);
		add_settings_field(
			'password',
			esc_html__( 'Password', 'classifai' ),
			[ $this, 'render_input' ],
			'classifai-settings',
			'credentials',
			[
				'label_for'    => 'watson_password',
				'option_index' => 'credentials',
				'input_type'   => 'text',
			]
		);
	}

	/**
	 * Helper method to create the post types section
	 */
	protected function do_post_types_section() {
		// Add the settings section.
		add_settings_section( 'post-types', esc_html__( 'Post Types to classify', 'classifai' ), '', 'classifai-settings' );

		$post_types = get_post_types( [ 'public' => true ], 'objects' );
		foreach ( $post_types as $post_type ) {
			add_settings_field(
				$post_type->name,
				$post_type->label,
				[ $this, 'render_input' ],
				'classifai-settings',
				'post-types',
				[
					'label_for'    => $post_type->name,
					'option_index' => 'post_types',
					'input_type'   => 'checkbox',
				]
			);
		}
	}

	/**
	 * Helper method to create the watson features section
	 */
	protected function do_watson_features_section() {
		add_settings_section( 'watson-features', esc_html__( 'IBM Watson Features to enable', 'classifai' ), '', 'classifai-settings' );

		foreach ( $this->features as $feature ) {
			$title = ucfirst( $feature );
			// Checkbox.
			add_settings_field(
				$feature,
				sprintf( esc_html__( '%s:', 'classifai' ), esc_html( $title ) ), //@codingStandardsIgnoreLine.
				[ $this, 'render_input' ],
				'classifai-settings',
				'watson-features',
				[
					'label_for'    => $feature,
					'option_index' => 'features',
					'input_type'   => 'checkbox',
				]
			);
			// Threshold
			add_settings_field(
				"{$feature}-threshold",
				sprintf( esc_html__( '%s Threshold (%%):', 'classifai' ), esc_html( $title ) ), //@codingStandardsIgnoreLine.
				[ $this, 'render_input' ],
				'classifai-settings',
				'watson-features',
				[
					'label_for'     => "{$feature}_threshold",
					'option_index'  => 'features',
					'input_type'    => 'text',
					'default_value' => 70,
				]
			);
			// Taxonomy
			add_settings_field(
				"{$feature}-taxonomy",
				sprintf( esc_html__( '%s Taxonomy:', 'classifai' ), esc_html( $title ) ), //@codingStandardsIgnoreLine.
				[ $this, 'render_select' ],
				'classifai-settings',
				'watson-features',
				[
					'label_for'    => "{$feature}_taxonomy",
					'option_index' => 'features',
					'feature'      => $feature,
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
			<h2><?php esc_html_e( 'Classifai Settings', 'classifai' ); ?></h2>

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
		?>
		<input
			type="<?php echo esc_attr( $type ); ?>"
			id="<?php echo esc_attr( $args['label_for'] ); ?>"
			name="classifai_settings[<?php echo esc_attr( $args['option_index'] ); ?>][<?php echo esc_attr( $args['label_for'] ); ?>]"
			<?php
			switch ( $type ) {
				case 'text':
				case 'number':
					echo 'value="' . esc_attr( $value ) . '"';
					break;
				case 'checkbox':
					echo 'value="1"';
					checked( '1', $value );
					break;
			}
			?>
		/>
		<?php
	}

	/**
	 * @param array $args The settings for the select input instance.
	 */
	public function render_select( $args ) {
		$taxonomies = $this->get_supported_taxonomies();
		$features   = $this->get_settings( 'features' );
		?>
		<select id="<?php echo esc_attr( "{$args['feature']}_taxonomy" ); ?>" name="classifai_settings[features][<?php echo esc_attr( "{$args['feature']}_taxonomy" ); ?>]">
			<option><?php esc_html_e( 'Please choose', 'classifai' ); ?></option>
			<?php foreach ( $taxonomies as $name => $singular_name ) : ?>
				<option value="<?php echo esc_attr( $name ); ?>" <?php selected( $features[ "{$args['feature']}_taxonomy" ], esc_attr( $name ) ); ?> ><?php echo esc_html( $singular_name ); ?></option>
			<?php endforeach; ?>
		</select>
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
		) {
			return true;
		}

		$request           = new \Classifai\Watson\APIRequest();
		$request->username = $settings['credentials']['watson_username'];
		$request->password = $settings['credentials']['watson_password'];
		$url               = 'https://gateway.watsonplatform.net/natural-language-understanding/api/v1/analyze?version=2017-02-27';
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

		// If the API authentication fails, return whatever is already saved.
		if ( $this->authentication_check_failed( $settings ) ) {
			add_settings_error(
				'credentials',
				'classifai-auth',
				esc_html__( 'Authentication Failed. Please check credentials.', 'classifai' ),
				'error'
			);
			return $new_settings;
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

		foreach ( $this->features as $feature ) {

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
