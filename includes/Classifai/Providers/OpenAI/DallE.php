<?php
/**
 * OpenAI DALL·E integration
 */

namespace Classifai\Providers\OpenAI;

use Classifai\Providers\Provider;
use Classifai\Providers\OpenAI\APIRequest;
use function Classifai\get_asset_info;
use WP_Error;

class DallE extends Provider {

	use \Classifai\Providers\OpenAI\OpenAI;

	/**
	 * OpenAI DALL·E URL
	 *
	 * @var string
	 */
	protected $dalle_url = 'https://api.openai.com/v1/images/generations';

	/**
	 * Maximum number of characters a prompt can have
	 *
	 * @var int
	 */
	public $max_prompt_chars = 1000;

	/**
	 * OpenAI DALL·E constructor.
	 *
	 * @param string $service The service this class belongs to.
	 */
	public function __construct( $service ) {
		parent::__construct(
			'Image Generation',
			'DALL·E',
			'openai_dalle',
			$service
		);

		// Set the onboarding options.
		$this->onboarding_options = array(
			'title'    => __( 'Image Generation', 'classifai' ),
			'fields'   => array( 'api-key' ),
			'features' => array(
				'enable_image_gen' => __( 'Image generation', 'classifai' ),
			),
		);
	}

	/**
	 * Register what we need for the provider.
	 *
	 * This only fires if can_register returns true.
	 */
	public function register() {
		if ( $this->is_feature_enabled() ) {
			add_action( 'admin_menu', [ $this, 'register_generate_media_page' ], 0 );
			add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_scripts' ] );
			add_action( 'print_media_templates', [ $this, 'print_media_templates' ] );
		}
	}

	/**
	 * Registers a Media > Generate Image submenu
	 */
	public function register_generate_media_page() {
		$settings         = $this->get_settings();
		$number_of_images = absint( $settings['number'] );

		add_submenu_page(
			'upload.php',
			$number_of_images > 1 ? esc_html__( 'Generate Images', 'classifai' ) : esc_html__( 'Generate Image', 'classifai' ),
			$number_of_images > 1 ? esc_html__( 'Generate Images', 'classifai' ) : esc_html__( 'Generate Image', 'classifai' ),
			'upload_files',
			esc_url( admin_url( 'upload.php?action=classifai-generate-image' ) ),
			''
		);
	}

	/**
	 * Enqueue the admin scripts.
	 *
	 * @param string $hook_suffix The current admin page.
	 */
	public function enqueue_admin_scripts( $hook_suffix = '' ) {
		if ( 'post.php' !== $hook_suffix && 'post-new.php' !== $hook_suffix && 'upload.php' !== $hook_suffix ) {
			return;
		}

		$settings         = $this->get_settings();
		$number_of_images = absint( $settings['number'] );

		wp_enqueue_media();

		wp_enqueue_style(
			'classifai-image-processing-style',
			CLASSIFAI_PLUGIN_URL . 'dist/media-modal.css',
			[],
			CLASSIFAI_PLUGIN_VERSION,
			'all'
		);

		wp_enqueue_script(
			'classifai-generate-images',
			CLASSIFAI_PLUGIN_URL . 'dist/media-modal.js',
			[ 'jquery', 'wp-api', 'wp-media-utils', 'wp-url' ],
			get_asset_info( 'media-modal', 'version' ),
			true
		);

		wp_enqueue_script(
			'classifai-inserter-media-category',
			CLASSIFAI_PLUGIN_URL . 'dist/inserter-media-category.js',
			get_asset_info( 'inserter-media-category', 'dependencies' ),
			get_asset_info( 'inserter-media-category', 'version' ),
			true
		);

		/**
		 * Filter the default attribution added to generated images.
		 *
		 * @since 2.1.0
		 * @hook classifai_dalle_caption
		 *
		 * @param {string} $caption Attribution to be added as a caption to the image.
		 *
		 * @return {string} Caption.
		 */
		$caption = apply_filters(
			'classifai_dalle_caption',
			sprintf(
				/* translators: %1$s is replaced with the OpenAI DALL·E URL */
				esc_html__( 'Image generated by <a href="%s">OpenAI\'s DALL·E</a>', 'classifai' ),
				'https://openai.com/research/dall-e'
			)
		);

		wp_localize_script(
			'classifai-generate-images',
			'classifaiDalleData',
			[
				'endpoint'   => 'classifai/v1/openai/generate-image',
				'tabText'    => $number_of_images > 1 ? esc_html__( 'Generate images', 'classifai' ) : esc_html__( 'Generate image', 'classifai' ),
				'errorText'  => esc_html__( 'Something went wrong. No results found', 'classifai' ),
				'buttonText' => esc_html__( 'Select image', 'classifai' ),
				'caption'    => $caption,
			]
		);

		if ( 'upload.php' === $hook_suffix ) {
			$action = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

			if ( 'classifai-generate-image' === $action ) {
				wp_enqueue_script(
					'classifai-generate-images-media-upload',
					CLASSIFAI_PLUGIN_URL . 'dist/generate-image-media-upload.js',
					[ 'jquery' ],
					get_asset_info( 'classifai-generate-images-media-upload', 'version' ),
					true
				);

				wp_localize_script(
					'classifai-generate-images-media-upload',
					'classifaiGenerateImages',
					[
						'upload_url' => esc_url( admin_url( 'upload.php' ) ),
					]
				);
			}
		}
	}

