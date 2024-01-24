<?php

namespace Classifai\Features;

use Classifai\Services\LanguageProcessing;
use Classifai\Providers\OpenAI\ChatGPT;
use WP_REST_Server;
use WP_REST_Request;
use WP_Error;

use function Classifai\get_asset_info;
use function Classifai\sanitize_prompts;

/**
 * Class ExcerptGeneration
 */
class ExcerptGeneration extends Feature {
	/**
	 * ID of the current feature.
	 *
	 * @var string
	 */
	const ID = 'feature_excerpt_generation';

	/**
	 * Prompt for generating excerpts.
	 *
	 * @var string
	 */
	public $prompt = 'Summarize the following message using a maximum of {{WORDS}} words. Ensure this summary pairs well with the following text: {{TITLE}}.';

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->label = __( 'Excerpt Generation', 'classifai' );

		// Contains all providers that are registered to the service.
		$this->provider_instances = $this->get_provider_instances( LanguageProcessing::get_service_providers() );

		// Contains just the providers this feature supports.
		$this->supported_providers = [
			ChatGPT::ID => __( 'OpenAI ChatGPT', 'classifai' ),
		];
	}

	/**
	 * Set up necessary hooks.
	 */
	public function setup() {
		parent::setup();

		add_action( 'rest_api_init', [ $this, 'register_endpoints' ] );
		add_action( 'enqueue_block_assets', [ $this, 'enqueue_editor_assets' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
	}

	/**
	 * Register any needed endpoints.
	 */
	public function register_endpoints() {
		register_rest_route(
			'classifai/v1',
			'generate-excerpt(?:/(?P<id>\d+))?',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'rest_endpoint_callback' ],
					'args'                => [
						'id' => [
							'required'          => true,
							'type'              => 'integer',
							'sanitize_callback' => 'absint',
							'description'       => esc_html__( 'Post ID to generate excerpt for.', 'classifai' ),
						],
					],
					'permission_callback' => [ $this, 'generate_excerpt_permissions_check' ],
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
							'description'       => esc_html__( 'Content to summarize into an excerpt.', 'classifai' ),
						],
						'title'   => [
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
							'validate_callback' => 'rest_validate_request_arg',
							'description'       => esc_html__( 'Title of content we want a summary for.', 'classifai' ),
						],
					],
					'permission_callback' => [ $this, 'generate_excerpt_permissions_check' ],
				],
			]
		);
	}

	/**
	 * Check if a given request has access to generate an excerpt.
	 *
	 * This check ensures we have a proper post ID, the current user
	 * making the request has access to that post, that we are
	 * properly authenticated with OpenAI and that excerpt generation
	 * is turned on.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|bool
	 */
	public function generate_excerpt_permissions_check( WP_REST_Request $request ) {
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
			return new WP_Error( 'not_enabled', esc_html__( 'Excerpt generation not currently enabled.', 'classifai' ) );
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

		if ( '/classifai/v1/generate-excerpt' === $route ) {
			return rest_ensure_response(
				$this->run(
					$request->get_param( 'id' ),
					'excerpt',
					[
						'content' => $request->get_param( 'content' ),
						'title'   => $request->get_param( 'title' ),
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

		if ( empty( $post ) ) {
			return;
		}

		// This script removes the core excerpt panel and replaces it with our own.
		wp_enqueue_script(
			'classifai-post-excerpt',
			CLASSIFAI_PLUGIN_URL . 'dist/post-excerpt.js',
			array_merge( get_asset_info( 'post-excerpt', 'dependencies' ), [ 'lodash' ] ),
			get_asset_info( 'post-excerpt', 'version' ),
			true
		);
	}

	/**
	 * Enqueue the admin scripts.
	 *
	 * @param string $hook_suffix The current admin page.
	 */
	public function enqueue_admin_assets( string $hook_suffix ) {
		// Load jQuery UI Dialog for prompt deletion.
		if (
			(
				'tools_page_classifai' === $hook_suffix
				&& ( isset( $_GET['tab'], $_GET['feature'] ) ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				&& 'language_processing' === sanitize_text_field( wp_unslash( $_GET['tab'] ) ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				&& 'feature_excerpt_generation' === sanitize_text_field( wp_unslash( $_GET['feature'] ) ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			) ||
			'admin_page_classifai_setup' === $hook_suffix
		) {
			wp_enqueue_script( 'jquery-ui-dialog' );
			wp_enqueue_style( 'wp-jquery-ui-dialog' );

			add_action(
				'admin_footer',
				static function () {
					printf(
						'<div id="js-classifai--delete-prompt-modal" style="display:none;"><p>%1$s</p></div>',
						esc_html__( 'Are you sure you want to delete the prompt?', 'classifai' ),
					);
				}
			);
		}

		// Load asset in new post and edit post screens.
		if ( 'post.php' === $hook_suffix || 'post-new.php' === $hook_suffix ) {
			$screen = get_current_screen();

			// Load the assets for the classic editor.
			if ( $screen && ! $screen->is_block_editor() ) {
				if ( post_type_supports( $screen->post_type, 'excerpt' ) ) {
					wp_enqueue_style(
						'classifai-generate-title-classic-css',
						CLASSIFAI_PLUGIN_URL . 'dist/generate-title-classic.css',
						[],
						get_asset_info( 'generate-title-classic', 'version' ),
						'all'
					);

					wp_enqueue_script(
						'classifai-generate-excerpt-classic-js',
						CLASSIFAI_PLUGIN_URL . 'dist/generate-excerpt-classic.js',
						array_merge( get_asset_info( 'generate-excerpt-classic', 'dependencies' ), array( 'wp-api' ) ),
						get_asset_info( 'generate-excerpt-classic', 'version' ),
						true
					);

					wp_add_inline_script(
						'classifai-generate-excerpt-classic-js',
						sprintf(
							'var classifaiGenerateExcerpt = %s;',
							wp_json_encode(
								[
									'path'           => '/classifai/v1/generate-excerpt/',
									'buttonText'     => __( 'Generate excerpt', 'classifai' ),
									'regenerateText' => __( 'Re-generate excerpt', 'classifai' ),
								]
							)
						),
						'before'
					);
				}
			}

			wp_enqueue_style(
				'classifai-language-processing-style',
				CLASSIFAI_PLUGIN_URL . 'dist/language-processing.css',
				[],
				get_asset_info( 'language-processing', 'version' ),
			);
		}
	}

	/**
	 * Get the description for the enable field.
	 *
	 * @return string
	 */
	public function get_enable_description(): string {
		return esc_html__( 'A button will be added to the status panel that can be used to generate titles.', 'classifai' );
	}

	/**
	 * Add any needed custom fields.
	 */
	public function add_custom_settings_fields() {
		$settings          = $this->get_settings();
		$post_types        = \Classifai\get_post_types_for_language_settings();
		$post_type_options = array();

		foreach ( $post_types as $post_type ) {
			if ( post_type_supports( $post_type->name, 'excerpt' ) ) {
				$post_type_options[ $post_type->name ] = $post_type->label;
			}
		}

		add_settings_field(
			'generate_excerpt_prompt',
			esc_html__( 'Prompt', 'classifai' ),
			[ $this, 'render_prompt_repeater_field' ],
			$this->get_option_name(),
			$this->get_option_name() . '_section',
			[
				'option_index'  => static::ID,
				'label_for'     => 'generate_excerpt_prompt',
				'placeholder'   => $this->prompt,
				'default_value' => $settings['generate_excerpt_prompt'],
				'description'   => esc_html__( "Add a custom prompt. Note the following variables that can be used in the prompt and will be replaced with content: {{WORDS}} will be replaced with the desired excerpt length setting. {{TITLE}} will be replaced with the item's title.", 'classifai' ),
			]
		);

		add_settings_field(
			'post_types',
			esc_html__( 'Allowed post types', 'classifai' ),
			[ $this, 'render_checkbox_group' ],
			$this->get_option_name(),
			$this->get_option_name() . '_section',
			[
				'label_for'      => 'post_types',
				'options'        => $post_type_options,
				'default_values' => $settings['post_types'],
				'description'    => __( 'Choose which post types support this feature.', 'classifai' ),
			]
		);

		add_settings_field(
			'length',
			esc_html__( 'Excerpt length', 'classifai' ),
			[ $this, 'render_input' ],
			$this->get_option_name(),
			$this->get_option_name() . '_section',
			[
				'label_for'     => 'length',
				'input_type'    => 'number',
				'min'           => 1,
				'step'          => 1,
				'default_value' => $settings['length'],
				'description'   => __( 'How many words should the excerpt be? Note that the final result may not exactly match this. In testing, ChatGPT tended to exceed this number by 10-15 words.', 'classifai' ),
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
			'generate_excerpt_prompt' => [
				[
					'title'    => esc_html__( 'ClassifAI default', 'classifai' ),
					'prompt'   => $this->prompt,
					'original' => 1,
				],
			],
			'post_types'              => [],
			'length'                  => absint( apply_filters( 'excerpt_length', 55 ) ),
			'provider'                => ChatGPT::ID,
		];
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

		$new_settings['generate_excerpt_prompt'] = sanitize_prompts( 'generate_excerpt_prompt', $new_settings );

		$new_settings['length'] = absint( $settings['length'] ?? $new_settings['length'] );

		foreach ( $post_types as $post_type ) {
			if ( ! post_type_supports( $post_type->name, 'excerpt' ) ) {
				continue;
			}

			if ( ! isset( $new_settings['post_types'][ $post_type->name ] ) ) {
				$new_settings['post_types'][ $post_type->name ] = $settings['post_types'];
			} else {
				$new_settings['post_types'][ $post_type->name ] = sanitize_text_field( $new_settings['post_types'][ $post_type->name ] );
			}
		}

		return $new_settings;
	}
}
