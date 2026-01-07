<?php
/**
 * Stable Diffusion integration
 */

namespace Classifai\Providers\Localhost;

use Classifai\Providers\Provider;
use Classifai\Providers\OpenAI\APIRequest;
use Classifai\Features\ImageGeneration;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Stable Diffusion class
 */
class StableDiffusion extends Provider {

	/**
	 * The Provider ID.
	 */
	const ID = 'stable_diffusion';

	/**
	 * Maximum number of characters a prompt can have.
	 *
	 * @var int
	 */
	public $max_prompt_chars = 180;

	/**
	 * Stable Diffusion constructor.
	 *
	 * @param \Classifai\Features\Feature $feature_instance The feature instance.
	 */
	public function __construct( $feature_instance = null ) {
		$this->feature_instance = $feature_instance;
	}

	/**
	 * Register what we need for the provider.
	 */
	public function register() {
		$feature = new ImageGeneration();

		if (
			! $feature->is_feature_enabled() ||
			$feature->get_feature_provider_instance()::ID !== static::ID
		) {
			return;
		}

		add_filter( 'classifai_' . ImageGeneration::ID . '_rest_route_generate-image_args', [ $this, 'register_rest_args' ] );
		add_filter( 'classifai_dalle_caption', [ $this, 'modify_default_caption' ] );
	}

	/**
	 * Returns the image size options.
	 *
	 * @return array
	 */
	public static function get_image_size_options(): array {
		$options = [
			'1024x1024' => __( '1024x1024 (square)', 'classifai' ),
			'1536x1024' => __( '1536x1024 (landscape)', 'classifai' ),
			'1024x1536' => __( '1024x1536 (portrait)', 'classifai' ),
		];

		/**
		 * Filter the image size options that are available.
		 *
		 * Useful if you want to change to a different model
		 * that has different options.
		 *
		 * @since 3.6.0
		 * @hook classifai_stable_diffusion_size_options
		 *
		 * @param array $options The default size options.
		 *
		 * @return array The size options.
		 */
		return apply_filters( 'classifai_stable_diffusion_size_options', $options );
	}

	/**
	 * Returns the default settings for this provider.
	 *
	 * @return array
	 */
	public function get_default_provider_settings(): array {
		return [
			'endpoint_url'       => 'http://127.0.0.1:7860/',
			'authenticated'      => false,
			'model'              => '',
			'models'             => [],
			'number_of_images'   => 1,
			'image_size'         => '1024x1024',
			'per_image_settings' => false,
		];
	}

	/**
	 * Sanitize the settings for this Provider.
	 *
	 * @param array $new_settings The settings array.
	 * @return array
	 */
	public function sanitize_settings( array $new_settings ): array {
		$settings = $this->feature_instance->get_settings();

		$new_settings[ static::ID ]['authenticated'] = $settings[ static::ID ]['authenticated'];

		if ( ! empty( $new_settings[ static::ID ]['endpoint_url'] ) ) {
			$new_url = trailingslashit( esc_url_raw( $new_settings[ static::ID ]['endpoint_url'] ) );

			$new_settings[ static::ID ]['models'] = $this->get_models(
				[
					'endpoint_url' => $new_url,
				]
			);

			$new_settings[ static::ID ]['endpoint_url'] = $new_url;

			if ( ! empty( $new_settings[ static::ID ]['models'] ) ) {
				$new_settings[ static::ID ]['authenticated'] = true;
			} else {
				$new_settings[ static::ID ]['models']        = [];
				$new_settings[ static::ID ]['authenticated'] = false;
			}
		} else {
			$new_settings[ static::ID ]['endpoint_url'] = $settings[ static::ID ]['endpoint_url'];

			add_settings_error(
				$this->feature_instance->get_option_name(),
				'classifai-auth-empty',
				esc_html__( 'Please enter a valid endpoint URL in order to connect.', 'classifai' ),
				'error'
			);
		}

		$new_settings[ static::ID ]['model'] = sanitize_text_field( $new_settings[ static::ID ]['model'] ?? $settings[ static::ID ]['model'] );

		// Ensure the model being saved is valid. If not valid or we don't have one, use the first model.
		if ( ! in_array( $new_settings[ static::ID ]['model'], array_keys( $new_settings[ static::ID ]['models'] ), true ) ) {
			$new_settings[ static::ID ]['model'] = array_keys( $new_settings[ static::ID ]['models'] )[0];
		}

		$new_settings[ static::ID ]['number_of_images'] = absint( $new_settings[ static::ID ]['number_of_images'] ?? $settings[ static::ID ]['number_of_images'] );

		if ( in_array( $new_settings[ static::ID ]['image_size'], array_keys( $this->get_image_size_options() ), true ) ) {
			$new_settings[ static::ID ]['image_size'] = sanitize_text_field( $new_settings[ static::ID ]['image_size'] );
		} else {
			$new_settings[ static::ID ]['image_size'] = $settings[ static::ID ]['image_size'];
		}

		return $new_settings;
	}

