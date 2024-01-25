<?php
/**
 * Azure Computer vision
 */

namespace Classifai\Providers\Azure;

use Classifai\Features\DescriptiveTextGenerator;
use Classifai\Features\ImageTagsGenerator;
use Classifai\Features\ImageTextExtraction;
use Classifai\Features\PDFTextExtraction;
use Classifai\Features\ImageCropping;
use Classifai\Providers\Provider;
use DOMDocument;
use WP_Error;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;

use function Classifai\computer_vision_max_filesize;
use function Classifai\get_largest_acceptable_image_url;
use function Classifai\get_modified_image_source_url;
use function Classifai\attachment_is_pdf;
use function Classifai\check_term_permissions;
use function Classifai\get_asset_info;
use function Classifai\clean_input;

class ComputerVision extends Provider {

	const ID = 'ms_computer_vision';

	/**
	 * @var string URL fragment to the analyze API endpoint
	 */
	protected $analyze_url = 'vision/v3.0/analyze';

	/**
	 * ComputerVision constructor.
	 *
	 * @param \Classifai\Features\Feature $feature_instance The feature instance.
	 */
	public function __construct( $feature_instance = null ) {
		parent::__construct(
			'Microsoft Azure',
			'AI Vision',
			'computer_vision'
		);

		$this->feature_instance = $feature_instance;
	}

	/**
	 * Resets settings for the ComputerVision provider.
	 */
	public function reset_settings() {
		update_option( $this->get_option_name(), $this->get_default_settings() );
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
				'description'   => __( 'Supported protocol and hostname endpoints, e.g., <code>https://REGION.api.cognitive.microsoft.com</code> or <code>https://EXAMPLE.cognitiveservices.azure.com</code>. This can look different based on your setting choices in Azure.', 'classifai' ),
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

		do_action( 'classifai_' . static::ID . '_render_provider_fields', $this );
	}

