<?php

namespace Classifai\Features;

use Classifai\Providers\Azure\ComputerVision;
use Classifai\Services\ImageProcessing;
use WP_REST_Server;
use WP_REST_Request;
use WP_Error;

use function Classifai\attachment_is_pdf;
use function Classifai\clean_input;

/**
 * Class PDFTextExtraction
 */
class PDFTextExtraction extends Feature {
	/**
	 * ID of the current feature.
	 *
	 * @var string
	 */
	const ID = 'feature_pdf_to_text_generation';

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->label = __( 'PDF Text Extraction', 'classifai' );

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
		add_action( 'add_attachment', [ $this, 'read_pdf' ] );
		add_action( 'edit_attachment', [ $this, 'maybe_rescan_pdf' ] );

		add_filter( 'attachment_fields_to_edit', [ $this, 'add_rescan_button_to_media_modal' ], 10, 2 );
	}

	/**
	 * Register any needed endpoints.
	 */
	public function register_endpoints() {
		register_rest_route(
			'classifai/v1',
			'read-pdf/(?P<id>\d+)',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'rest_endpoint_callback' ],
				'args'                => [
					'id' => [
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
						'description'       => esc_html__( 'Attachment ID to extact text from the PDF file.', 'classifai' ),
					],
				],
				'permission_callback' => [ $this, 'read_pdf_permissions_check' ],
			]
		);
	}

	/**
	 * Check if a given request has access to read a PDF.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return bool|WP_Error
	 */
	public function read_pdf_permissions_check( WP_REST_Request $request ) {
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
			return new WP_Error( 'not_enabled', esc_html__( 'PDF Text Extraction is disabled. Please check your settings.', 'classifai' ) );
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

		if ( strpos( $route, '/classifai/v1/read-pdf' ) === 0 ) {
			return rest_ensure_response(
				$this->run( $request->get_param( 'id' ), 'read_pdf' )
			);
		}

		return parent::rest_endpoint_callback( $request );
	}

	/**
	 * Adds a meta box for rescanning options if the settings are configured.
	 *
	 * @param \WP_Post $post The post object.
	 */
	public function setup_attachment_meta_box( \WP_Post $post ) {
		if ( ! attachment_is_pdf( $post ) || ! $this->is_feature_enabled() ) {
			return;
		}

		add_meta_box(
			'classifai_pdf_processing',
			__( 'ClassifAI PDF Processing', 'classifai' ),
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
		 * Filter the status of the PDF read operation.
		 *
		 * @since 3.0.0
		 * @hook classifai_feature_pdf_to_text_generation_read_status
		 *
		 * @param {array} $status Status of the PDF read operation.
		 * @param {int} $post_id ID of attachment.
		 *
		 * @return {array} Status.
		 */
		$status = apply_filters( 'classifai_' . static::ID . '_read_status', [], $post->ID );

		$read    = ! empty( $status['read'] ) && (bool) $status['read'] ? __( 'Rescan PDF for text', 'classifai' ) : __( 'Scan PDF for text', 'classifai' );
		$running = ! empty( $status['running'] ) && (bool) $status['running'];
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
	 * Read text out of newly uploaded PDFs.
	 *
	 * @param int $attachment_id Attachment ID.
	 */
	public function read_pdf( int $attachment_id ) {
		$this->run( $attachment_id, 'read_pdf' );
	}

	/**
	 * Determine if we need to rescan the PDF.
	 *
	 * @param int $attachment_id Attachment ID.
	 */
	public function maybe_rescan_pdf( int $attachment_id ) {
		if ( clean_input( 'rescan-pdf' ) ) {
			$this->run( $attachment_id, 'read_pdf' );
		}
	}

	/**
	 * Save the returned result.
	 *
	 * @param string $result The result to save.
	 * @param int    $attachment_id The attachment ID.
	 */
	public function save( string $result, int $attachment_id ) {
		// Ensure we don't re-run this when the attachment is updated.
		remove_action( 'edit_attachment', [ $this, 'maybe_rescan_pdf' ] );

		return wp_update_post(
			[
				'ID'           => $attachment_id,
				'post_content' => $result,
			]
		);
	}

	/**
	 * Adds the rescan buttons to the media modal.
	 *
	 * @param array    $form_fields Array of fields
	 * @param \WP_Post $post        Post object for the attachment being viewed.
	 * @return array
	 */
	public function add_rescan_button_to_media_modal( array $form_fields, \WP_Post $post ): array {
		if ( ! $this->is_feature_enabled() || ! attachment_is_pdf( $post ) ) {
			return $form_fields;
		}

		$read_text = empty( get_the_content( null, false, $post ) ) ? __( 'Scan', 'classifai' ) : __( 'Rescan', 'classifai' );
		$status    = apply_filters( 'classifai_' . static::ID . '_read_status', [], $post->ID );

		if ( ! empty( $status['running'] ) && (bool) $status['running'] ) {
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

		return $form_fields;
	}

	/**
	 * Get the description for the enable field.
	 *
	 * @return string
	 */
	public function get_enable_description(): string {
		return esc_html__( 'Extract visible text from multi-pages PDF documents. Store the result as the attachment description.', 'classifai' );
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
		$new_settings = array();

		$new_settings['provider'] = 'ms_computer_vision';

		if ( isset( $old_settings['enable_read_pdf'] ) ) {
			$new_settings['status'] = $old_settings['enable_read_pdf'];
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

		if ( isset( $old_settings['read_pdf_roles'] ) ) {
			$new_settings['roles'] = $old_settings['read_pdf_roles'];
		}

		if ( isset( $old_settings['read_pdf_users'] ) ) {
			$new_settings['users'] = $old_settings['read_pdf_users'];
		}

		if ( isset( $old_settings['ocr_user_based_opt_out'] ) ) {
			$new_settings['user_based_opt_out'] = $old_settings['ocr_user_based_opt_out'];
		}

		return $new_settings;
	}
}
