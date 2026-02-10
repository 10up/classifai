<?php

namespace Classifai\Features;

use Classifai\Services\LanguageProcessing;
use Classifai\Providers\OpenAI\ChatGPT;
use Classifai\Providers\Azure\OpenAI;
use Classifai\Providers\Localhost\Ollama;
use WP_REST_Server;
use WP_REST_Request;
use WP_Error;

use function Classifai\get_asset_info;
use function Classifai\sanitize_prompts;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class KeyTakeaways
 */
class KeyTakeaways extends Feature {

	/**
	 * ID of the current feature.
	 *
	 * @var string
	 */
	const ID = 'feature_key_takeaways';

	/**
	 * Prompt for generating the key takeaways.
	 *
	 * @var string
	 */
	public $prompt = 'The content you will be provided with is from an already written article. This article has the title of: {{TITLE}}. Your task is to carefully read through this article and provide a summary that captures all the important points. This summary should be concise and limited to about 2-4 points but should also be detailed enough to allow someone to quickly grasp the full article. Read the article a few times before providing the summary and trim each point down to be as concise as possible.';

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->label = __( 'Key Takeaways', 'classifai' );

		// Contains all providers that are registered to the service.
		$this->provider_instances = $this->get_provider_instances( LanguageProcessing::get_service_providers() );

		// Contains just the providers this feature supports.
		$this->supported_providers = [
			ChatGPT::ID => __( 'OpenAI ChatGPT', 'classifai' ),
			OpenAI::ID  => __( 'Azure OpenAI', 'classifai' ),
			Ollama::ID  => __( 'Ollama', 'classifai' ),
		];
	}

	/**
	 * Set up necessary hooks.
	 *
	 * This is always run so useful if we need to register
	 * things even if the feature is not enabled, not configured
	 * or the user does not have access.
	 */
	public function setup() {
		parent::setup();
		add_action( 'rest_api_init', [ $this, 'register_endpoints' ] );

		if ( $this->is_configured() && $this->is_enabled() ) {
			add_action( 'enqueue_block_assets', [ $this, 'enqueue_editor_assets' ] );
			$this->register_block();
		}
	}

	/**
	 * Set up necessary hooks.
	 *
	 * Only fires if the feature is enabled, configured and user has access.
	 */
	public function feature_setup() {
	}

	/**
	 * Register the block used for this feature.
	 */
	public function register_block() {
		register_block_type_from_metadata(
			CLASSIFAI_PLUGIN_DIR . '/includes/Classifai/Blocks/key-takeaways', // this is the directory where the block.json is found.
		);
	}

	/**
	 * Register any needed endpoints.
	 */
	public function register_endpoints() {
		register_rest_route(
			'classifai/v1',
			'key-takeaways(?:/(?P<id>\d+))?',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'rest_endpoint_callback' ],
					'args'                => [
						'id'     => [
							'required'          => true,
							'type'              => 'integer',
							'sanitize_callback' => 'absint',
							'description'       => esc_html__( 'Post ID to generate key takeaways for.', 'classifai' ),
						],
						'render' => [
							'type'              => 'string',
							'enum'              => [
								'list',
								'paragraph',
							],
							'sanitize_callback' => 'sanitize_text_field',
							'validate_callback' => 'rest_validate_request_arg',
							'description'       => esc_html__( 'How the key takeaways should be rendered.', 'classifai' ),
						],
						'run'    => [
							'type'              => 'string',
							'enum'              => [
								'auto',
								'manual',
							],
							'default'           => 'auto',
							'sanitize_callback' => 'sanitize_text_field',
							'validate_callback' => 'rest_validate_request_arg',
							'description'       => esc_html__( 'Whether the key takeaways were generated automatically or manually.', 'classifai' ),
						],
					],
					'permission_callback' => [ $this, 'generate_key_takeaways_permissions_check' ],
				],
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'rest_endpoint_callback' ],
					'args'                => [
						'content' => [
							'required'          => true,
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
							'validate_callback' => 'rest_validate_request_arg',
							'description'       => esc_html__( 'Content to generate key takeaways from.', 'classifai' ),
						],
						'title'   => [
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
							'validate_callback' => 'rest_validate_request_arg',
							'description'       => esc_html__( 'Title of content to generate key takeaways from.', 'classifai' ),
						],
						'render'  => [
							'type'              => 'string',
							'enum'              => [
								'list',
								'paragraph',
							],
							'sanitize_callback' => 'sanitize_text_field',
							'validate_callback' => 'rest_validate_request_arg',
							'description'       => esc_html__( 'How the key takeaways should be rendered.', 'classifai' ),
						],
						'run'     => [
							'type'              => 'string',
							'enum'              => [
								'auto',
								'manual',
							],
							'default'           => 'auto',
							'sanitize_callback' => 'sanitize_text_field',
							'validate_callback' => 'rest_validate_request_arg',
							'description'       => esc_html__( 'Whether the key takeaways were generated automatically or manually.', 'classifai' ),
						],
					],
					'permission_callback' => [ $this, 'generate_key_takeaways_permissions_check' ],
				],
			]
		);
	}

	/**
	 * Check if a given request has access to generate key takeaways.
	 *
	 * This check ensures we have a proper post ID, the current user
	 * making the request has access to that post, that we are
	 * properly authenticated and that the feature is turned on.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|bool
	 */
	public function generate_key_takeaways_permissions_check( WP_REST_Request $request ) {
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
			return new WP_Error( 'not_enabled', esc_html__( 'Key takeaways not currently enabled.', 'classifai' ) );
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

		if ( strpos( $route, '/classifai/v1/key-takeaways' ) === 0 ) {
			return rest_ensure_response(
				$this->run(
					$request->get_param( 'id' ),
					'key_takeaways',
					[
						'content' => $request->get_param( 'content' ),
						'title'   => $request->get_param( 'title' ),
						'render'  => $request->get_param( 'render' ),
						'run'     => $request->get_param( 'run' ),
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

		wp_register_script(
			'key-takeaways-editor-script',
			CLASSIFAI_PLUGIN_URL . 'dist/key-takeaways-block.js',
			get_asset_info( 'key-takeaways', 'dependencies' ),
			get_asset_info( 'key-takeaways', 'version' ),
			true
		);
	}

	/**
	 * Get the description for the enable field.
	 *
	 * @return string
	 */
	public function get_enable_description(): string {
		return esc_html__( 'A new block will be registered that when added to an item, will generate key takeaways from the content.', 'classifai' );
	}

	/**
	 * Returns the default settings for the feature.
	 *
	 * @return array
	 */
	public function get_feature_default_settings(): array {
		return [
			'key_takeaways_prompt' => [
				[
					'title'    => esc_html__( 'ClassifAI default', 'classifai' ),
					'prompt'   => $this->prompt,
					'original' => 1,
				],
			],
			'provider'             => ChatGPT::ID,
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
		if ( $settings && ! empty( $settings['key_takeaways_prompt'] ) ) {
			foreach ( $settings['key_takeaways_prompt'] as $key => $prompt ) {
				if ( 1 === intval( $prompt['original'] ) ) {
					$settings['key_takeaways_prompt'][ $key ]['prompt'] = $this->prompt;
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
		$new_settings['key_takeaways_prompt'] = sanitize_prompts( 'key_takeaways_prompt', $new_settings );

		return $new_settings;
	}
}
