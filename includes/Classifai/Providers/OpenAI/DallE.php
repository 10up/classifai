<?php
/**
 * OpenAI DALL·E integration
 */

namespace Classifai\Providers\OpenAI;

use Classifai\Features\ImageGeneration;
use Classifai\Providers\Provider;
use Classifai\Providers\OpenAI\APIRequest;
use function Classifai\get_asset_info;
use function Classifai\render_disable_feature_link;

use WP_Error;
use WP_REST_Request;
use WP_REST_Server;

class DallE extends Provider {

	use \Classifai\Providers\OpenAI\OpenAI;

	const ID = 'openai_dalle';

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
	 * @param \Classifai\Features\Feature $feature_instance The feature instance.
	 */
	public function __construct( $feature_instance = null ) {
		parent::__construct(
			'OpenAI',
			'DALL·E',
			'openai_dalle'
		);

		// Features provided by this provider.
		$this->features = array(
			'image_generation' => __( 'Generate images', 'classifai' ),
		);

		// Set the onboarding options.
		$this->onboarding_options = array(
			'title'    => __( 'OpenAI DALL·E', 'classifai' ),
			'fields'   => array( 'api-key' ),
			'features' => array(
				'enable_image_gen' => __( 'Image generation', 'classifai' ),
			),
		);

		$this->feature_instance = $feature_instance;

		add_action( 'rest_api_init', [ $this, 'register_endpoints' ] );
	}

	/**
	 * Register what we need for the provider.
	 *
	 * This only fires if can_register returns true.
	 */
	public function register() {
		add_action( 'admin_menu', [ $this, 'register_generate_media_page' ], 0 );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_scripts' ] );
		add_action( 'print_media_templates', [ $this, 'print_media_templates' ] );
	}

	public function render_provider_fields() {
		$settings = $this->feature_instance->get_settings( static::ID );

		add_settings_field(
			static::ID . '_api_key',
			esc_html__( 'API Key', 'classifai' ),
			[ $this->feature_instance, 'render_input' ],
			$this->feature_instance->get_option_name(),
			$this->feature_instance->get_option_name() . '_section',
			[
				'option_index'  => static::ID,
				'label_for'     => 'api_key',
				'input_type'    => 'password',
				'default_value' => $settings['api_key'],
				'class'         => 'classifai-provider-field hidden' . ' provider-scope-' . static::ID, // Important to add this.
			]
		);

		add_settings_field(
			static::ID . 'number_of_images',
			esc_html__( 'Number of images', 'classifai' ),
			[ $this->feature_instance, 'render_select' ],
			$this->feature_instance->get_option_name(),
			$this->feature_instance->get_option_name() . '_section',
			[
				'option_index'  => static::ID,
				'label_for'     => 'number_of_images',
				'options'       => array_combine( range( 1, 10 ), range( 1, 10 ) ),
				'default_value' => $settings['number_of_images'],
				'description'   => __( 'Number of images that will be generated in one request. Note that each image will incur separate costs.', 'classifai' ),
				'class'         => 'classifai-provider-field hidden' . ' provider-scope-' . static::ID, // Important to add this.
			]
		);

		add_settings_field(
			static::ID . 'image_size',
			esc_html__( 'Image size', 'classifai' ),
			[ $this->feature_instance, 'render_select' ],
			$this->feature_instance->get_option_name(),
			$this->feature_instance->get_option_name() . '_section',
			[
				'option_index'  => static::ID,
				'label_for'     => 'image_size',
				'options'       => [
					'256x256'   => '256x256',
					'512x512'   => '512x512',
					'1024x1024' => '1024x1024',
				],
				'default_value' => $settings['image_size'],
				'description'   => __( 'Size of generated images.', 'classifai' ),
				'class'         => 'classifai-provider-field hidden' . ' provider-scope-' . static::ID, // Important to add this.
			]
		);
	}

	public function get_default_provider_settings() {
		$common_settings = [
			'api_key'       => '',
			'authenticated' => false,
		];

		switch ( $this->feature_instance::ID ) {
			case ImageGeneration::ID:
				return array_merge(
					$common_settings,
					[
						'number_of_images' => 1,
						'image_size'       => '256x256',
					]
				);
		}

		return $common_settings;
	}

	/**
	 * Registers a Media > Generate Image submenu
	 */
	public function register_generate_media_page() {
		$image_generation = new ImageGeneration();

		if ( $image_generation->is_feature_enabled() ) {
			$settings         = $image_generation->get_settings( static::ID );
			$number_of_images = absint( $settings['number_of_images'] );

			add_submenu_page(
				'upload.php',
				$number_of_images > 1 ? esc_html__( 'Generate Images', 'classifai' ) : esc_html__( 'Generate Image', 'classifai' ),
				$number_of_images > 1 ? esc_html__( 'Generate Images', 'classifai' ) : esc_html__( 'Generate Image', 'classifai' ),
				'upload_files',
				esc_url( admin_url( 'upload.php?action=classifai-generate-image' ) ),
				''
			);
		}
	}

