<?php
/**
 * Azure AI Vision
 */

namespace Classifai\Providers\Azure;

use Classifai\Features\DescriptiveTextGenerator;
use Classifai\Features\ImageTagsGenerator;
use Classifai\Features\ImageTextExtraction;
use Classifai\Features\PDFTextExtraction;
use Classifai\Features\ImageCropping;
use Classifai\Providers\Azure\SmartCropping;
use Classifai\Providers\Provider;
use WP_Error;

use function Classifai\computer_vision_max_filesize;
use function Classifai\get_largest_size_and_dimensions_image_url;
use function Classifai\get_modified_image_source_url;

class ComputerVision extends Provider {

	const ID = 'ms_computer_vision';

	/**
	 * @var string URL fragment to the analyze API endpoint
	 */
	protected $analyze_url = 'computervision/imageanalysis:analyze?api-version=2024-02-01';

	/**
	 * Image types to process.
	 *
	 * @var array
	 */
	private $image_types_to_process = [
		'bmp',
		'gif',
		'jpeg',
		'png',
		'webp',
		'ico',
		'tiff',
		'mpo',
	];

	/**
	 * ComputerVision constructor.
	 *
	 * @param \Classifai\Features\Feature $feature_instance The feature instance.
	 */
	public function __construct( $feature_instance = null ) {
		$this->feature_instance = $feature_instance;
	}

	/**
	 * Renders the provider fields.
	 */
	public function render_provider_fields() {
		$settings = $this->feature_instance->get_settings( static::ID );

		add_settings_field(
			static::ID . '_endpoint_url',
			esc_html__( 'Endpoint URL', 'classifai' ),
			[ $this->feature_instance, 'render_input' ],
			$this->feature_instance->get_option_name(),
			$this->feature_instance->get_option_name() . '_section',
			[
				'option_index'  => static::ID,
				'label_for'     => 'endpoint_url',
				'input_type'    => 'text',
				'default_value' => $settings['endpoint_url'],
				'description'   => $this->feature_instance->is_configured_with_provider( static::ID ) ?
					'' :
					__( 'Supported protocol and hostname endpoints, e.g., <code>https://REGION.api.cognitive.microsoft.com</code> or <code>https://EXAMPLE.cognitiveservices.azure.com</code>. This can look different based on your setting choices in Azure.', 'classifai' ),
				'class'         => 'large-text classifai-provider-field hidden provider-scope-' . static::ID, // Important to add this.
			]
		);

		add_settings_field(
			static::ID . '_api_key',
			esc_html__( 'API Key', 'classifai' ),
			[ $this->feature_instance, 'render_input' ],
			$this->feature_instance->get_option_name(),
			$this->feature_instance->get_option_name() . '_section',
			[
				'option_index'  => static::ID,
				'label_for'     => 'api_key',
				'input_type'    => 'password',
				'default_value' => $settings['api_key'],
				'class'         => 'classifai-provider-field hidden provider-scope-' . static::ID, // Important to add this.
			]
		);

		switch ( $this->feature_instance::ID ) {
			case DescriptiveTextGenerator::ID:
				$this->add_descriptive_text_generation_fields();
				break;

			case ImageTagsGenerator::ID:
				$this->add_image_tags_generation_fields();
				break;
		}

		/**
		 * Allows more Provider specific settings to be rendered.
		 *
		 * @since 3.0.0
		 * @hook classifai_ms_computer_vision_render_provider_fields
		 *
		 * @param {object} $this The Provider object.
		 */
		do_action( 'classifai_' . static::ID . '_render_provider_fields', $this );
	}

	/**
	 * Renders fields for the Descriptive Text Feature.
	 */
	public function add_descriptive_text_generation_fields() {
		$settings = $this->feature_instance->get_settings( static::ID );

		add_settings_field(
			static::ID . '_descriptive_confidence_threshold',
			esc_html__( 'Confidence threshold', 'classifai' ),
			[ $this->feature_instance, 'render_input' ],
			$this->feature_instance->get_option_name(),
			$this->feature_instance->get_option_name() . '_section',
			[
				'option_index'  => static::ID,
				'label_for'     => 'descriptive_confidence_threshold',
				'input_type'    => 'number',
				'min'           => 1,
				'step'          => 1,
				'default_value' => $settings['descriptive_confidence_threshold'],
				'description'   => esc_html__( 'Minimum confidence score for automatically added generated text, numeric value from 0-100. Recommended to be set to at least 70.', 'classifai' ),
				'class'         => 'classifai-provider-field hidden provider-scope-' . static::ID, // Important to add this.
			]
		);
	}

