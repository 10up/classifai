<?php
/**
 * Azure Computer vision
 */

namespace Classifai\Providers\Azure;

use Classifai\Providers\Provider;
use DOMDocument;
use WP_Error;

use function Classifai\computer_vision_max_filesize;
use function Classifai\get_largest_acceptable_image_url;
use function Classifai\get_modified_image_source_url;
use function Classifai\attachment_is_pdf;
use function Classifai\get_asset_info;
use function Classifai\clean_input;

class ComputerVision extends Provider {

	/**
	 * @var string URL fragment to the analyze API endpoint
	 */
	protected $analyze_url = 'vision/v3.0/analyze';

	/**
	 * ComputerVision constructor.
	 *
	 * @param string $service The service this class belongs to.
	 */
	public function __construct( $service ) {
		parent::__construct(
			'Microsoft Azure',
			'Computer Vision',
			'computer_vision',
			$service
		);

		// Set the onboarding options.
		$this->onboarding_options = array(
			'title'    => __( 'Microsoft Azure Computer Vision', 'classifai' ),
			'fields'   => array( 'url', 'api-key' ),
			'features' => array(
				'enable_image_captions' => __( 'Automatically add alt-text to images', 'classifai' ),
				'enable_image_tagging'  => __( 'Automatically tag images', 'classifai' ),
				'enable_smart_cropping' => __( 'Smart crop images', 'classifai' ),
				'enable_ocr'            => __( 'Scan images for text', 'classifai' ),
				'enable_read_pdf'       => __( 'Scan PDFs for text', 'classifai' ),
			),
		);
	}

	/**
	 * Resets settings for the ComputerVision provider.
	 */
	public function reset_settings() {
		update_option( $this->get_option_name(), $this->get_default_settings() );
	}

	/**
	 * Default settings for ComputerVision
	 *
	 * @return array
	 */
	private function get_default_settings() {
		return [
			'valid'                 => false,
			'url'                   => '',
			'api_key'               => '',
			'enable_image_captions' => array(
				'alt'         => 0,
				'caption'     => 0,
				'description' => 0,
			),
			'enable_image_tagging'  => true,
			'enable_smart_cropping' => false,
			'enable_ocr'            => false,
			'enable_read_pdf'       => false,
			'caption_threshold'     => 75,
			'tag_threshold'         => 70,
			'image_tag_taxonomy'    => 'classifai-image-tags',
		];
	}

	/**
	 * Returns an array of fields enabled to be set to store image captions.
	 *
	 * @return array
	 */
	public function get_alt_text_settings() {
		$settings       = $this->get_settings();
		$enabled_fields = array();

		if ( ! isset( $settings['enable_image_captions'] ) ) {
			return array();
		}

		if ( ! is_array( $settings['enable_image_captions'] ) ) {
			return array(
				'alt'         => 'no' === $settings['enable_image_captions'] ? 0 : 'alt',
				'caption'     => 0,
				'description' => 0,
			);
		}

		foreach ( $settings['enable_image_captions'] as $key => $value ) {
			if ( 0 !== $value && '0' !== $value ) {
				$enabled_fields[] = $key;
			}
		}

		return $enabled_fields;
	}