	/**
	 * Enqueue the admin scripts.
	 *
	 * @since 2.4.0 Use get_asset_info to get the asset version and dependencies.
	 *
	 * @param string $hook_suffix The current admin page.
	 */
	public function enqueue_admin_scripts( $hook_suffix = '' ) {
		if ( 'post.php' !== $hook_suffix && 'post-new.php' !== $hook_suffix && 'upload.php' !== $hook_suffix ) {
			return;
		}

		$image_generation = new ImageGeneration();

		if ( $image_generation->is_feature_enabled() ) {
			$settings = $image_generation->get_settings( static::ID );
			$number_of_images = absint( $settings['number_of_images'] );

			wp_enqueue_media();

			wp_enqueue_style(
				'classifai-image-processing-style',
				CLASSIFAI_PLUGIN_URL . 'dist/media-modal.css',
				[],
				get_asset_info( 'media-modal', 'version' ),
				'all'
			);

			wp_enqueue_script(
				'classifai-generate-images',
				CLASSIFAI_PLUGIN_URL . 'dist/media-modal.js',
				array_merge( get_asset_info( 'media-modal', 'dependencies' ), array( 'jquery', 'wp-api' ) ),
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
						array_merge( get_asset_info( 'generate-image-media-upload', 'dependencies' ), array( 'jquery' ) ),
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
	}

	/**
	 * Print the templates we need for our media modal integration.
	 */
	public function print_media_templates() {
		$image_generation = new ImageGeneration();

		if ( $image_generation->is_feature_enabled() ) :
			$settings         = $image_generation->get_settings( static::ID );
			$number_of_images = absint( $settings['number_of_images'] );
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
				<p>
					<?php echo wp_kses_post( render_disable_feature_link( 'image_generation' ) ); ?>
				</p>
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

		<?php endif;
	}

	/**
	 * Setup fields
	 */
	public function setup_fields_sections() {}

	/**
	 * Sanitization for the options being saved.
	 *
	 * @param array $new_settings Array of settings about to be saved.
	 * @return array The sanitized settings to be saved.
	 */
	public function sanitize_settings( $new_settings ) {
		$settings                                    = $this->feature_instance->get_settings();
		$api_key_settings                            = $this->sanitize_api_key_settings( $new_settings, $settings );
		$new_settings[ static::ID ]['api_key']       = $api_key_settings[ static::ID ]['api_key'];
		$new_settings[ static::ID ]['authenticated'] = $api_key_settings[ static::ID ]['authenticated'];

		if ( $this->feature_instance instanceof ImageGeneration ) {
			$new_settings[ static::ID ]['number_of_images'] = absint( $new_settings[ static::ID ]['number_of_images'] ?? $settings[ static::ID ]['number_of_images'] );

			if ( in_array( $new_settings[ static::ID ]['image_size'], [ '256x256', '512x512', '1024x1024' ] ) ) {
				$new_settings[ static::ID ]['image_size'] = sanitize_text_field( $new_settings[ static::ID ]['image_size'] ?? $settings[ static::ID ]['image_size'] );
			}
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
	public function get_default_settings() {}

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
			__( 'Number of images', 'classifai' ) => absint( $settings['number_of_images'] ?? 1 ),
			__( 'Image size', 'classifai' )       => sanitize_text_field( $settings['image_size'] ?? '1024x1024' ),
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
	public function generate_image( string $prompt = '', array $args = [] ) {
		if ( ! $prompt ) {
			return new WP_Error( 'prompt_required', esc_html__( 'A prompt is required to generate an image.', 'classifai' ) );
		}

		$image_generation = new ImageGeneration();
		$settings         = $image_generation->get_settings( static::ID );
		$args             = wp_parse_args(
			array_filter( $args ),
			[
				'num'    => $settings['number_of_images'] ?? 1,
				'size'   => $settings['image_size'] ?? '1024x1024',
				'format' => 'url',
			]
		);

		if ( ! $image_generation->is_feature_enabled() ) {
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

		$request = new APIRequest( $settings['api_key'] ?? '', 'generate-image' );

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

	public function register_endpoints() {
		register_rest_route(
			'classifai/v1/openai',
			'generate-image',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'generate_image_endpoint_callback' ],
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
	 * Handle request to generate an image for a given prompt.
	 *
	 * @param WP_REST_Request $request The full request object.
	 * @return \WP_REST_Response|WP_Error
	 */
	public function generate_image_endpoint_callback( WP_REST_Request $request ) {
		return rest_ensure_response(
			( new ImageGeneration() )->run(
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
		$image_generation = new ImageGeneration();

		// Ensure the feature is enabled. Also runs a user check.
		if ( ! $image_generation->is_feature_enabled() ) {
			return new WP_Error( 'not_enabled', esc_html__( 'Image generation not currently enabled.', 'classifai' ) );
		}

		return true;
	}
}
