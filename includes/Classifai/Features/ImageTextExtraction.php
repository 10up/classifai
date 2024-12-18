<?php

namespace Classifai\Features;

use Classifai\Providers\Azure\ComputerVision;
use Classifai\Services\ImageProcessing;
use WP_REST_Server;
use WP_REST_Request;
use WP_Error;
use DOMDocument;

use function Classifai\get_asset_info;
use function Classifai\clean_input;

/**
 * Class ImageTextExtraction
 */
class ImageTextExtraction extends Feature {
	/**
	 * ID of the current feature.
	 *
	 * @var string
	 */
	const ID = 'feature_image_to_text_generator';

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->label = __( 'Image Text Extraction', 'classifai' );

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
		add_action( 'rest_api_init', [ $this, 'add_ocr_data_to_api_response' ] );
		add_action( 'enqueue_block_editor_assets', [ $this, 'enqueue_editor_assets' ] );
		add_action( 'add_meta_boxes_attachment', [ $this, 'setup_attachment_meta_box' ] );
		add_action( 'edit_attachment', [ $this, 'maybe_rescan_image' ] );

		add_filter( 'the_content', [ $this, 'add_ocr_aria_describedby' ] );
		add_filter( 'attachment_fields_to_edit', [ $this, 'add_rescan_button_to_media_modal' ], 10, 2 );
		add_filter( 'wp_generate_attachment_metadata', [ $this, 'generate_ocr_text' ], 9, 2 );
	}

	/**
	 * Register any needed endpoints.
	 */
	public function register_endpoints() {
		register_rest_route(
			'classifai/v1',
			'ocr/(?P<id>\d+)',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'rest_endpoint_callback' ],
				'args'                => [
					'id' => [
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
						'description'       => esc_html__( 'Image ID to read text from.', 'classifai' ),
					],
				],
				'permission_callback' => [ $this, 'image_text_extractor_permissions_check' ],
			]
		);
	}

	/**
	 * Check if a given request has access to generate OCR.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return bool|WP_Error
	 */
	public function image_text_extractor_permissions_check( WP_REST_Request $request ) {
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
			return new WP_Error( 'not_enabled', esc_html__( 'Scan image for text is disabled. Please check your settings.', 'classifai' ) );
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

		if ( strpos( $route, '/classifai/v1/ocr' ) === 0 ) {
			$result = $this->run( $request->get_param( 'id' ), 'ocr' );

			if ( $result && ! is_wp_error( $result ) ) {
				$this->save( $result, $request->get_param( 'id' ) );
			}

			return rest_ensure_response( $result );
		}

		return parent::rest_endpoint_callback( $request );
	}

	/**
	 * Generate the tags for the image being uploaded.
	 *
	 * @param array $metadata      The metadata for the image.
	 * @param int   $attachment_id Post ID for the attachment.
	 * @return array
	 */
	public function generate_ocr_text( array $metadata, int $attachment_id ): array {
		if ( ! $this->is_feature_enabled() ) {
			return $metadata;
		}

		$result = $this->run( $attachment_id, 'ocr' );

		if ( $result && ! is_wp_error( $result ) ) {
			$this->save( $result, $attachment_id );
		}

		return $metadata;
	}

	/**
	 * Save the OCR text.
	 *
	 * @param string $result The result to save.
	 * @param int    $attachment_id The attachment ID.
	 */
	public function save( string $result, int $attachment_id ) {
		$content = get_the_content( null, false, $attachment_id );

		$post_args = [
			'ID'           => $attachment_id,
			'post_content' => sanitize_text_field( $result ),
		];

		/**
		 * Filter the post arguments before saving the text to post_content.
		 *
		 * This enables text to be stored in a different post or post meta field,
		 * or do other post data setting based on scan results.
		 *
		 * @since 1.6.0
		 * @hook classifai_ocr_text_post_args
		 *
		 * @param {string} $post_args     Array of post data for the attachment post update. Defaults to `ID` and `post_content`.
		 * @param {int}    $attachment_id ID of the attachment post.
		 * @param {object} $result        The full scan results from the API.
		 *
		 * @return {string} The filtered text data.
		 */
		$post_args = apply_filters( 'classifai_ocr_text_post_args', $post_args, $attachment_id, $result );

		if ( $content !== $result ) {
			wp_update_post( $post_args );
		}
	}

	/**
	 * Include classifai_computer_vision_ocr in API response.
	 */
	public function add_ocr_data_to_api_response() {
		register_rest_field(
			'attachment',
			'classifai_has_ocr',
			[
				'get_callback' => function ( $params ) {
					return ! empty( get_post_meta( $params['id'], 'classifai_computer_vision_ocr', true ) );
				},
				'schema'       => [
					'type'    => 'boolean',
					'context' => [ 'view' ],
				],
			]
		);
	}

	/**
	 * Enqueue the editor scripts.
	 */
	public function enqueue_editor_assets() {
		wp_enqueue_script(
			'classifai-plugin-editor-ocr-js',
			CLASSIFAI_PLUGIN_URL . 'dist/classifai-plugin-editor-ocr.js',
			array_merge( get_asset_info( 'classifai-plugin-editor-ocr', 'dependencies' ), array( 'lodash' ) ),
			get_asset_info( 'classifai-plugin-editor-ocr', 'version' ),
			true
		);
	}

	/**
	 * Adds a meta box for rescanning options if the settings are configured.
	 *
	 * @param \WP_Post $post The post object.
	 */
	public function setup_attachment_meta_box( \WP_Post $post ) {
		global $wp_meta_boxes;

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
	 *
	 * @param \WP_Post $post The post object.
	 */
	public function attachment_data_meta_box_content( \WP_Post $post ) {
		$ocr = get_post_meta( $post->ID, 'classifai_computer_vision_ocr', true ) ? __( 'Rescan for text', 'classifai' ) : __( 'Scan image for text', 'classifai' );
		?>

		<?php if ( $this->is_feature_enabled() ) : ?>
			<div class="misc-pub-section">
				<label for="rescan-ocr">
					<input type="checkbox" value="yes" id="rescan-ocr" name="rescan-ocr"/>
					<?php echo esc_html( $ocr ); ?>
				</label>
			</div>
			<?php
		endif;
	}

	/**
	 * Determine if we need to rescan the image.
	 *
	 * @param int $attachment_id Attachment ID.
	 */
	public function maybe_rescan_image( int $attachment_id ) {
		if ( clean_input( 'rescan-ocr' ) ) {
			$result = $this->run( $attachment_id, 'ocr' );

			if ( $result && ! is_wp_error( $result ) ) {
				// Ensure we don't re-run this when the attachment is updated.
				remove_action( 'edit_attachment', [ $this, 'maybe_rescan_image' ] );
				$this->save( $result, $attachment_id );
			}
		}
	}

	/**
	 * Filter the post content to inject aria-describedby attribute.
	 *
	 * @param string $content Post content.
	 * @return string
	 */
	public function add_ocr_aria_describedby( string $content ): string {
		$modified = false;

		if ( ! is_singular() || empty( $content ) ) {
			return $content;
		}

		$dom = new DOMDocument();

		// Suppress warnings generated by loadHTML.
		$errors = libxml_use_internal_errors( true );
		$dom->loadHTML(
			sprintf(
				'<!DOCTYPE html><html><head><meta charset="%s"></head><body>%s</body></html>',
				esc_attr( get_bloginfo( 'charset' ) ),
				$content
			)
		);
		libxml_use_internal_errors( $errors );

		foreach ( $dom->getElementsByTagName( 'img' ) as $image ) {
			foreach ( $image->attributes as $attribute ) {
				if ( 'aria-describedby' === $attribute->name ) {
					break;
				}

				if ( 'class' !== $attribute->name ) {
					continue;
				}

				$image_id            = preg_match( '~wp-image-\K\d+~', $image->getAttribute( 'class' ), $out ) ? $out[0] : 0;
				$ocr_scanned_text_id = "classifai-ocr-$image_id";
				$ocr_scanned_text    = $dom->getElementById( $ocr_scanned_text_id );

				if ( ! empty( $ocr_scanned_text ) ) {
					$image->setAttribute( 'aria-describedby', $ocr_scanned_text_id );
					$modified = true;
				}
			}
		}

		if ( $modified ) {
			$body = $dom->getElementsByTagName( 'body' )->item( 0 );
			return trim( $dom->saveHTML( $body ) );
		}

		return $content;
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

		$ocr_text = empty( get_post_meta( $post->ID, 'classifai_computer_vision_ocr', true ) ) ? __( 'Scan', 'classifai' ) : __( 'Rescan', 'classifai' );

		$form_fields['rescan_ocr'] = [
			'label'        => __( 'Scan image for text', 'classifai' ),
			'input'        => 'html',
			'show_in_edit' => false,
			'html'         => '<button class="button secondary" id="classifai-rescan-ocr" data-id="' . esc_attr( absint( $post->ID ) ) . '">' . esc_html( $ocr_text ) . '</button><span class="spinner" style="display:none;float:none;"></span><span class="error" style="display:none;color:#bc0b0b;padding:5px;"></span>',
		];

		return $form_fields;
	}

	/**
	 * Get the description for the enable field.
	 *
	 * @return string
	 */
	public function get_enable_description(): string {
		return esc_html__( 'OCR detects text in images (e.g., handwritten notes) and saves that as content with the image.', 'classifai' );
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
	 * Generates feature setting data required for migration from
	 * ClassifAI < 3.0.0 to 3.0.0
	 *
	 * @return array
	 */
	public function migrate_settings() {
		$old_settings = get_option( 'classifai_computer_vision', array() );
		$new_settings = $this->get_default_settings();

		$new_settings['provider'] = 'ms_computer_vision';

		if ( isset( $old_settings['enable_smart_cropping'] ) ) {
			$new_settings['status'] = $old_settings['enable_smart_cropping'];
		}

		if ( isset( $old_settings['url'] ) ) {
			$new_settings['ms_computer_vision']['endpoint_url'] = $old_settings['url'];
		}

		if ( isset( $old_settings['api_key'] ) ) {
			$new_settings['ms_computer_vision']['api_key'] = $old_settings['api_key'];
		}

		if ( isset( $old_settings['authenticated'] ) ) {
			$new_settings['ms_computer_vision']['authenticated'] = $old_settings['authenticated'];
		}

		if ( isset( $old_settings['ocr_roles'] ) ) {
			$new_settings['roles'] = $old_settings['ocr_roles'];
		}

		if ( isset( $old_settings['ocr_users'] ) ) {
			$new_settings['users'] = $old_settings['ocr_users'];
		}

		if ( isset( $old_settings['ocr_user_based_opt_out'] ) ) {
			$new_settings['user_based_opt_out'] = $old_settings['ocr_user_based_opt_out'];
		}

		return $new_settings;
	}
}
