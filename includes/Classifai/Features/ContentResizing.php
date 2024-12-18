<?php

namespace Classifai\Features;

use Classifai\Providers\Azure\OpenAI;
use Classifai\Providers\GoogleAI\GeminiAPI;
use Classifai\Providers\OpenAI\ChatGPT;
use Classifai\Providers\Browser\ChromeAI;
use Classifai\Services\LanguageProcessing;
use WP_REST_Server;
use WP_REST_Request;
use WP_Error;

use function Classifai\sanitize_prompts;
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
			ChatGPT::ID   => __( 'OpenAI ChatGPT', 'classifai' ),
			GeminiAPI::ID => __( 'Google AI (Gemini API)', 'classifai' ),
			OpenAI::ID    => __( 'Azure OpenAI', 'classifai' ),
			ChromeAI::ID  => __( 'Chrome AI (experimental)', 'classifai' ),
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
		add_action(
			'admin_footer',
			static function () {
				if (
					( isset( $_GET['tab'], $_GET['feature'] ) ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					&& 'language_processing' === sanitize_text_field( wp_unslash( $_GET['tab'] ) ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					&& 'feature_content_resizing' === sanitize_text_field( wp_unslash( $_GET['feature'] ) ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				) {
					printf(
						'<div id="js-classifai--delete-prompt-modal" style="display:none;"><p>%1$s</p></div>',
						esc_html__( 'Are you sure you want to delete the prompt?', 'classifai' ),
					);
				}
			}
		);
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

		if ( strpos( $route, '/classifai/v1/resize-content' ) === 0 ) {
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

		if ( empty( $post ) || ! is_admin() ) {
			return;
		}

		wp_enqueue_script(
			'classifai-plugin-content-resizing-js',
			CLASSIFAI_PLUGIN_URL . 'dist/classifai-plugin-content-resizing.js',
			get_asset_info( 'classifai-plugin-content-resizing', 'dependencies' ),
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
		return esc_html__( '"Condense this text" and "Expand this text" menu items will be added to the paragraph block\'s toolbar menu.', 'classifai' );
	}

	/**
	 * Add any needed custom fields.
	 */
	public function add_custom_settings_fields() {
		$settings = $this->get_settings();

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
			'condense_text_prompt' => [
				[
					'title'    => esc_html__( 'ClassifAI default', 'classifai' ),
					'prompt'   => $this->condense_prompt,
					'original' => 1,
				],
			],
			'expand_text_prompt'   => [
				[
					'title'    => esc_html__( 'ClassifAI default', 'classifai' ),
					'prompt'   => $this->expand_prompt,
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
		if ( $settings && ! empty( $settings['condense_text_prompt'] ) ) {
			foreach ( $settings['condense_text_prompt'] as $key => $prompt ) {
				if ( 1 === intval( $prompt['original'] ) ) {
					$settings['condense_text_prompt'][ $key ]['prompt'] = $this->condense_prompt;
					break;
				}
			}
		}

		if ( $settings && ! empty( $settings['expand_text_prompt'] ) ) {
			foreach ( $settings['expand_text_prompt'] as $key => $prompt ) {
				if ( 1 === intval( $prompt['original'] ) ) {
					$settings['expand_text_prompt'][ $key ]['prompt'] = $this->expand_prompt;
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

		$new_settings['condense_text_prompt'] = sanitize_prompts( 'condense_text_prompt', $new_settings );
		$new_settings['expand_text_prompt']   = sanitize_prompts( 'expand_text_prompt', $new_settings );

		return $new_settings;
	}

	/**
	 * Generates feature setting data required for migration from
	 * ClassifAI < 3.0.0 to 3.0.0
	 *
	 * @return array
	 */
	public function migrate_settings() {
		$old_settings = get_option( 'classifai_openai_chatgpt', array() );
		$new_settings = $this->get_default_settings();

		if ( isset( $old_settings['enable_resize_content'] ) ) {
			$new_settings['status'] = $old_settings['enable_resize_content'];
		}

		$new_settings['provider'] = 'openai_chatgpt';

		if ( isset( $old_settings['api_key'] ) ) {
			$new_settings['openai_chatgpt']['api_key'] = $old_settings['api_key'];
		}

		if ( isset( $old_settings['authenticated'] ) ) {
			$new_settings['openai_chatgpt']['authenticated'] = $old_settings['authenticated'];
		}

		if ( isset( $old_settings['number_resize_content'] ) ) {
			$new_settings['openai_chatgpt']['number_of_suggestions'] = $old_settings['number_resize_content'];
		}

		if ( isset( $old_settings['shrink_content_prompt'] ) ) {
			$new_settings['condense_text_prompt'] = $old_settings['shrink_content_prompt'];
		}

		if ( isset( $old_settings['grow_content_prompt'] ) ) {
			$new_settings['expand_text_prompt'] = $old_settings['grow_content_prompt'];
		}

		if ( isset( $old_settings['resize_content_roles'] ) ) {
			$new_settings['roles'] = $old_settings['resize_content_roles'];
		}

		if ( isset( $old_settings['resize_content_users'] ) ) {
			$new_settings['users'] = $old_settings['resize_content_users'];
		}

		if ( isset( $old_settings['resize_content_user_based_opt_out'] ) ) {
			$new_settings['user_based_opt_out'] = $old_settings['resize_content_user_based_opt_out'];
		}

		return $new_settings;
	}
}