	/**
	 * Register the functionality.
	 */
	public function register() {
		add_action( 'add_meta_boxes_attachment', [ $this, 'setup_attachment_meta_box' ] );
		add_filter( 'attachment_fields_to_edit', [ $this, 'add_rescan_button_to_media_modal' ], 10, 2 );
		add_action( 'edit_attachment', [ $this, 'maybe_rescan_image' ] );
		add_filter( 'wp_generate_attachment_metadata', [ $this, 'smart_crop_image' ], 7, 2 );
		add_filter( 'wp_generate_attachment_metadata', [ $this, 'generate_image_alt_tags' ], 8, 2 );
		add_filter( 'posts_clauses', [ $this, 'filter_attachment_query_keywords' ], 10, 1 );

		$settings = $this->get_settings();

		if ( isset( $settings['enable_ocr'] ) && '1' === $settings['enable_ocr'] ) {
			add_action( 'enqueue_block_editor_assets', [ $this, 'enqueue_editor_assets' ] );
			add_filter( 'the_content', [ $this, 'add_ocr_aria_describedby' ] );
			add_filter( 'rest_api_init', [ $this, 'add_ocr_data_to_api_response' ] );
		}

		if ( isset( $settings['enable_read_pdf'] ) && '1' === $settings['enable_read_pdf'] ) {
			add_action( 'add_attachment', [ $this, 'read_pdf' ] );
			add_action( 'classifai_retry_get_read_result', [ $this, 'do_read_cron' ], 10, 2 );
			add_action( 'wp_ajax_classifai_get_read_status', [ $this, 'get_read_status_ajax' ] );
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
				'get_callback' => function( $params ) {
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
			get_asset_info( 'editor-ocr', 'dependencies' ),
			get_asset_info( 'editor-ocr', 'version' ),
			true
		);
	}

	/**
	 * Filter the post content to inject aria-describedby attribute.
	 *
	 * @param string $content Post content.
	 *
	 * @return string
	 */
	public function add_ocr_aria_describedby( $content ) {
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
	 * Adds a meta box for rescanning options if the settings are configured
	 *
	 * @param \WP_Post $post The post object.
	 */
	public function setup_attachment_meta_box( $post ) {
		$settings = $this->get_settings();

		if ( wp_attachment_is_image( $post ) ) {
			add_meta_box(
				'attachment_meta_box',
				__( 'ClassifAI Image Processing', 'classifai' ),
				[ $this, 'attachment_data_meta_box' ],
				'attachment',
				'side',
				'high'
			);
		}

		if ( attachment_is_pdf( $post ) && is_array( $settings ) && isset( $settings['enable_read_pdf'] ) && '1' === $settings['enable_read_pdf'] ) {
			add_meta_box(
				'attachment_meta_box',
				__( 'ClassifAI PDF Processing', 'classifai' ),
				[ $this, 'attachment_pdf_data_meta_box' ],
				'attachment',
				'side',
				'high'
			);
		}
	}

	/**
	 * Display meta data
	 *
	 * @param \WP_Post $post The post object.
	 */
	public function attachment_data_meta_box( $post ) {
		$settings   = $this->get_settings();
		$captions   = get_post_meta( $post->ID, '_wp_attachment_image_alt', true ) ? __( 'No descriptive text? Rescan image', 'classifai' ) : __( 'Generate descriptive text', 'classifai' );
		$tags       = ! empty( wp_get_object_terms( $post->ID, 'classifai-image-tags' ) ) ? __( 'Rescan image for new tags', 'classifai' ) : __( 'Generate image tags', 'classifai' );
		$ocr        = get_post_meta( $post->ID, 'classifai_computer_vision_ocr', true ) ? __( 'Rescan for text', 'classifai' ) : __( 'Scan image for text', 'classifai' );
		$smart_crop = get_transient( 'classifai_azure_computer_vision_smart_cropping_latest_response' ) ? __( 'Regenerate smart thumbnail', 'classifai' ) : __( 'Create smart thumbnail', 'classifai' );
		?>

		<div class="misc-publishing-actions">
			<?php if ( ! empty( $this->get_alt_text_settings() ) ) : ?>
				<div class="misc-pub-section">
					<label for="rescan-captions">
						<input type="checkbox" value="yes" id="rescan-captions" name="rescan-captions"/>
						<?php echo esc_html( $captions ); ?>
					</label>
				</div>
			<?php endif; ?>

			<?php if ( is_array( $settings ) && isset( $settings['enable_image_tagging'] ) && '1' === $settings['enable_image_tagging'] ) : ?>
				<div class="misc-pub-section">
					<label for="rescan-tags">
						<input type="checkbox" value="yes" id="rescan-tags" name="rescan-tags"/>
						<?php echo esc_html( $tags ); ?>
					</label>
				</div>
			<?php endif; ?>

			<?php if ( is_array( $settings ) && isset( $settings['enable_ocr'] ) && '1' === $settings['enable_ocr'] ) : ?>
				<div class="misc-pub-section">
					<label for="rescan-ocr">
						<input type="checkbox" value="yes" id="rescan-ocr" name="rescan-ocr"/>
						<?php echo esc_html( $ocr ); ?>
					</label>
				</div>
			<?php endif; ?>

			<?php if ( is_array( $settings ) && isset( $settings['enable_smart_cropping'] ) && '1' === $settings['enable_smart_cropping'] ) : ?>
				<div class="misc-pub-section">
					<label for="rescan-smart-crop">
						<input type="checkbox" value="yes" id="rescan-smart-crop" name="rescan-smart-crop"/>
						<?php echo esc_html( $smart_crop ); ?>
					</label>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Display PDF scanning actions.
	 *
	 * @param \WP_Post $post The post object.
	 */
	public function attachment_pdf_data_meta_box( $post ) {
		$status  = self::get_read_status( $post->ID );
		$read    = (bool) $status['read'] ? __( 'Rescan PDF for text', 'classifai' ) : __( 'Scan PDF for text', 'classifai' );
		$running = (bool) $status['running'];
		?>
		<div class="misc-publishing-actions">
			<div class="misc-pub-section">
				<label for="rescan-pdf">
					<input type="checkbox" value="yes" id="rescan-pdf" name="rescan-pdf" <?php disabled( $running ); ?>/>
					<?php echo esc_html( $read ); ?>
					<?php if ( $running ) : ?>
						<?php echo ' - ' . esc_html__( 'In progress!', 'classifai' ); ?>
					<?php endif; ?>
				</label>
			</div>
		</div>
		<?php
	}

	/**
	 * Adds the rescan buttons to the media modal.
	 *
	 * @param array    $form_fields Array of fields
	 * @param \WP_post $post        Post object for the attachment being viewed.
	 */
	public function add_rescan_button_to_media_modal( $form_fields, $post ) {
		$settings = $this->get_settings();

		if ( attachment_is_pdf( $post ) && is_array( $settings ) && isset( $settings['enable_read_pdf'] ) && '1' === $settings['enable_read_pdf'] ) {
			$read_text = empty( get_the_content( null, false, $post ) ) ? __( 'Scan', 'classifai' ) : __( 'Rescan', 'classifai' );
			$status    = get_post_meta( $post->ID, '_classifai_azure_read_status', true );
			if ( ! empty( $status['status'] ) && 'running' === $status['status'] ) {
				$html = '<button class="button secondary" disabled>' . esc_html__( 'In progress!', 'classifai' ) . '</button>';
			} else {
				$html = '<button class="button secondary" id="classifai-rescan-pdf" data-id="' . esc_attr( absint( $post->ID ) ) . '">' . esc_html( $read_text ) . '</button>';
			}

			$form_fields['rescan_pdf'] = [
				'label'        => __( 'Scan PDF for text', 'classifai' ),
				'input'        => 'html',
				'html'         => $html,
				'show_in_edit' => false,
			];
		}

		if ( wp_attachment_is_image( $post ) ) {
			$alt_tags_text   = empty( get_post_meta( $post->ID, '_wp_attachment_image_alt', true ) ) ? __( 'Generate', 'classifai' ) : __( 'Rescan', 'classifai' );
			$image_tags_text = empty( wp_get_object_terms( $post->ID, 'classifai-image-tags' ) ) ? __( 'Generate', 'classifai' ) : __( 'Rescan', 'classifai' );
			$ocr_text        = empty( get_post_meta( $post->ID, 'classifai_computer_vision_ocr', true ) ) ? __( 'Scan', 'classifai' ) : __( 'Rescan', 'classifai' );
			$smart_crop_text = empty( get_transient( 'classifai_azure_computer_vision_smart_cropping_latest_response' ) ) ? __( 'Generate', 'classifai' ) : __( 'Regenerate', 'classifai' );

			if ( ! empty( $this->get_alt_text_settings() ) ) {
				$form_fields['rescan_alt_tags'] = [
					'label'        => __( 'Descriptive text', 'classifai' ),
					'input'        => 'html',
					'html'         => '<button class="button secondary" id="classifai-rescan-alt-tags" data-id="' . esc_attr( absint( $post->ID ) ) . '">' . esc_html( $alt_tags_text ) . '</button><span class="spinner" style="display:none;float:none;"></span><span class="error" style="display:none;color:#bc0b0b;padding:5px;"></span>',
					'show_in_edit' => false,
				];
			}

			if ( is_array( $settings ) && isset( $settings['enable_image_tagging'] ) && '1' === $settings['enable_image_tagging'] ) {
				$form_fields['rescan_captions'] = [
					'label'        => __( 'Image tags', 'classifai' ),
					'input'        => 'html',
					'html'         => '<button class="button secondary" id="classifai-rescan-image-tags" data-id="' . esc_attr( absint( $post->ID ) ) . '">' . esc_html( $image_tags_text ) . '</button><span class="spinner" style="display:none;float:none;"></span><span class="error" style="display:none;color:#bc0b0b;padding:5px;"></span>',
					'show_in_edit' => false,
				];
			}

			if ( is_array( $settings ) && isset( $settings['enable_smart_cropping'] ) && '1' === $settings['enable_smart_cropping'] ) {
				$form_fields['rescan_smart_crop'] = [
					'label'        => __( 'Smart thumbnail', 'classifai' ),
					'input'        => 'html',
					'html'         => '<button class="button secondary" id="classifai-rescan-smart-crop" data-id="' . esc_attr( absint( $post->ID ) ) . '">' . esc_html( $smart_crop_text ) . '</button><span class="spinner" style="display:none;float:none;"></span><span class="error" style="display:none;color:#bc0b0b;padding:5px;"></span>',
					'show_in_edit' => false,
				];
			}

			if ( is_array( $settings ) && isset( $settings['enable_ocr'] ) && '1' === $settings['enable_ocr'] ) {
				$form_fields['rescan_ocr'] = [
					'label'        => __( 'Scan image for text', 'classifai' ),
					'input'        => 'html',
					'html'         => '<button class="button secondary" id="classifai-rescan-ocr" data-id="' . esc_attr( absint( $post->ID ) ) . '">' . esc_html( $ocr_text ) . '</button><span class="spinner" style="display:none;float:none;"></span><span class="error" style="display:none;color:#bc0b0b;padding:5px;"></span>',
					'show_in_edit' => false,
				];
			}
		}

		return $form_fields;
	}

	/**
	 * Callback to get the status of the PDF read with AJAX support.
	 */
	public static function get_read_status_ajax() {
		if ( ! wp_doing_ajax() ) {
			return;
		}

		// Nonce check.
		if ( ! check_ajax_referer( 'classifai', 'nonce', false ) ) {
			$error = new \WP_Error( 'classifai_nonce_error', __( 'Nonce could not be verified.', 'classifai' ) );
			wp_send_json_error( $error );
			exit();
		}

		// Attachment ID check.
		$attachment_id = filter_input( INPUT_POST, 'attachment_id', FILTER_SANITIZE_NUMBER_INT );
		if ( empty( $attachment_id ) ) {
			$error = new \WP_Error( 'invalid_post', __( 'Invalid attachment ID.', 'classifai' ) );
			wp_send_json_error( $error );
			exit();
		}

		// User capability check.
		if ( ! current_user_can( 'edit_post', $attachment_id ) ) {
			$error = new \WP_Error( 'unauthorized_access', __( 'Unauthorized access.', 'classifai' ) );
			wp_send_json_error( $error );
			exit();
		}

		wp_send_json_success( self::get_read_status( $attachment_id ) );
	}

	/**
	 * Callback to get the status of the PDF read.
	 *
	 * @param  int $attachment_id The attachment ID.
	 * @return array Read and running status.
	 */
	public static function get_read_status( $attachment_id = null ) {
		if ( empty( $attachment_id ) || ! is_numeric( $attachment_id ) ) {
			return;
		}

		// Cast to an integer
		$attachment_id = (int) $attachment_id;

		$read    = ! empty( get_the_content( null, false, $attachment_id ) );
		$status  = get_post_meta( $attachment_id, '_classifai_azure_read_status', true );
		$running = ( ! empty( $status['status'] ) && 'running' === $status['status'] );

		$resp = [
			'read'    => $read,
			'running' => $running,
		];

		return $resp;
	}

	/**
	 *
	 * @param int $attachment_id Post id for the attachment
	 */
	public function maybe_rescan_image( $attachment_id ) {
		if ( clean_input( 'rescan-pdf' ) ) {
			$this->read_pdf( $attachment_id );
			return; // We can exit early, if this is a call for PDF scanning - everything else relates to images.
		}

		$routes   = [];
		$metadata = wp_get_attachment_metadata( $attachment_id );

		// Allow rescanning image that are not stored in local storage.
		$image_url = get_modified_image_source_url( $attachment_id );

		if ( empty( $image_url ) || ! filter_var( $image_url, FILTER_VALIDATE_URL ) ) {
			$image_url = get_largest_acceptable_image_url(
				get_attached_file( $attachment_id ),
				wp_get_attachment_url( $attachment_id ),
				$metadata['sizes'] ?? [],
				computer_vision_max_filesize()
			);
		}

		if ( clean_input( 'rescan-captions' ) ) {
			$routes[] = 'alt-tags';
		} elseif ( clean_input( 'rescan-tags' ) ) {
			$routes[] = 'image-tags';
		} elseif ( clean_input( 'rescan-smart-crop' ) ) {
			$routes[] = 'smart-crop';
		}

		if ( in_array( 'smart-crop', $routes, true ) ) {
			// Are we smart cropping the image?
			if ( clean_input( 'rescan-smart-crop' ) && ! empty( $metadata ) ) {
				$this->smart_crop_image( $metadata, $attachment_id );
			}
		} else {
			$image_scan = $this->scan_image( $image_url, $routes );

			if ( ! is_wp_error( $image_scan ) ) {
				// Are we updating the captions?
				if ( clean_input( 'rescan-captions' ) && isset( $image_scan->description->captions ) ) {
					$this->generate_alt_tags( $image_scan->description->captions, $attachment_id );
				}
				// Are we updating the tags?
				if ( clean_input( 'rescan-tags' ) && isset( $image_scan->tags ) ) {
					$this->generate_image_tags( $image_scan->tags, $attachment_id );
				}
			}
		}

		// Are we updating the OCR text?
		if ( clean_input( 'rescan-ocr' ) ) {
			$this->ocr_processing( wp_get_attachment_metadata( $attachment_id ), $attachment_id, true );
		}
	}

	/**
	 * Adds smart-cropped image thumbnails to the attachment metadata.
	 *
	 * @since 1.5.0
	 * @filter wp_generate_attachment_metadata
	 *
	 * @param array $metadata Attachment metadata.
	 * @param int   $attachment_id Attachment ID.
	 * @return array Filtered attachment metadata.
	 */
	public function smart_crop_image( $metadata, $attachment_id ) {
		$settings = $this->get_settings();

		if ( ! is_array( $metadata ) || ! is_array( $settings ) ) {
			return $metadata;
		}

		$should_smart_crop = isset( $settings['enable_smart_cropping'] ) && '1' === $settings['enable_smart_cropping'];

		/**
		 * Filters whether to apply smart cropping to the current image.
		 *
		 * @since 1.5.0
		 * @hook classifai_should_smart_crop_image
		 *
		 * @param {bool}  $should_smart_crop Whether to apply smart cropping. The default value is set in ComputerVision settings.
		 * @param {array} $metadata          Image metadata.
		 * @param {int}   $attachment_id     The attachment ID.
		 *
		 * @return {bool} Whether to apply smart cropping.
		 */
		if ( ! apply_filters( 'classifai_should_smart_crop_image', $should_smart_crop, $metadata, $attachment_id ) ) {
			return $metadata;
		}

		// Direct file system access is required for the current implementation of this feature.
		if ( ! function_exists( 'get_filesystem_method' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		$access_type = get_filesystem_method();
		if ( 'direct' !== $access_type || ! WP_Filesystem() ) {
			return $metadata;
		}

		$smart_cropping = new SmartCropping( $settings );

		return $smart_cropping->generate_attachment_metadata( $metadata, intval( $attachment_id ) );
	}

	/**
	 * Generate the alt tags for the image being uploaded.
	 *
	 * @param array $metadata      The metadata for the image.
	 * @param int   $attachment_id Post ID for the attachment.
	 *
	 * @return mixed
	 */
	public function generate_image_alt_tags( $metadata, $attachment_id ) {

		$image_scan = false;
		$settings   = $this->get_settings();
		if (
			'no' !== $settings['enable_image_tagging'] ||
			! empty( $this->get_alt_text_settings() )
		) {

			// Allow scanning image that are not stored in local storage.
			$image_url = get_modified_image_source_url( $attachment_id );

			if ( empty( $image_url ) || ! filter_var( $image_url, FILTER_VALIDATE_URL ) ) {
				if ( isset( $metadata['sizes'] ) && is_array( $metadata['sizes'] ) ) {
					$image_url = get_largest_acceptable_image_url(
						get_attached_file( $attachment_id ),
						wp_get_attachment_url( $attachment_id ),
						$metadata['sizes'],
						computer_vision_max_filesize()
					);
				} else {
					$image_url = wp_get_attachment_url( $attachment_id );
				}
			}

			if ( ! empty( $image_url ) ) {
				$image_scan = $this->scan_image( $image_url );
				set_transient( 'classifai_azure_computer_vision_image_scan_latest_response', $image_scan, DAY_IN_SECONDS * 30 );
				if ( ! is_wp_error( $image_scan ) ) {
					// Check for captions
					if ( isset( $image_scan->description->captions ) ) {
						// Process the captions
						$this->generate_alt_tags( $image_scan->description->captions, $attachment_id );
					}
					// Check for tags
					if ( isset( $image_scan->tags ) ) {
						// Process the tags
						$this->generate_image_tags( $image_scan->tags, $attachment_id );
					}
				}
			}
		}

		// OCR processing
		$this->ocr_processing( $metadata, $attachment_id, false, is_wp_error( $image_scan ) ? false : $image_scan );

		return $metadata;
	}

	/**
	 * Runs text recognition on the attachment.
	 *
	 * @since 1.6.0
	 *
	 * @filter wp_generate_attachment_metadata
	 *
	 * @param array       $metadata Attachment metadata.
	 * @param int         $attachment_id Attachment ID.
	 * @param boolean     $force Whether to force processing or not. Default false.
	 * @param bool|object $scan Previously run image scan. Default false.
	 * @return array Filtered attachment metadata.
	 */
	public function ocr_processing( array $metadata = [], int $attachment_id = 0, bool $force = false, $scan = false ) {
		$settings = $this->get_settings();

		if ( ! is_array( $metadata ) || ! is_array( $settings ) ) {
			return $metadata;
		}

		$should_ocr_scan = isset( $settings['enable_ocr'] ) && '1' === $settings['enable_ocr'];

		/**
		 * Filters whether to run OCR scanning on the current image.
		 *
		 * @since 1.6.0
		 * @hook classifai_should_ocr_scan_image
		 *
		 * @param {bool}  $should_ocr_scan Whether to run OCR scanning. The default value is set in ComputerVision settings.
		 * @param {array} $metadata        Image metadata.
		 * @param {int}   $attachment_id   The attachment ID.
		 *
		 * @return {bool} Whether to run OCR scanning.
		 */
		if ( ! $force && ! apply_filters( 'classifai_should_ocr_scan_image', $should_ocr_scan, $metadata, $attachment_id ) ) {
			return $metadata;
		}

		$ocr      = new OCR( $settings, $scan, $force );
		$response = $ocr->generate_ocr_data( $metadata, $attachment_id );

		if ( $force ) {
			return $response;
		}

		return $metadata;
	}

	/**
	 * Scan the image and return the captions.
	 *
	 * @param string $image_url Path to the uploaded image.
	 * @param array  $routes    Routes we are calling.
	 *
	 * @return bool|object|WP_Error
	 */
	protected function scan_image( $image_url, array $routes = [] ) {
		$settings = $this->get_settings();

		// Check if valid authentication is in place.
		if ( empty( $settings ) || ( isset( $settings['authenticated'] ) && false === $settings['authenticated'] ) ) {
			return new WP_Error( 'auth', esc_html__( 'Please set up valid authentication with Azure.', 'classifai' ) );
		}

		$url = $this->prep_api_url( $routes );

		/*
		 * MS Computer Vision requires full image URL. So, if the file URL is relative,
		 * then we transform it into a full URL.
		 */
		if ( '/' === substr( $image_url, 0, 1 ) ) {
			$image_url = get_site_url() . $image_url;
		}

		$request = wp_remote_post(
			$url,
			[
				'headers' => [
					'Ocp-Apim-Subscription-Key' => $settings['api_key'],
					'Content-Type'              => 'application/json',
				],
				'body'    => '{"url":"' . $image_url . '"}',
			]
		);

		if ( ! is_wp_error( $request ) ) {
			$body = json_decode( wp_remote_retrieve_body( $request ) );

			if ( 200 !== wp_remote_retrieve_response_code( $request ) && isset( $body->message ) ) {
				$rtn = new WP_Error( $body->code ?? 'error', $body->message, $body );
			} else {
				$rtn = $body;
			}
		} else {
			$rtn = $request;
		}

		return $rtn;
	}

	/**
	 * Build and return the API endpoint based on settings.
	 *
	 * @param array $routes The routes we are calling.
	 *
	 * @return string
	 */
	protected function prep_api_url( array $routes = [] ) {
		$settings     = $this->get_settings();
		$api_features = [];

		if ( in_array( 'alt-tags', $routes, true ) || ! empty( $this->get_alt_text_settings() ) ) {
			$api_features[] = 'Description';
		}

		if ( in_array( 'image-tags', $routes, true ) || ( isset( $settings['enable_image_tagging'] ) && 'no' !== $settings['enable_image_tagging'] ) ) {
			$api_features[] = 'Tags';
		}

		$endpoint = add_query_arg( 'visualFeatures', implode( ',', $api_features ), trailingslashit( $settings['url'] ) . $this->analyze_url );

		return $endpoint;
	}

	/**
	 * Generate the alt tags for the image being uploaded.
	 *
	 * @param array $captions      Captions returned from the API
	 * @param int   $attachment_id Post ID for the attachment.
	 *
	 * @return string
	 */
	protected function generate_alt_tags( $captions, $attachment_id ) {
		$rtn = '';

		$enabled_fields = $this->get_alt_text_settings();

		/**
		 * Filter the captions returned from the API.
		 *
		 * @since 1.4.0
		 * @hook classifai_computer_vision_captions
		 *
		 * @param {array} $captions The returned caption data.
		 *
		 * @return {array} The filtered caption data.
		 */
		$captions = apply_filters( 'classifai_computer_vision_captions', $captions );

		// If $captions isn't an array, don't save them.
		if ( is_array( $captions ) && ! empty( $captions ) ) {
			$threshold = $this->get_settings( 'caption_threshold' );

			// Save the first caption as the alt text if it passes the threshold.
			if ( $captions[0]->confidence * 100 > $threshold ) {
				if ( in_array( 'alt', $enabled_fields, true ) ) {
					update_post_meta( $attachment_id, '_wp_attachment_image_alt', $captions[0]->text );
				}

				if ( in_array( 'caption', $enabled_fields, true ) ) {
					wp_update_post(
						array(
							'ID'           => $attachment_id,
							'post_excerpt' => $captions[0]->text,
						)
					);
				}

				if ( in_array( 'description', $enabled_fields, true ) ) {
					wp_update_post(
						array(
							'ID'           => $attachment_id,
							'post_content' => $captions[0]->text,
						)
					);
				}
				$rtn = $captions[0]->text;
			} else {
				/**
				 * Fires if there were no captions returned.
				 *
				 * @since 1.5.0
				 * @hook classifai_computer_vision_caption_failed
				 *
				 * @param {array} $tags      The caption data.
				 * @param {int}   $threshold The caption_threshold setting.
				 */
				do_action( 'classifai_computer_vision_caption_failed', $captions, $threshold );
			}

			// Save all the results for later.
			update_post_meta( $attachment_id, 'classifai_computer_vision_captions', $captions );
		}

		// return the caption or empty string
		return $rtn;
	}

	/**
	 * Read PDF content and update the description of attachment.
	 *
	 * @param int $attachment_id Attachment ID.
	 */
	public function read_pdf( $attachment_id ) {
		$settings = $this->get_settings();

		if ( ! is_array( $settings ) ) {
			return new WP_Error( 'invalid_settings', 'Can not retrieve the plugin settings.' );
		}

		$should_read_pdf = isset( $settings['enable_read_pdf'] ) && '1' === $settings['enable_read_pdf'];

		if ( ! $should_read_pdf ) {
			return false;
		}

		// Direct file system access is required for the current implementation of this feature.
		if ( ! function_exists( 'get_filesystem_method' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		$access_type = get_filesystem_method();

		if ( 'direct' !== $access_type || ! WP_Filesystem() ) {
			return new WP_Error( 'invalid_access_type', 'Invalid access type! Direct file system access is required.' );
		}

		$read = new Read( $settings, intval( $attachment_id ) );

		return $read->read_document();
	}

	/**
	 * Wrapper action callback for Read cron job.
	 *
	 * @param string $operation_url Operation URL for checking the read status.
	 * @param int    $attachment_id Attachment ID.
	 */
	public function do_read_cron( $operation_url, $attachment_id ) {
		$settings = $this->get_settings();

		( new Read( $settings, intval( $attachment_id ) ) )->check_read_result( $operation_url );
	}

	/**
	 * Generate the image tags for the image being uploaded.
	 *
	 * @param array $tags          Array of tags returned from the API.
	 * @param int   $attachment_id Post ID for the attachment.
	 *
	 * @return string|array|WP_Error
	 */
	protected function generate_image_tags( $tags, $attachment_id ) {
		$rtn      = '';
		$settings = $this->get_settings();

		// Don't save tags if the setting is disabled.
		if ( ! is_array( $settings ) || ! isset( $settings['enable_image_tagging'] ) || '1' !== $settings['enable_image_tagging'] ) {
			return new WP_Error( 'invalid_settings', esc_html__( 'Image tagging is disabled.', 'classifai' ) );
		}

		/**
		 * Filter the tags returned from the API.
		 *
		 * @since 1.4.0
		 * @hook classifai_computer_vision_image_tags
		 *
		 * @param {array} $tags The image tag data.
		 *
		 * @return {array} The filtered image tags.
		 */
		$tags = apply_filters( 'classifai_computer_vision_image_tags', $tags );

		// If $tags isn't an array, don't save them.
		if ( is_array( $tags ) && ! empty( $tags ) ) {
			$threshold   = $this->get_settings( 'tag_threshold' );
			$taxonomy    = $this->get_settings( 'image_tag_taxonomy' );
			$custom_tags = [];

			// Save the first caption as the alt text if it passes the threshold.
			foreach ( $tags as $tag ) {
				if ( $tag->confidence * 100 > $threshold ) {
					$custom_tags[] = $tag->name;
					wp_add_object_terms( $attachment_id, $tag->name, $taxonomy );
				}
			}

			if ( ! empty( $custom_tags ) ) {
				wp_update_term_count_now( $custom_tags, $taxonomy );
				$rtn = $custom_tags;
			} else {
				/**
				 * Fires if there were no tags added.
				 *
				 * @since 1.5.0
				 * @hook classifai_computer_vision_image_tag_failed
				 *
				 * @param {array} $tags      The image tag data.
				 * @param {int}   $threshold The tag_threshold setting.
				 */
				do_action( 'classifai_computer_vision_image_tag_failed', $tags, $threshold );
			}

			// Save the tags for later
			update_post_meta( $attachment_id, 'classifai_computer_vision_image_tags', $tags );
		}

		return $rtn;
	}

	/**
	 * Setup fields
	 */
	public function setup_fields_sections() {
		add_settings_section( $this->get_option_name(), $this->provider_service_name, '', $this->get_option_name() );
		$default_settings = $this->get_default_settings();
		add_settings_field(
			'url',
			esc_html__( 'Endpoint URL', 'classifai' ),
			[ $this, 'render_input' ],
			$this->get_option_name(),
			$this->get_option_name(),
			[
				'label_for'     => 'url',
				'input_type'    => 'text',
				'default_value' => $default_settings['url'],
				'description'   => __( 'Supported protocol and hostname endpoints, e.g., <code>https://REGION.api.cognitive.microsoft.com</code> or <code>https://EXAMPLE.cognitiveservices.azure.com</code>. This can look different based on your setting choices in Azure.', 'classifai' ),
			]
		);
		add_settings_field(
			'api-key',
			esc_html__( 'API Key', 'classifai' ),
			[ $this, 'render_input' ],
			$this->get_option_name(),
			$this->get_option_name(),
			[
				'label_for'     => 'api_key',
				'input_type'    => 'password',
				'default_value' => $default_settings['api_key'],
			]
		);
		add_settings_field(
			'enable-image-captions',
			esc_html__( 'Generate descriptive text', 'classifai' ),
			[ $this, 'render_auto_caption_fields' ],
			$this->get_option_name(),
			$this->get_option_name(),
			[
				'label_for'     => 'enable_image_captions',
				'input_type'    => 'checkbox',
				'default_value' => $default_settings['enable_image_captions'],
				'description'   => __( 'Choose image fields where the generated captions should be applied.', 'classifai' ),
			]
		);
		add_settings_field(
			'caption-threshold',
			esc_html__( 'Descriptive text confidence threshold', 'classifai' ),
			[ $this, 'render_input' ],
			$this->get_option_name(),
			$this->get_option_name(),
			[
				'label_for'     => 'caption_threshold',
				'input_type'    => 'number',
				'default_value' => $default_settings['caption_threshold'],
				'description'   => __( 'Minimum confidence score for automatically added alt text, numeric value from 0-100. Recommended to be set to at least 75.', 'classifai' ),
			]
		);
		add_settings_field(
			'enable-image-tagging',
			esc_html__( 'Tag images', 'classifai' ),
			[ $this, 'render_input' ],
			$this->get_option_name(),
			$this->get_option_name(),
			[
				'label_for'     => 'enable_image_tagging',
				'input_type'    => 'checkbox',
				'default_value' => $default_settings['enable_image_tagging'],
				'description'   => __( 'Image tags will be added automatically.', 'classifai' ),
			]
		);
		add_settings_field(
			'image-tag-threshold',
			esc_html__( 'Tag confidence threshold', 'classifai' ),
			[ $this, 'render_input' ],
			$this->get_option_name(),
			$this->get_option_name(),
			[
				'label_for'     => 'tag_threshold',
				'input_type'    => 'number',
				'default_value' => $default_settings['tag_threshold'],
				'description'   => __( 'Minimum confidence score for automatically added image tags, numeric value from 0-100. Recommended to be set to at least 70.', 'classifai' ),
			]
		);
		// What taxonomy should we tag images with?
		$attachment_taxonomies = get_object_taxonomies( 'attachment', 'objects' );
		$options               = [];
		foreach ( $attachment_taxonomies as $name => $taxonomy ) {
			$options[ $name ] = $taxonomy->label;
		}
		add_settings_field(
			'image-tag-taxonomy',
			esc_html__( 'Tag taxonomy', 'classifai' ),
			[ $this, 'render_select' ],
			$this->get_option_name(),
			$this->get_option_name(),
			[
				'label_for'     => 'image_tag_taxonomy',
				'options'       => $options,
				'default_value' => $default_settings['image_tag_taxonomy'],
			]
		);
		add_settings_field(
			'enable-smart-cropping',
			esc_html__( 'Enable smart cropping', 'classifai' ),
			[ $this, 'render_input' ],
			$this->get_option_name(),
			$this->get_option_name(),
			[
				'label_for'     => 'enable_smart_cropping',
				'input_type'    => 'checkbox',
				'default_value' => $default_settings['enable_smart_cropping'],
				'description'   => __(
					'ComputerVision detects and saves the most visually interesting part of your image (i.e., faces, animals, notable text).',
					'classifai'
				),
			]
		);
		add_settings_field(
			'enable-ocr',
			esc_html__( 'Scan images for text', 'classifai' ),
			[ $this, 'render_input' ],
			$this->get_option_name(),
			$this->get_option_name(),
			[
				'label_for'     => 'enable_ocr',
				'input_type'    => 'checkbox',
				'default_value' => $default_settings['enable_ocr'],
				'description'   => __(
					'OCR detects text in images (e.g., handwritten notes) and saves that as post content.',
					'classifai'
				),
			]
		);
		add_settings_field(
			'enable-read-pdf',
			esc_html__( 'Enable scanning PDF', 'classifai' ),
			[ $this, 'render_input' ],
			$this->get_option_name(),
			$this->get_option_name(),
			[
				'label_for'     => 'enable_read_pdf',
				'input_type'    => 'checkbox',
				'default_value' => $default_settings['enable_read_pdf'],
				'description'   => __(
					'Extract visible text from multi-pages PDF documents. Store the result as the attachment description.',
					'classifai'
				),
			]
		);
	}

	/**
	 * Sanitization
	 *
	 * @param array $settings The settings being saved.
	 *
	 * @return array|mixed
	 */
	public function sanitize_settings( $settings ) {
		$new_settings = [];
		if ( ! empty( $settings['url'] ) && ! empty( $settings['api_key'] ) ) {
			$auth_check = $this->authenticate_credentials( $settings['url'], $settings['api_key'] );
			if ( is_wp_error( $auth_check ) ) {
				$settings_errors['classifai-registration-credentials-error'] = $auth_check->get_error_message();
				$new_settings['authenticated']                               = false;
			} else {
				$new_settings['authenticated'] = true;
			}
			$new_settings['url']     = esc_url_raw( $settings['url'] );
			$new_settings['api_key'] = sanitize_text_field( $settings['api_key'] );
		} else {
			$new_settings['valid']   = false;
			$new_settings['url']     = '';
			$new_settings['api_key'] = '';

			$settings_errors['classifai-registration-credentials-empty'] = __( 'Please enter your credentials', 'classifai' );
		}

		$checkbox_settings = [
			'enable_image_tagging',
			'enable_smart_cropping',
			'enable_ocr',
			'enable_read_pdf',
		];

		foreach ( $checkbox_settings as $checkbox_setting ) {

			if ( empty( $settings[ $checkbox_setting ] ) || 1 !== (int) $settings[ $checkbox_setting ] ) {
				$new_settings[ $checkbox_setting ] = 'no';
			} else {
				$new_settings[ $checkbox_setting ] = '1';
			}
		}

		if ( isset( $settings['caption_threshold'] ) && is_numeric( $settings['caption_threshold'] ) && (int) $settings['caption_threshold'] >= 0 && (int) $settings['caption_threshold'] <= 100 ) {
			$new_settings['caption_threshold'] = absint( $settings['caption_threshold'] );
		} else {
			$new_settings['caption_threshold'] = 75;
		}

		if ( isset( $settings['tag_threshold'] ) && is_numeric( $settings['tag_threshold'] ) && (int) $settings['tag_threshold'] >= 0 && (int) $settings['tag_threshold'] <= 100 ) {
			$new_settings['tag_threshold'] = absint( $settings['tag_threshold'] );
		} else {
			$new_settings['tag_threshold'] = 75;
		}

		if ( isset( $settings['image_tag_taxonomy'] ) && taxonomy_exists( $settings['image_tag_taxonomy'] ) ) {
			$new_settings['image_tag_taxonomy'] = $settings['image_tag_taxonomy'];
		} elseif ( taxonomy_exists( 'classifai-image-tags' ) ) {
			$new_settings['image_tag_taxonomy'] = 'classifai-image-tags';
		}

		if ( isset( $settings['enable_image_captions'] ) ) {
			if ( is_array( $settings['enable_image_captions'] ) ) {
				$new_settings['enable_image_captions'] = $settings['enable_image_captions'];
			} elseif ( 1 === (int) $settings['enable_image_captions'] ) {
				// Handle submission from onboarding wizard.
				$new_settings['enable_image_captions'] = array(
					'alt'         => 'alt',
					'caption'     => 0,
					'description' => 0,
				);
			}
		} else {
			$new_settings['enable_image_captions'] = array(
				'alt'         => 0,
				'caption'     => 0,
				'description' => 0,
			);
		}

		if ( ! empty( $settings_errors ) ) {

			$registered_settings_errors = wp_list_pluck( get_settings_errors( $this->get_option_name() ), 'code' );

			foreach ( $settings_errors as $code => $message ) {

				if ( ! in_array( $code, $registered_settings_errors, true ) ) {
					add_settings_error(
						$this->get_option_name(),
						$code,
						esc_html( $message ),
						'error'
					);
				}
			}
		}

		return $new_settings;
	}

	/**
	 * Authenitcates our credentials.
	 *
	 * @param string $url     Endpoint URL.
	 * @param string $api_key Api Key.
	 *
	 * @return bool|WP_Error
	 */
	protected function authenticate_credentials( $url, $api_key ) {
		$rtn     = false;
		$request = wp_remote_post(
			trailingslashit( $url ) . $this->analyze_url,
			[
				'headers' => [
					'Ocp-Apim-Subscription-Key' => $api_key,
					'Content-Type'              => 'application/json',
				],
				'body'    => '{"url":"https://classifaiplugin.com/wp-content/themes/classifai-theme/assets/img/header.png"}',
			]
		);

		if ( ! is_wp_error( $request ) ) {
			$response = json_decode( wp_remote_retrieve_body( $request ) );
			if ( ! empty( $response->error ) ) {
				$rtn = new WP_Error( 'auth', $response->error->message );
			} else {
				$rtn = true;
			}
		}

		return $rtn;
	}

	/**
	 * Provides debug information related to the provider.
	 *
	 * @param null|array $settings Settings array. If empty, settings will be retrieved.
	 * @return array Keyed array of debug information.
	 * @since 1.4.0
	 */
	public function get_provider_debug_information( $settings = null ) {
		if ( is_null( $settings ) ) {
			$settings = $this->sanitize_settings( $this->get_settings() );
		}

		$authenticated = 1 === intval( $settings['authenticated'] ?? 0 );

		return [
			__( 'Authenticated', 'classifai' )         => $authenticated ? __( 'yes', 'classifai' ) : __( 'no', 'classifai' ),
			__( 'API URL', 'classifai' )               => $settings['url'] ?? '',
			__( 'Caption threshold', 'classifai' )     => $settings['caption_threshold'] ?? null,
			__( 'Latest response - Image Scan', 'classifai' ) => $this->get_formatted_latest_response( get_transient( 'classifai_azure_computer_vision_image_scan_latest_response' ) ),
			__( 'Latest response - Smart Cropping', 'classifai' ) => $this->get_formatted_latest_response( get_transient( 'classifai_azure_computer_vision_smart_cropping_latest_response' ) ),
			__( 'Latest response - OCR', 'classifai' ) => $this->get_formatted_latest_response( get_transient( 'classifai_azure_computer_vision_ocr_latest_response' ) ),
		];
	}

	/**
	 * Filter the SQL clauses of an attachment query to include tags and alt text.
	 *
	 * @param array $clauses An array including WHERE, GROUP BY, JOIN, ORDER BY,
	 *                       DISTINCT, fields (SELECT), and LIMITS clauses.
	 * @return array The modified clauses.
	 */
	public function filter_attachment_query_keywords( $clauses ) {
		global $wpdb;
		remove_filter( 'posts_clauses', __FUNCTION__ );

		if ( ! preg_match( "/\({$wpdb->posts}.post_content (NOT LIKE|LIKE) (\'[^']+\')\)/", $clauses['where'] ) ) {
			return $clauses;
		}

		// Add a LEFT JOIN of the postmeta table so we don't trample existing JOINs.
		$clauses['join'] .= " LEFT JOIN {$wpdb->postmeta} AS classifai_postmeta ON ( {$wpdb->posts}.ID = classifai_postmeta.post_id AND ( classifai_postmeta.meta_key = 'classifai_computer_vision_image_tags' OR classifai_postmeta.meta_key = '_wp_attachment_image_alt' ) )";

		$clauses['groupby'] = "{$wpdb->posts}.ID";

		$clauses['where'] = preg_replace(
			"/\({$wpdb->posts}.post_content (NOT LIKE|LIKE) (\'[^']+\')\)/",
			'$0 OR ( classifai_postmeta.meta_value $1 $2 )',
			$clauses['where']
		);

		return $clauses;
	}

	/**
	 * Common entry point for all REST endpoints for this provider.
	 * This is called by the Service.
	 *
	 * @param int    $post_id       The Post Id we're processing.
	 * @param string $route_to_call The name of the route we're going to be processing.
	 * @param array  $args          Optional arguments to pass to the route.
	 * @return array|string|WP_Error
	 */
	public function rest_endpoint_callback( $post_id, $route_to_call, $args = [] ) {
		$route_to_call = strtolower( $route_to_call );
		// Check to be sure the post both exists and is an attachment.
		if ( ! get_post( $post_id ) || 'attachment' !== get_post_type( $post_id ) ) {
			/* translators: %1$s: the attachment ID */
			return new WP_Error( 'incorrect_ID', sprintf( esc_html__( '%1$d is not found or is not an attachment', 'classifai' ), $post_id ), [ 'status' => 404 ] );
		}

		$metadata = wp_get_attachment_metadata( $post_id );

		if ( 'ocr' === $route_to_call ) {
			return $this->ocr_processing( $metadata, $post_id, true );
		}

		if ( 'read-pdf' === $route_to_call ) {
			return $this->read_pdf( $post_id );
		}

		// Allow rescanning image that are not stored in local storage.
		$image_url = get_modified_image_source_url( $post_id );

		if ( empty( $image_url ) || ! filter_var( $image_url, FILTER_VALIDATE_URL ) ) {
			$image_url = get_largest_acceptable_image_url(
				get_attached_file( $post_id ),
				wp_get_attachment_url( $post_id ),
				$metadata['sizes'],
				computer_vision_max_filesize()
			);
		}

		if ( empty( $image_url ) ) {
			return new WP_Error( 'error', esc_html__( 'Valid image size not found. Make sure the image is less than 4MB.' ) );
		}

		$image_scan_results = $this->scan_image( $image_url, [ $route_to_call ] );

		if ( is_wp_error( $image_scan_results ) ) {
			return $image_scan_results;
		}

		switch ( $route_to_call ) {
			case 'alt-tags':
				if ( isset( $image_scan_results->description->captions ) ) {
					// Process the captions.
					return $this->generate_alt_tags( $image_scan_results->description->captions, $post_id );
				}
				break;
			case 'image-tags':
				if ( isset( $image_scan_results->tags ) ) {
					// Process the tags.
					return $this->generate_image_tags( $image_scan_results->tags, $post_id );
				}
				break;
			case 'smart-crop':
				if ( ! empty( $metadata ) ) {
					// Process the smart crop.
					return $this->smart_crop_image( $metadata, $post_id );
				}
				break;
		}
	}
}
