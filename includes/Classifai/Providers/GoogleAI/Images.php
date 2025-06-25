<?php
/**
 * Google AI Image integration
 */

namespace Classifai\Providers\GoogleAI;

use Classifai\Features\ImageGeneration;
use Classifai\Providers\Provider;
use WP_Error;

class Images extends Provider {

	use \Classifai\Providers\GoogleAI\GoogleAI;

	/**
	 * Provider ID
	 *
	 * @var string
	 */
	const ID = 'googleai_images';

	/**
	 * Google AI Image URL.
	 *
	 * @var string
	 */
	protected $api_url = 'https://generativelanguage.googleapis.com/v1beta';

	/**
	 * Google AI Image model.
	 *
	 * @var string
	 */
	protected $model = 'imagen-4.0-generate-preview-06-06';

	/**
	 * Maximum number of characters a prompt can have.
	 *
	 * @var int
	 */
	public $max_prompt_chars = 1920;

	/**
	 * Google AI Image constructor.
	 *
	 * @param \Classifai\Features\Feature $feature_instance The feature instance.
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
		add_filter( 'classifai_' . ImageGeneration::ID . '_rest_route_generate-image_args', [ $this, 'register_rest_args' ] );
	}

	/**
	 * Get the API URL.
	 *
	 * @return string
	 */
	public function get_api_url(): string {
		/**
		 * Filter the API URL.
		 *
		 * @since x.x.x
		 * @hook classifai_googleai_images_api_url
		 *
		 * @param {string} $url The default API URL.
		 *
		 * @return {string} The API URL.
		 */
		return apply_filters( 'classifai_googleai_images_api_url', $this->api_url );
	}

	/**
	 * Get the model name.
	 *
	 * @return string
	 */
	public function get_model(): string {
		/**
		 * Filter the model name.
		 *
		 * Useful if you want to use a different model, like
		 * imagen-4.0-ultra.
		 *
		 * @since x.x.x
		 * @hook classifai_googleai_images_model
		 *
		 * @param {string} $model The default model to use.
		 *
		 * @return {string} The model to use.
		 */
		return apply_filters( 'classifai_googleai_images_model', $this->model );
	}

	/**
	 * Get the maximum number of characters the prompt supports.
	 *
	 * @return int
	 */
	public function get_max_prompt_chars(): int {
		/**
		 * Filter the max number of characters the prompt can have.
		 *
		 * Useful if you want to change to a different model
		 * that has a different maximum.
		 *
		 * @since x.x.x
		 * @hook classifai_googleai_images_max_prompt_chars
		 *
		 * @param {int} $model The default maximum prompt characters.
		 *
		 * @return {int} The maximum prompt characters.
		 */
		return apply_filters( 'classifai_googleai_images_max_prompt_chars', $this->max_prompt_chars );
	}

