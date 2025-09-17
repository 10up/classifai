<?php
/**
 * Quick Draft Integration Feature
 *
 * Integrates ClassifAI Content Generation with WordPress Quick Draft widget.
 *
 */
namespace Classifai\Features;

use Classifai\Features\ContentGeneration;
use WP_REST_Server;
use WP_REST_Request;
use WP_Error;

/**
 * Quick Draft Integration Feature
 * 
 * Integrates ClassifAI Content Generation with WordPress Quick Draft widget.
 */
class QuickDraftIntegration {

	/**
	 * Content Generation feature instance.
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
		// Only initialize if Content Generation is enabled
		if ( ! $this->content_generation->is_feature_enabled() ) {
			return;
		}

		// Check if Quick Draft integration is enabled
		if ( ! $this->is_quick_draft_enabled() ) {
			return;
		}

		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
		add_action( 'wp_dashboard_setup', [ $this, 'modify_quick_draft_widget' ] );
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
		
		// Only load on dashboard
		if ( ! $screen || 'dashboard' !== $screen->id ) {
			return;
		}

		// Only load if user can create posts
		if ( ! current_user_can( 'edit_posts' ) ) {
			return;
		}

		wp_enqueue_script(
			'classifai-quick-draft-js',
			CLASSIFAI_PLUGIN_URL . 'dist/classifai-quick-draft.js',
			[ 'jquery', 'wp-api-fetch' ],
			CLASSIFAI_PLUGIN_VERSION,
			true
		);

		wp_localize_script(
			'classifai-quick-draft-js',
			'classifaiQuickDraft',
			[
				'nonce'         => wp_create_nonce( 'classifai_quick_draft' ),
				'restUrl'       => rest_url( 'classifai/v1/' ),
				'createContent' => __( 'Create Draft from Prompt Content', 'classifai' ),
				'generating'    => __( 'Generating...', 'classifai' ),
				'error'         => __( 'Error generating content. Please try again.', 'classifai' ),
				'currentUserId' => get_current_user_id(),
			]
		);

		wp_enqueue_style(
			'classifai-quick-draft-css',
			CLASSIFAI_PLUGIN_URL . 'dist/classifai-quick-draft.css',
			[],
			CLASSIFAI_PLUGIN_VERSION
		);
	}

	/**
	 * Modify the Quick Draft widget to add our button.
	 */
	public function modify_quick_draft_widget() {
		// Only modify if user can create posts
		if ( ! current_user_can( 'edit_posts' ) ) {
			return;
		}

		// Use a different approach - add the button via JavaScript
		add_action( 'admin_footer', [ $this, 'add_quick_draft_button_script' ] );
	}

	/**
	 * Add the button via JavaScript in the admin footer.
	 */
	public function add_quick_draft_button_script() {
		$screen = get_current_screen();
		
		// Only add on dashboard
		if ( ! $screen || 'dashboard' !== $screen->id ) {
			return;
		}

		?>
		<script type="text/javascript">
		jQuery(document).ready(function($) {
			// Wait for the Quick Draft widget to be loaded
			setTimeout(function() {
				var saveButton = $('#save-post');
				if (saveButton.length && !$('#classifai-generate-content').length) {
					var generateButton = $('<input type="button" id="classifai-generate-content" class="button" value="<?php echo esc_js( __( 'Create Draft from Prompt Content', 'classifai' ) ); ?>" style="margin-left: 10px;" />');
					saveButton.after(generateButton);
				}
			}, 1000);
		});
		</script>
		<?php
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
						'type'              => 'string',
						'sanitize_callback'  => 'sanitize_text_field',
						'validate_callback'  => 'rest_validate_request_arg',
						'description'        => esc_html__( 'The title of the article.', 'classifai' ),
					],
					'content' => [
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_textarea_field',
						'validate_callback' => 'rest_validate_request_arg',
						'description'       => esc_html__( 'The prompt content for generation.', 'classifai' ),
					],
				],
			]
		);
	}

	/**
	 * Check permissions for Quick Draft generation.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|bool
	 */
	public function permissions_check( WP_REST_Request $request ) {
		// Ensure user can create posts
		if ( ! current_user_can( 'edit_posts' ) ) {
			return false;
		}

		// Ensure the feature is enabled
		if ( ! $this->content_generation->is_feature_enabled() ) {
			return new WP_Error( 'not_enabled', esc_html__( 'Content Generation is not currently enabled.', 'classifai' ) );
		}

		return true;
	}

	/**
	 * Handle Quick Draft content generation.
	 *
	 * @param WP_REST_Request $request The full request object.
	 * @return \WP_REST_Response
	 */
	public function endpoint_callback( WP_REST_Request $request ) {
		$title   = $request->get_param( 'title' );
		$content = $request->get_param( 'content' );

		// Create a new auto-draft post
		$post_data = [
			'post_title'   => $title ? $title : '',
			'post_content' => '',
			'post_status'  => 'auto-draft',
			'post_type'    => 'post',
			'post_author'  => get_current_user_id(),
		];

		$post_id = wp_insert_post( $post_data );

		if ( is_wp_error( $post_id ) ) {
			return new WP_Error( 'post_creation_failed', esc_html__( 'Failed to create draft post.', 'classifai' ) );
		}

		// Generate content using the existing content generation logic
		$result = $this->content_generation->run(
			$post_id,
			'create_content',
			[
				'title'   => $title,
				'summary' => $content,
			]
		);

		if ( is_wp_error( $result ) ) {
			// Clean up the post if generation failed
			wp_delete_post( $post_id, true );
			return $result;
		}

		// Update the post with generated content
		$updated_post = [
			'ID'           => $post_id,
			'post_content' => $result,
			'post_status'  => 'draft',
		];

		$update_result = wp_update_post( $updated_post );

		if ( is_wp_error( $update_result ) ) {
			return new WP_Error( 'post_update_failed', esc_html__( 'Failed to update post with generated content.', 'classifai' ) );
		}

		return rest_ensure_response( [
			'post_id'      => $post_id,
			'edit_url'     => admin_url( "post.php?post={$post_id}&action=edit" ),
			'content'      => $result,
			'title'        => $title,
			'success'      => true,
		] );
	}
}
