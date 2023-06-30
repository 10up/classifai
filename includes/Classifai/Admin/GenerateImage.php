<?php

namespace Classifai\Admin;

use Classifai\Providers\OpenAI\DallE;

class GenerateImage {

	/**
	 * Inintialize the class and register the actions needed.
	 */
	public function init() {
		add_action( 'admin_menu', [ $this, 'register_generate_media_page' ], 0 );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_scripts' ], 5, 1 );
	}

	/**
	 * Enqueue the admin scripts.
	 *
	 * @param string $hook_suffix The current admin page.
	 */
	public function enqueue_admin_scripts( $hook_suffix = '' ) {
		if ( 'media_page_classifai_generate_image' !== $hook_suffix ) {
			return;
		}

		wp_enqueue_media();
	}

	/**
	 * Registers Media > Generate Image submenu
	 */
	public function register_generate_media_page() {
		add_submenu_page(
			'upload.php',
			esc_attr__( 'Generate Image', 'classifai' ),
			esc_attr__( 'Generate Image', 'classifai' ),
			apply_filters( 'classifai_generate_image_menu_capability', 'upload_files' ),
			'classifai_generate_image',
			[ $this, 'render_generate_media_page' ]
		);
	}

	/**
	 * Renders the Generate Image admin page.
	 */
	public function render_generate_media_page() {
		?> 
			<script>
				( function( $ ) {
					$( document ).ready( function () {
						if ( wp.media ) {
							const frame = wp.media({
								title: 'Generate Image',
								multiple: false,
							});

							frame.on('open', function() {
								const uploadImageTab = frame.$el.find('.media-menu-item#menu-item-upload');
								const browseImageTab = frame.$el.find('.media-menu-item#menu-item-browse');
								const generateImageTab = frame.$el.find('.media-menu-item#menu-item-generate');
								
								// Remove unwanted items
								if ( uploadImageTab.length ) {
									uploadImageTab.hide();
								}

								if ( browseImageTab.length ) {
									browseImageTab.hide();
								}

								// Open Generate Image Tab
								if ( generateImageTab.length ) {
									generateImageTab.trigger( 'click' );
								}
							});

							frame.open();

						}
					} );
				} )( jQuery );
			</script>
		<?php
	}
}