	/**
	 * Renders fields for the Image Tags Feature.
	 */
	public function add_image_tags_generation_fields() {
		$settings = $this->feature_instance->get_settings( static::ID );

		add_settings_field(
			static::ID . '_tag_confidence_threshold',
			esc_html__( 'Confidence threshold', 'classifai' ),
			[ $this->feature_instance, 'render_input' ],
			$this->feature_instance->get_option_name(),
			$this->feature_instance->get_option_name() . '_section',
			[
				'option_index'  => static::ID,
				'label_for'     => 'tag_confidence_threshold',
				'input_type'    => 'number',
				'min'           => 1,
				'step'          => 1,
				'default_value' => $settings['tag_confidence_threshold'],
				'description'   => esc_html__( 'Minimum confidence score for automatically added image tags, numeric value from 0-100. Recommended to be set to at least 70.', 'classifai' ),
				'class'         => 'classifai-provider-field hidden provider-scope-' . static::ID, // Important to add this.
			]
		);
	}

	/**
	 * Returns the default settings for the current provider
	 * and the settings needed for the feature which uses this provider.
	 *
	 * @return array
	 */
	public function get_default_provider_settings(): array {
		$common_settings = [
			'endpoint_url'  => '',
			'api_key'       => '',
			'authenticated' => false,
		];

		switch ( $this->feature_instance::ID ) {
			case DescriptiveTextGenerator::ID:
				return array_merge(
					$common_settings,
					[
						'descriptive_confidence_threshold' => 70,
					]
				);

			case ImageTagsGenerator::ID:
				return array_merge(
					$common_settings,
					[
						'tag_confidence_threshold' => 70,
					]
				);
		}

		return $common_settings;
	}

	/**
	 * Sanitization
	 *
	 * @param array $new_settings The settings being saved.
	 * @return array|mixed
	 */
	public function sanitize_settings( array $new_settings ) {
		$settings = $this->feature_instance->get_settings();

		if ( ! empty( $new_settings[ static::ID ]['endpoint_url'] ) && ! empty( $new_settings[ static::ID ]['api_key'] ) ) {
			$new_settings[ static::ID ]['authenticated'] = $settings[ static::ID ]['authenticated'];
			$new_settings[ static::ID ]['endpoint_url']  = esc_url_raw( $new_settings[ static::ID ]['endpoint_url'] ?? $settings[ static::ID ]['endpoint_url'] );
			$new_settings[ static::ID ]['api_key']       = sanitize_text_field( $new_settings[ static::ID ]['api_key'] ?? $settings[ static::ID ]['api_key'] );

			$is_authenticated = $new_settings[ static::ID ]['authenticated'];
			$is_endpoint_same = $new_settings[ static::ID ]['endpoint_url'] === $settings[ static::ID ]['endpoint_url'];
			$is_api_key_same  = $new_settings[ static::ID ]['api_key'] === $settings[ static::ID ]['api_key'];

			if ( ! ( $is_authenticated && $is_endpoint_same && $is_api_key_same ) ) {
				$auth_check = $this->authenticate_credentials(
					$new_settings[ static::ID ]['endpoint_url'],
					$new_settings[ static::ID ]['api_key']
				);

				if ( is_wp_error( $auth_check ) ) {
					$new_settings[ static::ID ]['authenticated'] = false;

					$error_message = $auth_check->get_error_message();

					// Add an error message.
					add_settings_error(
						'api_key',
						'classifai-auth',
						$error_message,
						'error'
					);
				} else {
					$new_settings[ static::ID ]['authenticated'] = true;
				}
			}
		} else {
			$new_settings[ static::ID ]['endpoint_url'] = $settings[ static::ID ]['endpoint_url'];
			$new_settings[ static::ID ]['api_key']      = $settings[ static::ID ]['api_key'];
		}

		if ( $this->feature_instance instanceof DescriptiveTextGenerator ) {
			$new_settings[ static::ID ]['descriptive_confidence_threshold'] = absint( $new_settings[ static::ID ]['descriptive_confidence_threshold'] ?? $settings[ static::ID ]['descriptive_confidence_threshold'] );
		}

		if ( $this->feature_instance instanceof ImageTagsGenerator ) {
			$new_settings[ static::ID ]['tag_confidence_threshold'] = absint( $new_settings[ static::ID ]['tag_confidence_threshold'] ?? $settings[ static::ID ]['tag_confidence_threshold'] );
		}

		return $new_settings;
	}

