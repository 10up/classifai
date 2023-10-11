<?php
/**
 * OpenAI ChatGPT integration
 */

namespace Classifai\Providers\OpenAI;

use Classifai\Providers\Provider;
use Classifai\Watson\Normalizer;
use WP_Error;
use function Classifai\get_asset_info;

class ChatGPT extends Provider {

	use \Classifai\Providers\OpenAI\OpenAI;

	/**
	 * OpenAI ChatGPT URL
	 *
	 * @var string
	 */
	protected $chatgpt_url = 'https://api.openai.com/v1/chat/completions';

	/**
	 * OpenAI ChatGPT model
	 *
	 * @var string
	 */
	protected $chatgpt_model = 'gpt-3.5-turbo';

	/**
	 * Maximum number of tokens our model supports
	 *
	 * @var int
	 */
	protected $max_tokens = 4096;

	/**
	 * OpenAI ChatGPT constructor.
	 *
	 * @param string $service The service this class belongs to.
	 */
	public function __construct( $service ) {
		parent::__construct(
			'OpenAI ChatGPT',
			'ChatGPT',
			'openai_chatgpt',
			$service
		);

		// Set the onboarding options.
		$this->onboarding_options = array(
			'title'    => __( 'OpenAI ChatGPT', 'classifai' ),
			'fields'   => array( 'api-key' ),
			'features' => array(
				'enable_excerpt'        => __( 'Excerpt generation', 'classifai' ),
				'enable_titles'         => __( 'Title generation', 'classifai' ),
				'enable_resize_content' => __( 'Content resizing', 'classifai' ),
			),
		);
	}

	/**
	 * Determine if the current user can access the feature
	 *
	 * @param string $feature Feature to check.
	 * @return bool
	 */
	public function is_feature_enabled( string $feature = '' ) {
		$access        = false;
		$settings      = $this->get_settings();
		$user_roles    = wp_get_current_user()->roles ?? [];
		$feature_roles = [];

		$role_keys = [
			'enable_excerpt'        => 'roles',
			'enable_titles'         => 'title_roles',
			'enable_resize_content' => 'resize_content_roles',
		];

		if ( isset( $role_keys[ $feature ] ) ) {
			$feature_roles = $settings[ $role_keys[ $feature ] ] ?? [];
		}

		// Check if user has access to the feature and the feature is turned on.
		if (
			( ! empty( $feature_roles ) && ! empty( array_intersect( $user_roles, $feature_roles ) ) )
			&& ( isset( $settings[ $feature ] ) && 1 === (int) $settings[ $feature ] )
		) {
			$access = true;
		}

		/**
		 * Filter to override permission to a ChatGPT generate feature.
		 *
		 * @since 2.3.0
		 * @hook classifai_openai_chatgpt_{$feature}
		 *
		 * @param {bool}  $access Current access value.
		 * @param {array} $settings Current feature settings.
		 *
		 * @return {bool} Should the user have access?
		 */
		return apply_filters( "classifai_openai_chatgpt_{$feature}", $access, $settings );
	}