	/**
	 * Renders fields for the Descriptive Text Feature.
	 */
	public function add_descriptive_text_generation_fields() {
		$settings = $this->feature_instance->get_settings( static::ID );

		$checkbox_options = array(
			'alt'         => esc_html__( 'Alt text', 'classifai' ),
			'caption'     => esc_html__( 'Image caption', 'classifai' ),
			'description' => esc_html__( 'Image description', 'classifai' ),
		);

		add_settings_field(
			static::ID . '_descriptive_text_fields',
			esc_html__( 'Generate descriptive text', 'classifai' ),
			[ $this->feature_instance, 'render_checkbox_group' ],
			$this->feature_instance->get_option_name(),
			$this->feature_instance->get_option_name() . '_section',
			[
				'option_index'   => static::ID,
				'label_for'      => 'descriptive_text_fields',
				'options'        => $checkbox_options,
				'default_values' => $settings['descriptive_text_fields'],
				'description'    => __( 'Choose image fields where the generated captions should be applied.', 'classifai' ),
				'class'          => 'classifai-provider-field hidden provider-scope-' . static::ID, // Important to add this.
			]
		);

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
				'description'   => esc_html__( 'Minimum confidence score for automatically added alt text, numeric value from 0-100. Recommended to be set to at least 75.', 'classifai' ),
				'class'         => 'classifai-provider-field hidden provider-scope-' . static::ID, // Important to add this.
			]
		);
	}

	/**
	 * Renders fields for the Image Tags Feature.
	 */
	public function add_image_tags_generation_fields() {
		$settings = $this->feature_instance->get_settings( static::ID );

		$attachment_taxonomies = get_object_taxonomies( 'attachment', 'objects' );
		$options               = [];

		foreach ( $attachment_taxonomies as $name => $taxonomy ) {
			$options[ $name ] = $taxonomy->label;
		}

		add_settings_field(
			static::ID . '_tag_taxonomy',
			esc_html__( 'Tag taxonomy', 'classifai' ),
			[ $this->feature_instance, 'render_select' ],
			$this->feature_instance->get_option_name(),
			$this->feature_instance->get_option_name() . '_section',
			[
				'option_index'  => static::ID,
				'label_for'     => 'tag_taxonomy',
				'options'       => $options,
				'default_value' => $settings['tag_taxonomy'],
				'class'         => 'classifai-provider-field hidden provider-scope-' . static::ID, // Important to add this.
			]
		);

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
						'descriptive_text_fields'          => [
							'alt'         => 0,
							'caption'     => 0,
							'description' => 0,
						],
						'descriptive_confidence_threshold' => 75,
					]
				);

			case ImageTagsGenerator::ID:
				$attachment_taxonomies = get_object_taxonomies( 'attachment', 'objects' );
				$options               = [];

				foreach ( $attachment_taxonomies as $name => $taxonomy ) {
					$options[ $name ] = $taxonomy->label;
				}

				return array_merge(
					$common_settings,
					[
						'tag_confidence_threshold' => 70,
						'tag_taxonomy'             => array_key_first( $options ),
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
			$new_settings[ static::ID ]['descriptive_text_fields']          = array_map( 'sanitize_text_field', $new_settings[ static::ID ]['descriptive_text_fields'] ?? $settings[ static::ID ]['descriptive_text_fields'] );
		}

		if ( $this->feature_instance instanceof ImageTagsGenerator ) {
			$new_settings[ static::ID ]['tag_confidence_threshold'] = absint( $new_settings[ static::ID ]['tag_confidence_threshold'] ?? $settings[ static::ID ]['tag_confidence_threshold'] );
			$new_settings[ static::ID ]['tag_taxonomy']             = $new_settings[ static::ID ]['tag_taxonomy'] ?? $settings[ static::ID ]['tag_taxonomy'];
		}

		return $new_settings;
	}

	/**
	 * Returns an array of fields enabled to be set to store image captions.
	 *
	 * @return array
	 */
	public function get_alt_text_settings(): array {
		$alt_generator  = new DescriptiveTextGenerator();
		$settings       = $alt_generator->get_settings( static::ID );
		$enabled_fields = array();

		if ( ! isset( $settings['descriptive_text_fields'] ) ) {
			return array();
		}

		if ( ! is_array( $settings['descriptive_text_fields'] ) ) {
			return array(
				'alt'         => 'no' === $settings['descriptive_text_fields']['caption'] ? 0 : 'alt',
				'caption'     => 0,
				'description' => 0,
			);
		}

		foreach ( $settings['descriptive_text_fields'] as $key => $value ) {
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
		add_filter( 'posts_clauses', [ $this, 'filter_attachment_query_keywords' ], 10, 1 );
		add_action( 'rest_api_init', [ $this, 'register_endpoints' ] );
		add_action( 'enqueue_block_editor_assets', [ $this, 'enqueue_editor_assets' ] );

		if ( ( new ImageCropping() )->is_feature_enabled() ) {
			add_filter( 'wp_generate_attachment_metadata', [ $this, 'smart_crop_image' ], 7, 2 );
		}

		if ( ( new DescriptiveTextGenerator() )->is_feature_enabled() ) {
			add_filter( 'wp_generate_attachment_metadata', [ $this, 'generate_image_alt_tags' ], 8, 2 );
		}

		if ( ( new PDFTextExtraction() )->is_feature_enabled() ) {
			add_action( 'add_attachment', [ $this, 'read_pdf' ] );
			add_action( 'classifai_retry_get_read_result', [ $this, 'do_read_cron' ], 10, 2 );
			add_action( 'wp_ajax_classifai_get_read_status', [ $this, 'get_read_status_ajax' ] );
		}

		if ( ( new ImageTextExtraction() )->is_feature_enabled() ) {
			add_filter( 'the_content', [ $this, 'add_ocr_aria_describedby' ] );
			add_filter( 'rest_api_init', [ $this, 'add_ocr_data_to_api_response' ] );
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
			'editor-ocr',
			CLASSIFAI_PLUGIN_URL . 'dist/editor-ocr.js',
			array_merge( get_asset_info( 'editor-ocr', 'dependencies' ), array( 'lodash' ) ),
			get_asset_info( 'editor-ocr', 'version' ),
			true
		);
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
	 * Adds a meta box for rescanning options if the settings are configured
	 *
	 * @param \WP_Post $post The post object.
	 */
	public function setup_attachment_meta_box( \WP_Post $post ) {
		if (
			wp_attachment_is_image( $post ) &&
			(
				( new DescriptiveTextGenerator() )->is_feature_enabled() ||
				( new ImageTagsGenerator() )->is_feature_enabled() ||
				( new ImageTextExtraction() )->is_feature_enabled() ||
				( new ImageCropping() )->is_feature_enabled()
			)
		) {
			add_meta_box(
				'attachment_meta_box',
				__( 'ClassifAI Image Processing', 'classifai' ),
				[ $this, 'attachment_data_meta_box' ],
				'attachment',
				'side',
				'high'
			);
		}

		if ( ( new PDFTextExtraction() )->is_feature_enabled() && attachment_is_pdf( $post ) ) {
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
	public function attachment_data_meta_box( \WP_Post $post ) {
		$alt_generator  = new DescriptiveTextGenerator();
		$image_tagging  = new ImageTagsGenerator();
		$image_to_text  = new ImageTextExtraction();
		$crop_generator = new ImageCropping();

		$captions   = get_post_meta( $post->ID, '_wp_attachment_image_alt', true ) ? __( 'No descriptive text? Rescan image', 'classifai' ) : __( 'Generate descriptive text', 'classifai' );
		$tags       = ! empty( wp_get_object_terms( $post->ID, 'classifai-image-tags' ) ) ? __( 'Rescan image for new tags', 'classifai' ) : __( 'Generate image tags', 'classifai' );
		$ocr        = get_post_meta( $post->ID, 'classifai_computer_vision_ocr', true ) ? __( 'Rescan for text', 'classifai' ) : __( 'Scan image for text', 'classifai' );
		$smart_crop = get_transient( 'classifai_azure_computer_vision_image_cropping_latest_response' ) ? __( 'Regenerate smart thumbnail', 'classifai' ) : __( 'Create smart thumbnail', 'classifai' );
		?>

		<div class="misc-publishing-actions">
			<?php if ( $alt_generator->is_feature_enabled() && ! empty( $this->get_alt_text_settings() ) ) : ?>
				<div class="misc-pub-section">
					<label for="rescan-captions">
						<input type="checkbox" value="yes" id="rescan-captions" name="rescan-captions"/>
						<?php echo esc_html( $captions ); ?>
					</label>
				</div>
			<?php endif; ?>

			<?php if ( $image_tagging->is_feature_enabled() ) : ?>
				<div class="misc-pub-section">
					<label for="rescan-tags">
						<input type="checkbox" value="yes" id="rescan-tags" name="rescan-tags"/>
						<?php echo esc_html( $tags ); ?>
					</label>
				</div>
			<?php endif; ?>

			<?php if ( $image_to_text->is_feature_enabled() ) : ?>
				<div class="misc-pub-section">
					<label for="rescan-ocr">
						<input type="checkbox" value="yes" id="rescan-ocr" name="rescan-ocr"/>
						<?php echo esc_html( $ocr ); ?>
					</label>
				</div>
			<?php endif; ?>

			<?php if ( $crop_generator->is_feature_enabled() ) : ?>
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
	public function attachment_pdf_data_meta_box( \WP_Post $post ) {
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
	 * @param \WP_Post $post        Post object for the attachment being viewed.
	 */
	public function add_rescan_button_to_media_modal( array $form_fields, \WP_Post $post ) {
		$pdf_to_text   = new PDFTextExtraction();
		$alt_generator = new DescriptiveTextGenerator();
		$image_to_text = new ImageTextExtraction();
		$smart_crop    = new ImageCropping();
		$image_tagging = new ImageTagsGenerator();

		// PDF to text.
		if ( $pdf_to_text->is_feature_enabled() && attachment_is_pdf( $post ) ) {
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

		// Description generator.
		if ( $alt_generator->is_feature_enabled() && wp_attachment_is_image( $post ) ) {
			if ( ! empty( $this->get_alt_text_settings() ) ) {
				$alt_tags_text                  = empty( get_post_meta( $post->ID, '_wp_attachment_image_alt', true ) ) ? __( 'Generate', 'classifai' ) : __( 'Rescan', 'classifai' );
				$form_fields['rescan_alt_tags'] = [
					'label'        => __( 'Descriptive text', 'classifai' ),
					'input'        => 'html',
					'html'         => '<button class="button secondary" id="classifai-rescan-alt-tags" data-id="' . esc_attr( absint( $post->ID ) ) . '">' . esc_html( $alt_tags_text ) . '</button><span class="spinner" style="display:none;float:none;"></span><span class="error" style="display:none;color:#bc0b0b;padding:5px;"></span>',
					'show_in_edit' => false,
				];
			}
		}

		// Image tagging.
		if ( $image_tagging->is_feature_enabled() && wp_attachment_is_image( $post ) ) {
			$image_tags_text                = empty( wp_get_object_terms( $post->ID, 'classifai-image-tags' ) ) ? __( 'Generate', 'classifai' ) : __( 'Rescan', 'classifai' );
			$form_fields['rescan_captions'] = [
				'label'        => __( 'Image tags', 'classifai' ),
				'input'        => 'html',
				'html'         => '<button class="button secondary" id="classifai-rescan-image-tags" data-id="' . esc_attr( absint( $post->ID ) ) . '">' . esc_html( $image_tags_text ) . '</button><span class="spinner" style="display:none;float:none;"></span><span class="error" style="display:none;color:#bc0b0b;padding:5px;"></span>',
				'show_in_edit' => false,
			];
		}

		// Smart crop.
		if ( $smart_crop->is_feature_enabled() && wp_attachment_is_image( $post ) ) {
			$smart_crop_text                  = empty( get_transient( 'classifai_azure_computer_vision_image_cropping_latest_response' ) ) ? __( 'Generate', 'classifai' ) : __( 'Regenerate', 'classifai' );
			$form_fields['rescan_smart_crop'] = [
				'label'        => __( 'Smart thumbnail', 'classifai' ),
				'input'        => 'html',
				'html'         => '<button class="button secondary" id="classifai-rescan-smart-crop" data-id="' . esc_attr( absint( $post->ID ) ) . '">' . esc_html( $smart_crop_text ) . '</button><span class="spinner" style="display:none;float:none;"></span><span class="error" style="display:none;color:#bc0b0b;padding:5px;"></span>',
				'show_in_edit' => false,
			];
		}

		// Image to text.
		if ( $image_to_text->is_feature_enabled() && wp_attachment_is_image( $post ) ) {
			$ocr_text                  = empty( get_post_meta( $post->ID, 'classifai_computer_vision_ocr', true ) ) ? __( 'Scan', 'classifai' ) : __( 'Rescan', 'classifai' );
			$form_fields['rescan_ocr'] = [
				'label'        => __( 'Scan image for text', 'classifai' ),
				'input'        => 'html',
				'html'         => '<button class="button secondary" id="classifai-rescan-ocr" data-id="' . esc_attr( absint( $post->ID ) ) . '">' . esc_html( $ocr_text ) . '</button><span class="spinner" style="display:none;float:none;"></span><span class="error" style="display:none;color:#bc0b0b;padding:5px;"></span>',
				'show_in_edit' => false,
			];
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
	 * @return array|null Read and running status.
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
	 * Determine if we need to rescan the image.
	 *
	 * @param int $attachment_id Post id for the attachment
	 */
	public function maybe_rescan_image( int $attachment_id ) {
		if ( clean_input( 'rescan-pdf' ) ) {
			$this->read_pdf( $attachment_id );
			return; // We can exit early, if this is a call for PDF scanning - everything else relates to images.
		}

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

		if ( clean_input( 'rescan-smart-crop' ) ) {
			$feature = new ImageCropping();
			$feature->run( $metadata, $attachment_id );
		}

		if ( clean_input( 'rescan-tags' ) ) {
			$feature = new ImageTagsGenerator();
			$feature->run( $attachment_id );
		}

		if ( clean_input( 'rescan-captions' ) ) {
			$feature = new DescriptiveTextGenerator();
			$feature->run( $attachment_id );
		}

		// Are we updating the OCR text?
		if ( clean_input( 'rescan-ocr' ) ) {
			$feature = new ImageTextExtraction();
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
	public function smart_crop_image( $metadata, int $attachment_id ): array {
		$feature  = new ImageCropping();
		$settings = $feature->get_settings( static::ID );

		if ( ! is_array( $metadata ) || ! is_array( $settings ) ) {
			return $metadata;
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

		$smart_cropping = new \Classifai\Providers\Azure\SmartCropping( $settings );

		return $smart_cropping->generate_attachment_metadata( $metadata, intval( $attachment_id ) );
	}

	/**
	 * Generate the alt tags for the image being uploaded.
	 *
	 * @param array $metadata      The metadata for the image.
	 * @param int   $attachment_id Post ID for the attachment.
	 * @return mixed
	 */
	public function generate_image_alt_tags( array $metadata, int $attachment_id ) {
		$feature = new ImageTagsGenerator();

		if ( $feature->is_feature_enabled() ) {

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

			$feature->run( $attachment_id );
		}

		$feature = new DescriptiveTextGenerator();

		if ( $feature->is_feature_enabled() && ! empty( $this->get_alt_text_settings() ) ) {
			$feature->run( $attachment_id );
		}

		return $metadata;
	}

	/**
	 * Runs text recognition on the attachment.
	 *
	 * @since 1.6.0
	 *
	 * @filter wp_generate_attachment_metadata
	 *
	 * @param array   $metadata Attachment metadata.
	 * @param int     $attachment_id Attachment ID.
	 * @param boolean $force Whether to force processing or not. Default false.
	 * @return array Filtered attachment metadata.
	 */
	public function ocr_processing( array $metadata = [], int $attachment_id = 0, bool $force = false ): array {
		$feature  = new ImageTextExtraction();
		$settings = $feature->get_settings( static::ID );

		if ( ! is_array( $metadata ) || ! is_array( $settings ) ) {
			return $metadata;
		}

		$should_ocr_scan = $feature->is_feature_enabled();

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

		$image_url = wp_get_attachment_url( $attachment_id );
		$scan      = $this->scan_image( $image_url, $feature );

		$ocr      = new OCR( $settings, $scan, $force );
		$response = $ocr->generate_ocr_data( $metadata, $attachment_id );

		set_transient( 'classifai_azure_computer_vision_image_text_extraction_latest_response', $scan, DAY_IN_SECONDS * 30 );

		if ( $force ) {
			return $response;
		}

		return $metadata;
	}

	/**
	 * Scan the image and return the captions.
	 *
	 * @param string                      $image_url Path to the uploaded image.
	 * @param \Classifai\Features\Feature $feature   Feature instance
	 * @return bool|object|WP_Error
	 */
	protected function scan_image( string $image_url, \Classifai\Features\Feature $feature = null ) {
		$settings = $feature->get_settings( static::ID );

		// Check if valid authentication is in place.
		if ( ! $feature->is_feature_enabled() ) {
			return new WP_Error( 'feature_disabled', esc_html__( 'Feature not enabled.', 'classifai' ) );
		}

		$endpoint_url = $this->prep_api_url( $feature );

		/*
		 * MS Computer Vision requires full image URL. So, if the file URL is relative,
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
				'body'    => '{"url":"' . $image_url . '"}',
			]
		);

		if ( ! is_wp_error( $response ) ) {
			$body = json_decode( wp_remote_retrieve_body( $response ) );

			if ( 200 !== wp_remote_retrieve_response_code( $response ) && isset( $body->message ) ) {
				$rtn = new WP_Error( $body->code ?? 'error', $body->message, $body );
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
	protected function prep_api_url( \Classifai\Features\Feature $feature = null ): string {
		$settings     = $feature->get_settings( static::ID );
		$api_features = [];

		if ( $feature instanceof DescriptiveTextGenerator && $feature->is_feature_enabled() && ! empty( $this->get_alt_text_settings() ) ) {
			$api_features[] = 'Description';
		}

		if ( $feature instanceof ImageTagsGenerator && $feature->is_feature_enabled() ) {
			$api_features[] = 'Tags';
		}

		$endpoint = add_query_arg( 'visualFeatures', implode( ',', $api_features ), trailingslashit( $settings['endpoint_url'] ) . $this->analyze_url );

		return $endpoint;
	}

	/**
	 * Generate the alt tags for the image being uploaded.
	 *
	 * @param int $attachment_id Post ID for the attachment.
	 * @return string|WP_Error
	 */
	public function generate_alt_tags( int $attachment_id ) {
		$rtn = '';

		$enabled_fields = $this->get_alt_text_settings();
		$feature        = new DescriptiveTextGenerator();
		$image_url      = wp_get_attachment_url( $attachment_id );
		$details        = $this->scan_image( $image_url, $feature );
		$captions       = isset( $details->description->captions ) ? $details->description->captions : [];

		set_transient( 'classifai_azure_computer_vision_descriptive_text_latest_response', $details, DAY_IN_SECONDS * 30 );

		// Don't save tags if feature is disabled or user don't have access to use it.
		if ( ! $this->is_feature_enabled( 'image_captions' ) ) {
			return new WP_Error( 'invalid_settings', esc_html__( 'Image descriptive text feature is disabled.', 'classifai' ) );
		}

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
			$settings  = $feature->get_settings( static::ID );
			$threshold = $settings['descriptive_confidence_threshold'];

			// Save the first caption as the alt text if it passes the threshold.
			if ( $captions[0]->confidence * 100 > $threshold ) {
				if ( in_array( 'alt', $enabled_fields, true ) ) {
					update_post_meta( $attachment_id, '_wp_attachment_image_alt', $captions[0]->text );
				}

				$excerpt = get_the_excerpt( $attachment_id );

				if ( in_array( 'caption', $enabled_fields, true ) && $excerpt !== $captions[0]->text ) {
					wp_update_post(
						array(
							'ID'           => $attachment_id,
							'post_excerpt' => $captions[0]->text,
						)
					);
				}

				$content = get_the_content( null, false, $attachment_id );

				if ( in_array( 'description', $enabled_fields, true ) && $content !== $captions[0]->text ) {
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
	public function read_pdf( int $attachment_id ) {
		$feature         = new PDFTextExtraction();
		$settings        = $feature->get_settings( static::ID );
		$should_read_pdf = $feature->is_feature_enabled();

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
	public function do_read_cron( string $operation_url, int $attachment_id ) {
		$settings = ( new PDFTextExtraction() )->get_settings( static::ID );

		( new Read( $settings, intval( $attachment_id ) ) )->check_read_result( $operation_url );
	}

	/**
	 * Generate the image tags for the image being uploaded.
	 *
	 * @param int $attachment_id Post ID for the attachment.
	 * @return string|array|WP_Error
	 */
	public function generate_image_tags( int $attachment_id ) {
		$rtn      = '';
		$feature  = new ImageTagsGenerator();
		$settings = $feature->get_settings( static::ID );

		// Don't save tags if the setting is disabled.
		if ( ! $feature->is_feature_enabled() ) {
			return new WP_Error( 'invalid_settings', esc_html__( 'Image tagging is disabled.', 'classifai' ) );
		}

		$image_url = wp_get_attachment_url( $attachment_id );
		$details   = $this->scan_image( $image_url, $feature );
		$tags      = isset( $details->tags ) ? $details->tags : [];

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

		// If $tags isn't an array, don't save them.
		if ( is_array( $tags ) && ! empty( $tags ) ) {
			$threshold   = $settings['tag_confidence_threshold'];
			$taxonomy    = $settings['tag_taxonomy'];
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
	public function setup_fields_sections() {}

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
	 * Register the REST API endpoints for this provider.
	 */
	public function register_endpoints() {
		register_rest_route(
			'classifai/v1',
			'alt-tags/(?P<id>\d+)',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'computer_vision_endpoint_callback' ],
				'args'                => [
					'id'    => [
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
						'description'       => esc_html__( 'Image ID to generate alt text for.', 'classifai' ),
					],
					'route' => [ 'alt-tags' ],
				],
				'permission_callback' => [ $this, 'descriptive_text_generator_permissions_check' ],
			]
		);

		register_rest_route(
			'classifai/v1',
			'image-tags/(?P<id>\d+)',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'computer_vision_endpoint_callback' ],
				'args'                => [
					'id'    => [
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
						'description'       => esc_html__( 'Image ID to generate alt text for.', 'classifai' ),
					],
					'route' => [ 'image-tags' ],
				],
				'permission_callback' => [ $this, 'image_tags_generator_permissions_check' ],
			]
		);

		register_rest_route(
			'classifai/v1',
			'smart-crop/(?P<id>\d+)',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'computer_vision_endpoint_callback' ],
				'args'                => [
					'id'    => [
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
						'description'       => esc_html__( 'Image ID to generate smart crop.', 'classifai' ),
					],
					'route' => [ 'smart-crop' ],
				],
				'permission_callback' => [ $this, 'smart_crop_permissions_check' ],
			]
		);

		register_rest_route(
			'classifai/v1',
			'read-pdf/(?P<id>\d+)',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'computer_vision_endpoint_callback' ],
				'args'                => [
					'id'    => [
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
						'description'       => esc_html__( 'Image ID to generate alt text for.', 'classifai' ),
					],
					'route' => [ 'read-pdf' ],
				],
				'permission_callback' => [ $this, 'pdf_read_permissions_check' ],
			]
		);

		register_rest_route(
			'classifai/v1',
			'ocr/(?P<id>\d+)',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'computer_vision_endpoint_callback' ],
				'args'                => [
					'id'    => [
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
						'description'       => esc_html__( 'Image ID to generate text from.', 'classifai' ),
					],
					'route' => [ 'ocr' ],
				],
				'permission_callback' => [ $this, 'image_text_extractor_permissions_check' ],
			]
		);
	}

	/**
	 * REST request callback for Computer Vision features.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_Error|WP_REST_Response
	 */
	public function computer_vision_endpoint_callback( WP_REST_Request $request ) {
		$attachment_id = $request->get_param( 'id' );
		$custom_atts   = $request->get_attributes();
		$route_to_call = empty( $custom_atts['args']['route'] ) ? false : strtolower( $custom_atts['args']['route'][0] );

		// Check to be sure the post both exists and is an attachment.
		if ( ! get_post( $attachment_id ) || 'attachment' !== get_post_type( $attachment_id ) ) {
			/* translators: %1$s: the attachment ID */
			return new WP_Error( 'incorrect_ID', sprintf( esc_html__( '%1$d is not found or is not an attachment', 'classifai' ), $attachment_id ), [ 'status' => 404 ] );
		}

		// If no args, we can't pass the call into the active provider.
		if ( false === $route_to_call ) {
			return new WP_Error( 'no_route', esc_html__( 'No route indicated for the provider class to use.', 'classifai' ), [ 'status' => 404 ] );
		}

		// Call the provider endpoint function
		return rest_ensure_response( $this->rest_endpoint_callback( $attachment_id, $route_to_call ) );
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
	public function rest_endpoint_callback( $post_id, string $route_to_call = '', array $args = [] ) {
		// Check to be sure the post both exists and is an attachment.
		if ( ! get_post( $post_id ) || 'attachment' !== get_post_type( $post_id ) ) {
			/* translators: %1$s: the attachment ID */
			return new WP_Error( 'incorrect_ID', sprintf( esc_html__( '%1$d is not found or is not an attachment', 'classifai' ), $post_id ), [ 'status' => 404 ] );
		}

		$metadata  = wp_get_attachment_metadata( $post_id );
		$image_url = get_modified_image_source_url( $post_id );

		if ( 'ocr' === $route_to_call ) {
			$feature = new ImageTextExtraction();
			return $feature->run( $metadata, $post_id, true );
		}

		if ( 'read-pdf' === $route_to_call ) {
			$feature = new PDFTextExtraction();
			return $feature->run( $post_id );
		}

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

		switch ( $route_to_call ) {
			case 'alt-tags':
				$feature = new DescriptiveTextGenerator();
				return $feature->run( $post_id );

			case 'image-tags':
				$feature = new ImageTagsGenerator();
				return $feature->run( $post_id );

			case 'smart-crop':
				$feature = new ImageCropping();
				return $feature->run( $metadata, $post_id );
		}
	}

	/**
	 * REST request permissions check for DescriptiveTextGenerator feature.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return bool|WP_Error
	 */
	public function descriptive_text_generator_permissions_check( WP_REST_Request $request ) {
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

		if ( ! ( new DescriptiveTextGenerator() )->is_feature_enabled() ) {
			return new WP_Error( 'not_enabled', esc_html__( 'Image descriptive text is disabled or Microsoft Azure authentication failed. Please check your settings.', 'classifai' ) );
		}

		return true;
	}

	/**
	 * REST request permissions check for ImageTagsGenerator feature.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return bool|WP_Error
	 */
	public function image_tags_generator_permissions_check( WP_REST_Request $request ) {
		$attachment_id      = $request->get_param( 'id' );
		$post_type          = get_post_type_object( 'attachment' );
		$image_tags_feature = new ImageTagsGenerator();

		// Ensure attachments are allowed in REST endpoints.
		if ( empty( $post_type ) || empty( $post_type->show_in_rest ) ) {
			return false;
		}

		// Ensure we have a logged in user that can upload and change files.
		if ( empty( $attachment_id ) || ! current_user_can( 'edit_post', $attachment_id ) || ! current_user_can( 'upload_files' ) ) {
			return false;
		}

		if ( ! $image_tags_feature->is_feature_enabled() ) {
			return new WP_Error( 'not_enabled', esc_html__( 'Image tagging is disabled or Microsoft Azure authentication failed. Please check your settings.', 'classifai' ) );
		}

		$settings = $image_tags_feature->get_settings();
		if ( ! empty( $settings ) && isset( $settings[ static::ID ]['tag_taxonomy'] ) ) {
			$permission = check_term_permissions( $settings[ static::ID ]['tag_taxonomy'] );

			if ( is_wp_error( $permission ) ) {
				return $permission;
			}
		} else {
			return new WP_Error( 'invalid_settings', esc_html__( 'Ensure the service settings have been saved.', 'classifai' ) );
		}

		return true;
	}

	/**
	 * REST request permissions check for ImageCropping feature.
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

		if ( ! ( new ImageCropping() )->is_feature_enabled() ) {
			return new WP_Error( 'not_enabled', esc_html__( 'Smart cropping is disabled or Microsoft Azure authentication failed. Please check your settings.', 'classifai' ) );
		}

		return true;
	}

	/**
	 * REST request permissions check for PDFTextExtraction feature.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return bool|WP_Error
	 */
	public function pdf_read_permissions_check( WP_REST_Request $request ) {
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

		if ( ! ( new PDFTextExtraction() )->is_feature_enabled() ) {
			return new WP_Error( 'not_enabled', esc_html__( 'PDF Text Extraction is disabled or Microsoft Azure authentication failed. Please check your settings.', 'classifai' ) );
		}

		return true;
	}

	/**
	 * REST request permissions check for ImageTextExtraction feature.
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

		if ( ! ( new ImageTextExtraction() )->is_feature_enabled() ) {
			return new WP_Error( 'not_enabled', esc_html__( 'Scan image for text is disabled or Microsoft Azure authentication failed. Please check your settings.', 'classifai' ) );
		}

		return true;
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
		$settings          = $this->feature_instance->get_settings();
		$provider_settings = $settings[ static::ID ];
		$debug_info        = [];

		if ( $this->feature_instance instanceof DescriptiveTextGenerator ) {
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
			$debug_info[ __( 'Tag taxonomy', 'classifai' ) ]         = $provider_settings['tag_taxonomy'];
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
