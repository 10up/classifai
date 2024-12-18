<?php

namespace Classifai\Features;

use Classifai\Providers\Azure\ComputerVision;
use Classifai\Services\ImageProcessing;
use WP_REST_Server;
use WP_REST_Request;
use WP_Error;

use function Classifai\clean_input;
use function Classifai\check_term_permissions;

/**
 * Class ImageTagsGenerator
 */
class ImageTagsGenerator extends Feature {
	/**
	 * ID of the current feature.
	 *
	 * @var string
	 */
	const ID = 'feature_image_tags_generator';

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->label = __( 'Image Tags Generator', 'classifai' );

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
		add_action( 'edit_attachment', [ $this, 'maybe_rescan_image' ] );

		add_filter( 'attachment_fields_to_edit', [ $this, 'add_rescan_button_to_media_modal' ], 10, 2 );
		add_filter( 'wp_generate_attachment_metadata', [ $this, 'generate_image_tags' ], 8, 2 );
	}

	/**
	 * Register any needed endpoints.
	 */
	public function register_endpoints() {
		register_rest_route(
			'classifai/v1',
			'image-tags/(?P<id>\d+)',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'rest_endpoint_callback' ],
				'args'                => [
					'id' => [
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
						'description'       => esc_html__( 'Image ID to generate tags for.', 'classifai' ),
					],
				],
				'permission_callback' => [ $this, 'image_tags_generator_permissions_check' ],
			]
		);
	}

	/**
	 * Check if a given request has access to generate image tags.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return bool|WP_Error
	 */
	public function image_tags_generator_permissions_check( WP_REST_Request $request ) {
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
			return new WP_Error( 'not_enabled', esc_html__( 'Image tagging is disabled. Please check your settings.', 'classifai' ) );
		}

		$settings = $this->get_settings();
		if ( ! empty( $settings ) && isset( $settings['tag_taxonomy'] ) ) {
			$permission = check_term_permissions( $settings['tag_taxonomy'] );

			if ( is_wp_error( $permission ) ) {
				return $permission;
			}
		} else {
			return new WP_Error( 'invalid_settings', esc_html__( 'Ensure the service settings have been saved.', 'classifai' ) );
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

		if ( strpos( $route, '/classifai/v1/image-tags' ) === 0 ) {
			$result = $this->run( $request->get_param( 'id' ), 'tags' );

			if ( ! empty( $result ) && ! is_wp_error( $result ) ) {
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
	public function generate_image_tags( array $metadata, int $attachment_id ): array {
		if ( ! $this->is_feature_enabled() ) {
			return $metadata;
		}

		$result = $this->run( $attachment_id, 'tags' );

		if ( ! empty( $result ) && ! is_wp_error( $result ) ) {
			$this->save( $result, $attachment_id );
		}

		return $metadata;
	}

	/**
	 * Save the returned result based on our settings.
	 *
	 * @param array $result The results to save.
	 * @param int   $attachment_id The attachment ID.
	 */
	public function save( array $result, int $attachment_id ) {
		$settings = $this->get_settings();
		$taxonomy = $settings['tag_taxonomy'];

		foreach ( $result as $tag ) {
			wp_add_object_terms( $attachment_id, $tag, $taxonomy );
		}

		wp_update_term_count_now( $result, $taxonomy );
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
	 * Display meta data
	 *
	 * @param \WP_Post $post The post object.
	 */
	public function attachment_data_meta_box_content( \WP_Post $post ) {
		$tags = ! empty( wp_get_object_terms( $post->ID, 'classifai-image-tags' ) ) ? __( 'Rescan image for new tags', 'classifai' ) : __( 'Generate image tags', 'classifai' );
		?>

		<?php if ( $this->is_feature_enabled() ) : ?>
			<div class="misc-pub-section">
				<label for="rescan-tags">
					<input type="checkbox" value="yes" id="rescan-tags" name="rescan-tags"/>
					<?php echo esc_html( $tags ); ?>
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
		if ( clean_input( 'rescan-tags' ) ) {
			$result = $this->run( $attachment_id, 'tags' );

			if ( ! empty( $result ) && ! is_wp_error( $result ) ) {
				$this->save( $result, $attachment_id );
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

		$image_tags_text = empty( wp_get_object_terms( $post->ID, 'classifai-image-tags' ) ) ? __( 'Generate', 'classifai' ) : __( 'Rescan', 'classifai' );

		$form_fields['rescan_captions'] = [
			'label'        => __( 'Image tags', 'classifai' ),
			'input'        => 'html',
			'show_in_edit' => false,
			'html'         => '<button class="button secondary" id="classifai-rescan-image-tags" data-id="' . esc_attr( absint( $post->ID ) ) . '">' . esc_html( $image_tags_text ) . '</button><span class="spinner" style="display:none;float:none;"></span><span class="error" style="display:none;color:#bc0b0b;padding:5px;"></span>',
		];

		return $form_fields;
	}

	/**
	 * Get the description for the enable field.
	 *
	 * @return string
	 */
	public function get_enable_description(): string {
		return esc_html__( 'Automatically add relevant tags to your images.', 'classifai' );
	}

	/**
	 * Add any needed custom fields.
	 */
	public function add_custom_settings_fields() {
		$settings              = $this->get_settings();
		$attachment_taxonomies = get_object_taxonomies( 'attachment', 'objects' );
		$options               = [];

		foreach ( $attachment_taxonomies as $name => $taxonomy ) {
			$options[ $name ] = $taxonomy->label;
		}

		add_settings_field(
			'tag_taxonomy',
			esc_html__( 'Tag taxonomy', 'classifai' ),
			[ $this, 'render_select' ],
			$this->get_option_name(),
			$this->get_option_name() . '_section',
			[
				'label_for'     => 'tag_taxonomy',
				'options'       => $options,
				'default_value' => $settings['tag_taxonomy'],
			]
		);
	}

	/**
	 * Returns the default settings for the feature.
	 *
	 * @return array
	 */
	public function get_feature_default_settings(): array {
		$attachment_taxonomies = get_object_taxonomies( 'attachment', 'objects' );
		$options               = [];

		foreach ( $attachment_taxonomies as $name => $taxonomy ) {
			$options[ $name ] = $taxonomy->label;
		}

		return [
			'tag_taxonomy' => array_key_first( $options ),
			'provider'     => ComputerVision::ID,
		];
	}

	/**
	 * Sanitizes the default feature settings.
	 *
	 * @param array $new_settings Settings being saved.
	 * @return array
	 */
	public function sanitize_default_feature_settings( array $new_settings ): array {
		$settings = $this->get_settings();

		$new_settings['tag_taxonomy'] = $new_settings['tag_taxonomy'] ?? $settings['tag_taxonomy'];

		return $new_settings;
	}

	/**
	 * Return the list of Attachment taxonomies for the feature settings.
	 *
	 * @param array $object_types Array of object types to filter taxonomies by, not in use.
	 * @return array
	 */
	public function get_taxonomies( array $object_types = [] ): array {
		$attachment_taxonomies = get_object_taxonomies( 'attachment', 'objects' );
		$taxonomies            = [];

		foreach ( $attachment_taxonomies as $name => $taxonomy ) {
			$taxonomies[ $name ] = $taxonomy->label;
		}

		/**
		 * Filter taxonomies shown in settings.
		 *
		 * @since x.x.x
		 * @hook classifai_feature_image_tags_generator_setting_taxonomies
		 *
		 * @param {array} $supported Array of supported image taxonomies.
		 * @param {object} $this Current instance of the class.
		 *
		 * @return {array} Array of taxonomies.
		 */
		return apply_filters( 'classifai_' . static::ID . '_setting_taxonomies', $taxonomies, $this );
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

		if ( isset( $old_settings['enable_image_tagging'] ) ) {
			$new_settings['status'] = $old_settings['enable_image_tagging'];
		}

		if ( isset( $old_settings['url'] ) ) {
			$new_settings['ms_computer_vision']['endpoint_url'] = $old_settings['url'];
		}

		if ( isset( $old_settings['api_key'] ) ) {
			$new_settings['ms_computer_vision']['api_key'] = $old_settings['api_key'];
		}

		if ( isset( $old_settings['tag_threshold'] ) ) {
			$new_settings['ms_computer_vision']['tag_confidence_threshold'] = $old_settings['tag_threshold'];
		}

		if ( isset( $old_settings['authenticated'] ) ) {
			$new_settings['ms_computer_vision']['authenticated'] = $old_settings['authenticated'];
		}

		if ( isset( $old_settings['image_tag_taxonomy'] ) ) {
			$new_settings['tag_taxonomy'] = $old_settings['image_tag_taxonomy'];
		}

		if ( isset( $old_settings['image_tagging_roles'] ) ) {
			$new_settings['roles'] = $old_settings['image_tagging_roles'];
		}

		if ( isset( $old_settings['image_tagging_users'] ) ) {
			$new_settings['users'] = $old_settings['image_tagging_users'];
		}

		if ( isset( $old_settings['image_tagging_user_based_opt_out'] ) ) {
			$new_settings['user_based_opt_out'] = $old_settings['image_tagging_user_based_opt_out'];
		}

		return $new_settings;
	}
}