	/**
	 * Print the templates we need for our media modal integration.
	 */
	public function print_media_templates() {
		$settings         = $this->get_settings();
		$number_of_images = absint( $settings['number'] );
		?>

		<?php // Template for the Generate images tab content. Includes prompt input. ?>
		<script type="text/html" id="tmpl-dalle-prompt">
			<div class="prompt-view">
				<p>
					<?php
					if ( $number_of_images > 1 ) {
						esc_html_e( 'Enter a prompt below to generate images.', 'classifai' );
					} else {
						esc_html_e( 'Enter a prompt below to generate an image.', 'classifai' );
					}
					?>
				</p>
				<p>
					<?php
					if ( $number_of_images > 1 ) {
						esc_html_e( 'Once images are generated, choose one or more of those to import into your Media Library and then choose one image to insert.', 'classifai' );
					} else {
						esc_html_e( 'Once an image is generated, you can import it into your Media Library and then select to insert.', 'classifai' );
					}
					?>
				</p>
				<textarea class="prompt" placeholder="<?php esc_attr_e( 'Enter prompt', 'classifai' ); ?>" rows="4" maxlength="<?php echo absint( $this->max_prompt_chars ); ?>"></textarea>
				<button type="button" class="button button-secondary button-large button-generate">
					<?php
					if ( $number_of_images > 1 ) {
						esc_html_e( 'Generate images', 'classifai' );
					} else {
						esc_html_e( 'Generate image', 'classifai' );
					}
					?>
				</button>
				<span class="error"></span>
			</div>
			<div class="generated-images">
				<h2 class="prompt-text hidden">
					<?php
					if ( $number_of_images > 1 ) {
						esc_html_e( 'Images generated from prompt:', 'classifai' );
					} else {
						esc_html_e( 'Image generated from prompt:', 'classifai' );
					}
					?>
					<span></span>
				</h2>
				<span class="spinner"></span>
				<ul></ul>
			</div>
		</script>

		<?php
		// Template for a single generated image.
		/* phpcs:disable WordPressVIPMinimum.Security.Mustache.OutputNotation */
		?>
		<script type="text/html" id="tmpl-dalle-image">
			<div class="generated-image">
				<img src="data:image/png;base64,{{{ data.url }}}" />
				<button type="button" class="components-button button-secondary button-import"><?php esc_html_e( 'Import into Media Library', 'classifai' ); ?></button>
				<button type="button" class="components-button is-tertiary button-import-insert"><?php esc_html_e( 'Import and Insert', 'classifai' ); ?></button>
				<span class="spinner"></span>
				<span class="error"></span>
			</div>
		</script>
		<?php
		/* phpcs:enable WordPressVIPMinimum.Security.Mustache.OutputNotation */
		?>

		<?php
	}

