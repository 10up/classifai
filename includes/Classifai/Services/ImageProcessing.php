<?php
/**
 * Service definition for Image Processing
 */

namespace Classifai\Services;

use Classifai\Features\DescriptiveTextGenerator;
use Classifai\Taxonomy\ImageTagTaxonomy;

use function Classifai\get_asset_info;

class ImageProcessing extends Service {

	/**
	 * ImageProcessing constructor.
	 */
	public function __construct() {
		parent::__construct(
			__( 'Image Processing', 'classifai' ),
			'image_processing',
			self::get_service_providers()
		);
	}

	/**
	 * Register the Image Tags taxonomy along with
	 */
	public function init() {
		parent::init();

		$this->register_image_tags_taxonomy();

		add_filter( 'attachment_fields_to_edit', [ $this, 'custom_fields_edit' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_media_scripts' ] );
	}

	/**
	 * Get service providers for Image Processing.
	 *
	 * @return array
	 */
	public static function get_service_providers(): array {
		/**
		 * Filter the service providers for Image Processing service.
		 *
		 * @since 3.0.0
		 * @hook classifai_image_processing_service_providers
		 *
		 * @param {array} $providers Array of available providers for the service.
		 *
		 * @return {array} The filtered available providers.
		 */
		return apply_filters(
			'classifai_image_processing_service_providers',
			[
				'Classifai\Providers\Azure\ComputerVision',
				'Classifai\Providers\OpenAI\ChatGPT',
				'Classifai\Providers\OpenAI\DallE',
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
			'classifai-plugin-media-processing-js',
			CLASSIFAI_PLUGIN_URL . 'dist/classifai-plugin-media-processing.js',
			array_merge( get_asset_info( 'classifai-plugin-media-processing', 'dependencies' ), array( 'jquery', 'media-editor', 'lodash' ) ),
			get_asset_info( 'classifai-plugin-media-processing', 'version' ),
			true
		);

		$feature = new DescriptiveTextGenerator();
		wp_add_inline_script(
			'classifai-plugin-media-processing-js',
			'const classifaiMediaVars = ' . wp_json_encode(
				array(
					'enabledAltTextFields' => $feature->get_alt_text_settings() ? $feature->get_alt_text_settings() : array(),
				)
			),
			'before'
		);
	}

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