	/**
	 * Register what we need for the plugin.
	 *
	 * This only fires if can_register returns true.
	 */
	public function register() {
		add_action( 'enqueue_block_assets', [ $this, 'enqueue_editor_assets' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
		add_action( 'edit_form_before_permalink', [ $this, 'register_generated_titles_template' ] );
	}

	/**
	 * Returns localised data for title generation.
	 */
	public function get_localised_vars() {
		global $post;

		return [
			'enabledFeatures' => [
				0 => [
					'feature'       => 'title',
					'path'          => '/classifai/v1/openai/generate-title/',
					'buttonText'    => __( 'Generate titles', 'classifai' ),
					'modalTitle'    => __( 'Select a title', 'classifai' ),
					'selectBtnText' => __( 'Select', 'classifai' ),
				],
			],
			'noPermissions'   => ! is_user_logged_in() || ! current_user_can( 'edit_post', $post->ID ),
		];
	}

	/**
	 * Enqueue the editor scripts.
	 */
	public function enqueue_editor_assets() {
		global $post;

		if ( empty( $post ) ) {
			return;
		}

		if ( $this->is_feature_enabled( 'enable_excerpt' ) ) {
			// This script removes the core excerpt panel and replaces it with our own.
			wp_enqueue_script(
				'classifai-post-excerpt',
				CLASSIFAI_PLUGIN_URL . 'dist/post-excerpt.js',
				array_merge( get_asset_info( 'post-excerpt', 'dependencies' ), [ 'lodash' ] ),
				get_asset_info( 'post-excerpt', 'version' ),
				true
			);
		}

		if ( $this->is_feature_enabled( 'enable_titles' ) ) {
			wp_enqueue_script(
				'classifai-post-status-info',
				CLASSIFAI_PLUGIN_URL . 'dist/post-status-info.js',
				get_asset_info( 'post-status-info', 'dependencies' ),
				get_asset_info( 'post-status-info', 'version' ),
				true
			);

			wp_add_inline_script(
				'classifai-post-status-info',
				sprintf(
					'var classifaiChatGPTData = %s;',
					wp_json_encode( $this->get_localised_vars() )
				),
				'before'
			);
		}

		if ( $this->is_feature_enabled( 'enable_resize_content' ) ) {
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
				CLASSIFAI_PLUGIN_VERSION,
				'all'
			);
		}
	}

	/**
	 * Enqueue the admin scripts.
	 *
	 * @param string $hook_suffix The current admin page.
	 */
	public function enqueue_admin_assets( $hook_suffix = '' ) {
		if ( 'post.php' !== $hook_suffix && 'post-new.php' !== $hook_suffix ) {
			return;
		}

		$screen   = get_current_screen();
		$settings = $this->get_settings();

		// Load the assets for the classic editor.
		if ( $screen && ! $screen->is_block_editor() ) {
			if (
				post_type_supports( $screen->post_type, 'title' ) &&
				$this->is_feature_enabled( 'enable_titles' )
			) {
				wp_enqueue_style(
					'classifai-generate-title-classic-css',
					CLASSIFAI_PLUGIN_URL . 'dist/generate-title-classic.css',
					[],
					CLASSIFAI_PLUGIN_VERSION,
					'all'
				);

				wp_enqueue_script(
					'classifai-generate-title-classic-js',
					CLASSIFAI_PLUGIN_URL . 'dist/generate-title-classic.js',
					array_merge( get_asset_info( 'generate-title-classic', 'dependencies' ), array( 'wp-api' ) ),
					get_asset_info( 'generate-title-classic', 'version' ),
					true
				);

				wp_add_inline_script(
					'classifai-generate-title-classic-js',
					sprintf(
						'var classifaiChatGPTData = %s;',
						wp_json_encode( $this->get_localised_vars() )
					),
					'before'
				);
			}

			if (
				post_type_supports( $screen->post_type, 'excerpt' ) &&
				$this->is_feature_enabled( 'enable_excerpt' )
			) {
				wp_enqueue_style(
					'classifai-generate-title-classic-css',
					CLASSIFAI_PLUGIN_URL . 'dist/generate-title-classic.css',
					[],
					CLASSIFAI_PLUGIN_VERSION,
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
								'path'           => '/classifai/v1/openai/generate-excerpt/',
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
			CLASSIFAI_PLUGIN_VERSION,
			'all'
		);
	}

	/**
	 * HTML template for title generation result popup.
	 */
	public function register_generated_titles_template() {
		?>
		<div id="classifai-openai__results" style="display: none;">
			<div id="classifai-openai__overlay" style="opacity: 0;"></div>
			<div id="classifai-openai__modal" style="opacity: 0;">
				<h2 id="classifai-openai__results-title"></h2>
				<div id="classifai-openai__close-modal-button"></div>
				<div id="classifai-openai__results-content">
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Setup fields
	 */
	public function setup_fields_sections() {
		$default_settings = $this->get_default_settings();

		// Add API fields.
		$this->setup_api_fields( $default_settings['api_key'] );

		// Add excerpt fields.
		add_settings_section(
			$this->get_option_name() . '_excerpt',
			esc_html__( 'Excerpt settings', 'classifai' ),
			'',
			$this->get_option_name()
		);

		add_settings_field(
			'enable-excerpt',
			esc_html__( 'Generate excerpt', 'classifai' ),
			[ $this, 'render_input' ],
			$this->get_option_name(),
			$this->get_option_name() . '_excerpt',
			[
				'label_for'     => 'enable_excerpt',
				'input_type'    => 'checkbox',
				'default_value' => $default_settings['enable_excerpt'],
				'description'   => __( 'A button will be added to the excerpt panel that can be used to generate an excerpt.', 'classifai' ),
			]
		);

		$roles = get_editable_roles() ?? [];
		$roles = array_combine( array_keys( $roles ), array_column( $roles, 'name' ) );

		/**
		 * Filter the allowed WordPress roles for ChatGTP
		 *
		 * @since 2.3.0
		 * @hook classifai_chatgpt_allowed_roles
		 *
		 * @param {array} $roles            Array of arrays containing role information.
		 * @param {array} $default_settings Default setting values.
		 *
		 * @return {array} Roles array.
		 */
		$roles = apply_filters( 'classifai_chatgpt_allowed_roles', $roles, $default_settings );

		add_settings_field(
			'roles',
			esc_html__( 'Allowed roles', 'classifai' ),
			[ $this, 'render_checkbox_group' ],
			$this->get_option_name(),
			$this->get_option_name() . '_excerpt',
			[
				'label_for'      => 'roles',
				'options'        => $roles,
				'default_values' => $default_settings['roles'],
				'description'    => __( 'Choose which roles are allowed to generate excerpts.', 'classifai' ),
			]
		);

		add_settings_field(
			'length',
			esc_html__( 'Excerpt length', 'classifai' ),
			[ $this, 'render_input' ],
			$this->get_option_name(),
			$this->get_option_name() . '_excerpt',
			[
				'label_for'     => 'length',
				'input_type'    => 'number',
				'min'           => 1,
				'step'          => 1,
				'default_value' => $default_settings['length'],
				'description'   => __( 'How many words should the excerpt be? Note that the final result may not exactly match this. In testing, ChatGPT tended to exceed this number by 10-15 words.', 'classifai' ),
			]
		);

		// Add title fields.
		add_settings_section(
			$this->get_option_name() . '_title',
			esc_html__( 'Title settings', 'classifai' ),
			'',
			$this->get_option_name()
		);

		add_settings_field(
			'enable-titles',
			esc_html__( 'Generate titles', 'classifai' ),
			[ $this, 'render_input' ],
			$this->get_option_name(),
			$this->get_option_name() . '_title',
			[
				'label_for'     => 'enable_titles',
				'input_type'    => 'checkbox',
				'default_value' => $default_settings['enable_titles'],
				'description'   => __( 'A button will be added to the status panel that can be used to generate titles.', 'classifai' ),
			]
		);

		add_settings_field(
			'title-roles',
			esc_html__( 'Allowed roles', 'classifai' ),
			[ $this, 'render_checkbox_group' ],
			$this->get_option_name(),
			$this->get_option_name() . '_title',
			[
				'label_for'      => 'title_roles',
				'options'        => $roles,
				'default_values' => $default_settings['title_roles'],
				'description'    => __( 'Choose which roles are allowed to generate titles.', 'classifai' ),
			]
		);

		add_settings_field(
			'number-titles',
			esc_html__( 'Number of titles', 'classifai' ),
			[ $this, 'render_select' ],
			$this->get_option_name(),
			$this->get_option_name() . '_title',
			[
				'label_for'     => 'number_titles',
				'options'       => array_combine( range( 1, 10 ), range( 1, 10 ) ),
				'default_value' => $default_settings['number_titles'],
				'description'   => __( 'Number of titles that will be generated in one request.', 'classifai' ),
			]
		);

		// Add language settings
		add_settings_section(
			$this->get_option_name() . '_lang',
			esc_html__( 'Language settings', 'classifai' ),
			'',
			$this->get_option_name()
		);

		add_settings_field(
			'language',
			esc_html__( 'Language', 'classifai' ),
			[ $this, 'render_language' ],
			$this->get_option_name(),
			$this->get_option_name() . '_lang',
			[
				'label_for'     => 'language',
				'default_value' => $default_settings['language'],
			]
		);

		// Add resizing content fields.
		add_settings_section(
			$this->get_option_name() . '_resize_content_settings',
			esc_html__( 'Resizing content settings', 'classifai' ),
			'',
			$this->get_option_name()
		);

		add_settings_field(
			'enable-resize-content',
			esc_html__( 'Enable content resizing', 'classifai' ),
			[ $this, 'render_input' ],
			$this->get_option_name(),
			$this->get_option_name() . '_resize_content_settings',
			[
				'label_for'     => 'enable_resize_content',
				'input_type'    => 'checkbox',
				'default_value' => $default_settings['enable_resize_content'],
				'description'   => __( '"Shrink content" and "Grow content" menu items will be added to the paragraph block\'s toolbar menu.', 'classifai' ),
			]
		);

		$content_resize_roles = $roles;

		unset( $content_resize_roles['contributor'], $content_resize_roles['subscriber'] );

		add_settings_field(
			'resize-content-roles',
			esc_html__( 'Allowed roles', 'classifai' ),
			[ $this, 'render_checkbox_group' ],
			$this->get_option_name(),
			$this->get_option_name() . '_resize_content_settings',
			[
				'label_for'      => 'resize_content_roles',
				'options'        => $content_resize_roles,
				'default_values' => $default_settings['resize_content_roles'],
				'description'    => __( 'Choose which roles are allowed to resize content.', 'classifai' ),
			]
		);

		add_settings_field(
			'number-resize-content',
			esc_html__( 'Number of suggestions', 'classifai' ),
			[ $this, 'render_select' ],
			$this->get_option_name(),
			$this->get_option_name() . '_resize_content_settings',
			[
				'label_for'     => 'number_resize_content',
				'options'       => array_combine( range( 1, 10 ), range( 1, 10 ) ),
				'default_value' => $default_settings['number_resize_content'],
				'description'   => __( 'Number of suggestions that will be generated in one request.', 'classifai' ),
			]
		);
	}

	/**
	 * Sanitization for the options being saved.
	 *
	 * @param array $settings Array of settings about to be saved.
	 *
	 * @return array The sanitized settings to be saved.
	 */
	public function sanitize_settings( $settings ) {
		$new_settings = $this->get_settings();
		$new_settings = array_merge(
			$new_settings,
			$this->sanitize_api_key_settings( $new_settings, $settings )
		);

		if ( empty( $settings['enable_excerpt'] ) || 1 !== (int) $settings['enable_excerpt'] ) {
			$new_settings['enable_excerpt'] = 'no';
		} else {
			$new_settings['enable_excerpt'] = '1';
		}

		if ( isset( $settings['roles'] ) && is_array( $settings['roles'] ) ) {
			$new_settings['roles'] = array_map( 'sanitize_text_field', $settings['roles'] );
		} else {
			$new_settings['roles'] = array_keys( get_editable_roles() ?? [] );
		}

		if ( isset( $settings['length'] ) && is_numeric( $settings['length'] ) && (int) $settings['length'] >= 0 ) {
			$new_settings['length'] = absint( $settings['length'] );
		} else {
			$new_settings['length'] = 55;
		}

		if ( empty( $settings['enable_titles'] ) || 1 !== (int) $settings['enable_titles'] ) {
			$new_settings['enable_titles'] = 'no';
		} else {
			$new_settings['enable_titles'] = '1';
		}

		if ( isset( $settings['title_roles'] ) && is_array( $settings['title_roles'] ) ) {
			$new_settings['title_roles'] = array_map( 'sanitize_text_field', $settings['title_roles'] );
		} else {
			$new_settings['title_roles'] = array_keys( get_editable_roles() ?? [] );
		}

		if ( isset( $settings['number_titles'] ) && is_numeric( $settings['number_titles'] ) && (int) $settings['number_titles'] >= 1 && (int) $settings['number_titles'] <= 10 ) {
			$new_settings['number_titles'] = absint( $settings['number_titles'] );
		} else {
			$new_settings['number_titles'] = 1;
		}

		if ( isset( $settings['language'] ) ) {
			$new_settings['language'] = sanitize_text_field( $settings['language'] );
		} else {
			$new_settings['language'] = sanitize_text_field( str_replace( '-', '_', get_bloginfo( 'language' ) ) );
		}

		if ( empty( $settings['enable_resize_content'] ) || 1 !== (int) $settings['enable_resize_content'] ) {
			$new_settings['enable_resize_content'] = 'no';
		} else {
			$new_settings['enable_resize_content'] = '1';
		}

		if ( isset( $settings['resize_content_roles'] ) && is_array( $settings['resize_content_roles'] ) ) {
			$new_settings['resize_content_roles'] = array_map( 'sanitize_text_field', $settings['resize_content_roles'] );
		} else {
			$new_settings['resize_content_roles'] = array_keys( get_editable_roles() ?? [] );
		}

		if ( isset( $settings['number_resize_content'] ) && is_numeric( $settings['number_resize_content'] ) && (int) $settings['number_resize_content'] >= 1 && (int) $settings['number_resize_content'] <= 10 ) {
			$new_settings['number_resize_content'] = absint( $settings['number_resize_content'] );
		} else {
			$new_settings['number_resize_content'] = 1;
		}

		return $new_settings;
	}

	/**
	 * Resets settings for the provider.
	 */
	public function reset_settings() {
		update_option( $this->get_option_name(), $this->get_default_settings() );
	}

	/**
	 * Default settings for ChatGPT
	 *
	 * @return array
	 */
	public function get_default_settings() {
		if ( ! function_exists( 'get_editable_roles' ) ) {
			require_once ABSPATH . 'wp-admin/includes/user.php';
		}
		$editable_roles = get_editable_roles() ?? [];

		return [
			'authenticated'         => false,
			'api_key'               => '',
			'enable_excerpt'        => false,
			'roles'                 => array_keys( $editable_roles ),
			'length'                => (int) apply_filters( 'excerpt_length', 55 ),
			'enable_titles'         => false,
			'title_roles'           => array_keys( $editable_roles ),
			'number_titles'         => 1,
			'enable_resize_content' => false,
			'resize_content_roles'  => array_keys( $editable_roles ),
			'number_resize_content' => 1,
			'language'              => sanitize_text_field( str_replace( '-', '_', get_bloginfo( 'language' ) ) ),
		];
	}

	/**
	 * Provides debug information related to the provider.
	 *
	 * @param array|null $settings Settings array. If empty, settings will be retrieved.
	 * @param boolean    $configured Whether the provider is correctly configured. If null, the option will be retrieved.
	 * @return string|array
	 */
	public function get_provider_debug_information( $settings = null, $configured = null ) {
		if ( is_null( $settings ) ) {
			$settings = $this->sanitize_settings( $this->get_settings() );
		}

		$authenticated  = 1 === intval( $settings['authenticated'] ?? 0 );
		$enable_excerpt = 1 === intval( $settings['enable_excerpt'] ?? 0 );
		$enable_titles  = 1 === intval( $settings['enable_titles'] ?? 0 );

		return [
			__( 'Authenticated', 'classifai' )           => $authenticated ? __( 'yes', 'classifai' ) : __( 'no', 'classifai' ),
			__( 'Generate excerpt', 'classifai' )        => $enable_excerpt ? __( 'yes', 'classifai' ) : __( 'no', 'classifai' ),
			__( 'Allowed roles (excerpt)', 'classifai' ) => implode( ', ', $settings['roles'] ?? [] ),
			__( 'Excerpt length', 'classifai' )          => $settings['length'] ?? 55,
			__( 'Generate titles', 'classifai' )         => $enable_titles ? __( 'yes', 'classifai' ) : __( 'no', 'classifai' ),
			__( 'Allowed roles (titles)', 'classifai' )  => implode( ', ', $settings['title_roles'] ?? [] ),
			__( 'Number of titles', 'classifai' )        => absint( $settings['number_titles'] ?? 1 ),
			__( 'Allowed roles (resize)', 'classifai' )  => implode( ', ', $settings['resize_content_roles'] ?? [] ),
			__( 'Number of suggestions', 'classifai' )   => absint( $settings['number_resize_content'] ?? 1 ),
			__( 'Latest response', 'classifai' )         => $this->get_formatted_latest_response( get_transient( 'classifai_openai_chatgpt_latest_response' ) ),
		];
	}

	/**
	 * Common entry point for all REST endpoints for this provider.
	 * This is called by the Service.
	 *
	 * @param int    $post_id The Post Id we're processing.
	 * @param string $route_to_call The route we are processing.
	 * @param array  $args Optional arguments to pass to the route.
	 * @return string|WP_Error
	 */
	public function rest_endpoint_callback( $post_id = 0, $route_to_call = '', $args = [] ) {
		$route_to_call = strtolower( $route_to_call );
		if ( ! $post_id || ! get_post( $post_id ) ) {
			return new WP_Error( 'post_id_required', esc_html__( 'A valid post ID is required to generate an excerpt.', 'classifai' ) );
		}

		$return = '';

		// Handle all of our routes.
		switch ( $route_to_call ) {
			case 'excerpt':
				$return = $this->generate_excerpt( $post_id, $args );
				break;
			case 'title':
				$return = $this->generate_titles( $post_id, $args );
				break;
			case 'resize_content':
				$return = $this->resize_content( $post_id, $args );
				break;
		}

		return $return;
	}

	/**
	 * Generate an excerpt using ChatGPT.
	 *
	 * @param int   $post_id The Post ID we're processing
	 * @param array $args Arguments passed in.
	 * @return string|WP_Error
	 */
	public function generate_excerpt( int $post_id = 0, array $args = [] ) {
		if ( ! $post_id || ! get_post( $post_id ) ) {
			return new WP_Error( 'post_id_required', esc_html__( 'A valid post ID is required to generate an excerpt.', 'classifai' ) );
		}

		$settings = $this->get_settings();
		$args     = wp_parse_args(
			array_filter( $args ),
			[
				'content' => '',
				'title'   => get_the_title( $post_id ),
			]
		);

		// These checks (and the one above) happen in the REST permission_callback,
		// but we run them again here in case this method is called directly.
		if ( empty( $settings ) || ( isset( $settings['authenticated'] ) && false === $settings['authenticated'] ) || ( ! $this->is_feature_enabled( 'enable_excerpt' ) && ( ! defined( 'WP_CLI' ) || ! WP_CLI ) ) ) {
			return new WP_Error( 'not_enabled', esc_html__( 'Excerpt generation is disabled or OpenAI authentication failed. Please check your settings.', 'classifai' ) );
		}

		$excerpt_length = absint( $settings['length'] ?? 55 );
		$language       = isset( $settings['language'] ) && 'en' !== $settings['language'] ? ' in ' . sanitize_text_field( $settings['language'] ) . ' language' : '';

		$request = new APIRequest( $settings['api_key'] ?? '' );

		/**
		 * Filter the prompt we will send to ChatGPT.
		 *
		 * @since 2.0.0
		 * @hook classifai_chatgpt_excerpt_prompt
		 *
		 * @param {string} $prompt Prompt we are sending to ChatGPT. Gets added before post content.
		 * @param {int} $post_id ID of post we are summarizing.
		 * @param {int} $excerpt_length Length of final excerpt.
		 *
		 * @return {string} Prompt.
		 */
		$prompt = apply_filters( 'classifai_chatgpt_excerpt_prompt', sprintf( 'Summarize the following message using a maximum of %d words%s. Ensure this summary pairs well with the following text: %s.', $excerpt_length, $language, $args['title'] ), $post_id, $excerpt_length );

		/**
		 * Filter the request body before sending to ChatGPT.
		 *
		 * @since 2.0.0
		 * @hook classifai_chatgpt_excerpt_request_body
		 *
		 * @param {array} $body Request body that will be sent to ChatGPT.
		 * @param {int} $post_id ID of post we are summarizing.
		 *
		 * @return {array} Request body.
		 */
		$body = apply_filters(
			'classifai_chatgpt_excerpt_request_body',
			[
				'model'       => $this->chatgpt_model,
				'messages'    => [
					[
						'role'    => 'system',
						'content' => $prompt,
					],
					[
						'role'    => 'user',
						'content' => $this->get_content( $post_id, $excerpt_length, false, $args['content'] ) . '',
					],
				],
				'temperature' => 0.9,
			],
			$post_id
		);

		// Make our API request.
		$response = $request->post(
			$this->chatgpt_url,
			[
				'body' => wp_json_encode( $body ),
			]
		);

		set_transient( 'classifai_openai_chatgpt_latest_response', $response, DAY_IN_SECONDS * 30 );

		// Extract out the text response, if it exists.
		if ( ! is_wp_error( $response ) && ! empty( $response['choices'] ) ) {
			foreach ( $response['choices'] as $choice ) {
				if ( isset( $choice['message'], $choice['message']['content'] ) ) {
					// ChatGPT often adds quotes to strings, so remove those as well as extra spaces.
					$response = sanitize_text_field( trim( $choice['message']['content'], ' "\'' ) );
				}
			}
		}

		return $response;
	}

	/**
	 * Generate titles using ChatGPT.
	 *
	 * @param int   $post_id The Post Id we're processing
	 * @param array $args Arguments passed in.
	 * @return string|WP_Error
	 */
	public function generate_titles( int $post_id = 0, array $args = [] ) {
		if ( ! $post_id || ! get_post( $post_id ) ) {
			return new WP_Error( 'post_id_required', esc_html__( 'Post ID is required to generate titles.', 'classifai' ) );
		}

		$settings = $this->get_settings();
		$language = isset( $settings['language'] ) && 'en' !== $settings['language'] ? ' in ' . sanitize_text_field( $settings['language'] ) . ' language' : '';
		$args     = wp_parse_args(
			array_filter( $args ),
			[
				'num'     => $settings['number_titles'] ?? 1,
				'content' => '',
			]
		);

		// These checks happen in the REST permission_callback,
		// but we run them again here in case this method is called directly.
		if ( empty( $settings ) || ( isset( $settings['authenticated'] ) && false === $settings['authenticated'] ) || ! $this->is_feature_enabled( 'enable_titles' ) ) {
			return new WP_Error( 'not_enabled', esc_html__( 'Title generation is disabled or OpenAI authentication failed. Please check your settings.', 'classifai' ) );
		}

		$request = new APIRequest( $settings['api_key'] ?? '' );

		/**
		 * Filter the prompt we will send to ChatGPT.
		 *
		 * @since 2.2.0
		 * @hook classifai_chatgpt_title_prompt
		 *
		 * @param {string} $prompt Prompt we are sending to ChatGPT. Gets added before post content.
		 * @param {int} $post_id ID of post we are summarizing.
		 * @param {array} $args Arguments passed to endpoint.
		 *
		 * @return {string} Prompt.
		 */
		$prompt = apply_filters( 'classifai_chatgpt_title_prompt', 'Write an SEO-friendly title for the following content that will encourage readers to clickthrough, staying within a range of 40 to 60 characters' . $language, $post_id, $args );

		/**
		 * Filter the request body before sending to ChatGPT.
		 *
		 * @since 2.2.0
		 * @hook classifai_chatgpt_title_request_body
		 *
		 * @param {array} $body Request body that will be sent to ChatGPT.
		 * @param {int} $post_id ID of post we are summarizing.
		 *
		 * @return {array} Request body.
		 */
		$body = apply_filters(
			'classifai_chatgpt_title_request_body',
			[
				'model'       => $this->chatgpt_model,
				'messages'    => [
					[
						'role'    => 'system',
						'content' => $prompt,
					],
					[
						'role'    => 'user',
						'content' => $this->get_content( $post_id, absint( $args['num'] ) * 15, false, $args['content'] ) . '',
					],
				],
				'temperature' => 0.9,
				'n'           => absint( $args['num'] ),
			],
			$post_id
		);

		// Make our API request.
		$response = $request->post(
			$this->chatgpt_url,
			[
				'body' => wp_json_encode( $body ),
			]
		);

		set_transient( 'classifai_openai_chatgpt_latest_response', $response, DAY_IN_SECONDS * 30 );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( empty( $response['choices'] ) ) {
			return new WP_Error( 'no_choices', esc_html__( 'No choices were returned from OpenAI.', 'classifai' ) );
		}

		// Extract out the text response.
		$return = [];
		foreach ( $response['choices'] as $choice ) {
			if ( isset( $choice['message'], $choice['message']['content'] ) ) {
				// ChatGPT often adds quotes to strings, so remove those as well as extra spaces.
				$return[] = sanitize_text_field( trim( $choice['message']['content'], ' "\'' ) );
			}
		}

		return $return;
	}

	/**
	 * Resizes content.
	 *
	 * @param int   $post_id The Post Id we're processing
	 * @param array $args Arguments passed in.
	 * @return string|WP_Error
	 */
	public function resize_content( int $post_id, array $args = array() ) {
		if ( ! $post_id || ! get_post( $post_id ) ) {
			return new WP_Error( 'post_id_required', esc_html__( 'Post ID is required to resize content.', 'classifai' ) );
		}

		$settings = $this->get_settings();
		$args     = wp_parse_args(
			array_filter( $args ),
			[
				'num' => $settings['number_resize_content'] ?? 1,
			]
		);

		$request = new APIRequest( $settings['api_key'] ?? '' );

		if ( 'shrink' === $args['resize_type'] ) {
			$prompt = 'Decrease the content length no more than 2 to 4 sentences.';
		} else {
			$prompt = 'Increase the content length no more than 2 to 4 sentences.';
		}

		/**
		 * Filter the resize prompt we will send to ChatGPT.
		 *
		 * @since 2.3.0
		 *
		 * @param {string} $prompt Resize prompt we are sending to ChatGPT. Gets added as a system prompt.
		 * @param {int} $post_id ID of post.
		 * @param {array} $args Arguments passed to endpoint.
		 *
		 * @return {string} Prompt.
		 */
		$prompt = apply_filters( 'classifai_chatgpt_' . $args['resize_type'] . '_content_prompt', $prompt, $post_id, $args );

		/**
		 * Filter the resize request body before sending to ChatGPT.
		 *
		 * @since 2.3.0
		 * @hook classifai_chatgpt_resize_content_request_body
		 *
		 * @param {array} $body Request body that will be sent to ChatGPT.
		 * @param {int}   $post_id ID of post.
		 *
		 * @return {array} Request body.
		 */
		$body = apply_filters(
			'classifai_chatgpt_resize_content_request_body',
			[
				'model'       => $this->chatgpt_model,
				'messages'    => [
					[
						'role'    => 'system',
						'content' => $prompt,
					],
					[
						'role'    => 'user',
						'content' => esc_html( $args['content'] ),
					],
				],
				'temperature' => 0.9,
				'n'           => absint( $args['num'] ),
			],
			$post_id
		);

		// Make our API request.
		$response = $request->post(
			$this->chatgpt_url,
			[
				'body' => wp_json_encode( $body ),
			]
		);

		set_transient( 'classifai_openai_chatgpt_latest_response', $response, DAY_IN_SECONDS * 30 );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( empty( $response['choices'] ) ) {
			return new WP_Error( 'no_choices', esc_html__( 'No choices were returned from OpenAI.', 'classifai' ) );
		}

		// Extract out the text response.
		$return = [];

		foreach ( $response['choices'] as $choice ) {
			if ( isset( $choice['message'], $choice['message']['content'] ) ) {
				// ChatGPT often adds quotes to strings, so remove those as well as extra spaces.
				$return[] = sanitize_text_field( trim( $choice['message']['content'], ' "\'' ) );
			}
		}

		return $return;
	}

	/**
	 * Get our content, trimming if needed.
	 *
	 * @param int    $post_id Post ID to get content from.
	 * @param int    $return_length Word length of returned content.
	 * @param bool   $use_title Whether to use the title or not.
	 * @param string $post_content The post content.
	 * @return string
	 */
	public function get_content( int $post_id = 0, int $return_length = 0, bool $use_title = true, string $post_content = '' ) {
		$tokenizer  = new Tokenizer( $this->max_tokens );
		$normalizer = new Normalizer();

		/**
		 * We first determine how many tokens, roughly, our returned content will require.
		 * This is determined by the number of words we expect to be returned and how
		 * many tokens are in an average word.
		 */
		$return_tokens = $tokenizer->tokens_in_words( $return_length );

		/**
		 * We then subtract those tokens from the max number of tokens ChatGPT allows
		 * in a single request, as well as subtracting out the number of tokens in our
		 * prompt (~50). ChatGPT counts both the tokens in the request and in
		 * the response towards the max.
		 */
		$max_content_tokens = $this->max_tokens - $return_tokens - 50;

		if ( empty( $post_content ) ) {
			$post         = get_post( $post_id );
			$post_content = apply_filters( 'the_content', $post->post_content );
		}

		$post_content = preg_replace( '#\[.+\](.+)\[/.+\]#', '$1', $post_content );

		// Then trim our content, if needed, to stay under the max.
		if ( $use_title ) {
			$content = $tokenizer->trim_content(
				$normalizer->normalize( $post_id, $post_content ),
				(int) $max_content_tokens
			);
		} else {
			$content = $tokenizer->trim_content(
				$normalizer->normalize_content( $post_content, '', $post_id ),
				(int) $max_content_tokens
			);
		}

		/**
		 * Filter content that will get sent to ChatGPT.
		 *
		 * @since 2.0.0
		 * @hook classifai_chatgpt_content
		 *
		 * @param {string} $content Content that will be sent to ChatGPT.
		 * @param {int} $post_id ID of post we are summarizing.
		 *
		 * @return {string} Content.
		 */
		return apply_filters( 'classifai_chatgpt_content', $content, $post_id );
	}

}
