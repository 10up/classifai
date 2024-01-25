<?php

namespace Classifai\Features;

use Classifai\Providers\OpenAI\ChatGPT;
use Classifai\Services\LanguageProcessing;
use WP_REST_Server;
use WP_REST_Request;
use WP_Error;

use function Classifai\sanitize_prompts;
use function Classifai\sanitize_number_of_responses_field;
use function Classifai\get_asset_info;

/**
 * Class ContentResizing
 */
class ContentResizing extends Feature {
	/**
	 * ID of the current feature.
	 *
	 * @var string
	 */
	const ID = 'feature_content_resizing';

	/**
	 * Prompt for shrinking content.
	 *
	 * @var string
	 */
	public $condense_prompt = 'Decrease the content length no more than 2 to 4 sentences.';

	/**
	 * Prompt for growing content.
	 *
	 * @var string
	 */
	public $expand_prompt = 'Increase the content length no more than 2 to 4 sentences.';

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->label = __( 'Content Resizing', 'classifai' );

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
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
	}

	/**
	 * Register any needed endpoints.
	 */
	public function register_endpoints() {
		register_rest_route(
			'classifai/v1',
			'resize-content',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'rest_endpoint_callback' ],
				'permission_callback' => [ $this, 'resize_content_permissions_check' ],
				'args'                => [
					'id'          => [
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
						'description'       => esc_html__( 'Post ID to resize the content for.', 'classifai' ),
					],
					'content'     => [
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
						'validate_callback' => 'rest_validate_request_arg',
						'description'       => esc_html__( 'The content to resize.', 'classifai' ),
					],
					'resize_type' => [
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
						'validate_callback' => 'rest_validate_request_arg',
						'description'       => esc_html__( 'The type of resize operation. "expand" or "condense".', 'classifai' ),
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
			return new WP_Error( 'not_enabled', esc_html__( 'Content resizing is not currently enabled.', 'classifai' ) );
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

		if ( '/classifai/v1/resize-content' === $route ) {
			return rest_ensure_response(
				$this->run(
					$request->get_param( 'id' ),
					'resize_content',
					[
						'content'     => $request->get_param( 'content' ),
						'resize_type' => $request->get_param( 'resize_type' ),
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

		wp_enqueue_script(
			'classifai-content-resizing-plugin-js',
			CLASSIFAI_PLUGIN_URL . 'dist/content-resizing-plugin.js',
			get_asset_info( 'content-resizing-plugin', 'dependencies' ),
			get_asset_info( 'content-resizing-plugin', 'version' ),
			true
		);

		wp_enqueue_style(
			'classifai-content-resizing-plugin-css',
			CLASSIFAI_PLUGIN_URL . 'dist/content-resizing-plugin.css',
			[],
			get_asset_info( 'content-resizing-plugin', 'version' ),
			'all'
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
				&& 'feature_content_resizing' === sanitize_text_field( wp_unslash( $_GET['feature'] ) ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
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
		return esc_html__( '"Condense this text" and "Expand this text" menu items will be added to the paragraph block\'s toolbar menu.', 'classifai' );
	}

	/**
	 * Add any needed custom fields.
	 */
	public function add_custom_settings_fields() {
		$settings = $this->get_settings();

		add_settings_field(
			'number_of_suggestions',
			esc_html__( 'Number of suggestions', 'classifai' ),
			[ $this, 'render_input' ],
			$this->get_option_name(),
			$this->get_option_name() . '_section',
			[
				'label_for'     => 'number_of_suggestions',
				'input_type'    => 'number',
				'min'           => 1,
				'step'          => 1,
				'default_value' => $settings['number_of_suggestions'],
				'description'   => esc_html__( 'Number of suggestions that will be generated in one request.', 'classifai' ),
			]
		);

		add_settings_field(
			'condense_text_prompt',
			esc_html__( 'Condense text prompt', 'classifai' ),
			[ $this, 'render_prompt_repeater_field' ],
			$this->get_option_name(),
			$this->get_option_name() . '_section',
			[
				'label_for'     => 'condense_text_prompt',
				'placeholder'   => esc_html__( 'Decrease the content length no more than 2 to 4 sentences.', 'classifai' ),
				'default_value' => $settings['condense_text_prompt'],
				'description'   => esc_html__( 'Enter your custom prompt.', 'classifai' ),
			]
		);

		add_settings_field(
			'expand_text_prompt',
			esc_html__( 'Expand text prompt', 'classifai' ),
			[ $this, 'render_prompt_repeater_field' ],
			$this->get_option_name(),
			$this->get_option_name() . '_section',
			[
				'label_for'     => 'expand_text_prompt',
				'placeholder'   => esc_html__( 'Increase the content length no more than 2 to 4 sentences.', 'classifai' ),
				'default_value' => $settings['expand_text_prompt'],
				'description'   => esc_html__( 'Enter your custom prompt.', 'classifai' ),
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
			'number_of_suggestions' => 1,
			'condense_text_prompt'  => [
				[
					'title'    => esc_html__( 'ClassifAI default', 'classifai' ),
					'prompt'   => $this->condense_prompt,
					'original' => 1,
				],
			],
			'expand_text_prompt'    => [
				[
					'title'    => esc_html__( 'ClassifAI default', 'classifai' ),
					'prompt'   => $this->expand_prompt,
					'original' => 1,
				],
			],
			'provider'              => ChatGPT::ID,
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

		$new_settings['number_of_suggestions'] = sanitize_number_of_responses_field( 'number_of_suggestions', $new_settings, $settings );
		$new_settings['condense_text_prompt']  = sanitize_prompts( 'condense_text_prompt', $new_settings );
		$new_settings['expand_text_prompt']    = sanitize_prompts( 'expand_text_prompt', $new_settings );

		return $new_settings;
	}
}
