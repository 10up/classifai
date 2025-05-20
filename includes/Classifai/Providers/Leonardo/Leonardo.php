<?php
namespace Classifai\Providers\Leonardo;

use Classifai\Providers\Provider;
use Classifai\Features\ImageGeneration;
use Classifai\Providers\OpenAI\APIRequest;
use WP_Error;

class Leonardo extends Provider {
	const ID = 'leonardo';

	const ASYNC_GENERATION_OPTION = 'classifai_' . self::ID . '_async_image_generation_options';

	/**
	 * Leonardo.Ai URL.
	 *
	 * @var string
	 */
	protected $leonardo_url = 'https://cloud.leonardo.ai/api/rest/v1/generations';

	/**
	 * @param \Classifai\Features\ImageGeneration $feature_instance The feature instance.
	 */
	public function __construct( $feature_instance = null ) {
		$this->feature_instance = $feature_instance;
	}

	/**
	 * Register what we need for the provider.
	 *
	 * This only fires if can_register returns true.
	 */
	public function register() {
		add_action( 'classifai_' . ImageGeneration::ID . '_media_template_additional_settings', [ $this, 'render_additional_media_template_settings' ], 10, 2 );
		add_filter( 'heartbeat_received', [ $this, 'poll_image_generation_results' ], 10, 2 );
	}

	/**
	 * Returns the default settings for the provider.
	 *
	 * @return array
	 */
	public function get_default_provider_settings(): array {
		$common_settings = [
			'api_key'       => '',
			'authenticated' => false,
		];

		switch ( $this->feature_instance::ID ) {
			case ImageGeneration::ID:
				return array_merge(
					$common_settings,
					[
						'model'      => '6b645e3a-d64f-4341-a6d8-7a3690fbf042',
						'num_images' => 1,
						'preset'     => 'debdf72a-91a4-467b-bf61-cc02bdeb69c6',
					]
				);
		}

		return $common_settings;
	}

	/**
	 * Sanitization callback for settings.
	 *
	 * @param array $new_settings The settings being saved.
	 * @return array
	 */
	public function sanitize_settings( array $new_settings ): array {
		$settings = $this->feature_instance->get_settings();

		// Ensure proper validation of credentials happens here.
		$new_settings[ static::ID ]['api_key']       = sanitize_text_field( $new_settings[ static::ID ]['api_key'] ?? $settings[ static::ID ]['api_key'] );
		$new_settings[ static::ID ]['authenticated'] = true;

		if ( $this->feature_instance instanceof ImageGeneration ) {
			$new_settings[ static::ID ]['num_images'] = absint( $new_settings[ static::ID ]['num_images'] ?? $settings[ static::ID ]['num_images'] );
			$new_settings[ static::ID ]['model']      = sanitize_text_field( $new_settings[ static::ID ]['model'] ?? $settings[ static::ID ]['model'] );
			$new_settings[ static::ID ]['preset']     = sanitize_text_field( $new_settings[ static::ID ]['preset'] ?? $settings[ static::ID ]['preset'] );
		}

		return $new_settings;
	}

	/**
	 * Common entry point for all REST endpoints for this provider.
	 *
	 * @param string $prompt The prompt used to generate an image.
	 * @param string $route_to_call The route we are processing.
	 * @param array  $args Optional arguments to pass to the route.
	 * @return string|WP_Error
	 */
	public function rest_endpoint_callback( $prompt = '', string $route_to_call = '', array $args = [] ) {
		$route_to_call = strtolower( $route_to_call );
		$return        = '';

		// Handle all of our routes.
		switch ( $route_to_call ) {
			case 'image_gen':
				$return = $this->generate_image( $prompt, $args );
				break;
		}

		return $return;
	}

