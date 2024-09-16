<?php

namespace Classifai\Features;

use Classifai\Providers\OpenAI\ChatGPT;
use Classifai\Services\LanguageProcessing;
use WP_REST_Server;
use WP_REST_Request;
use WP_Error;

use function Classifai\get_asset_info;
use function Classifai\sanitize_prompts;

/**
 * Class RewriteTone
 */
class RewriteTone extends Feature {
	/**
	 * ID of the current feature.
	 *
	 * @var string
	 */
	const ID = 'feature_rewrite_tone';

	/**
	 * Prompt for rewriting tone.
	 *
	 * @var string
	 */
	public $prompt = 'You are modifying the tone and lingo of the following text to Renaissance English.';

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->label = __( 'Rewrite Tone', 'classifai' );

		// Contains all providers that are registered to the service.
		$this->provider_instances = $this->get_provider_instances( LanguageProcessing::get_service_providers() );

		// Contains just the providers this feature supports.
		$this->supported_providers = [
			ChatGPT::ID => __( 'OpenAI ChatGPT', 'classifai' ),
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
		add_action( 'enqueue_block_assets', [ $this, 'enqueue_editor_assets' ] );
	}

	/**
	 * Register any needed endpoints.
	 */
	public function register_endpoints() {
		register_rest_route(
			'classifai/v1',
			'rewrite-tone',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'rest_endpoint_callback' ],
				'permission_callback' => '__return_true',
				'args'                => [
					'id'      => [
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
						'description'       => esc_html__( 'Post ID to resize the content for.', 'classifai' ),
					],
					'content' => [
						'type'              => 'array',
						'sanitize_callback' => function ( $content_array ) {
							if ( is_array( $content_array ) ) {
								return array_map(
									function ( $item ) {
										$item['clientId'] = sanitize_text_field( $item['clientId'] );
										$item['content']  = wp_kses_post( $item['content'] );
										return $item;
									},
									$content_array
								);
							}

							return [];
						},
						'validate_callback' => function ( $content_array ) {
							if ( is_array( $content_array ) ) {
								foreach ( $content_array as $item ) {
									if ( ! isset( $item['clientId'] ) || ! is_string( $item['clientId'] ) ) {
										return new WP_Error( 'rewrite_tone_invalid_client_id', __( 'Each item must have a valid clientId string.', 'classifai' ), [ 'status' => 400 ] );
									}

									if ( ! isset( $item['content'] ) || ! is_string( $item['content'] ) ) {
										return new WP_Error( 'rewrite_tone_invalid_content', __( 'Each item must have valid content as a string.', 'classifai' ), [ 'status' => 400 ] );
									}
								}
								return true;
							}
							return new WP_Error( 'rewrite_tone_invalid_data_format', __( 'Content must be an array of objects.', 'classifai' ), [ 'status' => 400 ] );
						},
						'description'       => esc_html__( 'The content to resize.', 'classifai' ),
					],
				],
			]
		);
	}

	/**
	 * Check if a given request has access to resize content.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|bool
	 */
	public function resize_content_permissions_check( WP_REST_Request $request ) {
		$post_id = $request->get_param( 'id' );

		// Ensure we have a logged in user that can edit the item.
		if ( empty( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return false;
		}

		$post_type     = get_post_type( $post_id );
		$post_type_obj = get_post_type_object( $post_type );

		// Ensure the post type is allowed in REST endpoints.
		if ( ! $post_type || empty( $post_type_obj ) || empty( $post_type_obj->show_in_rest ) ) {
			return false;
		}

		// Ensure the feature is enabled. Also runs a user check.
		if ( ! $this->is_feature_enabled() ) {
			return new WP_Error( 'not_enabled', esc_html__( 'Rewrite Tone is not currently enabled.', 'classifai' ) );
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

		if ( strpos( $route, '/classifai/v1/rewrite-tone' ) === 0 ) {
			return rest_ensure_response(
				$this->run(
					$request->get_param( 'id' ),
					'rewrite_tone',
					[
						'content' => $request->get_param( 'content' ),
					]
				)
			);
		}

		return parent::rest_endpoint_callback( $request );
	}

	/**
	 * Enqueue the editor scripts.
	 */
	public function enqueue_editor_assets() {
		global $post;

		if ( empty( $post ) || ! is_admin() ) {
			return;
		}

		wp_enqueue_script(
			'classifai-rewrite-tone-js',
			CLASSIFAI_PLUGIN_URL . 'dist/rewrite-tone-plugin.js',
			get_asset_info( 'rewrite-tone', 'dependencies' ),
			get_asset_info( 'rewrite-tone', 'version' ),
			true
		);
	}

	/**
	 * Get the description for the enable field.
	 *
	 * @return string
	 */
	public function get_enable_description(): string {
		return esc_html__( '"Condense this text" and "Expand this text" menu items will be added to the paragraph block\'s toolbar menu.', 'classifai' );
	}

	/**
	 * Add any needed custom fields.
	 */
	public function add_custom_settings_fields() {
		$settings = $this->get_settings();

		add_settings_field(
			'rewrite_tone_prompt',
			esc_html__( 'Prompt', 'classifai' ),
			[ $this, 'render_prompt_repeater_field' ],
			$this->get_option_name(),
			$this->get_option_name() . '_section',
			[
				'label_for'     => 'rewrite_tone_prompt',
				'placeholder'   => $this->prompt,
				'default_value' => $settings['rewrite_tone_prompt'],
				'description'   => esc_html__( 'Add a custom prompt, if desired.', 'classifai' ),
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
			'rewrite_tone_prompt' => [
				[
					'title'    => esc_html__( 'ClassifAI default', 'classifai' ),
					'prompt'   => $this->prompt,
					'original' => 1,
				],
			],
			'provider'            => ChatGPT::ID,
		];
	}

	/**
	 * Sanitizes the default feature settings.
	 *
	 * @param array $new_settings Settings being saved.
	 * @return array
	 */
	public function sanitize_default_feature_settings( array $new_settings ): array {
		$new_settings['rewrite_tone_prompt'] = sanitize_prompts( 'rewrite_tone_prompt', $new_settings );

		return $new_settings;
	}
}
