<?php

namespace Classifai\Features;

use Classifai\Services\ImageProcessing;
use Classifai\Providers\OpenAI\Images as OpenAIImages;
use Classifai\Providers\GoogleAI\Images as GoogleAIImagen;
use Classifai\Providers\Localhost\StableDiffusion as LocalhostStableDiffusion;
use Classifai\Providers\TogetherAI\Images as TogetherAIImages;
use WP_REST_Server;
use WP_REST_Request;
use WP_Error;

use function Classifai\get_asset_info;
use function Classifai\render_disable_feature_link;

/**
 * Class ImageGeneration
 */
class ImageGeneration extends Feature {
	/**
	 * ID of the current feature.
	 *
	 * @var string
	 */
	const ID = 'feature_image_generation';

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->label = __( 'Image Generation', 'classifai' );

		// Contains all providers that are registered to the service.
		$this->provider_instances = $this->get_provider_instances( ImageProcessing::get_service_providers() );

		// Contains just the providers this feature supports.
		$this->supported_providers = [
			OpenAIImages::ID             => __( 'OpenAI Images', 'classifai' ),
			GoogleAIImagen::ID           => __( 'Google AI Imagen', 'classifai' ),
			TogetherAIImages::ID         => __( 'Together AI', 'classifai' ),
			LocalhostStableDiffusion::ID => __( 'Stable Diffusion (local)', 'classifai' ),
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
		add_action( 'admin_menu', [ $this, 'register_generate_media_page' ], 0 );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_scripts' ] );
		add_action( 'print_media_templates', [ $this, 'print_media_templates' ] );
	}

