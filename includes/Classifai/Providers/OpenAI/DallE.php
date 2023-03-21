<?php
/**
 * OpenAI DALL·E integration
 */

namespace Classifai\Providers\OpenAI;

use Classifai\Providers\Provider;
use Classifai\Providers\OpenAI\APIRequest;
use WP_Error;

class DallE extends Provider {

	/**
	 * OpenAI model URL
	 *
	 * @var string
	 */
	protected $model_url = 'https://api.openai.com/v1/models';

	/**
	 * OpenAI DALL·E URL
	 *
	 * @var string
	 */
	protected $dalle_url = 'https://api.openai.com/v1/images/generations';

	/**
	 * OpenAI DALL·E constructor.
	 *
	 * @param string $service The service this class belongs to.
	 */
	public function __construct( $service ) {
		parent::__construct(
			'OpenAI',
			'DALL·E',
			'openai_dalle',
			$service
		);
	}

	/**
	 * Can the functionality be initialized?
	 *
	 * @return bool
	 */
	public function can_register() {
		$settings = $this->get_settings();

		if ( empty( $settings ) || ( isset( $settings['authenticated'] ) && false === $settings['authenticated'] ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Register what we need for the provider.
	 *
	 * This only fires if can_register returns true.
	 */
	public function register() {
		$settings = $this->get_settings();

		if ( isset( $settings['enable_image_gen'] ) && 1 === (int) $settings['enable_image_gen'] ) {
			add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_scripts' ] );
			add_action( 'print_media_templates', [ $this, 'print_media_templates' ] );
		}
	}

	/**
	 * Enqueue the admin scripts.
	 *
	 * @param string $hook_suffix The current admin page.
	 */
	public function enqueue_admin_scripts( $hook_suffix = '' ) {
		if ( 'post.php' !== $hook_suffix && 'post-new.php' !== $hook_suffix ) {
			return;
		}

		wp_enqueue_script(
			'classifai-generate-images',
			CLASSIFAI_PLUGIN_URL . 'src/js/modal.js', // TODO update this to a built file
			[ 'jquery', 'wp-api' ],
			CLASSIFAI_PLUGIN_VERSION,
			true
		);

		wp_localize_script(
			'classifai-generate-images',
			'classifaiDalleData',
			[
				'endpoint'   => 'classifai/v1/openai/generate-image',
				'tabText'    => esc_html__( 'Generate images', 'classifai' ),
				'buttonText' => esc_html__( 'Select image', 'classifai' ),
			]
		);
	}

	/**
	 * Print the templates we need for our media modal integration.
	 */
	public function print_media_templates() {
		?>

		<?php // Template for the Generate images tab content. Includes prompt input. ?>
		<script type="text/html" id="tmpl-dalle-prompt">
			<div class="prompt-view">
				<p>
					<?php esc_html_e( 'Enter a prompt to generate images from.', 'classifai' ); ?>
				</p>
				<p>
					<?php esc_html_e( 'Once images are generated, choose which of those you want to import into your site and finally choose which image you want to render.', 'classifai' ); ?>
				</p>
				<input type="search" class="prompt" placeholder="<?php esc_attr_e( 'Enter prompt', 'classifai' ); ?>" />
				<button type="button" class="button button-secondary button-large button-generate"><?php esc_html_e( 'Generate images', 'classifai' ); ?></button>
				<span class="error"></span>
			</div>
			<div class="generated-images">
				<h2 class="prompt-text hidden"><?php esc_html_e( 'Images generated from prompt: ', 'classifai' ); ?><span></span></h2>
				<span class="spinner"></span>
				<ul></ul>
			</div>
		</script>

		<?php // Template for a single generated image. ?>
		<script type="text/html" id="tmpl-dalle-image">
			<div class="generated-image">
				<img src="{{{ data.url }}}" />
				<button type="button" class="button button-secondary button-large button-import"><?php esc_html_e( 'Import into Media Library', 'classifai' ); ?></button>
				<span class="spinner"></span>
				<span class="error"></span>
			</div>
		</script>

		<?php
	}

	/**
	 * Setup fields
	 */
	public function setup_fields_sections() {
		$default_settings = $this->get_default_settings();

		// Add the settings section.
		add_settings_section(
			$this->get_option_name(),
			$this->provider_service_name,
			function() {
				printf(
					wp_kses(
						/* translators: %1$s is replaced with the OpenAI sign up URL */
						__( 'Don\'t have an OpenAI account yet? <a title="Sign up for an OpenAI account" href="%1$s">Sign up for one</a> in order to get your API key.', 'classifai' ),
						[
							'a' => [
								'href'  => [],
								'title' => [],
							],
						]
					),
					esc_url( 'https://platform.openai.com/signup' )
				);
			},
			$this->get_option_name()
		);

		// Add all our settings.
		add_settings_field(
			'api-key',
			esc_html__( 'API Key', 'classifai' ),
			[ $this, 'render_input' ],
			$this->get_option_name(),
			$this->get_option_name(),
			[
				'label_for'     => 'api_key',
				'input_type'    => 'password',
				'default_value' => $default_settings['api_key'],
			]
		);

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
		$new_settings  = $this->get_settings();
		$authenticated = $this->authenticate_credentials( $settings['api_key'] ?? '' );

		if ( is_wp_error( $authenticated ) ) {
			$new_settings['authenticated'] = false;
			$error_message                 = $authenticated->get_error_message();

			// For response code 429, credentials are valid but rate limit is reached.
			if ( 429 === (int) $authenticated->get_error_code() ) {
				$new_settings['authenticated'] = true;
				$error_message                 = str_replace( 'plan and billing details', '<a href="https://platform.openai.com/account/billing/overview" target="_blank" rel="noopener">plan and billing details</a>', $error_message );
			} else {
				$error_message = str_replace( 'https://platform.openai.com/account/api-keys', '<a href="https://platform.openai.com/account/api-keys" target="_blank" rel="noopener">https://platform.openai.com/account/api-keys</a>', $error_message );
			}

			add_settings_error(
				'api_key',
				'classifai-auth',
				$error_message,
				'error'
			);
		} else {
			$new_settings['authenticated'] = true;
		}

		$new_settings['api_key'] = sanitize_text_field( $settings['api_key'] ?? '' );

		if ( empty( $settings['enable_image_gen'] ) || 1 !== (int) $settings['enable_image_gen'] ) {
			$new_settings['enable_image_gen'] = 'no';
		} else {
			$new_settings['enable_image_gen'] = '1';
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
	 * Authenticate our credentials.
	 *
	 * @param string $api_key Api Key.
	 * @return bool|WP_Error
	 */
	protected function authenticate_credentials( string $api_key = '' ) {
		// Check that we have credentials before hitting the API.
		if ( empty( $api_key ) ) {
			return new WP_Error( 'auth', esc_html__( 'Please enter your OpenAI API key.', 'classifai' ) );
		}

		// Make request to ensure credentials work.
		$request  = new APIRequest( $api_key );
		$response = $request->get( $this->model_url );

		return ! is_wp_error( $response ) ? true : $response;
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
	private function get_default_settings() {
		return [
			'authenticated'    => false,
			'api_key'          => '',
			'enable_image_gen' => false,
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
			__( 'Number of images', 'classifai' ) => absint( $settings['number'] ?? 1 ),
			__( 'Image size', 'classifai' )       => sanitize_text_field( $settings['size'] ?? '1024x1024' ),
			__( 'Latest response', 'classifai' )  => $this->get_formatted_latest_response( 'classifai_openai_dalle_latest_response' ),
		];
	}

	/**
	 * Format the result of most recent request.
	 *
	 * @param string $transient Transient that holds our data.
	 * @return string
	 */
	private function get_formatted_latest_response( string $transient = '' ) {
		$data = get_transient( $transient );

		if ( ! $data ) {
			return __( 'N/A', 'classifai' );
		}

		if ( is_wp_error( $data ) ) {
			return $data->get_error_message();
		}

		return preg_replace( '/,"/', ', "', wp_json_encode( $data ) );
	}

	/**
	 * Entry point for the generate-image REST endpoint.
	 *
	 * @param string $prompt The prompt used to generate an image.
	 * @param int    $num Number of images to generate.
	 * @param string $size Size generated images should be.
	 * @return string|WP_Error
	 */
	public function generate_image_callback( string $prompt = '', int $num = null, string $size = null ) {
		if ( ! $prompt ) {
			return new WP_Error( 'prompt_required', esc_html__( 'A prompt is required to generate an image.', 'classifai' ) );
		}

		$settings = $this->get_settings();

		// These checks already ran in the REST permission_callback,
		// but we run them again here in case this method is called directly.
		if ( ! current_user_can( 'upload_files' ) ) {
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
		 * @since x.x.x
		 * @hook classifai_dalle_prompt
		 *
		 * @param {string} $prompt Prompt we are sending to DALL·E.
		 *
		 * @return {string} Prompt.
		 */
		$prompt = apply_filters( 'classifai_dalle_prompt', $prompt );

		// Set our needed params if those haven't been sent in the request.
		if ( ! $num ) {
			$num = $settings['number'] ?? 1;
		}

		if ( ! $size ) {
			$size = $settings['size'] ?? '1024x1024';
		}

		$request = new APIRequest( $settings['api_key'] ?? '' );

		/**
		 * Filter the request body before sending to DALL·E.
		 *
		 * @since x.x.x
		 * @hook classifai_dalle_request_body
		 *
		 * @param {array} $body Request body that will be sent to DALL·E.
		 *
		 * @return {array} Request body.
		 */
		$body = apply_filters(
			'classifai_dalle_request_body',
			[
				'prompt' => sanitize_text_field( $prompt ),
				'n'      => absint( $num ),
				'size'   => sanitize_text_field( $size ),
			]
		);

		// Hardcoded data for now to avoid excess API requests while testing.
		return [
			[
				'url' => 'https://oss.test/wp-content/uploads/2022/07/10up-Logo-2019@3x.png',
			],
			[
				'url' => 'https://oss.test/wp-content/uploads/2022/07/10up-Logo-2019@3x.png',
			],
			[
				'url' => 'https://oss.test/wp-content/uploads/2022/07/10up-Logo-2019@3x.png',
			],
			[
				'url' => 'https://oss.test/wp-content/uploads/2022/07/10up-Logo-2019@3x.png',
			],
			[
				'url' => 'https://oss.test/wp-content/uploads/2022/07/10up-Logo-2019@3x.png',
			],
			[
				'url' => 'https://oss.test/wp-content/uploads/2022/07/10up-Logo-2019@3x.png',
			],
			[
				'url' => 'https://oss.test/wp-content/uploads/2022/07/10up-Logo-2019@3x.png',
			],
			[
				'url' => 'https://oss.test/wp-content/uploads/2022/07/10up-Logo-2019@3x.png',
			],
			[
				'url' => 'https://oss.test/wp-content/uploads/2022/07/10up-Logo-2019@3x.png',
			],
			[
				'url' => 'https://oss.test/wp-content/uploads/2022/07/10up-Logo-2019@3x.png',
			],
		];

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
				if ( ! empty( $data['url'] ) ) {
					$cleaned_response[] = [ 'url' => esc_url_raw( $data['url'] ) ];
				}
			}

			$response = $cleaned_response;
		}

		return $response;
	}

}
