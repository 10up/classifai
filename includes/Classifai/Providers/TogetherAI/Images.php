<?php
/**
 * Together AI Image integration
 */

namespace Classifai\Providers\TogetherAI;

use Classifai\Providers\Provider;
use Classifai\Providers\OpenAI\APIRequest;
use Classifai\Features\ImageGeneration;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Images extends Provider {

	use \Classifai\Providers\TogetherAI\TogetherAI;

	const ID = 'togetherai_image';

	/**
	 * Maximum number of characters a prompt can have.
	 *
	 * @var int
	 */
	public $max_prompt_chars = 1000;

	/**
	 * Together AI Image API path.
	 *
	 * @var string
	 */
	protected $api_path = 'images/generations';

	/**
	 * Together AI Image constructor.
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
		 * @hook classifai_togetherai_image_size_options
		 *
		 * @param array $options The default size options.
		 *
		 * @return array The size options.
		 */
		return apply_filters( 'classifai_togetherai_image_size_options', $options );
	}

	/**
	 * Returns the default settings for the provider.
	 *
	 * @return array
	 */
	public function get_default_provider_settings(): array {
		return [
			'api_key'            => '',
			'authenticated'      => false,
			'model'              => '',
			'models'             => [],
			'number_of_images'   => 1,
			'image_size'         => '1024x1024',
			'per_image_settings' => false,
		];
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
		$new_settings[ static::ID ]['models']        = $api_key_settings[ static::ID ]['models'];

		$new_settings[ static::ID ]['model'] = sanitize_text_field( $new_settings[ static::ID ]['model'] ?? $settings[ static::ID ]['model'] );

		if ( $this->feature_instance instanceof ImageGeneration ) {
			// Trim our array of models to only include the ones where type => image.
			$new_settings[ static::ID ]['models'] = array_filter(
				$new_settings[ static::ID ]['models'],
				fn( $model ) => 'image' === $model['type']
			);

			// Ensure the model being saved is valid. If not valid or we don't have one, use the first model.
			if ( ! in_array( $new_settings[ static::ID ]['model'], array_column( $new_settings[ static::ID ]['models'], 'id' ), true ) ) {
				$new_settings[ static::ID ]['model'] = array_column( $new_settings[ static::ID ]['models'], 'id' )[0];
			}

			$new_settings[ static::ID ]['number_of_images'] = absint( $new_settings[ static::ID ]['number_of_images'] ?? $settings[ static::ID ]['number_of_images'] );

			if ( in_array( $new_settings[ static::ID ]['image_size'], array_keys( $this->get_image_size_options() ), true ) ) {
				$new_settings[ static::ID ]['image_size'] = sanitize_text_field( $new_settings[ static::ID ]['image_size'] );
			} else {
				$new_settings[ static::ID ]['image_size'] = $settings[ static::ID ]['image_size'];
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
				'num'    => $settings['number_of_images'] ?? 1,
				'size'   => $settings['image_size'] ?? '1024x1024',
				'format' => 'b64_json',
				'model'  => $settings['model'] ?? '',
			]
		);

		if ( ! $image_generation->is_feature_enabled() ) {
			return new WP_Error( 'not_enabled', esc_html__( 'Image generation is disabled or Together AI authentication failed. Please check your settings.', 'classifai' ) );
		}

		/**
		 * Filter the prompt we will send to Together AI.
		 *
		 * @since 3.6.0
		 * @hook classifai_togetherai_image_prompt
		 *
		 * @param string $prompt Prompt we are sending to Together AI.
		 *
		 * @return string Prompt.
		 */
		$prompt = apply_filters( 'classifai_togetherai_image_prompt', $prompt );

		$request = new APIRequest( $settings['api_key'] ?? '', 'generate-image' );

		$dimensions = $this->get_dimensions_from_size( $args['size'] );
		$body       = [
			'prompt'          => sanitize_text_field( $prompt ),
			'model'           => sanitize_text_field( $args['model'] ),
			'n'               => absint( $args['num'] ),
			'height'          => absint( $dimensions['height'] ),
			'width'           => absint( $dimensions['width'] ),
			'response_format' => 'b64_json' === $args['format'] ? 'base64' : 'url',
		];

		/**
		 * Filter the request body before sending to Together AI.
		 *
		 * @since 3.6.0
		 * @hook classifai_togetherai_image_request_body
		 *
		 * @param array $body Request body that will be sent to Together AI.
		 *
		 * @return array Request body.
		 */
		$body = apply_filters( 'classifai_togetherai_image_request_body', $body );

		$responses = [];

		$responses[] = $request->post(
			$this->get_api_url( $this->api_path ),
			[
				'body' => wp_json_encode( $body ),
			]
		);

		$cleaned_responses = [];

		foreach ( $responses as $response ) {
			// Extract out the image response, if it exists.
			if ( ! is_wp_error( $response ) && ! empty( $response['data'] ) ) {
				foreach ( $response['data'] as $data ) {
					if ( ! empty( $data[ $args['format'] ] ) ) {
						if ( 'url' === $args['format'] ) {
							$cleaned_responses[] = [ 'url' => esc_url_raw( $data[ $args['format'] ] ) ];
						} else {
							$cleaned_responses[] = [ 'url' => $data[ $args['format'] ] ];
						}
					}
				}
			} elseif ( is_wp_error( $response ) ) {
				return $response;
			}
		}

		return $cleaned_responses;
	}

	/**
	 * Register additional REST arguments for the provider.
	 *
	 * @param array $args Existing REST arguments.
	 * @return array
	 */
	public function register_rest_args( array $args = [] ): array {
		$provider_args = [
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
				'enum'              => array_keys( $this->get_image_size_options() ),
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
			/* translators: %1$s is replaced with the Together AI URL */
			esc_html__( 'Image generated by <a href="%s">Together AI</a>', 'classifai' ),
			'https://together.ai/'
		);
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
