<?php

namespace Classifai\Features;

use Classifai\Providers\Azure\OpenAI;
use Classifai\Providers\OpenAI\ChatGPT;
use Classifai\Providers\Localhost\Ollama;
use Classifai\Services\LanguageProcessing;
use WP_REST_Server;
use WP_REST_Request;
use WP_Error;

use function Classifai\sanitize_prompts;
use function Classifai\get_asset_info;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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
	public $prompt = 'Act as an experienced SEO copywriter tasked with writing an article based off of a given summary and an optionally provided title. Your goal is to craft a compelling, informative piece that adheres to SEO best practices, is well-researched, engaging to the target audience, and structured in a way that enhances readability. Incorporate relevant keywords naturally throughout the text, without compromising the flow or quality of the content. Ensure that the article provides value to the reader. Only return the contents of the article, not the title or other commentary.';

	// phpcs:disable Squiz.PHP.Heredoc.NotAllowed, PluginCheck.CodeAnalysis.Heredoc.NotAllowed
	/**
	 * The format of how we'd like content to be returned.
	 *
	 * @var string
	 */
	public $return_format = <<<EOD
The content returned should be valid WordPress block markup as described below, using elements like paragraphs and headings where appropriate. Be selective on the elements you use, defaulting to paragraphs. Please check the content before returning to ensure each element has proper opening and closing block markup and HTML tags and any required block attributes. Ensure elements don't nest inside each other, i.e. don't put a paragraph inside another paragraph or a list within a paragraph. Don't start the content with a heading, start with a paragraph.

Markup available to use; don't use any other blocks, even if requested:
<!-- wp:paragraph -->
<p>CONTENT</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2 class="wp-block-heading">CONTENT</h2>
<!-- /wp:heading -->

<!-- wp:table -->
<figure class="wp-block-table"><table class="has-fixed-layout"><tbody><tr><td>CONTENT</td></tr><tr><td>CONTENT</td></tr></tbody></table></figure>
<!-- /wp:table -->

<!-- wp:quote -->
<blockquote class="wp-block-quote">
<p>CONTENT</p>
</blockquote>
<!-- /wp:quote -->

<!-- wp:pullquote -->
<figure class="wp-block-pullquote"><blockquote><p>QUOTE</p><cite>AUTHOR</cite></blockquote></figure>
<!-- /wp:pullquote -->

<!-- wp:list -->
<ul class="wp-block-list">
<li>CONTENT</li>
</ul>
<!-- /wp:list -->

<!-- wp:list {"ordered":true} -->
<ol class="wp-block-list">
<li>CONTENT</li>
</ol>
<!-- /wp:list -->
EOD;
	// phpcs:enable Squiz.PHP.Heredoc.NotAllowed

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->label = __( 'Content Generation', 'classifai' );

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
					'id'           => [
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
						'description'       => esc_html__( 'Post ID where content should be stored.', 'classifai' ),
					],
					'summary'      => [
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
						'validate_callback' => 'rest_validate_request_arg',
						'description'       => esc_html__( 'The summary that will be used to generate the full article.', 'classifai' ),
					],
					'title'        => [
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
						'validate_callback' => 'rest_validate_request_arg',
						'description'       => esc_html__( 'The title of the article.', 'classifai' ),
					],
					'conversation' => [
						'type'        => 'object',
						'properties'  => [
							'prompt'   => [
								'type'              => 'string',
								'sanitize_callback' => 'wp_kses_post',
								'validate_callback' => 'rest_validate_request_arg',
								'description'       => esc_html__( 'The prompt a user sent.', 'classifai' ),
							],
							'response' => [
								'type'              => 'string',
								'sanitize_callback' => 'wp_kses_post',
								'validate_callback' => 'rest_validate_request_arg',
								'description'       => esc_html__( 'The response from the assistant to the prompt.', 'classifai' ),
							],
						],
						'description' => esc_html__( 'Any previous conversation between a user and assistant.', 'classifai' ),
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
						'title'        => $request->get_param( 'title' ),
						'summary'      => $request->get_param( 'summary' ),
						'conversation' => $request->get_param( 'conversation' ),
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
			'classifai-plugin-content-generation-js',
			CLASSIFAI_PLUGIN_URL . 'dist/classifai-plugin-content-generation.js',
			get_asset_info( 'classifai-plugin-content-generation', 'dependencies' ),
			get_asset_info( 'classifai-plugin-content-generation', 'version' ),
			true
		);
	}

	/**
	 * Get the description for the enable field.
	 *
	 * @return string
	 */
	public function get_enable_description(): string {
		return esc_html__( 'A sparkle icon will show in the bottom right corner in the block editor. Click on this to start the generation process.', 'classifai' );
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
		$post_types = \Classifai\get_post_types_for_language_settings();

		$new_settings['prompt'] = sanitize_prompts( 'prompt', $new_settings );

		foreach ( $post_types as $post_type ) {
			if ( ! isset( $new_settings['post_types'][ $post_type->name ] ) ) {
				$new_settings['post_types'][ $post_type->name ] = '';
			} else {
				$new_settings['post_types'][ $post_type->name ] = sanitize_text_field( $new_settings['post_types'][ $post_type->name ] );
			}
		}

		return $new_settings;
	}
}