	/**
	 * Setup fields
	 */
	public function setup_fields_sections() {
		$default_settings = $this->get_default_settings();

		$this->setup_api_fields( $default_settings['api_key'] );

		add_settings_field(
			'enable-image-gen',
			esc_html__( 'Enable image generation', 'classifai' ),
			[ $this, 'render_input' ],
			$this->get_option_name(),
			$this->get_option_name(),
			[
				'label_for'     => 'enable_image_gen',
				'input_type'    => 'checkbox',
				'default_value' => $default_settings['enable_image_gen'],
				'description'   => __( 'When enabled, a new Generate images tab will be shown in the media upload flow, allowing you to generate and import images.', 'classifai' ),
			]
		);

		// Get all roles that have the upload_files cap.
		$roles = get_editable_roles() ?? [];
		$roles = array_filter(
			$roles,
			function( $role ) {
				return isset( $role['capabilities'], $role['capabilities']['upload_files'] ) && $role['capabilities']['upload_files'];
			}
		);
		$roles = array_combine( array_keys( $roles ), array_column( $roles, 'name' ) );

		/**
		 * Filter the allowed WordPress roles for DALL·E
		 *
		 * @since 2.3.0
		 * @hook classifai_openai_dalle_allowed_image_roles
		 *
		 * @param {array} $roles            Array of arrays containing role information.
		 * @param {array} $default_settings Default setting values.
		 *
		 * @return {array} Roles array.
		 */
		$roles = apply_filters( 'classifai_openai_dalle_allowed_image_roles', $roles, $default_settings );

		add_settings_field(
			'roles',
			esc_html__( 'Allowed roles', 'classifai' ),
			[ $this, 'render_checkbox_group' ],
			$this->get_option_name(),
			$this->get_option_name(),
			[
				'label_for'      => 'roles',
				'options'        => $roles,
				'default_values' => $default_settings['roles'],
				'description'    => __( 'Choose which roles are allowed to generate images. Note that the roles above only include those that have permissions to upload media.', 'classifai' ),
			]
		);

		add_settings_field(
			'number',
			esc_html__( 'Number of images', 'classifai' ),
			[ $this, 'render_select' ],
			$this->get_option_name(),
			$this->get_option_name(),
			[
				'label_for'     => 'number',
				'options'       => array_combine( range( 1, 10 ), range( 1, 10 ) ),
				'default_value' => $default_settings['number'],
				'description'   => __( 'Number of images that will be generated in one request. Note that each image will incur separate costs.', 'classifai' ),
			]
		);

		add_settings_field(
			'size',
			esc_html__( 'Image size', 'classifai' ),
			[ $this, 'render_select' ],
			$this->get_option_name(),
			$this->get_option_name(),
			[
				'label_for'     => 'size',
				'options'       => [
					'256x256'   => '256x256',
					'512x512'   => '512x512',
					'1024x1024' => '1024x1024',
				],
				'default_value' => $default_settings['size'],
				'description'   => __( 'Size of generated images.', 'classifai' ),
			]
		);
	}

