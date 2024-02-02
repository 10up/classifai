<?php

namespace Classifai\Features;

use Classifai\Providers\Azure\ComputerVision;
use Classifai\Services\ImageProcessing;
use WP_REST_Server;
use WP_REST_Request;
use WP_Error;

use function Classifai\clean_input;

/**
 * Class ImageCropping
 */
class ImageCropping extends Feature {
	/**
	 * ID of the current feature.
	 *
	 * @var string
	 */
	const ID = 'feature_image_cropping';

	/**
	 * WP_Filesystem_Base instance.
	 *
	 * @since 1.5.0
	 *
	 * @var WP_Filesystem_Base
	 */
	private $wp_filesystem;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->label = __( 'Image Cropping', 'classifai' );

		// Contains all providers that are registered to the service.
		$this->provider_instances = $this->get_provider_instances( ImageProcessing::get_service_providers() );

		// Contains just the providers this feature supports.
		$this->supported_providers = [
			ComputerVision::ID => __( 'Microsoft Azure AI Vision', 'classifai' ),
		];
	}

	/**
	 * Set up necessary hooks.
	 *
	 * We utilize this so we can register the REST route.
	 */
	public function setup() {
		parent::setup();
		add_action( 'rest_api_init', [ $this, 'register_endpoints' ] );
	}

	/**
	 * Set up necessary hooks.
	 */
	public function feature_setup() {
		add_action( 'add_meta_boxes_attachment', [ $this, 'setup_attachment_meta_box' ] );
		add_action( 'edit_attachment', [ $this, 'maybe_crop_image' ] );

		add_filter( 'attachment_fields_to_edit', [ $this, 'add_rescan_button_to_media_modal' ], 10, 2 );
		add_filter( 'wp_generate_attachment_metadata', [ $this, 'generate_smart_crops' ], 7, 2 );
	}

	/**
	 * Register any needed endpoints.
	 */
	public function register_endpoints() {
		register_rest_route(
			'classifai/v1',
			'smart-crop/(?P<id>\d+)',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'rest_endpoint_callback' ],
				'args'                => [
					'id' => [
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
						'description'       => esc_html__( 'Image ID to generate smart crop.', 'classifai' ),
					],
				],
				'permission_callback' => [ $this, 'smart_crop_permissions_check' ],
			]
		);
	}

	/**
	 * Check if a given request has access to generate smart crops.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return bool|WP_Error
	 */
	public function smart_crop_permissions_check( WP_REST_Request $request ) {
		$attachment_id = $request->get_param( 'id' );
		$post_type     = get_post_type_object( 'attachment' );

		// Ensure attachments are allowed in REST endpoints.
		if ( empty( $post_type ) || empty( $post_type->show_in_rest ) ) {
			return false;
		}

		// Ensure we have a logged in user that can upload and change files.
		if ( empty( $attachment_id ) || ! current_user_can( 'edit_post', $attachment_id ) || ! current_user_can( 'upload_files' ) ) {
			return false;
		}

		if ( ! $this->is_feature_enabled() ) {
			return new WP_Error( 'not_enabled', esc_html__( 'Smart cropping is disabled. Please check your settings.', 'classifai' ) );
		}

		return true;
	}

	/**
	 * Generic request handler for all our custom routes.
	 *
	 * @param WP_REST_Request $request The full request object.
	 * @return \WP_REST_Response
	 */
	public function rest_endpoint_callback( WP_REST_Request $request ) {
		$route = $request->get_route();

		if ( strpos( $route, '/classifai/v1/smart-crop' ) === 0 ) {
			$result = $this->run( $request->get_param( 'id' ), 'crop' );

			if ( ! empty( $result ) && ! is_wp_error( $result ) ) {
				$meta = $this->save( $result, $request->get_param( 'id' ) );
				wp_update_attachment_metadata( $request->get_param( 'id' ), $meta );
			}

			return rest_ensure_response( $result );
		}

		return parent::rest_endpoint_callback( $request );
	}

	/**
	 * Generate smart cropped thumbnails for the image being uploaded.
	 *
	 * @param array $metadata      The metadata for the image.
	 * @param int   $attachment_id Post ID for the attachment.
	 * @return array
	 */
	public function generate_smart_crops( array $metadata, int $attachment_id ): array {
		if ( ! $this->is_feature_enabled() ) {
			return $metadata;
		}

		$result = $this->run( $attachment_id, 'crop', $metadata );

		if ( ! empty( $result ) && ! is_wp_error( $result ) ) {
			$metadata = $this->save( $result, $attachment_id );
		}

		return $metadata;
	}

	/**
	 * Save the cropped images.
	 *
	 * @param array $result The results to save.
	 * @param int   $attachment_id The attachment ID.
	 * @return array
	 */
	public function save( array $result, int $attachment_id ): array {
		$metadata = wp_get_attachment_metadata( $attachment_id );

		foreach ( $result as $size => $image ) {
			if ( is_wp_error( $image['data'] ) || empty( $image['data'] ) ) {
				continue;
			}

			$attached_file       = get_attached_file( $attachment_id );
			$file_path_info      = pathinfo( $attached_file );
			$new_thumb_file_name = str_replace(
				$file_path_info['filename'],
				sprintf(
					'%s-%dx%d',
					$file_path_info['filename'],
					$image['width'],
					$image['height']
				),
				$attached_file
			);

			/**
			 * Filters the file name of the smart-cropped image.
			 *
			 * By default, the filename mirrors what is generated by
			 * core -- e.g., my-thumb-150x150.jpg -- so will override the
			 * core-generated image. Apply this filter to keep the original
			 * file in the file system.
			 *
			 * @since 1.5.0
			 * @hook classifai_smart_cropping_thumb_file_name
			 *
			 * @param {string} Default file name.
			 * @param {int}    The ID of the attachment being processed.
			 * @param {array}  Width and height data for the image.
			 *
			 * @return {string} Filtered file name.
			 */
			$new_thumb_file_name = apply_filters(
				'classifai_smart_cropping_thumb_file_name',
				$new_thumb_file_name,
				$attachment_id,
				[
					'width'  => $image['width'],
					'height' => $image['height'],
				]
			);

			$filesystem = $this->get_wp_filesystem();
			if ( $filesystem && $filesystem->put_contents( $new_thumb_file_name, $image['data'] ) ) {
				$metadata['sizes'][ $size ]['file'] = basename( $new_thumb_file_name );
			}
		}

		return $metadata;
	}

	/**
	 * Adds a meta box for rescanning options if the settings are configured.
	 *
	 * @param \WP_Post $post The post object.
	 */
	public function setup_attachment_meta_box( \WP_Post $post ) {
		if ( ! wp_attachment_is_image( $post ) || ! $this->is_feature_enabled() ) {
			return;
		}

		// Add our content to the metabox.
		add_action( 'classifai_render_attachment_metabox', [ $this, 'attachment_data_meta_box_content' ] );

		// If the metabox was already registered, don't add it again.
		if ( isset( $wp_meta_boxes['attachment']['side']['high']['classifai_image_processing'] ) ) {
			return;
		}

		// Register the metabox if needed.
		add_meta_box(
			'classifai_image_processing',
			__( 'ClassifAI Image Processing', 'classifai' ),
			[ $this, 'attachment_data_meta_box' ],
			'attachment',
			'side',
			'high'
		);
	}

	/**
	 * Render the meta box.
	 *
	 * @param \WP_Post $post The post object.
	 */
	public function attachment_data_meta_box( \WP_Post $post ) {
		/**
		 * Allows more fields to be rendered in attachment metabox.
		 *
		 * @since 3.0.0
		 * @hook classifai_render_attachment_metabox
		 *
		 * @param {WP_Post} $post The post object.
		 * @param {object} $this The Provider object.
		 */
		do_action( 'classifai_render_attachment_metabox', $post, $this );
	}

	/**
	 * Display meta data.
	 */
	public function attachment_data_meta_box_content() {
		$smart_crop = get_transient( 'classifai_azure_computer_vision_image_cropping_latest_response' ) ? __( 'Regenerate smart thumbnail', 'classifai' ) : __( 'Create smart thumbnail', 'classifai' );
		?>

		<?php if ( $this->is_feature_enabled() ) : ?>
			<div class="misc-pub-section">
				<label for="rescan-smart-crop">
					<input type="checkbox" value="yes" id="rescan-smart-crop" name="rescan-smart-crop"/>
					<?php echo esc_html( $smart_crop ); ?>
				</label>
			</div>
			<?php
		endif;
	}

	/**
	 * Determine if we need to crop the image.
	 *
	 * @param int $attachment_id Attachment ID.
	 */
	public function maybe_crop_image( int $attachment_id ) {
		if ( clean_input( 'rescan-smart-crop' ) ) {
			$result = $this->run( $attachment_id, 'crop' );

			if ( ! empty( $result ) && ! is_wp_error( $result ) ) {
				$meta = $this->save( $result, $attachment_id );
				wp_update_attachment_metadata( $attachment_id, $meta );
			}
		}
	}

	/**
	 * Adds the rescan buttons to the media modal.
	 *
	 * @param array    $form_fields Array of fields
	 * @param \WP_Post $post        Post object for the attachment being viewed.
	 * @return array
	 */
	public function add_rescan_button_to_media_modal( array $form_fields, \WP_Post $post ): array {
		if ( ! $this->is_feature_enabled() || ! wp_attachment_is_image( $post ) ) {
			return $form_fields;
		}

		$smart_crop_text = empty( get_transient( 'classifai_azure_computer_vision_image_cropping_latest_response' ) ) ? __( 'Generate', 'classifai' ) : __( 'Regenerate', 'classifai' );

		$form_fields['rescan_smart_crop'] = [
			'label'        => __( 'Smart thumbnail', 'classifai' ),
			'input'        => 'html',
			'show_in_edit' => false,
			'html'         => '<button class="button secondary" id="classifai-rescan-smart-crop" data-id="' . esc_attr( absint( $post->ID ) ) . '">' . esc_html( $smart_crop_text ) . '</button><span class="spinner" style="display:none;float:none;"></span><span class="error" style="display:none;color:#bc0b0b;padding:5px;"></span>',
		];

		return $form_fields;
	}

	/**
	 * Get the description for the enable field.
	 *
	 * @return string
	 */
	public function get_enable_description(): string {
		return esc_html__( 'AI Vision detects and saves the most visually interesting part of your image (i.e., faces, animals, notable text).', 'classifai' );
	}

	/**
	 * Returns the default settings for the feature.
	 *
	 * @return array
	 */
	public function get_feature_default_settings(): array {
		return [
			'provider' => ComputerVision::ID,
		];
	}

	/**
	 * Provides the global WP_Filesystem_Base class instance.
	 *
	 * @return WP_Filesystem_Base
	 */
	public function get_wp_filesystem() {
		global $wp_filesystem;

		if ( is_null( $this->wp_filesystem ) ) {
			if ( ! $wp_filesystem ) {
				WP_Filesystem(); // Initiates the global.
			}

			$this->wp_filesystem = $wp_filesystem;
		}

		/**
		 * Filters the filesystem class instance used to save image files.
		 *
		 * @since 1.5.0
		 * @hook classifai_smart_crop_wp_filesystem
		 *
		 * @param {WP_Filesystem_Base} $this->wp_filesystem Filesystem class for saving images.
		 *
		 * @return {WP_Filesystem_Base} Filtered Filesystem class.
		 */
		return apply_filters( 'classifai_smart_crop_wp_filesystem', $this->wp_filesystem );
	}
}
