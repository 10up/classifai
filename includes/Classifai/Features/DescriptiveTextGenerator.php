<?php

namespace Classifai\Features;

use Classifai\Providers\Azure\ComputerVision;
use Classifai\Providers\OpenAI\ChatGPT;
use Classifai\Providers\XAI\Grok;
use Classifai\Services\ImageProcessing;
use WP_REST_Server;
use WP_REST_Request;
use WP_Error;

use function Classifai\clean_input;

/**
 * Class DescriptiveTextGenerator
 */
class DescriptiveTextGenerator extends Feature {
	/**
	 * ID of the current feature.
	 *
	 * @var string
	 */
	const ID = 'feature_descriptive_text_generator';

	/**
	 * Prompt for generating descriptive text.
	 *
	 * @var string
	 */
	public $prompt = 'You are an assistant that generates descriptions of images that are used on a website. You will be provided with an image and will describe the main item you see in the image, giving details but staying concise. There is no need to say "the image contains" or similar, just describe what is actually in the image. This text will be important for screen readers, so make sure it is descriptive and accurate but not overly verbose. Before returning the text, re-evaluate your response and ensure you are following the above points, in particular ensuring the text is concise.';

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->label = __( 'Descriptive Text Generator', 'classifai' );

		// Contains all providers that are registered to the service.
		$this->provider_instances = $this->get_provider_instances( ImageProcessing::get_service_providers() );

		// Contains just the providers this feature supports.
		$this->supported_providers = [
			ComputerVision::ID => __( 'Microsoft Azure AI Vision', 'classifai' ),
			ChatGPT::ID        => __( 'OpenAI', 'classifai' ),
			Grok::ID           => __( 'xAI Grok', 'classifai' ),
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
		add_filter( 'wp_generate_attachment_metadata', [ $this, 'generate_image_alt_tags' ], 8, 2 );
	}