	/**
	 * Register the functionality.
	 */
	public function register() {
		add_action( 'classifai_retry_get_read_result', [ $this, 'do_read_cron' ], 10, 2 );
		add_action( 'wp_ajax_classifai_get_read_status', [ $this, 'get_read_status_ajax' ] );
		add_filter( 'classifai_feature_pdf_to_text_generation_read_status', [ $this, 'get_read_status' ], 10, 2 );
		add_filter( 'posts_clauses', [ $this, 'filter_attachment_query_keywords' ], 10, 1 );
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

		wp_send_json_success( self::get_read_status( [], $attachment_id ) );
	}

	/**
	 * Callback to get the status of the PDF read.
	 *
	 * @param array $status Current status.
	 * @param int   $attachment_id The attachment ID.
	 * @return array Read and running status.
	 */
	public static function get_read_status( array $status = [], $attachment_id = null ) {
		if ( empty( $attachment_id ) || ! is_numeric( $attachment_id ) ) {
			return $status;
		}

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
	 * Wrapper action callback for Read cron job.
	 *
	 * @param string $operation_url Operation URL for checking the read status.
	 * @param int    $attachment_id Attachment ID.
	 */
	public function do_read_cron( string $operation_url, int $attachment_id ) {
		$feature  = new PDFTextExtraction();
		$settings = $feature->get_settings( static::ID );

		( new Read( $settings, intval( $attachment_id ), $feature ) )->check_read_result( $operation_url );
	}

	/**
	 * Generate smart-cropped image thumbnails.
	 *
	 * @since 1.5.0
	 *
	 * @param array $metadata Attachment metadata.
	 * @param int   $attachment_id Attachment ID.
	 * @return array|WP_Error
	 */
	public function smart_crop_image( array $metadata, int $attachment_id ) {
		if ( ! wp_attachment_is_image( $attachment_id ) ) {
			return new WP_Error( 'invalid', esc_html__( 'This attachment can\'t be processed.', 'classifai' ) );
		}

		$feature  = new ImageCropping();
		$settings = $feature->get_settings( static::ID );

		if ( ! is_array( $metadata ) || ! is_array( $settings ) ) {
			return new WP_Error( 'invalid', esc_html__( 'Invalid data found. Please check your settings and try again.', 'classifai' ) );
		}

		$should_smart_crop = $feature->is_feature_enabled();

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
			return [];
		}

		// Direct file system access is required for the current implementation of this feature.
		if ( ! function_exists( 'get_filesystem_method' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		$access_type = get_filesystem_method();
		if ( 'direct' !== $access_type || ! WP_Filesystem() ) {
			return new WP_Error( 'access', esc_html__( 'Access to the filesystem is required for this feature to work.', 'classifai' ) );
		}

		$smart_cropping = new SmartCropping( $settings );

		return $smart_cropping->generate_cropped_images( $metadata, intval( $attachment_id ) );
	}

	/**
	 * Runs text recognition on the attachment.
	 *
	 * @since 1.6.0
	 *
	 * @param string $image_url URL of image to process.
	 * @param int    $attachment_id Attachment ID.
	 * @return string|WP_Error
	 */
	public function ocr_processing( string $image_url, int $attachment_id = 0 ) {
		if ( ! wp_attachment_is_image( $attachment_id ) ) {
			return new WP_Error( 'invalid', esc_html__( 'This attachment can\'t be processed.', 'classifai' ) );
		}

		$feature = new ImageTextExtraction();
		$rtn     = '';

		/**
		 * Filters whether to run OCR scanning on the current image.
		 *
		 * @since 1.6.0
		 * @hook classifai_should_ocr_scan_image
		 *
		 * @param {bool}  $should_scan   Whether to run OCR scanning. Defaults to feature being enabled.
		 * @param {string} $image_url    URL of image to process.
		 * @param {int}   $attachment_id The attachment ID.
		 *
		 * @return {bool} Whether to run OCR scanning.
		 */
		if ( ! apply_filters( 'classifai_should_ocr_scan_image', $feature->is_feature_enabled(), $image_url, $attachment_id ) ) {
			return '';
		}

		$details = $this->scan_image( $image_url, $feature );

		if ( is_wp_error( $details ) ) {
			return $details;
		}

		set_transient( 'classifai_azure_computer_vision_image_text_extraction_latest_response', $details, DAY_IN_SECONDS * 30 );

		if ( isset( $details->readResult ) ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			$text = [];

			// Iterate down the chain to find the text we want.
			foreach ( $details->readResult as $result ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				foreach ( $result as $block ) {
					foreach ( $block as $lines ) {
						foreach ( $lines as $line ) {
							if ( isset( $line->text ) ) {
								$text[] = $line->text;
							}
						}
					}
				}
			}

			if ( ! empty( $text ) ) {

				/**
				 * Filter the text returned from the API.
				 *
				 * @since 1.6.0
				 * @hook classifai_ocr_text
				 *
				 * @param {string} $text    The returned text data.
				 * @param {object} $details The full scan results from the API.
				 *
				 * @return {string} The filtered text data.
				 */
				$rtn = apply_filters( 'classifai_ocr_text', implode( ' ', $text ), $details );

				// Save all the results for later
				update_post_meta( $attachment_id, 'classifai_computer_vision_ocr', $details );
			}
		}

		return $rtn;
	}

	/**
	 * Generate alt tags for an image.
	 *
	 * @param string $image_url URL of image to process.
	 * @param int    $attachment_id Post ID for the attachment.
	 * @return string|WP_Error
	 */
	public function generate_alt_tags( string $image_url, int $attachment_id ) {
		if ( ! wp_attachment_is_image( $attachment_id ) ) {
			return new WP_Error( 'invalid', esc_html__( 'This attachment can\'t be processed.', 'classifai' ) );
		}

		$feature = new DescriptiveTextGenerator();
		$rtn     = '';

		$details = $this->scan_image( $image_url, $feature );

		if ( is_wp_error( $details ) ) {
			return $details;
		}

		$caption = isset( $details->captionResult ) ? (array) $details->captionResult : []; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

		set_transient( 'classifai_azure_computer_vision_descriptive_text_latest_response', $details, DAY_IN_SECONDS * 30 );

		/**
		 * Filter the caption returned from the API.
		 *
		 * @since 1.4.0
		 * @hook classifai_computer_vision_captions
		 *
		 * @param {array} $caption The returned caption data.
		 *
		 * @return {array} The filtered caption data.
		 */
		$caption = apply_filters( 'classifai_computer_vision_captions', $caption );

		// Process the returned caption to see if it passes the threshold.
		if ( is_array( $caption ) && ! empty( $caption ) ) {
			$settings  = $feature->get_settings( static::ID );
			$threshold = $settings['descriptive_confidence_threshold'];

			// Check the caption to see if it passes the threshold.
			if ( isset( $caption['confidence'] ) && $caption['confidence'] * 100 > $threshold ) {
				$rtn = ucfirst( $caption['text'] ?? '' );
			} else {
				/* translators: 1: Confidence score, 2: Threshold setting */
				$rtn = new WP_Error( 'threshold', sprintf( esc_html__( 'Caption confidence score is %1$d%% which is lower than your threshold setting of %2$d%%', 'classifai' ), $caption['confidence'] * 100, $threshold ) );

				/**
				 * Fires if there were no captions returned.
				 *
				 * @since 1.5.0
				 * @hook classifai_computer_vision_caption_failed
				 *
				 * @param {array} $caption   The caption data.
				 * @param {int}   $threshold The caption_threshold setting.
				 */
				do_action( 'classifai_computer_vision_caption_failed', $caption, $threshold );
			}

			// Save full results for later.
			update_post_meta( $attachment_id, 'classifai_computer_vision_captions', $caption );
		}

		return $rtn;
	}

	/**
	 * Read PDF content and update the description of attachment.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return string|WP_Error
	 */
	public function read_pdf( int $attachment_id ) {
		$feature         = new PDFTextExtraction();
		$settings        = $feature->get_settings( static::ID );
		$should_read_pdf = $feature->is_feature_enabled();

		if ( ! $should_read_pdf ) {
			return new WP_Error( 'not_enabled', esc_html__( 'PDF Text Extraction is disabled. Please check your settings.', 'classifai' ) );
		}

		// Direct file system access is required for the current implementation of this feature.
		if ( ! function_exists( 'get_filesystem_method' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		$access_type = get_filesystem_method();

		if ( 'direct' !== $access_type || ! WP_Filesystem() ) {
			return new WP_Error( 'invalid_access_type', 'Invalid access type! Direct file system access is required.' );
		}

		$read = new Read( $settings, intval( $attachment_id ), $feature );

		return $read->read_document();
	}

	/**
	 * Generate the image tags for the passed in image.
	 *
	 * @param string $image_url URL of image to process.
	 * @param int    $attachment_id Post ID for the attachment.
	 * @return array|WP_Error
	 */
	public function generate_image_tags( string $image_url, int $attachment_id ) {
		if ( ! wp_attachment_is_image( $attachment_id ) ) {
			return new WP_Error( 'invalid', esc_html__( 'This attachment can\'t be processed.', 'classifai' ) );
		}

		$rtn      = [];
		$feature  = new ImageTagsGenerator();
		$settings = $feature->get_settings( static::ID );

		$details = $this->scan_image( $image_url, $feature );

		if ( is_wp_error( $details ) ) {
			return $details;
		}

		$tags = $details->tagsResult->values ?? []; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

		set_transient( 'classifai_azure_computer_vision_image_tags_latest_response', $details, DAY_IN_SECONDS * 30 );

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

		// Process the returned tags to see if they pass the threshold.
		if ( is_array( $tags ) && ! empty( $tags ) ) {
			$threshold   = $settings['tag_confidence_threshold'];
			$custom_tags = [];

			// Save each tag if it passes the threshold.
			foreach ( $tags as $tag ) {
				if ( $tag->confidence * 100 > $threshold ) {
					$custom_tags[] = $tag->name;
				}
			}

			if ( ! empty( $custom_tags ) ) {
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

			// Save all the tags for later.
			update_post_meta( $attachment_id, 'classifai_computer_vision_image_tags', $tags );
		}

		return $rtn;
	}

	/**
	 * Scan the image and return the results.
	 *
	 * @param string                      $image_url Path to the uploaded image.
	 * @param \Classifai\Features\Feature $feature   Feature instance
	 * @return bool|object|WP_Error
	 */
	protected function scan_image( string $image_url, ?\Classifai\Features\Feature $feature = null ) {
		$settings = $feature->get_settings( static::ID );

		// Check if valid authentication is in place.
		if ( ! $feature->is_feature_enabled() ) {
			return new WP_Error( 'feature_disabled', esc_html__( 'Feature not enabled.', 'classifai' ) );
		}

		$endpoint_url = $this->prep_api_url( $feature );

		/*
		 * Azure AI Vision requires full image URL. So, if the file URL is relative,
		 * then we transform it into a full URL.
		 */
		if ( '/' === substr( $image_url, 0, 1 ) ) {
			$image_url = get_site_url() . $image_url;
		}

		$response = wp_remote_post(
			$endpoint_url,
			[
				'headers' => [
					'Ocp-Apim-Subscription-Key' => $settings['api_key'],
					'Content-Type'              => 'application/json',
				],
				/**
				 * Filters the timeout for the image scan request.
				 *
				 * Default: 60 seconds.
				 *
				 * @since 3.1.0
				 * @hook classifai_ms_computer_vision_scan_image_timeout
				 *
				 * @param {int} $timeout Timeout in seconds.
				 *
				 * @return {int} Timeout in seconds.
				 */
				'timeout' => apply_filters(
					'classifai_' . self::ID . '_scan_image_timeout',
					60
				),
				'body'    => '{"url":"' . $image_url . '"}',
			]
		);

		if ( ! is_wp_error( $response ) ) {
			$body = json_decode( wp_remote_retrieve_body( $response ) );

			if ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
				if ( isset( $body->error ) ) {
					$rtn = new WP_Error( $body->error->code ?? 'error', $body->error->message ?? esc_html__( 'An error occurred.', 'classifai' ), $body );
				} elseif ( isset( $body->message ) ) {
					$rtn = new WP_Error( $body->code ?? 'error', $body->message, $body );
				} else {
					$rtn = new WP_Error( 'error', esc_html__( 'An error occurred.', 'classifai' ), $body );
				}
			} else {
				$rtn = $body;
			}
		} else {
			$rtn = $response;
		}

		return $rtn;
	}

	/**
	 * Build and return the API endpoint based on settings.
	 *
	 * @param \Classifai\Features\Feature $feature Feature instance
	 * @return string
	 */
	protected function prep_api_url( ?\Classifai\Features\Feature $feature = null ): string {
		$settings     = $feature->get_settings( static::ID );
		$api_features = [];

		if ( $feature instanceof DescriptiveTextGenerator && $feature->is_feature_enabled() && ! empty( $feature->get_alt_text_settings() ) ) {
			$api_features[] = 'caption';
		}

		if ( $feature instanceof ImageTagsGenerator && $feature->is_feature_enabled() ) {
			$api_features[] = 'tags';
		}

		if ( $feature instanceof ImageTextExtraction && $feature->is_feature_enabled() ) {
			$api_features[] = 'read';
		}

		$endpoint = add_query_arg( 'features', implode( ',', $api_features ), trailingslashit( $settings['endpoint_url'] ) . $this->analyze_url );

		return $endpoint;
	}

	/**
	 * Authenticates our credentials.
	 *
	 * @param string $url     Endpoint URL.
	 * @param string $api_key Api Key.
	 * @return bool|WP_Error
	 */
	protected function authenticate_credentials( string $url, string $api_key ) {
		$rtn     = false;
		$request = wp_remote_post(
			trailingslashit( $url ) . $this->analyze_url,
			[
				'headers' => [
					'Ocp-Apim-Subscription-Key' => $api_key,
					'Content-Type'              => 'application/json',
				],
				'body'    => '{"url":"https://classifaiplugin.com/wp-content/themes/fse-classifai-theme/assets/img/header.png"}',
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
	 * Common entry point for all REST endpoints for this provider.
	 *
	 * @param int    $attachment_id The attachment ID we're processing.
	 * @param string $route_to_call The name of the route we're going to be processing.
	 * @param array  $args          Optional arguments to pass to the route.
	 * @return array|string|WP_Error|null
	 */
	public function rest_endpoint_callback( $attachment_id, string $route_to_call = '', array $args = [] ) {
		// Check to be sure the post both exists and is an attachment.
		if ( ! get_post( $attachment_id ) || 'attachment' !== get_post_type( $attachment_id ) ) {
			/* translators: %1$s: the attachment ID */
			return new WP_Error( 'incorrect_ID', sprintf( esc_html__( '%1$d is not found or is not an attachment', 'classifai' ), $attachment_id ), [ 'status' => 404 ] );
		}

		if ( 'read_pdf' === $route_to_call ) {
			return $this->read_pdf( $attachment_id );
		}

		$metadata = wp_get_attachment_metadata( $attachment_id );

		if ( ! $metadata || ! is_array( $metadata ) ) {
			return new WP_Error( 'invalid', esc_html__( 'No valid metadata found.', 'classifai' ) );
		}

		if ( 'crop' === $route_to_call ) {
			return $this->smart_crop_image( $metadata, $attachment_id );
		}

		// Check if the image is of a type we can process.
		$mime_type          = get_post_mime_type( $attachment_id );
		$matched_extensions = explode( '|', array_search( $mime_type, wp_get_mime_types(), true ) );
		$process            = false;

		foreach ( $matched_extensions as $ext ) {
			if ( in_array( $ext, $this->image_types_to_process, true ) ) {
				$process = true;
				break;
			}
		}

		if ( ! $process ) {
			return new WP_Error( 'invalid', esc_html__( 'Image does not match a valid mime type.', 'classifai' ) );
		}

		$image_url = get_modified_image_source_url( $attachment_id );

		if ( empty( $image_url ) || ! filter_var( $image_url, FILTER_VALIDATE_URL ) ) {
			if ( isset( $metadata['sizes'] ) && is_array( $metadata['sizes'] ) ) {
				$image_url = get_largest_size_and_dimensions_image_url(
					get_attached_file( $attachment_id ),
					wp_get_attachment_url( $attachment_id ),
					$metadata,
					[ 50, 16000 ],
					[ 50, 16000 ],
					computer_vision_max_filesize()
				);
			} else {
				$image_url = wp_get_attachment_url( $attachment_id );
			}
		}

		if ( empty( $image_url ) ) {
			return new WP_Error( 'error', esc_html__( 'Image does not meet size requirements. Please ensure it is at least 50x50 but less than 16000x16000 and smaller than 20MB.', 'classifai' ) );
		}

		switch ( $route_to_call ) {
			case 'descriptive_text':
				return $this->generate_alt_tags( $image_url, $attachment_id );

			case 'ocr':
				return $this->ocr_processing( $image_url, $attachment_id );

			case 'tags':
				return $this->generate_image_tags( $image_url, $attachment_id );
		}
	}

	/**
	 * Filter the SQL clauses of an attachment query to include tags and alt text.
	 *
	 * @param array $clauses An array including WHERE, GROUP BY, JOIN, ORDER BY,
	 *                       DISTINCT, fields (SELECT), and LIMITS clauses.
	 * @return array The modified clauses.
	 */
	public function filter_attachment_query_keywords( array $clauses ): array {
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
	 * Returns the debug information for the provider settings.
	 *
	 * @return array
	 */
	public function get_debug_information(): array {
		$settings          = [];
		$provider_settings = [];
		$debug_info        = [];

		if ( $this->feature_instance ) {
			$settings          = $this->feature_instance->get_settings();
			$provider_settings = $settings[ static::ID ];
		}

		if ( $this->feature_instance instanceof DescriptiveTextGenerator ) {
			if ( ! isset( $provider_settings['descriptive_text_fields'] ) || ! is_array( $provider_settings['descriptive_text_fields'] ) ) {
				$provider_settings['descriptive_text_fields'] = array(
					'alt'         => 0,
					'caption'     => 0,
					'description' => 0,
				);
			}

			$descriptive_text = array_filter(
				$provider_settings['descriptive_text_fields'],
				function ( $type ) {
					return '0' !== $type;
				}
			);

			$debug_info[ __( 'Generate descriptive text', 'classifai' ) ] = implode( ', ', $descriptive_text );
			$debug_info[ __( 'Confidence threshold', 'classifai' ) ]      = $provider_settings['descriptive_confidence_threshold'];
			$debug_info[ __( 'Latest response:', 'classifai' ) ]          = $this->get_formatted_latest_response( get_transient( 'classifai_azure_computer_vision_descriptive_text_latest_response' ) );
		}

		if ( $this->feature_instance instanceof ImageTagsGenerator ) {
			$debug_info[ __( 'Tag taxonomy', 'classifai' ) ]         = $provider_settings['tag_taxonomy'] ?? 'image_tags';
			$debug_info[ __( 'Confidence threshold', 'classifai' ) ] = $provider_settings['tag_confidence_threshold'];
			$debug_info[ __( 'Latest response:', 'classifai' ) ]     = $this->get_formatted_latest_response( get_transient( 'classifai_azure_computer_vision_image_tags_latest_response' ) );
		}

		if ( $this->feature_instance instanceof ImageCropping ) {
			$debug_info[ __( 'Latest response:', 'classifai' ) ] = $this->get_formatted_latest_response( get_transient( 'classifai_azure_computer_vision_image_cropping_latest_response' ) );
		}

		if ( $this->feature_instance instanceof ImageTextExtraction ) {
			$debug_info[ __( 'Latest response:', 'classifai' ) ] = $this->get_formatted_latest_response( get_transient( 'classifai_azure_computer_vision_image_text_extraction_latest_response' ) );
		}

		if ( $this->feature_instance instanceof PDFTextExtraction ) {
			$debug_info[ __( 'Latest response:', 'classifai' ) ] = $this->get_formatted_latest_response( get_transient( 'classifai_azure_computer_vision_pdf_text_extraction_check_result_latest_response' ) );
		}

		return apply_filters(
			'classifai_' . self::ID . '_debug_information',
			$debug_info,
			$settings,
		);
	}
}