	/**
	 * Register additional REST arguments for the provider.
	 *
	 * @param array $args Existing REST arguments.
	 * @return array
	 */
	public function register_rest_args( array $args = [] ): array {
		$provider_args = [
			'n'    => [
				'type'              => 'integer',
				'minimum'           => 1,
				'maximum'           => 5,
				'sanitize_callback' => 'absint',
				'validate_callback' => 'rest_validate_request_arg',
				'description'       => esc_html__( 'Number of images to generate', 'classifai' ),
			],
			'size' => [
				'type'              => 'string',
				'enum'              => array_keys( $this->get_image_size_options() ),
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => 'rest_validate_request_arg',
				'description'       => esc_html__( 'Size of generated image', 'classifai' ),
			],
		];

		// Merge the provider args with the existing args.
		$args['args'] = array_merge( $args['args'], $provider_args );

		return $args;
	}

	/**
	 * Modify the default caption for the image generation.
	 *
	 * @return string
	 */
	public function modify_default_caption(): string {
		return sprintf(
			/* translators: %1$s is replaced with the Stable Diffusion URL */
			esc_html__( 'Image generated by <a href="%s">Stable Diffusion</a>', 'classifai' ),
			'https://github.com/AUTOMATIC1111/stable-diffusion-webui/'
		);
	}

