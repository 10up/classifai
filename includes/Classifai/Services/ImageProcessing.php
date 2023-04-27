<?php
/**
 * Service definition for Language Processing
 */

namespace Classifai\Services;

use Classifai\Taxonomy\ImageTagTaxonomy;
use WP_REST_Server;
use WP_REST_Request;
use WP_Error;
use function Classifai\attachment_is_pdf;
use function Classifai\get_asset_info;

class ImageProcessing extends Service {

	/**
	 * ImageProcessing constructor.
	 */
	public function __construct() {
		parent::__construct(
			__( 'Image Processing', 'classifai' ),
			'image_processing',
			[
				'Classifai\Providers\Azure\ComputerVision',
				'Classifai\Providers\OpenAI\DallE',
			]
		);
	}

	/**
	 * Register the Image Tags taxonomy along with
	 */
	public function init() {
		parent::init();
		$this->register_image_tags_taxonomy();
		add_filter( 'attachment_fields_to_edit', [ $this, 'custom_fields_edit' ] );
		add_action( 'rest_api_init', [ $this, 'register_endpoints' ] );
		add_filter( 'attachment_fields_to_edit', [ $this, 'add_rescan_button_to_media_modal' ], 10, 2 );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_media_scripts' ] );
	}

	/**
	 * Enqueue the script for the media modal.
	 */
	public function enqueue_media_scripts() {
		wp_enqueue_script(
			'classifai-media-script',
			CLASSIFAI_PLUGIN_URL . 'dist/media.js',
			array( 'jquery', 'media-editor', 'lodash', 'wp-i18n' ),
			get_asset_info( 'media', 'version' ),
			true
		);

		wp_add_inline_script(
			'classifai-media-script',
			'const classifaiMediaVars = ' . wp_json_encode(
				array(
					'enabledAltTextFields' => $this->provider_classes[0]->get_alt_text_settings() ? $this->provider_classes[0]->get_alt_text_settings() : array(),
				)
			),
			'before'
		);
	}

