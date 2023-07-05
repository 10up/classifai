<?php
namespace Classifai\Admin;

use Classifai\Providers\OpenAI\DallE;
use function Classifai\get_asset_info;

class GenerateImage {

	/**
	 * @var object $dalle Setup DallE.
	 */
	private $dalle;

	/**
	 * Inintialize the class and register the actions needed.
	 */
	public function init() {
		$this->dalle = new DallE( false );

		add_action( 'admin_menu', [ $this, 'register_generate_media_page' ], 0 );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_scripts' ], 5, 1 );
	}

	/**
	 * Enqueue the admin scripts.
	 *
	 * @param string $hook_suffix The current admin page.
	 */
	public function enqueue_admin_scripts( $hook_suffix = '' ) {
		$hook_suffix;
		if ( 'upload.php' !== $hook_suffix ) {
			return;
		}

		$action = isset( $_GET['action'] ) ? sanitize_key( $_GET['action'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if (
			'classifai-generate-image' === $action
			&& $this->dalle->can_generate_image()
		) {
			wp_enqueue_media();
			wp_enqueue_script(
				'classifai-generate-images-media-upload',
				CLASSIFAI_PLUGIN_URL . 'dist/generate-image-media-upload.js',
				[ 'jquery' ],
				get_asset_info( 'classifai-generate-images-media-upload', 'version' ),
				true
			);

			wp_localize_script(
				'classifai-generate-images-media-upload',
				'classifaiGenerateImages',
				[
					'upload_url' => esc_url( admin_url( 'upload.php' ) ),
				]
			);
		}
	}

	/**
	 * Registers Media > Generate Image submenu
	 */
	public function register_generate_media_page() {
		if ( $this->dalle->can_generate_image() ) {
			add_submenu_page(
				'upload.php',
				esc_attr__( 'Generate Image', 'classifai' ),
				esc_attr__( 'Generate Image', 'classifai' ),
				apply_filters( 'classifai_generate_image_menu_capability', 'upload_files' ),
				esc_url( admin_url( 'upload.php?action=classifai-generate-image' ) ),
				'',
			);
		}
	}
}
