<?php
/**
 * Azure settings
 *
 * @package Classifai\Azure;
 */

namespace Classifai\Azure;

use Classifai\Admin\ProviderSettings;

class AzureSettings extends ProviderSettings {

	/**
	 * AzureSettings constructor.
	 */
	public function __construct() {
		parent::__construct( 'azure', 'azure_settings' );
	}

	/**
	 * Render the settings page.
	 */
	public function render_settings_page() {
		?>
		<div class="wrap">
			<h2><?php esc_html_e( 'Azure Settings', 'classifai' ); ?></h2>
			<p>Settings for the Service</p>
			<form action="options.php" method="post">
				<?php settings_fields( $this->option_group ); ?>
				<?php do_settings_sections( $this->settings_section ); ?>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Register the actions required for the settings page.
	 */
	public function register() {
		parent::register();
		add_action( 'admin_menu', [ $this, 'register_admin_menu_item' ], 11 );
		add_action( 'admin_init', [ $this, 'setup_fields_sections' ] );
	}

	/**
	 * Registers the admin item.
	 */
	public function register_admin_menu_item() {
		$is_setup = get_option( 'classifai_configured' );
		$title    = esc_html__( 'Azure', 'classifai' );
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
	 * setup the settings sections.
	 */
	public function setup_fields_sections() {
		add_settings_section( 'computer-vision', esc_html__( 'Computer Vision', 'classifai' ), '', $this->settings_section );
		add_settings_field(
			'url',
			esc_html__( 'Endpoint URL', 'classifai' ),
			[ $this, 'render_input' ],
			$this->settings_section,
			'computer-vision',
			[
				'label_for'    => 'url',
				'option_index' => 'computer-vision',
				'input_type'   => 'text',
			]
		);
		add_settings_field(
			'api-key',
			esc_html__( 'API Key', 'classifai' ),
			[ $this, 'render_input' ],
			$this->settings_section,
			'computer-vision',
			[
				'label_for'    => 'key',
				'option_index' => 'computer-vision',
				'input_type'   => 'text',
			]
		);

		add_settings_section( 'computer-vision', esc_html__( 'Computer Vision', 'classifai' ), '', $this->settings_section );
		add_settings_field(
			'url',
			esc_html__( 'Endpoint URL', 'classifai' ),
			[ $this, 'render_input' ],
			$this->settings_section,
			'computer-vision',
			[
				'label_for'    => 'url',
				'option_index' => 'computer-vision',
				'input_type'   => 'text',
			]
		);
		add_settings_field(
			'api-key',
			esc_html__( 'API Key', 'classifai' ),
			[ $this, 'render_input' ],
			$this->settings_section,
			'computer-vision',
			[
				'label_for'    => 'key',
				'option_index' => 'computer-vision',
				'input_type'   => 'text',
			]
		);
	}

	/**
	 * Generic text input field callback
	 *
	 * @param array $args The args passed to add_settings_field.
	 */
	public function render_input( $args ) {
		?>
		<input
			type="text"
			id="classifai-settings-<?php echo esc_attr( $args['label_for'] ); ?>"
			class="large-text"
			name="<?php echo esc_attr( $this->option_group ); ?>[<?php echo esc_attr( $args['option_index'] ); ?>][<?php echo esc_attr( $args['label_for'] ); ?>]"
		<?php
	}
}
