<?php
/**
 * Quick Draft Integration Feature.
 *
 * Integrates ClassifAI Content Generation with WordPress Quick Draft widget.
 */

namespace Classifai\Features;

use Classifai\Features\ContentGeneration;
use WP_REST_Server;
use WP_REST_Request;
use WP_Error;

use function Classifai\get_asset_info;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Quick Draft Integration Feature.
 *
 * Integrates ClassifAI Content Generation with WordPress Quick Draft widget.
 */
class QuickDraftIntegration {

	/**
	 * Content Generation Feature instance.
	 *
	 * @var ContentGeneration
	 */
	private $content_generation;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->content_generation = new ContentGeneration();
	}

	/**
	 * Initialize the Quick Draft integration.
	 */
	public function init() {
		// Check if Quick Draft integration is enabled.
		if ( ! $this->is_quick_draft_enabled() ) {
			return;
		}

		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
		add_action( 'rest_api_init', [ $this, 'register_endpoints' ] );
	}

	/**
	 * Check if Quick Draft integration is enabled.
	 *
	 * @return bool
	 */
	public function is_quick_draft_enabled(): bool {
		$settings = $this->content_generation->get_settings();
		return isset( $settings['enable_quick_draft'] ) ? (bool) $settings['enable_quick_draft'] : true;
	}

	/**
	 * Enqueue Quick Draft assets on the dashboard.
	 */
	public function enqueue_assets() {
		$screen = get_current_screen();

		// Only load on dashboard.
		if ( ! $screen || 'dashboard' !== $screen->id ) {
			return;
		}

		// Only load if user can create posts.
		if ( ! current_user_can( 'edit_posts' ) ) {
			return;
		}

		wp_enqueue_script(
			'classifai-quick-draft-js',
			CLASSIFAI_PLUGIN_URL . 'dist/classifai-quick-draft.js',
			array_merge( get_asset_info( 'classifai-quick-draft', 'dependencies' ), array( 'jquery', 'media-editor', 'lodash' ) ),
			get_asset_info( 'classifai-quick-draft', 'version' ),
			true
		);

		wp_localize_script(
			'classifai-quick-draft-js',
			'classifaiQuickDraft',
			[
				'createContent' => __( 'Create Draft from Prompt', 'classifai' ),
				'generating'    => __( 'Generating...', 'classifai' ),
				'error'         => __( 'Error generating content. Please try again.', 'classifai' ),
			]
		);

		wp_enqueue_style(
			'classifai-quick-draft-css',
			CLASSIFAI_PLUGIN_URL . 'dist/classifai-quick-draft.css',
			[],
			get_asset_info( 'classifai-quick-draft', 'version' ),
		);
	}

	/**
	 * Register Quick Draft specific endpoints.
	 */
	public function register_endpoints() {
		register_rest_route(
			'classifai/v1',
			'quick-draft-generate',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'endpoint_callback' ],
				'permission_callback' => [ $this, 'permissions_check' ],
				'args'                => [
					'title'   => [
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
						'validate_callback' => 'rest_validate_request_arg',
						'description'       => esc_html__( 'The title of the post.', 'classifai' ),
					],
					'content' => [
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_textarea_field',
						'validate_callback' => 'rest_validate_request_arg',
						'description'       => esc_html__( 'The prompt to use for content generation.', 'classifai' ),
					],
				],
			]
		);
	}

	/**
	 * Check permissions for Quick Draft generation.
	 *
	 * @return WP_Error|bool
	 */
	public function permissions_check() {
		// Ensure user can create posts.
		if ( ! current_user_can( 'edit_posts' ) ) {
			return false;
		}

		$post_type_obj = get_post_type_object( 'post' );

		// Ensure the post type is allowed in REST endpoints.
		if ( empty( $post_type_obj ) || empty( $post_type_obj->show_in_rest ) ) {
			return false;
		}

		// Ensure the Feature is enabled.
		if ( ! $this->content_generation->is_feature_enabled() ) {
			return new WP_Error( 'not_enabled', esc_html__( 'Content Generation is not currently enabled.', 'classifai' ) );
		}

		return true;
	}

	/**
	 * Handle Quick Draft content generation.
	 *
	 * @param WP_REST_Request $request The full request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function endpoint_callback( WP_REST_Request $request ) {
		$title   = $request->get_param( 'title' );
		$content = $request->get_param( 'content' );

		if ( empty( $title ) || empty( $content ) ) {
			return new WP_Error( 'missing_required_parameters', esc_html__( 'Title and content are required.', 'classifai' ) );
		}

		// Create a new auto-draft post.
		$post_data = [
			'post_title'   => $title,
			'post_content' => '',
			'post_status'  => 'auto-draft',
			'post_type'    => 'post',
			'post_author'  => get_current_user_id(),
		];

		$post_id = wp_insert_post( $post_data, true );

		if ( is_wp_error( $post_id ) ) {
			return new WP_Error( 'post_creation_failed', esc_html__( 'Failed to create draft post.', 'classifai' ) );
		}

		// Generate content using the existing content generation logic.
		$result = $this->content_generation->run(
			$post_id,
			'create_content',
			[
				'title'   => $title,
				'summary' => $content,
			]
		);

		if ( is_wp_error( $result ) ) {
			// Clean up the post if generation failed.
			wp_delete_post( $post_id, true );
			return $result;
		}

		// Update the post with generated content.
		$updated_post = [
			'ID'           => $post_id,
			'post_content' => $result,
			'post_status'  => 'draft',
		];

		$update_result = wp_update_post( $updated_post );

		if ( is_wp_error( $update_result ) ) {
			return new WP_Error( 'post_update_failed', esc_html__( 'Failed to update post with generated content.', 'classifai' ) );
		}

		return rest_ensure_response(
			[
				'post_id'  => $post_id,
				'edit_url' => admin_url( "post.php?post={$post_id}&action=edit" ),
				'content'  => $result,
				'title'    => $title,
				'success'  => true,
			]
		);
	}
}