	/**
	 * Adds the rescan buttons to the media modal.
	 *
	 * @param array    $form_fields Array of fields
	 * @param \WP_post $post        Post object for the attachment being viewed.
	 */
	public function add_rescan_button_to_media_modal( $form_fields, $post ) {
		$settings = get_option( 'classifai_computer_vision' );

		if ( attachment_is_pdf( $post ) && $settings && isset( $settings['enable_read_pdf'] ) && '1' === $settings['enable_read_pdf'] ) {
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

			$form_fields['rescan_alt_tags'] = [
				'label'        => __( 'Descriptive text', 'classifai' ),
				'input'        => 'html',
				'html'         => '<button class="button secondary" id="classifai-rescan-alt-tags" data-id="' . esc_attr( absint( $post->ID ) ) . '">' . esc_html( $alt_tags_text ) . '</button><span class="spinner" style="display:none;float:none;"></span><span class="error" style="display:none;color:#bc0b0b;padding:5px;"></span>',
				'show_in_edit' => false,
			];

			$form_fields['rescan_captions'] = [
				'label'        => __( 'Image tags', 'classifai' ),
				'input'        => 'html',
				'html'         => '<button class="button secondary" id="classifai-rescan-image-tags" data-id="' . esc_attr( absint( $post->ID ) ) . '">' . esc_html( $image_tags_text ) . '</button><span class="spinner" style="display:none;float:none;"></span><span class="error" style="display:none;color:#bc0b0b;padding:5px;"></span>',
				'show_in_edit' => false,
			];

			if ( $settings && isset( $settings['enable_smart_cropping'] ) && '1' === $settings['enable_smart_cropping'] ) {
				$form_fields['rescan_smart_crop'] = [
					'label'        => __( 'Smart thumbnail', 'classifai' ),
					'input'        => 'html',
					'html'         => '<button class="button secondary" id="classifai-rescan-smart-crop" data-id="' . esc_attr( absint( $post->ID ) ) . '">' . esc_html( $smart_crop_text ) . '</button><span class="spinner" style="display:none;float:none;"></span><span class="error" style="display:none;color:#bc0b0b;padding:5px;"></span>',
					'show_in_edit' => false,
				];
			}

			if ( $settings && isset( $settings['enable_ocr'] ) && '1' === $settings['enable_ocr'] ) {
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
	 * Create endpoints for services
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
				'permission_callback' => [ $this, 'computer_vision_endpoint_permissions_check' ],
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
				'permission_callback' => [ $this, 'computer_vision_endpoint_permissions_check' ],
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
						'description'       => esc_html__( 'Image ID to generate alt text for.', 'classifai' ),
					],
					'route' => [ 'ocr' ],
				],
				'permission_callback' => [ $this, 'computer_vision_endpoint_permissions_check' ],
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
						'description'       => esc_html__( 'Image ID to generate alt text for.', 'classifai' ),
					],
					'route' => [ 'smart-crop' ],
				],
				'permission_callback' => [ $this, 'computer_vision_endpoint_permissions_check' ],
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
				'permission_callback' => [ $this, 'computer_vision_endpoint_permissions_check' ],
			]
		);

		register_rest_route(
			'classifai/v1/openai',
			'generate-image',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'generate_image' ],
				'args'                => [
					'prompt' => [
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
						'validate_callback' => 'rest_validate_request_arg',
						'description'       => esc_html__( 'Prompt used to generate an image', 'classifai' ),
					],
					'n'      => [
						'type'              => 'integer',
						'minimum'           => 1,
						'maximum'           => 10,
						'sanitize_callback' => 'absint',
						'validate_callback' => 'rest_validate_request_arg',
						'description'       => esc_html__( 'Number of images to generate', 'classifai' ),
					],
					'size'   => [
						'type'              => 'string',
						'enum'              => [
							'256x256',
							'512x512',
							'1024x1024',
						],
						'sanitize_callback' => 'sanitize_text_field',
						'validate_callback' => 'rest_validate_request_arg',
						'description'       => esc_html__( 'Size of generated image', 'classifai' ),
					],
					'format' => [
						'type'              => 'string',
						'enum'              => [
							'url',
							'b64_json',
						],
						'sanitize_callback' => 'sanitize_text_field',
						'validate_callback' => 'rest_validate_request_arg',
						'description'       => esc_html__( 'Format of generated image', 'classifai' ),
					],
				],
				'permission_callback' => [ $this, 'generate_image_permissions_check' ],
			]
		);
	}

	/**
	 * Single callback to pass the route callback to the Computer Vision provider.
	 *
	 * @param WP_REST_Request $request The full request object.
	 * @return array|bool|string|WP_Error
	 */
	public function computer_vision_endpoint_callback( $request ) {
		$attachment_id = $request->get_param( 'id' );
		$custom_atts   = $request->get_attributes();
		$route_to_call = empty( $custom_atts['args']['route'] ) ? false : strtolower( $custom_atts['args']['route'][0] );
		$provider      = '';

		// Check to be sure the post both exists and is an attachment.
		if ( ! get_post( $attachment_id ) || 'attachment' !== get_post_type( $attachment_id ) ) {
			/* translators: %1$s: the attachment ID */
			return new WP_Error( 'incorrect_ID', sprintf( esc_html__( '%1$d is not found or is not an attachment', 'classifai' ), $attachment_id ), [ 'status' => 404 ] );
		}

		// If no args, we can't pass the call into the active provider.
		if ( false === $route_to_call ) {
			return new WP_Error( 'no_route', esc_html__( 'No route indicated for the provider class to use.', 'classifai' ), [ 'status' => 404 ] );
		}

		// Find the right provider class.
		foreach ( $this->provider_classes as $provider_class ) {
			if ( 'Computer Vision' === $provider_class->provider_service_name ) {
				$provider = $provider_class;
			}
		}

		// Ensure we have a provider class. Should never happen but :shrug:
		if ( ! $provider ) {
			return new WP_Error( 'provider_class_required', esc_html__( 'Provider class not found.', 'classifai' ) );
		}

		// Call the provider endpoint function
		return rest_ensure_response( $provider->rest_endpoint_callback( $attachment_id, $route_to_call ) );
	}

	/**
	 * Check if a given request has access to generate an excerpt.
	 *
	 * This check ensures the current user making the request has
	 * proper capabilities for media and that we are properly
	 * authenticated with Azure.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|bool
	 */
	public function computer_vision_endpoint_permissions_check( WP_REST_Request $request ) {
		$attachment_id = $request->get_param( 'id' );
		$custom_atts   = $request->get_attributes();
		$route_to_call = empty( $custom_atts['args']['route'] ) ? false : strtolower( $custom_atts['args']['route'][0] );
		$post_type     = get_post_type_object( 'attachment' );

		// Ensure attachments are allowed in REST endpoints.
		if ( empty( $post_type ) || empty( $post_type->show_in_rest ) ) {
			return false;
		}

		// Ensure we have a logged in user that can upload and change files.
		if ( empty( $attachment_id ) || ! current_user_can( 'edit_post', $attachment_id ) || ! current_user_can( 'upload_files' ) ) {
			return false;
		}

		$settings = \Classifai\get_plugin_settings( 'image_processing', 'Computer Vision' );

		// For the image-tags route, ensure the taxonomy is valid and the user has permission to assign terms.
		if ( 'image-tags' === $route_to_call ) {
			if ( ! empty( $settings ) && isset( $settings['image_tag_taxonomy'] ) ) {
				$permission = $this->check_term_permissions( $settings['image_tag_taxonomy'] );

				if ( is_wp_error( $permission ) ) {
					return $permission;
				}
			} else {
				return new WP_Error( 'invalid_settings', esc_html__( 'Ensure the service settings have been saved.', 'classifai' ) );
			}
		}

		// Check if valid authentication is in place.
		if ( empty( $settings ) || ( isset( $settings['authenticated'] ) && false === $settings['authenticated'] ) ) {
			return new WP_Error( 'auth', esc_html__( 'Please set up valid authentication with Azure.', 'classifai' ) );
		}

		return true;
	}

	/**
	 * Handle request to generate an image for a given prompt.
	 *
	 * @param WP_REST_Request $request The full request object.
	 * @return \WP_REST_Response|WP_Error
	 */
	public function generate_image( WP_REST_Request $request ) {
		$provider = '';

		// Find the right provider class.
		foreach ( $this->provider_classes as $provider_class ) {
			if ( 'DALL·E' === $provider_class->provider_service_name ) {
				$provider = $provider_class;
			}
		}

		// Ensure we have a provider class. Should never happen but :shrug:
		if ( ! $provider ) {
			return new WP_Error( 'provider_class_required', esc_html__( 'Provider class not found.', 'classifai' ) );
		}

		return rest_ensure_response(
			$provider->generate_image_callback(
				$request->get_param( 'prompt' ),
				[
					'num'    => $request->get_param( 'n' ),
					'size'   => $request->get_param( 'size' ),
					'format' => $request->get_param( 'format' ),
				]
			)
		);
	}

	/**
	 * Check if a given request has access to generate an image.
	 *
	 * This check ensures we have a valid user with proper capabilities
	 * making the request, that we are properly authenticated with OpenAI
	 * and that image generation is turned on.
	 *
	 * @return WP_Error|bool
	 */
	public function generate_image_permissions_check() {
		// Ensure we have a logged in user that can upload files.
		if ( ! current_user_can( 'upload_files' ) ) {
			return false;
		}

		$settings = \Classifai\get_plugin_settings( 'image_processing', 'DALL·E' );

		// Check if valid authentication is in place.
		if ( empty( $settings ) || ( isset( $settings['authenticated'] ) && false === $settings['authenticated'] ) ) {
			return new WP_Error( 'auth', esc_html__( 'Please set up valid authentication with OpenAI.', 'classifai' ) );
		}

		// Check if image generation is turned on.
		if ( empty( $settings ) || ( isset( $settings['enable_image_gen'] ) && 'no' === $settings['enable_image_gen'] ) ) {
			return new WP_Error( 'not_enabled', esc_html__( 'Image generation not currently enabled.', 'classifai' ) );
		}

		// Check if the current user's role is allowed.
		$roles      = $settings['roles'] ?? [];
		$user_roles = wp_get_current_user()->roles ?? [];

		if ( empty( $roles ) || ! empty( array_diff( $user_roles, $roles ) ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Register a common image tag taxonomy
	 */
	protected function register_image_tags_taxonomy() {
		$tax = new ImageTagTaxonomy();
		$tax->register();
		register_taxonomy_for_object_type( 'classifai-image-tags', 'attachment' );
	}

	/**
	 * Removes the UI on attachment modals for all taxonomies introduced by this plugin.
	 *
	 * @param array $form_fields The forms fields being rendered on the modal.
	 *
	 * @return mixed
	 */
	public function custom_fields_edit( $form_fields ) {
		unset( $form_fields['classifai-image-tags'] );
		unset( $form_fields['watson-category'] );
		unset( $form_fields['watson-keyword'] );
		unset( $form_fields['watson-concept'] );
		unset( $form_fields['watson-entity'] );
		return $form_fields;
	}

}