	/**
	 * Connects to Stable Diffusion and retrieves supported models.
	 *
	 * @param array $args Overridable args.
	 * @return array
	 */
	public function get_models( array $args = [] ): array {
		$settings = $this->feature_instance->get_settings( static::ID );

		$default = [
			'endpoint_url' => $settings[ static::ID ]['endpoint_url'] ?? '',
		];

		$default = wp_parse_args( $args, $default );

		// Return if credentials don't exist.
		if ( empty( $default['endpoint_url'] ) ) {
			return [];
		}

		// Make our request.
		$request  = new APIRequest( 'test' );
		$response = $request->get(
			$this->get_api_model_url( $default['endpoint_url'] ),
			[
				'timeout' => 30, // phpcs:ignore WordPressVIPMinimum.Performance.RemoteRequestTimeout.timeout_timeout
				'use_vip' => true,
			]
		);

		if ( is_wp_error( $response ) ) {
			add_settings_error(
				$this->feature_instance->get_option_name(),
				'stable-diffusion-request-failed',
				sprintf(
					/* translators: %s is replaced with the error message */
					esc_html__( 'Error making request, please ensure the Stable Diffusion service is running: %s', 'classifai' ),
					$response->get_error_message()
				),
				'error'
			);

			return [];
		}

		$sanitized_models = [];

		if ( is_array( $response ) ) {
			foreach ( $response as $model ) {
				$sanitized_models[ $model['title'] ] = $model['model_name'];
			}
		}

		return $sanitized_models;
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
				'format' => 'b64_json',
			]
		);

		if ( ! $image_generation->is_feature_enabled() ) {
			return new WP_Error( 'not_enabled', esc_html__( 'Image generation is disabled. Please check your settings.', 'classifai' ) );
		}

		/**
		 * Filter the prompt we will send to Stable Diffusion.
		 *
		 * @since 3.6.0
		 * @hook classifai_stable_diffusion_prompt
		 *
		 * @param string $prompt Prompt we are sending to Stable Diffusion.
		 *
		 * @return string Prompt.
		 */
		$prompt = apply_filters( 'classifai_stable_diffusion_prompt', $prompt );

		// If our prompt exceeds the max length, throw an error.
		if ( mb_strlen( $prompt ) > $this->max_prompt_chars ) {
			/* translators: %d is the maximum number of characters allowed in the prompt. */
			return new WP_Error( 'invalid_param', sprintf( esc_html__( 'Your image prompt is too long. Please ensure it doesn\'t exceed %d characters.', 'classifai' ), $this->max_prompt_chars ) );
		}

		$request = new APIRequest( 'test', 'generate-image' );

		$dimensions = $this->get_dimensions_from_size( $args['size'] );
		$body       = [
			'prompt'            => sanitize_text_field( $prompt ),
			'batch_size'        => 1,
			'height'            => absint( $dimensions['height'] ),
			'width'             => absint( $dimensions['width'] ),
			'steps'             => 15,
			'override_settings' => [
				'sd_model_checkpoint' => sanitize_text_field( $settings['model'] ?? '' ),
			],
		];

		/**
		 * Filter the request body before sending to Stable Diffusion.
		 *
		 * @since 3.6.0
		 * @hook classifai_stable_diffusion_request_body
		 *
		 * @param array $body Request body that will be sent to Stable Diffusion.
		 *
		 * @return array Request body.
		 */
		$body = apply_filters( 'classifai_stable_diffusion_request_body', $body );

		$responses = [];

		for ( $i = 0; $i < $args['num']; $i++ ) {
			$responses[] = $request->post(
				$this->get_api_url( $settings['endpoint_url'] ),
				[
					'body'    => wp_json_encode( $body ),
					'timeout' => 180, // phpcs:ignore WordPressVIPMinimum.Performance.RemoteRequestTimeout.timeout_timeout
				]
			);
		}

		$cleaned_responses = [];

		foreach ( $responses as $response ) {
			// Extract out the image response, if it exists.
			if ( ! is_wp_error( $response ) && ! empty( $response['images'] ) ) {
				foreach ( $response['images'] as $image ) {
					$cleaned_responses[] = [ 'url' => $image ];
				}
			} elseif ( is_wp_error( $response ) ) {
				return $response;
			}
		}

		return $cleaned_responses;
	}

	/**
	 * Common entry point for all REST endpoints for this provider.
	 *
	 * @param string $prompt The prompt used to generate an image.
	 * @param string $route_to_call The name of the route we're going to be processing.
	 * @param array  $args          Optional arguments to pass to the route.
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
	 * Builds the API Model URL.
	 *
	 * @param string $endpoint_url The endpoint URL.
	 * @return string
	 */
	public function get_api_model_url( string $endpoint_url ): string {
		return sprintf( '%s%s', trailingslashit( $endpoint_url ), 'sdapi/v1/sd-models' );
	}

	/**
	 * Builds the API Image Generation URL.
	 *
	 * @param string $endpoint_url The endpoint URL.
	 * @return string
	 */
	public function get_api_url( string $endpoint_url ): string {
		return sprintf( '%s%s', trailingslashit( $endpoint_url ), 'sdapi/v1/txt2img' );
	}

	/**
	 * Get the dimensions from the size.
	 *
	 * @param string $size The size.
	 * @return array
	 */
	public function get_dimensions_from_size( string $size ): array {
		$dimensions = explode( 'x', $size );

		return [
			'width'  => $dimensions[0],
			'height' => $dimensions[1],
		];
	}
}
