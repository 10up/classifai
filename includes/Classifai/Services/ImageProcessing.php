<?php
/**
 * Service definition for Image Processing
 */

namespace Classifai\Services;

use Classifai\Features\DescriptiveTextGenerator;
use Classifai\Features\ImageTagsGenerator;
use Classifai\Features\ImageTextExtraction;
use Classifai\Features\ImageCropping;
use Classifai\Taxonomy\ImageTagTaxonomy;

use function Classifai\get_asset_info;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ImageProcessing extends Service {

	/**
	 * Action Scheduler hook shared by the async image processing jobs.
	 *
	 * @var string
	 */
	const ASYNC_JOB_HOOK = 'classifai_schedule_image_process_job';

	/**
	 * Attachment meta key holding async processing status, keyed by Feature ID.
	 *
	 * @var string
	 */
	const STATUS_META = '_classifai_image_process_status';

	/**
	 * Map of Feature ID => run type for the async-capable image features.
	 *
	 * @var array
	 */
	const ASYNC_FEATURES = array(
		DescriptiveTextGenerator::ID => 'descriptive_text',
		ImageTagsGenerator::ID       => 'tags',
		ImageTextExtraction::ID      => 'ocr',
		ImageCropping::ID            => 'crop',
	);

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

		add_filter( 'attachment_fields_to_edit', array( $this, 'custom_fields_edit' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_media_scripts' ) );
		add_action( 'wp_ajax_classifai_get_image_process_status', array( $this, 'get_image_process_status_ajax' ) );
	}

	/**
	 * AJAX callback returning the async processing status for an attachment.
	 *
	 * Powers the media-modal polling that updates fields without a page reload.
	 * Returns, per run type, the current status plus the canonical saved values
	 * so the UI can populate fields when a job completes.
	 */
	public function get_image_process_status_ajax() {
		if ( ! wp_doing_ajax() ) {
			return;
		}

		if ( ! check_ajax_referer( 'classifai', 'nonce', false ) ) {
			wp_send_json_error( new \WP_Error( 'classifai_nonce_error', __( 'Nonce could not be verified.', 'classifai' ) ) );
		}

		$attachment_id = (int) filter_input( INPUT_POST, 'attachment_id', FILTER_SANITIZE_NUMBER_INT );

		if ( empty( $attachment_id ) || 'attachment' !== get_post_type( $attachment_id ) ) {
			wp_send_json_error( new \WP_Error( 'invalid_post', __( 'Invalid attachment ID.', 'classifai' ) ) );
		}

		if ( ! current_user_can( 'edit_post', $attachment_id ) ) {
			wp_send_json_error( new \WP_Error( 'unauthorized_access', __( 'Unauthorized access.', 'classifai' ) ) );
		}

		$statuses = get_post_meta( $attachment_id, self::STATUS_META, true );
		$statuses = is_array( $statuses ) ? $statuses : array();
		$response = array();

		foreach ( self::ASYNC_FEATURES as $feature_id => $type ) {
			$entry = $statuses[ $feature_id ] ?? array();
			$data  = array(
				'status' => $entry['status'] ?? 'done',
			);

			if ( ! empty( $entry['message'] ) ) {
				$data['message'] = $entry['message'];
			}

			// Surface the current saved values so the UI can update on completion.
			switch ( $type ) {
				case 'descriptive_text':
					$data['fields'] = array(
						'alt'         => get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ),
						'caption'     => get_the_excerpt( $attachment_id ),
						'description' => get_the_content( null, false, $attachment_id ),
					);
					break;

				case 'ocr':
					$data['description'] = get_the_content( null, false, $attachment_id );
					break;
			}

			$response[ $type ] = $data;
		}

		wp_send_json_success( $response );
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
		 * @param array $providers Array of available providers for the service.
		 *
		 * @return array The filtered available providers.
		 */
		return apply_filters(
			'classifai_image_processing_service_providers',
			array(
				'Classifai\Providers\Azure\ComputerVision',
				'Classifai\Providers\OpenAI\ChatGPT',
				'Classifai\Providers\OpenAI\Images',
				'Classifai\Providers\GoogleAI\Images',
				'Classifai\Providers\XAI\Grok',
				'Classifai\Providers\Localhost\OllamaMultimodal',
				'Classifai\Providers\Localhost\StableDiffusion',
				'Classifai\Providers\TogetherAI\Images',
			)
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
					'ajaxUrl'              => admin_url( 'admin-ajax.php' ),
					'nonce'                => wp_create_nonce( 'classifai' ),
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