	/**
	 * Returns the image aspect ratio options.
	 *
	 * @return array
	 */
	public static function get_image_aspect_ratio_options(): array {
		$options = [
			'1:1'  => __( '1:1 (square)', 'classifai' ),
			'3:4'  => __( '3:4 (portrait)', 'classifai' ),
			'4:3'  => __( '4:3 (landscape)', 'classifai' ),
			'9:16' => __( '9:16 (portrait)', 'classifai' ),
			'16:9' => __( '16:9 (landscape)', 'classifai' ),
		];

		/**
		 * Filter the image aspect ratio options that are available.
		 *
		 * Useful if you want to change to a different model
		 * that has different options.
		 *
		 * @since x.x.x
		 * @hook classifai_googleai_images_aspect_ratio_options
		 *
		 * @param {int} $model The default aspect ratio options.
		 *
		 * @return {int} The aspect ratio options.
		 */
		return apply_filters( 'classifai_googleai_images_aspect_ratio_options', $options );
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
						'number_of_images'   => 1,
						'aspect_ratio'       => '1:1',
						'per_image_settings' => false,
					]
				);
		}

		return $common_settings;
	}

	/**
	 * Sanitization for the options being saved.
	 *
	 * @param array $new_settings Array of settings about to be saved.
	 * @return array The sanitized settings to be saved.
	 */
	public function sanitize_settings( array $new_settings ): array {
		$settings         = $this->feature_instance->get_settings();
		$api_key_settings = $this->sanitize_api_key_settings( $new_settings, $settings );

		$new_settings[ static::ID ]['api_key']       = $api_key_settings[ static::ID ]['api_key'];
		$new_settings[ static::ID ]['authenticated'] = $api_key_settings[ static::ID ]['authenticated'];

		if ( $this->feature_instance instanceof ImageGeneration ) {
			$new_settings[ static::ID ]['number_of_images'] = absint( $new_settings[ static::ID ]['number_of_images'] ?? $settings[ static::ID ]['number_of_images'] );

			if ( in_array( $new_settings[ static::ID ]['aspect_ratio'], array_keys( $this->get_image_aspect_ratio_options() ), true ) ) {
				$new_settings[ static::ID ]['aspect_ratio'] = sanitize_text_field( $new_settings[ static::ID ]['aspect_ratio'] );
			} else {
				$new_settings[ static::ID ]['aspect_ratio'] = $settings[ static::ID ]['aspect_ratio'];
			}
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
				'num'          => $settings['number_of_images'] ?? 1,
				'aspect_ratio' => $settings['aspect_ratio'] ?? '1:1',
			]
		);

		if ( ! $image_generation->is_feature_enabled() ) {
			return new WP_Error( 'not_enabled', esc_html__( 'Image generation is disabled or authentication failed. Please check your settings.', 'classifai' ) );
		}

		/**
		 * Filter the prompt we will send to Google AI.
		 *
		 * @since x.x.x
		 * @hook classifai_googleai_images_prompt
		 *
		 * @param {string} $prompt Prompt we are sending to Google AI.
		 *
		 * @return {string} Prompt.
		 */
		$prompt = apply_filters( 'classifai_googleai_images_prompt', $prompt );

		$max_prompt_chars = $this->get_max_prompt_chars();

		// If our prompt exceeds the max length, throw an error.
		if ( mb_strlen( $prompt ) > $max_prompt_chars ) {
			/* translators: %d is the maximum number of characters allowed in the prompt. */
			return new WP_Error( 'invalid_param', sprintf( esc_html__( 'Your image prompt is too long. Please ensure it doesn\'t exceed %d characters.', 'classifai' ), $max_prompt_chars ) );
		}

		$request = new APIRequest( $settings['api_key'] ?? '', 'generate-image' );

		/**
		 * Filter the request body before sending to Google AI.
		 *
		 * @since x.x.x
		 * @hook classifai_googleai_images_request_body
		 *
		 * @param {array} $body Request body that will be sent to Google AI.
		 *
		 * @return {array} Request body.
		 */
		$body = apply_filters(
			'classifai_googleai_images_request_body',
			[
				'instances'  => [
					[
						'prompt' => sanitize_text_field( $prompt ),
					],
				],
				'parameters' => [
					'sampleCount' => absint( $args['num'] ),
					'aspectRatio' => sanitize_text_field( $args['aspect_ratio'] ),
				],
			],
		);

		// Make our API request.
		$response = $request->post(
			trailingslashit( $this->get_api_url() ) . 'models/' . $this->get_model() . ':predict',
			[
				'body' => wp_json_encode( $body ),
			]
		);

		// Extract out the image response, if it exists.
		if ( ! is_wp_error( $response ) && ! empty( $response['candidates'] ) ) {
			foreach ( $response['candidates'] as $candidate ) {
				if ( isset( $candidate['content'], $candidate['content']['parts'] ) ) {
					$parts    = $candidate['content']['parts'];
					$response = [ 'url' => sanitize_text_field( trim( $parts[0]['text'], ' "\'' ) ) ];
				}
			}
		}

		return $response;
	}

	/**
	 * Register additional REST arguments for the provider.
	 *
	 * @since x.x.x
	 *
	 * @param array $args Existing REST arguments.
	 * @return array
	 */
	public function register_rest_args( array $args = [] ): array {
		$provider_args = [
			'n'            => [
				'type'              => 'integer',
				'minimum'           => 1,
				'maximum'           => 4,
				'sanitize_callback' => 'absint',
				'validate_callback' => 'rest_validate_request_arg',
				'description'       => esc_html__( 'Number of images to generate', 'classifai' ),
			],
			'aspect_ratio' => [
				'type'              => 'string',
				'enum'              => array_keys( $this->get_image_aspect_ratio_options() ),
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => 'rest_validate_request_arg',
				'description'       => esc_html__( 'Aspect ratio of generated image', 'classifai' ),
			],
		];

		// Merge the provider args with the existing args.
		$args['args'] = array_merge( $args['args'], $provider_args );

		return $args;
	}
}
