<?php
/**
 * Service definition for Image Processing
 */

namespace Classifai\Services;

use Classifai\Providers\Azure\ComputerVision;
use Classifai\Taxonomy\ImageTagTaxonomy;
use function Classifai\get_asset_info;
use function Classifai\find_provider_class;

class ImageProcessing extends Service {

	/**
	 * ImageProcessing constructor.
	 */
	public function __construct() {
		parent::__construct(
			__( 'Image Processing', 'classifai' ),
			'image_processing',
			[
				'Classifai\Providers\Azure\ComputerVision',
			]
		);
	}

	/**
	 * Register the Image Tags taxonomy along with
	 */
	public function init() {
		parent::init();

		$this->register_image_tags_taxonomy();

		add_filter( 'attachment_fields_to_edit', [ $this, 'custom_fields_edit' ] );
		add_action( 'rest_api_init', [ $this, 'register_endpoints' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_media_scripts' ] );
	}

	/**
	 * Get service providers for Language Processing.
	 *
	 * @return array
	 */
	public static function get_service_providers(): array {
		/**
		 * Filter the service providers for Image Processing service.
		 *
		 * @since 3.0.0
		 * @hook classifai_language_processing_service_providers
		 *
		 * @param {array} $providers Array of available providers for the service.
		 *
		 * @return {array} The filtered available providers.
		 */
		return apply_filters(
			'classifai_language_processing_service_providers',
			[
				'Classifai\Providers\Azure\ComputerVision',
			]
		);
	}

	/**
	 * Enqueue the script for the media modal.
	 *
	 * @since 2.4.0 Use get_asset_info to get the asset version and dependencies.
	 */
	public function enqueue_media_scripts() {
		wp_enqueue_script(
			'classifai-media-script',
			CLASSIFAI_PLUGIN_URL . 'dist/media.js',
			array_merge( get_asset_info( 'media', 'dependencies' ), array( 'jquery', 'media-editor', 'lodash' ) ),
			get_asset_info( 'media', 'version' ),
			true
		);

		$provider = find_provider_class( $this->provider_classes ?? [], ComputerVision::ID );
		if ( ! is_wp_error( $provider ) ) {
			wp_add_inline_script(
				'classifai-media-script',
				'const classifaiMediaVars = ' . wp_json_encode(
					array(
						'enabledAltTextFields' => $provider->get_alt_text_settings() ? $provider->get_alt_text_settings() : array(),
					)
				),
				'before'
			);
		}
	}

	/**
	 * Create endpoints for services
	 */
	public function register_endpoints() {}

	/**
	 * Register a common image tag taxonomy
	 */
	protected function register_image_tags_taxonomy() {
		$tax = new ImageTagTaxonomy();
		$tax->register();
		register_taxonomy_for_object_type( 'classifai-image-tags', 'attachment' );
	}

	/**
	 * Removes the UI on attachment modals for all taxonomies introduced by this plugin.
	 *
	 * @param array $form_fields The forms fields being rendered on the modal.
	 * @return array
	 */
	public function custom_fields_edit( array $form_fields ): array {
		unset( $form_fields['classifai-image-tags'] );
		unset( $form_fields['watson-category'] );
		unset( $form_fields['watson-keyword'] );
		unset( $form_fields['watson-concept'] );
		unset( $form_fields['watson-entity'] );
		return $form_fields;
	}
}
