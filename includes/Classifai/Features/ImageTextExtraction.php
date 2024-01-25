<?php

namespace Classifai\Features;

use Classifai\Providers\Azure\ComputerVision;
use Classifai\Services\ImageProcessing;
use WP_REST_Server;
use WP_REST_Request;
use WP_Error;
use DOMDocument;

use function Classifai\get_asset_info;
use function Classifai\computer_vision_max_filesize;
use function Classifai\get_largest_acceptable_image_url;
use function Classifai\get_modified_image_source_url;
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
	const ID = 'feature_image_to_text_generation';

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
					'id'    => [
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
						'description'       => esc_html__( 'Image ID to read text from.', 'classifai' ),
					],
					'route' => [ 'ocr' ],
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

		if ( strpos( $route, '/classifai/v1/alt-tags' ) === 0 ) {
			return rest_ensure_response(
				$this->run(
					$request->get_param( 'id' ),
					'excerpt',
					[
						'content' => $request->get_param( 'content' ),
						'title'   => $request->get_param( 'title' ),
					]
				)
			);
		}

		return parent::rest_endpoint_callback( $request );
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
			'editor-ocr',
			CLASSIFAI_PLUGIN_URL . 'dist/editor-ocr.js',
			array_merge( get_asset_info( 'editor-ocr', 'dependencies' ), array( 'lodash' ) ),
			get_asset_info( 'editor-ocr', 'version' ),
			true
		);
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
		$metadata = wp_get_attachment_metadata( $attachment_id );

		// Allow rescanning images that are not stored in local storage.
		$image_url = get_modified_image_source_url( $attachment_id );

		if ( empty( $image_url ) || ! filter_var( $image_url, FILTER_VALIDATE_URL ) ) {
			$image_url = get_largest_acceptable_image_url(
				get_attached_file( $attachment_id ),
				wp_get_attachment_url( $attachment_id ),
				$metadata['sizes'] ?? [],
				computer_vision_max_filesize()
			);
		}

		if ( clean_input( 'rescan-ocr' ) ) {
			$this->run( $attachment_id, 'ocr', $metadata );
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
		return esc_html__( 'OCR detects text in images (e.g., handwritten notes) and saves that as post content.', 'classifai' );
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
	 * Runs the feature.
	 *
	 * @param mixed ...$args Arguments required by the feature depending on the provider selected.
	 * @return mixed
	 */
	public function run( ...$args ) {
		$settings          = $this->get_settings();
		$provider_id       = $settings['provider'] ?? ComputerVision::ID;
		$provider_instance = $this->get_feature_provider_instance( $provider_id );
		$result            = '';

		if ( ComputerVision::ID === $provider_instance::ID ) {
			/** @var ComputerVision $provider_instance */
			$result = call_user_func_array(
				[ $provider_instance, 'ocr_processing' ],
				[ ...$args ]
			);
		}

		return apply_filters(
			'classifai_' . static::ID . '_run',
			$result,
			$provider_instance,
			$args,
			$this
		);
	}
}