	/**
	 * Sanitization for the options being saved.
	 *
	 * @param array $settings Array of settings about to be saved.
	 * @return array The sanitized settings to be saved.
	 */
	public function sanitize_settings( $settings ) {
		$new_settings = $this->get_settings();
		$new_settings = array_merge(
			$new_settings,
			$this->sanitize_api_key_settings( $new_settings, $settings )
		);

		if ( empty( $settings['enable_image_gen'] ) || 1 !== (int) $settings['enable_image_gen'] ) {
			$new_settings['enable_image_gen'] = 'no';
		} else {
			$new_settings['enable_image_gen'] = '1';
		}

		if ( isset( $settings['roles'] ) && is_array( $settings['roles'] ) ) {
			$new_settings['roles'] = array_map( 'sanitize_text_field', $settings['roles'] );
		} else {
			$new_settings['roles'] = array_keys( get_editable_roles() ?? [] );
		}

		if ( isset( $settings['number'] ) && is_numeric( $settings['number'] ) && (int) $settings['number'] >= 1 && (int) $settings['number'] <= 10 ) {
			$new_settings['number'] = absint( $settings['number'] );
		} else {
			$new_settings['number'] = 1;
		}

		if ( isset( $settings['size'] ) && in_array( $settings['size'], [ '256x256', '512x512', '1024x1024' ], true ) ) {
			$new_settings['size'] = sanitize_text_field( $settings['size'] );
		} else {
			$new_settings['size'] = '1024x1024';
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
		return [
			'authenticated'    => false,
			'api_key'          => '',
			'enable_image_gen' => false,
			'roles'            => array_keys( get_editable_roles() ?? [] ),
			'number'           => 1,
			'size'             => '1024x1024',
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

		$authenticated = 1 === intval( $settings['authenticated'] ?? 0 );
		$enabled       = 1 === intval( $settings['enable_image_gen'] ?? 0 );

		return [
			__( 'Authenticated', 'classifai' )    => $authenticated ? __( 'yes', 'classifai' ) : __( 'no', 'classifai' ),
			__( 'Generate images', 'classifai' )  => $enabled ? __( 'yes', 'classifai' ) : __( 'no', 'classifai' ),
			__( 'Allowed roles', 'classifai' )    => implode( ', ', $settings['roles'] ?? [] ),
			__( 'Number of images', 'classifai' ) => absint( $settings['number'] ?? 1 ),
			__( 'Image size', 'classifai' )       => sanitize_text_field( $settings['size'] ?? '1024x1024' ),
			__( 'Latest response', 'classifai' )  => $this->get_formatted_latest_response( get_transient( 'classifai_openai_dalle_latest_response' ) ),
		];
	}

	/**
	 * Entry point for the generate-image REST endpoint.
	 *
	 * @param string $prompt The prompt used to generate an image.
	 * @param array  $args Optional arguments passed to endpoint.
	 * @return string|WP_Error
	 */
	public function generate_image_callback( string $prompt = '', array $args = [] ) {
		if ( ! $prompt ) {
			return new WP_Error( 'prompt_required', esc_html__( 'A prompt is required to generate an image.', 'classifai' ) );
		}

		$settings = $this->get_settings();
		$args     = wp_parse_args(
			array_filter( $args ),
			[
				'num'    => $settings['number'] ?? 1,
				'size'   => $settings['size'] ?? '1024x1024',
				'format' => 'url',
			]
		);

		// These checks already ran in the REST permission_callback,
		// but we run them again here in case this method is called directly.
		if ( ! $this->is_feature_enabled() ) {
			// Note that we purposely leave off the textdomain here as this is the same error
			// message core uses, so we want translations to load from there.
			return new WP_Error( 'rest_forbidden', esc_html__( 'Sorry, you are not allowed to do that.' ) );
		}

		if ( empty( $settings ) || ( isset( $settings['authenticated'] ) && false === $settings['authenticated'] ) || ( isset( $settings['enable_image_gen'] ) && 'no' === $settings['enable_image_gen'] ) ) {
			return new WP_Error( 'not_enabled', esc_html__( 'Image generation is disabled or OpenAI authentication failed. Please check your settings.', 'classifai' ) );
		}

		/**
		 * Filter the prompt we will send to DALL·E.
		 *
		 * @since 2.0.0
		 * @hook classifai_dalle_prompt
		 *
		 * @param {string} $prompt Prompt we are sending to DALL·E.
		 *
		 * @return {string} Prompt.
		 */
		$prompt = apply_filters( 'classifai_dalle_prompt', $prompt );

		// If our prompt exceeds the max length, throw an error.
		if ( mb_strlen( $prompt ) > $this->max_prompt_chars ) {
			return new WP_Error( 'invalid_param', esc_html__( 'Your image prompt is too long. Please ensure it doesn\'t exceed 1000 characters.', 'classifai' ) );
		}

		$request = new APIRequest( $settings['api_key'] ?? '' );

		/**
		 * Filter the request body before sending to DALL·E.
		 *
		 * @since 2.0.0
		 * @hook classifai_dalle_request_body
		 *
		 * @param {array} $body Request body that will be sent to DALL·E.
		 *
		 * @return {array} Request body.
		 */
		$body = apply_filters(
			'classifai_dalle_request_body',
			[
				'prompt'          => sanitize_text_field( $prompt ),
				'n'               => absint( $args['num'] ),
				'size'            => sanitize_text_field( $args['size'] ),
				'response_format' => sanitize_text_field( $args['format'] ),
			]
		);

		// Make our API request.
		$response = $request->post(
			$this->dalle_url,
			[
				'body' => wp_json_encode( $body ),
			]
		);

		set_transient( 'classifai_openai_dalle_latest_response', $response, DAY_IN_SECONDS * 30 );

		// Extract out the image response, if it exists.
		if ( ! is_wp_error( $response ) && ! empty( $response['data'] ) ) {
			$cleaned_response = [];

			foreach ( $response['data'] as $data ) {
				if ( ! empty( $data[ $args['format'] ] ) ) {
					if ( 'url' === $args['format'] ) {
						$cleaned_response[] = [ 'url' => esc_url_raw( $data[ $args['format'] ] ) ];
					} else {
						$cleaned_response[] = [ 'url' => $data[ $args['format'] ] ];
					}
				}
			}

			$response = $cleaned_response;
		}

		return $response;
	}

	/**
	 * Checks whether we can generate images.
	 *
	 * @return bool
	 */
	public function is_feature_enabled() {
		$access   = false;
		$settings = $this->get_settings();

		// Check if the current user has permission to generate images.
		$roles      = $settings['roles'] ?? [];
		$user_roles = wp_get_current_user()->roles ?? [];

		if (
			current_user_can( 'upload_files' )
			&& ( ! empty( $roles ) && empty( array_diff( $user_roles, $roles ) ) )
			&& ( isset( $settings['enable_image_gen'] ) && 1 === (int) $settings['enable_image_gen'] )
		) {
			$access = true;
		}

		/**
		 * Filter to override permission to use the image gen feature.
		 *
		 * @since 2.3.0
		 * @hook classifai_openai_dalle_enable_image_gen
		 *
		 * @param {bool} $access  Current access value.
		 * @param {array} $settings Feature settings.
		 *
		 * @return {bool} Should the user have access?
		 */
		return apply_filters( 'classifai_openai_dalle_enable_image_gen', $access, $settings );
	}

}
