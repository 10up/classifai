<?php
/**
 * Azure Computer vision
 */

namespace Classifai\Providers\Azure;

use Classifai\Providers\Provider;

use function Classifai\computer_vision_max_filesize;
use function Classifai\get_largest_acceptable_image_url;

class ComputerVision extends Provider {

	/**
	 * @var string URL fragment to the analyze API endpoint
	 */
	protected $analyze_url = '/vision/v1.0/analyze';

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
	}

	/**
	 * Resets settings for the ComputerVision provider.
	 */
	public function reset_settings() {
		// TODO: Implement reset_settings() method.
	}

	/**
	 * Can the functionality be initialized?
	 *
	 * @return bool
	 */
	public function can_register() {
		$options = get_option( $this->get_option_name() );
		if ( isset( $options['authenticated'] ) && false === $options['authenticated'] ) {
			return false;
		}
		if ( empty( $options ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Register the functionality.
	 */
	public function register() {
		add_action( 'add_meta_boxes_attachment', [ $this, 'setup_attachment_meta_box' ] );
		add_action( 'edit_attachment', [ $this, 'maybe_rescan_image' ] );
		add_filter( 'posts_clauses', [ $this, 'filter_attachment_query_keywords' ], 10, 1 );
		add_filter( 'wp_generate_attachment_metadata', [ $this, 'smart_crop_image' ], 8, 2 );
		add_filter( 'wp_generate_attachment_metadata', [ $this, 'generate_image_alt_tags' ], 8, 2 );
		add_filter( 'posts_clauses', [ $this, 'filter_attachment_query_keywords' ], 10, 1 );
		add_action( 'enqueue_block_editor_assets', [ $this, 'enqueue_editor_assets' ] );
	}

	/**
	 * Enqueue the editor scripts.
	 */
	public function enqueue_editor_assets() {
		$enable_ocr = isset( $settings['enable_ocr'] ) && '1' === $settings['enable_ocr'];

		if ( ! $enable_ocr ) {
			return;
		}

		wp_enqueue_script(
			'editor-ocr',
			CLASSIFAI_PLUGIN_URL . 'dist/js/editor-ocr.min.js',
			array( 'wp-blocks', 'wp-api-fetch', 'lodash' ),
			CLASSIFAI_PLUGIN_VERSION,
			true
		);
	}

	/**
	 * Adds a meta box for rescanning options if the settings are configured
	 */
	public function setup_attachment_meta_box() {
		add_meta_box(
			'attachment_meta_box',
			__( 'ClassifAI Image Processing', 'classifai' ),
			[ $this, 'attachment_data_meta_box' ],
			'attachment',
			'side',
			'high'
		);
	}

	/**
	 * Display meta data
	 *
	 * @param \WP_Post $post The post object.
	 */
	public function attachment_data_meta_box( $post ) {
		$captions = get_post_meta( $post->ID, '_wp_attachment_image_alt', true ) ? __( 'Rescan Alt Text', 'classifai' ) : __( 'Scan Alt Text', 'classifai' );
		$tags     = ! empty( wp_get_object_terms( $post->ID, 'classifai-image-tags' ) ) ? __( 'Rescan Tags', 'classifai' ) : __( 'Generate Tags', 'classifai' );
		$ocr      = get_post_meta( $post->ID, 'classifai_computer_vision_ocr', true ) ? __( 'Rescan Text', 'classifai' ) : __( 'Scan Text', 'classifai' );
		?>
		<div class="misc-publishing-actions">
			<div class="misc-pub-section">
				<label for="rescan-captions">
					<input type="checkbox" value="yes" id="rescan-captions" name="rescan-captions"/>
					<?php echo esc_html( $captions ); ?>
				</label>
			</div>
			<div class="misc-pub-section">
				<label for="rescan-tags">
					<input type="checkbox" value="yes" id="rescan-tags" name="rescan-tags"/>
					<?php echo esc_html( $tags ); ?>
				</label>
			</div>
			<div class="misc-pub-section">
				<label for="rescan-ocr">
					<input type="checkbox" value="yes" id="rescan-ocr" name="rescan-ocr"/>
					<?php echo esc_html( $ocr ); ?>
				</label>
			</div>
		</div>
		<?php
	}

	/**
	 *
	 * @param int $attachment_id Post id for the attachment
	 */
	public function maybe_rescan_image( $attachment_id ) {
		$image_url  = wp_get_attachment_image_url( $attachment_id );
		$image_scan = $this->scan_image( $image_url );
		if ( ! is_wp_error( $image_scan ) ) {
			// Are we updating the captions?
			if ( filter_input( INPUT_POST, 'rescan-captions' ) && isset( $image_scan->description->captions ) ) {
				$this->generate_alt_tags( $image_scan->description->captions, $attachment_id );
			}

			// Are we updating the tags?
			if ( filter_input( INPUT_POST, 'rescan-tags' ) && isset( $image_scan->tags ) ) {
				$this->generate_image_tags( $image_scan->tags, $attachment_id );
			}
		}

		// Are we updating the OCR text?
		if ( filter_input( INPUT_POST, 'rescan-ocr' ) ) {
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
			'no' !== $settings['enable_image_captions']
		) {

			$image_url = apply_filters( 'classifai_generate_image_alt_tags_source_url', null, $attachment_id );

			if ( empty( $image_url ) || ! filter_var( $image_url, FILTER_VALIDATE_URL ) ) {
				if ( isset( $metadata['sizes'] ) && is_array( $metadata['sizes'] ) ) {
					$image_url = get_largest_acceptable_image_url(
						get_attached_file( $attachment_id ),
						wp_get_attachment_url( $attachment_id, 'full' ),
						$metadata['sizes'],
						computer_vision_max_filesize()
					);
				} else {
					$image_url = wp_get_attachment_url( $attachment_id, 'full' );
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
		 * @param bool  $should_ocr_scan Whether to run OCR scanning. The default value is set in ComputerVision settings.
		 * @param array $metadata        Image metadata.
		 * @param int   $attachment_id   The attachment ID.
		 *
		 * @return bool Whether to run OCR scanning.
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
	 *
	 * @return bool|object|\WP_Error
	 */
	protected function scan_image( $image_url ) {
		$settings = $this->get_settings();
		$url      = $this->prep_api_url();

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
			$response = json_decode( wp_remote_retrieve_body( $request ) );
			if ( isset( $response->error ) ) {
				$rtn = new \WP_Error( 'auth', $response->error->message );
			} else {
				return $response;
			}
		} else {
			$rtn = $request;
		}

		return $rtn;
	}

	/**
	 * Build and return the API endpoint based on settings.
	 *
	 * @return string
	 */
	protected function prep_api_url() {
		$settings     = $this->get_settings();
		$api_features = [];
		if ( 'no' !== $settings['enable_image_captions'] ) {
			$api_features[] = 'Description';
		}
		if ( 'no' !== $settings['enable_image_tagging'] ) {
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
	 */
	protected function generate_alt_tags( $captions, $attachment_id ) {
		$rtn = '';
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
				update_post_meta( $attachment_id, '_wp_attachment_image_alt', $captions[0]->text );
				$rtn = $captions[0]->text;
			} else {
				/**
				 * Fires if there were no captions returned.
				 *
				 * @since 1.5.0
				 * @hook classifai_computer_vision_caption_failed
				 *
				 * @param array $tags      The caption data.
				 * @param int   $threshold The caption_threshold setting.
				 */
				do_action( 'classifai_computer_vision_caption_failed', $captions, $threshold );
			}
			// Save all the results for later.
			update_post_meta( $attachment_id, 'classifai_computer_vision_captions', $captions );
			// return the caption or empty string
			return $rtn;
		}
	}

	/**
	 * Generate the image tags for the image being uploaded.
	 *
	 * @param array $tags          Array ot tags returned from the API.
	 * @param int   $attachment_id Post ID for the attachment.
	 *
	 * @return mixed
	 */
	protected function generate_image_tags( $tags, $attachment_id ) {
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
			$threshold = $this->get_settings( 'tag_threshold' );
			$taxonomy  = $this->get_settings( 'image_tag_taxonomy' );
			// Save the first caption as the alt text if it passes the threshold.
			$custom_tags = [];
			foreach ( $tags as $tag ) {
				if ( $tag->confidence * 100 > $threshold ) {
					$custom_tags[] = $tag->name;
					wp_add_object_terms( $attachment_id, $tag->name, $taxonomy );
				}
			}
			if ( ! empty( $custom_tags ) ) {
				wp_update_term_count_now( $custom_tags, $taxonomy );
			} else {
				/**
				 * Fires if there were no tags added.
				 *
				 * @since 1.5.0
				 * @hook classifai_computer_vision_image_tag_failed
				 *
				 * @param array $tags      The image tag data.
				 * @param int   $threshold The tag_threshold setting.
				 */
				do_action( 'classifai_computer_vision_image_tag_failed', $tags, $threshold );
			}

			// Save the tags for later
			update_post_meta( $attachment_id, 'classifai_computer_vision_image_tags', $tags );
		}
	}

	/**
	 * Setup fields
	 */
	public function setup_fields_sections() {
		add_settings_section( $this->get_option_name(), $this->provider_service_name, '', $this->get_option_name() );
		add_settings_field(
			'url',
			esc_html__( 'Endpoint URL', 'classifai' ),
			[ $this, 'render_input' ],
			$this->get_option_name(),
			$this->get_option_name(),
			[
				'label_for'   => 'url',
				'input_type'  => 'text',
				'description' => __( 'e.g. <code>https://REGION.api.cognitive.microsoft.com/</code>', 'classifai' ),
			]
		);
		add_settings_field(
			'api-key',
			esc_html__( 'API Key', 'classifai' ),
			[ $this, 'render_input' ],
			$this->get_option_name(),
			$this->get_option_name(),
			[
				'label_for'  => 'api_key',
				'input_type' => 'password',
			]
		);
		add_settings_field(
			'enable-image-captions',
			esc_html__( 'Automatically Caption Images', 'classifai' ),
			[ $this, 'render_input' ],
			$this->get_option_name(),
			$this->get_option_name(),
			[
				'label_for'     => 'enable_image_captions',
				'input_type'    => 'checkbox',
				'default_value' => true,
				'description'   => __( 'Images will be captioned with alt text upon upload', 'classifai' ),
			]
		);
		add_settings_field(
			'caption-threshold',
			esc_html__( 'Caption Confidence Threshold', 'classifai' ),
			[ $this, 'render_input' ],
			$this->get_option_name(),
			$this->get_option_name(),
			[
				'label_for'     => 'caption_threshold',
				'input_type'    => 'number',
				'default_value' => 75,
				'description'   => __( 'Minimum confidence score for automatically applied image captions, numeric value from 0-100. Recommended to be set to at least 75.', 'classifai' ),
			]
		);
		add_settings_field(
			'enable-image-tagging',
			esc_html__( 'Automatically Tag Images', 'classifai' ),
			[ $this, 'render_input' ],
			$this->get_option_name(),
			$this->get_option_name(),
			[
				'label_for'     => 'enable_image_tagging',
				'input_type'    => 'checkbox',
				'default_value' => true,
				'description'   => __( 'Images will be tagged upon upload', 'classifai' ),
			]
		);
		add_settings_field(
			'image-tag-threshold',
			esc_html__( 'Tag Confidence Threshold', 'classifai' ),
			[ $this, 'render_input' ],
			$this->get_option_name(),
			$this->get_option_name(),
			[
				'label_for'     => 'tag_threshold',
				'input_type'    => 'number',
				'default_value' => 70,
				'description'   => __( 'Minimum confidence score for automatically applied image tags, numeric value from 0-100. Recommended to be set to at least 70.', 'classifai' ),
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
			esc_html__( 'Tag Taxonomy', 'classifai' ),
			[ $this, 'render_select' ],
			$this->get_option_name(),
			$this->get_option_name(),
			[
				'label_for' => 'image_tag_taxonomy',
				'options'   => $options,
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
				'default_value' => false,
				'description'   => __(
					'Crop images around a region of interest identified by ComputerVision',
					'classifai'
				),
			]
		);

		add_settings_field(
			'enable-ocr',
			esc_html__( 'Enable OCR', 'classifai' ),
			[ $this, 'render_input' ],
			$this->get_option_name(),
			$this->get_option_name(),
			[
				'label_for'     => 'enable_ocr',
				'input_type'    => 'checkbox',
				'default_value' => false,
				'description'   => __(
					'Detect text in an image and store that as post content',
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
				$new_settings['authenticated'] = false;
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
			'enable_image_captions',
			'enable_image_tagging',
			'enable_smart_cropping',
			'enable_ocr',
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
	 * @return bool|\WP_Error
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
				$rtn = new \WP_Error( 'auth', $response->error->message );
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
			__( 'Authenticated', 'classifai' )                    => $authenticated ? __( 'yes', 'classifai' ) : __( 'no', 'classifai' ),
			__( 'API URL', 'classifai' )                          => $settings['url'] ?? '',
			__( 'Caption threshold', 'classifai' )                => $settings['caption_threshold'] ?? null,
			__( 'Latest response - Image Scan', 'classifai' )     => $this->get_formatted_latest_response( get_transient( 'classifai_azure_computer_vision_image_scan_latest_response' ) ),
			__( 'Latest response - Smart Cropping', 'classifai' ) => $this->get_formatted_latest_response( get_transient( 'classifai_azure_computer_vision_smart_cropping_latest_response' ) ),
			__( 'Latest response - OCR', 'classifai' )            => $this->get_formatted_latest_response( get_transient( 'classifai_azure_computer_vision_ocr_latest_response' ) ),
		];
	}

	/**
	 * Format the result of most recent request.
	 *
	 * @param mixed $data Response data to format.
	 *
	 * @return string
	 */
	private function get_formatted_latest_response( $data ) {
		if ( ! $data ) {
			return __( 'N/A', 'classifai' );
		}

		if ( is_wp_error( $data ) ) {
			return $data->get_error_message();
		}

		return preg_replace( '/,"/', ', "', wp_json_encode( $data ) );
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
	 *
	 * @return mixed
	 */
	public function rest_endpoint_callback( $post_id, $route_to_call ) {
		$metadata = wp_get_attachment_metadata( $post_id );

		if ( 'ocr' === $route_to_call ) {
			return $this->ocr_processing( $metadata, $post_id, true );
		}

		$image_url = get_largest_acceptable_image_url(
			get_attached_file( $post_id ),
			wp_get_attachment_url( $post_id ),
			$metadata['sizes']
		);

		if ( empty( $image_url ) ) {
			return '';
		}

		$image_scan_results = $this->scan_image( $image_url );

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
		}
	}
}