	/**
	 * Register any needed endpoints.
	 */
	public function register_endpoints() {
		$route = 'generate-image';

		/**
		 * Filter the arguments for the REST route.
		 *
		 * This allows for adding or modifying the arguments for the route.
		 * The filter name is dynamic and based on the route.
		 * Example: classifai_feature_image_generation_rest_route_generate-image_args
		 *
		 * @since 3.0.0
		 * @hook classifai_{feature}_rest_route_{route}_args
		 *
		 * @param array $args Array of arguments for the REST route.
		 *
		 * @return array Modified array of arguments.
		 */
		$args = apply_filters(
			'classifai_' . static::ID . '_rest_route_' . $route . '_args',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'rest_endpoint_callback' ],
				'args'                => [
					'prompt' => [
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
						'validate_callback' => 'rest_validate_request_arg',
						'description'       => esc_html__( 'Prompt used to generate an image', 'classifai' ),
					],
				],
				'permission_callback' => [ $this, 'generate_image_permissions_check' ],
			]
		);

		register_rest_route(
			'classifai/v1',
			$route,
			$args
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
		// Ensure the feature is enabled. Also runs a user check.
		if ( ! $this->is_feature_enabled() ) {
			return new WP_Error( 'not_enabled', esc_html__( 'Image generation not currently enabled.', 'classifai' ) );
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

		if ( strpos( $route, '/classifai/v1/generate-image' ) === 0 ) {
			return rest_ensure_response(
				$this->run(
					$request->get_param( 'prompt' ),
					'image_gen',
					$request->get_params(),
				)
			);
		}

		return parent::rest_endpoint_callback( $request );
	}

	/**
	 * Registers a Media > Generate Image submenu.
	 */
	public function register_generate_media_page() {
		if ( ! $this->is_feature_enabled() ) {
			return;
		}

		$settings         = $this->get_settings();
		$provider_id      = $settings['provider'];
		$number_of_images = absint( $settings[ $provider_id ]['number_of_images'] );

		add_submenu_page(
			'upload.php',
			$number_of_images > 1 ? esc_html__( 'Generate Images', 'classifai' ) : esc_html__( 'Generate Image', 'classifai' ),
			$number_of_images > 1 ? esc_html__( 'Generate Images', 'classifai' ) : esc_html__( 'Generate Image', 'classifai' ),
			'upload_files',
			esc_url( admin_url( 'upload.php?action=classifai-generate-image&mode=grid' ) ),
			''
		);
	}

	/**
	 * Enqueue the admin scripts.
	 *
	 * @since 2.4.0 Use get_asset_info to get the asset version and dependencies.
	 *
	 * @param string $hook_suffix The current admin page.
	 */
	public function enqueue_admin_scripts( string $hook_suffix = '' ) {
		if ( 'post.php' !== $hook_suffix && 'post-new.php' !== $hook_suffix && 'upload.php' !== $hook_suffix ) {
			return;
		}

		if ( ! $this->is_feature_enabled() ) {
			return;
		}

		$settings         = $this->get_settings();
		$provider_id      = $settings['provider'];
		$number_of_images = absint( $settings[ $provider_id ]['number_of_images'] );

		wp_enqueue_media();

		wp_enqueue_style(
			'classifai-plugin-image-generation-media-modal-css',
			CLASSIFAI_PLUGIN_URL . 'dist/classifai-plugin-image-generation-media-modal.css',
			[],
			get_asset_info( 'classifai-plugin-image-generation-media-modal', 'version' ),
			'all'
		);

		wp_enqueue_script(
			'classifai-plugin-image-generation-media-modal-js',
			CLASSIFAI_PLUGIN_URL . 'dist/classifai-plugin-image-generation-media-modal.js',
			array_merge( get_asset_info( 'classifai-plugin-image-generation-media-modal', 'dependencies' ), array( 'jquery', 'wp-api' ) ),
			get_asset_info( 'classifai-plugin-image-generation-media-modal', 'version' ),
			true
		);

		wp_enqueue_script(
			'classifai-plugin-inserter-media-category-js',
			CLASSIFAI_PLUGIN_URL . 'dist/classifai-plugin-inserter-media-category.js',
			get_asset_info( 'classifai-plugin-inserter-media-category', 'dependencies' ),
			get_asset_info( 'classifai-plugin-inserter-media-category', 'version' ),
			true
		);

		/**
		 * Filter the default attribution added to generated images.
		 *
		 * @since 2.1.0
		 * @hook classifai_dalle_caption
		 *
		 * @param string $caption Attribution to be added as a caption to the image.
		 *
		 * @return string Caption.
		 */
		$caption = apply_filters(
			'classifai_dalle_caption',
			sprintf(
				/* translators: %1$s is replaced with the OpenAI Image Generation URL */
				esc_html__( 'Image generated by <a href="%s">OpenAI</a>', 'classifai' ),
				'https://platform.openai.com/docs/guides/image-generation'
			)
		);

		wp_localize_script(
			'classifai-plugin-image-generation-media-modal-js',
			'classifaiDalleData',
			[
				'endpoint'   => 'classifai/v1/generate-image',
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
					'classifai-plugin-image-generation-generate-image-media-upload-js',
					CLASSIFAI_PLUGIN_URL . 'dist/classifai-plugin-image-generation-generate-image-media-upload.js',
					array_merge( get_asset_info( 'classifai-plugin-image-generation-generate-image-media-upload', 'dependencies' ), array( 'jquery' ) ),
					get_asset_info( 'classifai-plugin-image-generation-generate-image-media-upload', 'version' ),
					true
				);

				wp_localize_script(
					'classifai-plugin-image-generation-generate-image-media-upload-js',
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
		if ( ! $this->is_feature_enabled() ) {
			return;
		}

		$settings           = $this->get_settings();
		$provider_id        = $settings['provider'];
		$number_of_images   = absint( $settings[ $provider_id ]['number_of_images'] );
		$per_image_settings = $settings[ $provider_id ]['per_image_settings'] ?? false;
		$provider_instance  = $this->get_feature_provider_instance( $provider_id );
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
				<textarea class="prompt" placeholder="<?php esc_attr_e( 'Enter prompt', 'classifai' ); ?>" rows="4" maxlength="<?php echo absint( $provider_instance->max_prompt_chars ?? 1000 ); ?>"></textarea>
				<br>
				<?php if ( $per_image_settings ) : ?>
					<input type="checkbox" id="view-additional-image-generation-settings" />
					<label id="view-additional-image-generation-settings-label" for="view-additional-image-generation-settings">
						<?php esc_html_e( 'Additional settings', 'classifai' ); ?>
					</label>
				<?php endif; ?>

				<div class="additional-image-generation-settings hidden">
					<?php
					$quality_options = method_exists( $provider_instance, 'get_image_quality_options' ) ? $provider_instance->get_image_quality_options() : [];
					if ( ! empty( $quality_options ) ) :
						?>
						<label>
							<span><?php esc_html_e( 'Quality:', 'classifai' ); ?></span>
							<select class="quality" name="quality">
								<?php
								$quality = $settings[ $provider_id ]['quality'];
								foreach ( $quality_options as $key => $value ) {
									echo '<option value="' . esc_attr( $key ) . '" ' . selected( $quality, $key, false ) . '>' . esc_html( $value ) . '</option>';
								}
								?>
							</select>
						</label>
					<?php endif; ?>

					<?php
					$size_options = method_exists( $provider_instance, 'get_image_size_options' ) ? $provider_instance->get_image_size_options() : [];
					if ( ! empty( $size_options ) ) :
						?>
						<label>
							<span><?php esc_html_e( 'Size:', 'classifai' ); ?></span>
							<select class="size" name="size">
								<?php
								$size = $settings[ $provider_id ]['image_size'];
								foreach ( $size_options as $key => $value ) {
									echo '<option value="' . esc_attr( $key ) . '" ' . selected( $size, $key, false ) . '>' . esc_html( $value ) . '</option>';
								}
								?>
							</select>
						</label>
					<?php endif; ?>

					<?php
					$aspect_ratio_options = method_exists( $provider_instance, 'get_image_aspect_ratio_options' ) ? $provider_instance->get_image_aspect_ratio_options() : [];
					if ( ! empty( $aspect_ratio_options ) ) :
						?>
						<label>
							<span><?php esc_html_e( 'Aspect ratio:', 'classifai' ); ?></span>
							<select class="aspect-ratio" name="aspect-ratio">
								<?php
								$aspect_ratio = $settings[ $provider_id ]['aspect_ratio'];
								foreach ( $aspect_ratio_options as $key => $value ) {
									echo '<option value="' . esc_attr( $key ) . '" ' . selected( $aspect_ratio, $key, false ) . '>' . esc_html( $value ) . '</option>';
								}
								?>
							</select>
						</label>
					<?php endif; ?>

					<?php
					$style_options = method_exists( $provider_instance, 'get_image_style_options' ) ? $provider_instance->get_image_style_options() : [];
					if ( ! empty( $style_options ) ) :
						?>
						<label>
							<span><?php esc_html_e( 'Style:', 'classifai' ); ?></span>
							<select class="style" name="style">
								<?php
								$style = $settings[ $provider_id ]['style'];
								foreach ( $style_options as $key => $value ) {
									echo '<option value="' . esc_attr( $key ) . '" ' . selected( $style, $key, false ) . '>' . esc_html( $value ) . '</option>';
								}
								?>
							</select>
						</label>
					<?php endif; ?>
				</div>
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
					<?php render_disable_feature_link( 'feature_image_generation' ); ?>
				</p>
			</div>
		</script>

		<?php
		// Template for a single generated image.
		/* phpcs:disable WordPressVIPMinimum.Security.Mustache.OutputNotation,PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage */
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
	}

	/**
	 * Assigns user roles to the $roles array.
	 */
	public function setup_roles() {
		$default_settings = $this->get_default_settings();

		// Get all roles that have the upload_files cap.
		$roles = get_editable_roles() ?? [];
		$roles = array_filter(
			$roles,
			function ( $role ) {
				return isset( $role['capabilities'], $role['capabilities']['upload_files'] ) && $role['capabilities']['upload_files'];
			}
		);
		$roles = array_combine( array_keys( $roles ), array_column( $roles, 'name' ) );

		/**
		 * Filter the allowed WordPress roles for image generation.
		 *
		 * @since 2.3.0
		 * @hook classifai_feature_image_generation_roles
		 *
		 * @param array $roles            Array of arrays containing role information.
		 * @param array $default_settings Default setting values.
		 *
		 * @return array Roles array.
		 */
		$this->roles = apply_filters( 'classifai_' . static::ID . '_roles', $roles, $default_settings );
	}

	/**
	 * Get the description for the enable field.
	 *
	 * @return string
	 */
	public function get_enable_description(): string {
		return esc_html__( 'Add a new "Generate images" tab in the media upload flow, allowing you to generate and import images.', 'classifai' );
	}

	/**
	 * Returns true if the feature meets all the criteria to be enabled.
	 *
	 * @return bool
	 */
	public function is_feature_enabled(): bool {
		$settings           = $this->get_settings();
		$is_feature_enabled = parent::is_feature_enabled() && current_user_can( 'upload_files' );

		/** This filter is documented in includes/Classifai/Features/Feature.php */
		return apply_filters( 'classifai_' . static::ID . '_is_feature_enabled', $is_feature_enabled, $settings );
	}

	/**
	 * Returns the default settings for the feature.
	 *
	 * @return array
	 */
	public function get_feature_default_settings(): array {
		return [
			'provider' => OpenAIImages::ID,
		];
	}

	/**
	 * Generates feature setting data required for migration from
	 * ClassifAI < 3.0.0 to 3.0.0
	 *
	 * @return array
	 */
	public function migrate_settings() {
		$old_settings = get_option( 'classifai_openai_dalle', array() );
		$new_settings = $this->get_default_settings();

		$new_settings['provider'] = 'openai_dalle';

		if ( isset( $old_settings['enable_image_gen'] ) ) {
			$new_settings['status'] = $old_settings['enable_image_gen'];
		}

		if ( isset( $old_settings['number'] ) ) {
			$new_settings['openai_dalle']['number_of_images'] = $old_settings['number'];
		}

		if ( isset( $old_settings['size'] ) ) {
			$new_settings['openai_dalle']['image_size'] = $old_settings['size'];
		}

		if ( isset( $old_settings['api_key'] ) ) {
			$new_settings['openai_dalle']['api_key'] = $old_settings['api_key'];
		}

		if ( isset( $old_settings['authenticated'] ) ) {
			$new_settings['openai_dalle']['authenticated'] = $old_settings['authenticated'];
		}

		if ( isset( $old_settings['image_generation_roles'] ) ) {
			$new_settings['roles'] = $old_settings['image_generation_roles'];
		}

		if ( isset( $old_settings['image_generation_users'] ) ) {
			$new_settings['users'] = $old_settings['image_generation_users'];
		}

		if ( isset( $old_settings['image_generation_user_based_opt_out'] ) ) {
			$new_settings['user_based_opt_out'] = $old_settings['image_generation_user_based_opt_out'];
		}

		return $new_settings;
	}
}