	/**
	 * Register any needed endpoints.
	 */
	public function register_endpoints() {
		register_rest_route(
			'classifai/v1',
			'alt-tags/(?P<id>\d+)',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'rest_endpoint_callback' ],
				'args'                => [
					'id' => [
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
						'description'       => esc_html__( 'Image ID to generate alt text for.', 'classifai' ),
					],
				],
				'permission_callback' => [ $this, 'descriptive_text_generator_permissions_check' ],
			]
		);
	}

	/**
	 * Check if a given request has access to generate descriptive text.
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

		if ( ! $this->is_feature_enabled() ) {
			return new WP_Error( 'not_enabled', esc_html__( 'Image descriptive text is disabled. Please check your settings.', 'classifai' ) );
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
			$result = $this->run( $request->get_param( 'id' ), 'descriptive_text' );

			if ( $result && ! is_wp_error( $result ) ) {
				$this->save( $result, $request->get_param( 'id' ) );
			}

			return rest_ensure_response( $result );
		}

		return parent::rest_endpoint_callback( $request );
	}

	/**
	 * Generate the alt tags for the image being uploaded.
	 *
	 * @param array $metadata      The metadata for the image.
	 * @param int   $attachment_id Post ID for the attachment.
	 * @return array
	 */
	public function generate_image_alt_tags( array $metadata, int $attachment_id ): array {
		if ( ! $this->is_feature_enabled() ) {
			return $metadata;
		}

		$result = $this->run( $attachment_id, 'descriptive_text' );

		if ( $result && ! is_wp_error( $result ) ) {
			$this->save( $result, $attachment_id );
		}

		return $metadata;
	}

	/**
	 * Save the returned result based on our settings.
	 *
	 * @param string $result The result to save.
	 * @param int    $attachment_id The attachment ID.
	 */
	public function save( string $result, int $attachment_id ) {
		$enabled_fields = $this->get_alt_text_settings();

		if ( in_array( 'alt', $enabled_fields, true ) ) {
			update_post_meta( $attachment_id, '_wp_attachment_image_alt', sanitize_text_field( $result ) );
		}

		$excerpt = get_the_excerpt( $attachment_id );

		if ( in_array( 'caption', $enabled_fields, true ) && $excerpt !== $result ) {
			wp_update_post(
				array(
					'ID'           => $attachment_id,
					'post_excerpt' => sanitize_text_field( $result ),
				)
			);
		}

		$content = get_the_content( null, false, $attachment_id );

		if ( in_array( 'description', $enabled_fields, true ) && $content !== $result ) {
			wp_update_post(
				array(
					'ID'           => $attachment_id,
					'post_content' => sanitize_text_field( $result ),
				)
			);
		}
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
		$captions = get_post_meta( $post->ID, '_wp_attachment_image_alt', true ) ? __( 'No descriptive text? Rescan image', 'classifai' ) : __( 'Generate descriptive text', 'classifai' );
		?>

		<?php if ( $this->is_feature_enabled() && ! empty( $this->get_alt_text_settings() ) ) : ?>
			<div class="misc-pub-section">
				<label for="rescan-captions">
					<input type="checkbox" value="yes" id="rescan-captions" name="rescan-captions"/>
					<?php echo esc_html( $captions ); ?>
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
		if ( clean_input( 'rescan-captions' ) ) {
			$result = $this->run( $attachment_id, 'descriptive_text' );

			if ( $result && ! is_wp_error( $result ) ) {
				// Ensure we don't re-run this when the attachment is updated.
				remove_action( 'edit_attachment', [ $this, 'maybe_rescan_image' ] );
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
		if (
			! $this->is_feature_enabled() ||
			! wp_attachment_is_image( $post ) ||
			empty( $this->get_alt_text_settings() )
		) {
			return $form_fields;
		}

		$alt_tags_text = empty( get_post_meta( $post->ID, '_wp_attachment_image_alt', true ) ) ? __( 'Generate', 'classifai' ) : __( 'Rescan', 'classifai' );

		$form_fields['rescan_alt_tags'] = [
			'label'        => __( 'Descriptive text', 'classifai' ),
			'input'        => 'html',
			'show_in_edit' => false,
			'html'         => '<button class="button secondary" id="classifai-rescan-alt-tags" data-id="' . esc_attr( absint( $post->ID ) ) . '">' . esc_html( $alt_tags_text ) . '</button><span class="spinner" style="display:none;float:none;"></span><span class="error" style="display:none;color:#bc0b0b;padding:5px;"></span>',
		];

		return $form_fields;
	}

	/**
	 * Returns an array of fields enabled to be set to store image captions.
	 *
	 * @return array
	 */
	public function get_alt_text_settings(): array {
		$settings       = $this->get_settings();
		$enabled_fields = array();

		if ( ! isset( $settings['descriptive_text_fields'] ) ) {
			return array();
		}

		if ( ! is_array( $settings['descriptive_text_fields'] ) ) {
			return array(
				'alt'         => 'no' === $settings['descriptive_text_fields'] ? 0 : 'alt',
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
	 * Get the description for the enable field.
	 *
	 * @return string
	 */
	public function get_enable_description(): string {
		return esc_html__( 'Automatically generate descriptive text for images, storing this as image alt text, caption or description.', 'classifai' );
	}

	/**
	 * Add any needed custom fields.
	 */
	public function add_custom_settings_fields() {
		$settings         = $this->get_settings();
		$checkbox_options = array(
			'alt'         => esc_html__( 'Alt text', 'classifai' ),
			'caption'     => esc_html__( 'Image caption', 'classifai' ),
			'description' => esc_html__( 'Image description', 'classifai' ),
		);

		add_settings_field(
			'descriptive_text_fields',
			esc_html__( 'Descriptive text fields', 'classifai' ),
			[ $this, 'render_checkbox_group' ],
			$this->get_option_name(),
			$this->get_option_name() . '_section',
			[
				'label_for'      => 'descriptive_text_fields',
				'options'        => $checkbox_options,
				'default_values' => $settings['descriptive_text_fields'],
				'description'    => __( 'Choose image fields where the generated text should be applied.', 'classifai' ),
			]
		);
	}

	/**
	 * Returns the default settings for the feature.
	 *
	 * @return array
	 */
	public function get_feature_default_settings(): array {
		return [
			'descriptive_text_fields' => [
				'alt'         => 'alt',
				'caption'     => 0,
				'description' => 0,
			],
			'provider'                => ComputerVision::ID,
		];
	}

	/**
	 * Returns the settings for the feature.
	 *
	 * @param string $index The index of the setting to return.
	 * @return array|mixed
	 */
	public function get_settings( $index = false ) {
		$settings = parent::get_settings( $index );

		// Keep using the original prompt from the codebase to allow updates.
		if ( $settings && ! empty( $settings[ ChatGPT::ID ]['prompt'] ) ) {
			foreach ( $settings[ ChatGPT::ID ]['prompt'] as $key => $prompt ) {
				if ( 1 === intval( $prompt['original'] ) ) {
					$settings[ ChatGPT::ID ]['prompt'][ $key ]['prompt'] = $this->prompt;
					break;
				}
			}
		}

		if ( $settings && ! empty( $settings[ Grok::ID ]['prompt'] ) ) {
			foreach ( $settings[ Grok::ID ]['prompt'] as $key => $prompt ) {
				if ( 1 === intval( $prompt['original'] ) ) {
					$settings[ Grok::ID ]['prompt'][ $key ]['prompt'] = $this->prompt;
					break;
				}
			}
		}

		return $settings;
	}

	/**
	 * Sanitizes the default feature settings.
	 *
	 * @param array $new_settings Settings being saved.
	 * @return array
	 */
	public function sanitize_default_feature_settings( array $new_settings ): array {
		$settings = $this->get_settings();

		$new_settings['descriptive_text_fields'] = array_map( 'sanitize_text_field', $new_settings['descriptive_text_fields'] ?? $settings['descriptive_text_fields'] );

		return $new_settings;
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

		if ( isset( $old_settings['url'] ) ) {
			$new_settings['ms_computer_vision']['endpoint_url'] = $old_settings['url'];
		}

		if ( isset( $old_settings['api_key'] ) ) {
			$new_settings['ms_computer_vision']['api_key'] = $old_settings['api_key'];
		}

		if ( isset( $old_settings['caption_threshold'] ) ) {
			$new_settings['ms_computer_vision']['descriptive_confidence_threshold'] = $old_settings['caption_threshold'];
		}

		if ( isset( $old_settings['authenticated'] ) ) {
			$new_settings['ms_computer_vision']['authenticated'] = $old_settings['authenticated'];
		}

		if ( isset( $old_settings['enable_image_captions'] ) ) {
			$new_settings['descriptive_text_fields'] = $old_settings['enable_image_captions'];

			foreach ( $new_settings['descriptive_text_fields'] as $key => $value ) {
				if ( '0' !== $value ) {
					$new_settings['status'] = '1';
					break;
				}
			}
		}

		if ( isset( $old_settings['image_captions_roles'] ) ) {
			$new_settings['roles'] = $old_settings['image_captions_roles'];
		}

		if ( isset( $old_settings['image_captions_users'] ) ) {
			$new_settings['users'] = $old_settings['image_captions_users'];
		}

		if ( isset( $old_settings['image_captions_user_based_opt_out'] ) ) {
			$new_settings['user_based_opt_out'] = $old_settings['image_captions_user_based_opt_out'];
		}

		return $new_settings;
	}
}
