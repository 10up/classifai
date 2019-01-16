<?php
/**
 * Created by PhpStorm.
 * User: ryanwelcher
 * Date: 2019-01-16
 * Time: 11:45
 */

namespace Klasifai\Admin;


class SettingsPage {

	/**
	 * Option that stores the klasifai settings
	 */
	public $option = 'klasifai_settings';

	/**
	 * The admin_support items require this method.
	 * @todo remove this requirement.
	 * @return bool
	 */
	public function can_register() {
		return true;
	}

	/**
	 * Helper to get the settings and allow for settings default values.
	 *
	 * @package string|bool|mixed Optional. Name of the settings option index.
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
			esc_html__( 'Klasifai 2', 'klasifai' ),
			esc_html__( 'Klasifai 2', 'klasifai' ),
			'manage_options',
			'klasifai-settings',
			[ $this, 'render_settings_page' ]
		);
	}


	/**
	 * Set up the fields for each section.
	 */
	public function setup_fields_sections() {
		//Create the Credentials Section
		$this->do_credentials_section();

		// Create the post types section
		$this->do_post_types_section();

		add_settings_section( 'watson-features', 'IBM Watson Features to enable', '', 'klasifai-settings' );
	}

	/**
	 * Helper method to keep the setup_fields_sections method manageable
	 */
	protected function do_credentials_section() {
		add_settings_section( 'credentials', esc_html__( 'IBM Watson API Credentials', 'klasifai' ), '', 'klasifai-settings' );
		add_settings_field(
			'username',
			esc_html__( 'Username', 'klasifai' ),
			[ $this, 'render_input' ],
			'klasifai-settings',
			'credentials',
			[
				'label_for'    => 'watson_username',
				'option_index' => 'credentials',
				'input_type'   => 'text',
			]
		);
		add_settings_field(
			'password',
			esc_html__( 'Password', 'klasifai' ),
			[ $this, 'render_input' ],
			'klasifai-settings',
			'credentials',
			[
				'label_for'    => 'watson_password',
				'option_index' => 'credentials',
				'input_type'   => 'text',
			]
		);
	}

	protected function do_post_types_section() {
		//Add the settings section
		add_settings_section( 'post-types', 'Post Types to classify', '', 'klasifai-settings' );

		$post_types = get_post_types( [ 'public' => true ], 'objects' );
		foreach ( $post_types as $post_type ) {
			add_settings_field(
				$post_type->name,
				$post_type->label,
				[ $this, 'render_input' ],
				'klasifai-settings',
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
	 * Register the settings and sanitzation callback method.
	 */
	public function register_settings() {
		register_setting( 'klasifai_settings', 'klasifai_settings', [ $this, 'sanitize_settings' ] );
	}


	/**
	 * Render the settings page.
	 */
	public function render_settings_page() {

		$settings = $this->get_settings();
		echo '<pre>' . print_r( $settings ,1 ) . '</pre>';
		?>
		<div class="wrap">
			<h2><?php esc_html_e( 'Klasifai Settings', 'klasifai' ); ?></h2>

			<form action="options.php" method="post">

				<?php settings_fields( 'klasifai_settings' ); ?>
				<?php do_settings_sections( 'klasifai-settings' ); ?>

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
		?>
		<input
			type="<?php echo esc_attr( $type ); ?>"
			id="<?php echo esc_attr( $args['label_for'] ); ?>"
			name="klasifai_settings[<?php echo esc_attr( $args['option_index'] ); ?>][<?php echo esc_attr( $args['label_for'] ); ?>]"
			<?php if ( 'text' === $type ) : ?>
				value="<?php echo esc_attr( $value ); ?>"
			<?php elseif ( 'checkbox' === $type ) : ?>
				value="1"
				<?php checked( '1', $value ); ?>
			<?php endif; ?>
		/>
		<?php
	}





	/**
	 * Sanitization for the options being saved.
	 * @param array $settings Array of settings about to be saved.
	 *
	 * @return array The sanitized settings to be saved.
	 */
	function sanitize_settings( $settings ) {
		$new_settings = $this->get_settings();

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

		return $new_settings;
	}

}
