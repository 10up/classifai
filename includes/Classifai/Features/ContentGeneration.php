<?php

namespace Classifai\Features;

use Classifai\Providers\Azure\OpenAI;
use Classifai\Providers\GoogleAI\GeminiAPI;
use Classifai\Providers\OpenAI\ChatGPT;
use Classifai\Providers\Browser\ChromeAI;
use Classifai\Providers\XAI\Grok;
use Classifai\Providers\Localhost\Ollama;
use Classifai\Services\LanguageProcessing;
use WP_REST_Server;
use WP_REST_Request;
use WP_Error;

use function Classifai\sanitize_prompts;
use function Classifai\get_asset_info;

/**
 * Class ContentGeneration
 */
class ContentGeneration extends Feature {
	/**
	 * ID of the feature.
	 *
	 * @var string
	 */
	const ID = 'feature_content_generation';

	/**
	 * Prompt for creating content.
	 *
	 * @var string
	 */
	public $prompt = 'Given the following summary, write a full length article to be shown on a website.';

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->label = __( 'Content Generation', 'classifai' );

		// Contains all providers that are registered to the service.
		$this->provider_instances = $this->get_provider_instances( LanguageProcessing::get_service_providers() );

		// Contains just the providers this feature supports.
		$this->supported_providers = [
			ChatGPT::ID   => __( 'OpenAI ChatGPT', 'classifai' ),
			// GeminiAPI::ID => __( 'Google AI (Gemini API)', 'classifai' ),
			// OpenAI::ID    => __( 'Azure OpenAI', 'classifai' ),
			// Grok::ID      => __( 'xAI Grok', 'classifai' ),
			// ChromeAI::ID  => __( 'Chrome AI (experimental)', 'classifai' ),
			Ollama::ID    => __( 'Ollama', 'classifai' ),
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
			'create-content',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'rest_endpoint_callback' ],
				'permission_callback' => [ $this, 'create_content_permissions_check' ],
				'args'                => [
					'id'      => [
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
						'description'       => esc_html__( 'Post ID where content should be stored.', 'classifai' ),
					],
					'summary' => [
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
						'validate_callback' => 'rest_validate_request_arg',
						'description'       => esc_html__( 'The summary that will be used to generate the full article.', 'classifai' ),
					],
				],
			]
		);
	}

	/**
	 * Check if a given request has access to create content.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|bool
	 */
	public function create_content_permissions_check( WP_REST_Request $request ) {
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
			return new WP_Error( 'not_enabled', esc_html__( 'Content Generation is not currently enabled.', 'classifai' ) );
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

		if ( strpos( $route, '/classifai/v1/create-content' ) === 0 ) {
			return rest_ensure_response(
				$this->run(
					$request->get_param( 'id' ),
					'create_content',
					[
						'summary' => $request->get_param( 'summary' ),
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
			'classifai-plugin-content-resizing-js',
			CLASSIFAI_PLUGIN_URL . 'dist/classifai-plugin-content-resizing.js',
			array_merge( get_asset_info( 'classifai-plugin-content-resizing', 'dependencies' ), [ 'lodash' ] ),
			get_asset_info( 'classifai-plugin-content-resizing', 'version' ),
			true
		);

		wp_enqueue_style(
			'classifai-plugin-content-resizing-css',
			CLASSIFAI_PLUGIN_URL . 'dist/classifai-plugin-content-resizing.css',
			[],
			get_asset_info( 'classifai-plugin-content-resizing', 'version' ),
			'all'
		);
	}

	/**
	 * Get the description for the enable field.
	 *
	 * @return string
	 */
	public function get_enable_description(): string {
		return esc_html__( 'A button will be added to the status panel that can be used to generate draft content.', 'classifai' );
	}

	/**
	 * Returns the default settings for the feature.
	 *
	 * @return array
	 */
	public function get_feature_default_settings(): array {
		return [
			'prompt'     => [
				[
					'title'    => esc_html__( 'ClassifAI default', 'classifai' ),
					'prompt'   => $this->prompt,
					'original' => 1,
				],
			],
			'post_types' => [
				'post' => 'post',
			],
			'provider'   => ChatGPT::ID,
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
		if ( $settings && ! empty( $settings['prompt'] ) ) {
			foreach ( $settings['prompt'] as $key => $prompt ) {
				if ( 1 === intval( $prompt['original'] ) ) {
					$settings['prompt'][ $key ]['prompt'] = $this->prompt;
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
		$settings   = $this->get_settings();
		$post_types = \Classifai\get_post_types_for_language_settings();

		$new_settings['prompt'] = sanitize_prompts( 'prompt', $new_settings );

		foreach ( $post_types as $post_type ) {
			if ( ! isset( $new_settings['post_types'][ $post_type->name ] ) ) {
				$new_settings['post_types'][ $post_type->name ] = $settings['post_types'];
			} else {
				$new_settings['post_types'][ $post_type->name ] = sanitize_text_field( $new_settings['post_types'][ $post_type->name ] );
			}
		}

		return $new_settings;
	}
}