	/**
	 * Starting point to initiate image geenration request for the current provider.
	 *
	 * @param string $prompt Image generation request text prompt.
	 * @param array  $args   Imgage generation request args.
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
				'num_images' => $settings['num_images'],
				'model'      => $settings['model'],
				'preset'     => $settings['preset'],
			]
		);

		if ( ! $image_generation->is_feature_enabled() ) {
			return new WP_Error( 'not_enabled', esc_html__( 'Image generation is disabled or Leonardo.Ai authentication failed. Please check your settings.', 'classifai' ) );
		}

		$request = new APIRequest( $settings['api_key'] ?? '', 'generate-image' );

		$options = [
			'headers' => [
				'Accept' => 'application/json',
			],
			'body'    => wp_json_encode(
				[
					'modelId'       => sanitize_text_field( $args['model'] ),
					'contrast'      => 3.5,
					'prompt'        => sanitize_text_field( $prompt ),
					'num_images'    => absint( $args['num_images'] ),
					'styleUUID'     => sanitize_text_field( $args['preset'] ),
					'enhancePrompt' => false,

					'width'         => 1920,
					'height'        => 1080,
					'ultra'         => true,
				]
			),
		];

		$response = $request->post( $this->leonardo_url, $options );

		if ( is_wp_error( $response ) ) {
			return;
		}

		$generation_id = '';
		$response_data = $response['sdGenerationJob'];
		$generation_id = $response_data['generationId'];

		$this->save_async_image_generation_response( absint( $args['post_id'] ), $generation_id, $prompt );
	}

	/**
	 * Renders provider specific settings in the media template.
	 *
	 * @param \Classifai\Features\Feature $feature_instance The feature instance.
	 */
	public function render_additional_media_template_settings( $feature_instance ) {
		$settings = $feature_instance->get_settings();

		if ( self::ID !== $settings['provider'] ) {
			return;
		}

		?>
		<input data-image-gen-setting name="image-generation-mode" value="async" type="hidden" />
		<input data-image-gen-setting name="image-generation-provider" value="<?php echo esc_attr( self::ID ); ?>" type="hidden" />
		<?php
	}

	/**
	 * Saves the response from image generation async request.
	 * This saved data will be used to perform interval polling to check
	 * for results.
	 *
	 * @param int    $post_id       The Post ID from where the request was initiated.
	 * @param string $generation_id The ID returned by the provider that will be later
	 *                              usedto fetch the result.
	 * @param string $prompt        The image generation text prompt.
	 */
	public function save_async_image_generation_response( int $post_id, string $generation_id, string $prompt ) {
		$option = get_option( self::ASYNC_GENERATION_OPTION, [] );

		if ( ! isset( $option[ $post_id ] ) ) {
			$option[ $post_id ] = [];
		}

		$generation_data = [
			'timestamp' => time(),
			'prompt'    => $prompt,
			'results'   => [],
		];

		$option[ $post_id ][ $generation_id ] = $generation_data;

		update_option( self::ASYNC_GENERATION_OPTION, $option );
	}

	/**
	 * Heartbeat interval-polling callback to check if results are ready.
	 *
	 * @param array $response Heartbeat data array.
	 * @param array $data     Heartbeat request payload.
	 *
	 * @return array
	 */
	public function poll_image_generation_results( array $response, array $data ) {
		if ( isset( $data['classifai_action'] ) && 'classifai_check_image_generation_results' !== $data['classifai_action'] ) {
			return $response;
		}

		$post_id  = absint( $data['classifai_post_id'] );
		$provider = sanitize_text_field( $data['classifai_provider'] );

		if ( self::ID !== $provider || ! $post_id ) {
			return $response;
		}

		$option = get_option( self::ASYNC_GENERATION_OPTION, [] );

		if ( empty( $option[ $post_id ] ) || ! is_array( $option[ $post_id ] ) ) {
			return $response;
		}

		$generated_images = $option[ $post_id ] ?? [];

		/** @var \Classifai\Features\ImageGeneration $feature */
		$image_generation = new ImageGeneration();
		$settings         = $image_generation->get_settings( static::ID );
		$api_key          = $settings['api_key'] ?? '';

		if ( ! $api_key ) {
			return $response;
		}

		$image_results    = [];
		$continue_polling = [];

		foreach ( $generated_images as $generation_id => $generation_data ) {
			if ( ! $generation_id ) {
				continue;
			}

			$request  = new APIRequest( $settings['api_key'] ?? '', 'generate-image' );
			$response = $request->get( $this->leonardo_url . '/' . $generation_id );

			if ( is_wp_error( $response ) ) {
				continue;
			}

			if ( ! is_array( $response ) ) {
				continue;
			}

			if ( 'COMPLETE' !== $response['generations_by_pk']['status'] ) {
				$continue_polling[] = true;
				continue;
			}

			$generated_image_store = $response['generations_by_pk']['generated_images'];

			foreach ( $generated_image_store as $generated_image ) {
				$image_id  = $generated_image['id'];
				$image_url = $generated_image['url'];

				$image_results[] = [
					'id'  => $image_id,
					'url' => $image_url,
				];
			}
		}

		$response['continue_polling'] = in_array( false, $continue_polling, true );

		$response['generated_images'] = $image_results;

		return $response;
	}
}
