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
		add_settings_section( 'credentials', esc_html__( 'IBM Watson API Credentials', 'klasifai' ), '', 'klasifai-settings' );
		add_settings_field(
			'username',
			esc_html__( 'Username', 'klasifai' ),
			[ $this, 'text_input' ],
			'klasifai-settings',
			'credentials',
			[
				'label_for'    => 'watson_username',
				'option_index' => 'credentials',
			]
		);
		add_settings_field(
			'password',
			esc_html__( 'Password', 'klasifai' ),
			[ $this, 'text_input' ],
			'klasifai-settings',
			'credentials',
			[
				'label_for'    => 'watson_password',
				'option_index' => 'credentials',
			]
		);




		add_settings_section( 'post-types', 'Post Types to classify', '', 'klasifai-settings' );
		add_settings_section( 'watson-features', 'IBM Watson Features to enable', '', 'klasifai-settings' );
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
	public function text_input( $args ) {
		$setting_index = $this->get_settings( $args['option_index'] );
		$value         = ( isset( $setting_index[ $args['label_for'] ] ) ) ? $setting_index[ $args['label_for'] ] : '';
		?>
		<input type="text" id="<?php echo esc_attr( $args['label_for'] ); ?>" name="klasifai_settings[<?php echo esc_attr( $args['option_index'] ); ?>][<?php echo esc_attr( $args['label_for'] ); ?>]" value="<?php echo esc_attr( $value ); ?>" />
		<?php
	}


	/**
	 * Sanitization for the options being saved.
	 * @param array $settings Array of settings about to be saved.
	 *
	 * @return array
	 */
	function sanitize_settings( $settings ) {
		$new_settings = $this->get_settings();

		if ( isset( $settings['credentials']['watson_username'] ) ) {
			$new_settings['credentials']['watson_username'] = sanitize_text_field( $settings['credentials']['watson_username'] );
		}

		if ( isset( $settings['credentials']['watson_password'] ) ) {
			$new_settings['credentials']['watson_password'] = sanitize_text_field( $settings['credentials']['watson_password'] );
		}

		return $new_settings;
	}

}
