<?php
/**
 * OpenAI DALL·E integration
 */

namespace Classifai\Providers\OpenAI;

use Classifai\Features\ImageGeneration;
use Classifai\Providers\Provider;
use Classifai\Providers\OpenAI\APIRequest;
use WP_Error;
use WP_REST_Server;

class DallE extends Provider {

	use \Classifai\Providers\OpenAI\OpenAI;

	const ID = 'openai_dalle';

	/**
	 * OpenAI DALL·E URL.
	 *
	 * @var string
	 */
	protected $dalle_url = 'https://api.openai.com/v1/images/generations';

	/**
	 * Maximum number of characters a prompt can have.
	 *
	 * @var int
	 */
	public $max_prompt_chars = 4000;

	/**
	 * OpenAI DALL·E constructor.
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
		add_action( 'rest_api_init', [ $this, 'register_endpoints' ] );
	}

	/**
	 * Register any needed endpoints.
	 *
	 * This endpoint is registered in the feature class
	 * but we need to add additional arguments to it
	 * that this provider supports.
	 */
	public function register_endpoints() {
		register_rest_route(
			'classifai/v1',
			'generate-image',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this->feature_instance, 'rest_endpoint_callback' ],
				'args'                => [
					'prompt'  => [
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
						'validate_callback' => 'rest_validate_request_arg',
						'description'       => esc_html__( 'Prompt used to generate an image', 'classifai' ),
					],
					'n'       => [
						'type'              => 'integer',
						'minimum'           => 1,
						'maximum'           => 10,
						'sanitize_callback' => 'absint',
						'validate_callback' => 'rest_validate_request_arg',
						'description'       => esc_html__( 'Number of images to generate', 'classifai' ),
					],
					'quality' => [
						'type'              => 'string',
						'enum'              => [
							'standard',
							'hd',
						],
						'sanitize_callback' => 'sanitize_text_field',
						'validate_callback' => 'rest_validate_request_arg',
						'description'       => esc_html__( 'Quality of generated image', 'classifai' ),
					],
					'size'    => [
						'type'              => 'string',
						'enum'              => [
							'1024x1024',
							'1792x1024',
							'1024x1792',
						],
						'sanitize_callback' => 'sanitize_text_field',
						'validate_callback' => 'rest_validate_request_arg',
						'description'       => esc_html__( 'Size of generated image', 'classifai' ),
					],
					'style'   => [
						'type'              => 'string',
						'enum'              => [
							'vivid',
							'natural',
						],
						'sanitize_callback' => 'sanitize_text_field',
						'validate_callback' => 'rest_validate_request_arg',
						'description'       => esc_html__( 'Style of generated image', 'classifai' ),
					],
					'format'  => [
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
				'permission_callback' => [ $this->feature_instance, 'generate_image_permissions_check' ],
			]
		);
	}

	/**
	 * Register settings for the provider.
	 */
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
				'class'         => 'classifai-provider-field hidden provider-scope-' . static::ID,
				'description'   => sprintf(
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
				),
			]
		);

		add_settings_field(
			static::ID . '_number_of_images',
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
				'class'         => 'classifai-provider-field hidden provider-scope-' . static::ID,
			]
		);

		add_settings_field(
			static::ID . '_quality',
			esc_html__( 'Image quality', 'classifai' ),
			[ $this->feature_instance, 'render_select' ],
			$this->feature_instance->get_option_name(),
			$this->feature_instance->get_option_name() . '_section',
			[
				'option_index'  => static::ID,
				'label_for'     => 'quality',
				'options'       => [
					'standard' => __( 'Standard', 'classifai' ),
					'hd'       => __( 'High Definition', 'classifai' ),
				],
				'default_value' => $settings['quality'],
				'description'   => __( 'The quality of the image that will be generated. High Definition creates images with finer details and greater consistency across the image but costs more.', 'classifai' ),
				'class'         => 'classifai-provider-field hidden provider-scope-' . static::ID,
			]
		);

		add_settings_field(
			static::ID . '_image_size',
			esc_html__( 'Image size', 'classifai' ),
			[ $this->feature_instance, 'render_select' ],
			$this->feature_instance->get_option_name(),
			$this->feature_instance->get_option_name() . '_section',
			[
				'option_index'  => static::ID,
				'label_for'     => 'image_size',
				'options'       => [
					'1024x1024' => '1024x1024 (square)',
					'1792x1024' => '1792x1024 (landscape)',
					'1024x1792' => '1024x1792 (portrait)',
				],
				'default_value' => $settings['image_size'],
				'description'   => __( 'Size of generated images. Larger sizes cost more.', 'classifai' ),
				'class'         => 'classifai-provider-field hidden provider-scope-' . static::ID,
			]
		);

		add_settings_field(
			static::ID . '_style',
			esc_html__( 'Image style', 'classifai' ),
			[ $this->feature_instance, 'render_select' ],
			$this->feature_instance->get_option_name(),
			$this->feature_instance->get_option_name() . '_section',
			[
				'option_index'  => static::ID,
				'label_for'     => 'style',
				'options'       => [
					'vivid'   => __( 'Vivid', 'classifai' ),
					'natural' => __( 'Natural', 'classifai' ),
				],
				'default_value' => $settings['style'],
				'description'   => __( 'The style of the generated images. Vivid causes more hyper-real and dramatic images. Natural causes more natural, less hyper-real looking images.', 'classifai' ),
				'class'         => 'classifai-provider-field hidden provider-scope-' . static::ID,
			]
		);
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
						'number_of_images' => 1,
						'quality'          => 'standard',
						'image_size'       => '1024x1024',
						'style'            => 'vivid',
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
		$settings                                    = $this->feature_instance->get_settings();
		$api_key_settings                            = $this->sanitize_api_key_settings( $new_settings, $settings );
		$new_settings[ static::ID ]['api_key']       = $api_key_settings[ static::ID ]['api_key'];
		$new_settings[ static::ID ]['authenticated'] = $api_key_settings[ static::ID ]['authenticated'];

		if ( $this->feature_instance instanceof ImageGeneration ) {
			$new_settings[ static::ID ]['number_of_images'] = absint( $new_settings[ static::ID ]['number_of_images'] ?? $settings[ static::ID ]['number_of_images'] );

			if ( in_array( $new_settings[ static::ID ]['quality'], [ 'standard', 'hd' ], true ) ) {
				$new_settings[ static::ID ]['quality'] = sanitize_text_field( $new_settings[ static::ID ]['quality'] );
			} else {
				$new_settings[ static::ID ]['quality'] = $settings[ static::ID ]['quality'];
			}

			if ( in_array( $new_settings[ static::ID ]['image_size'], [ '1024x1024', '1792x1024', '1024x1792' ], true ) ) {
				$new_settings[ static::ID ]['image_size'] = sanitize_text_field( $new_settings[ static::ID ]['image_size'] );
			} else {
				$new_settings[ static::ID ]['image_size'] = $settings[ static::ID ]['image_size'];
			}

			if ( in_array( $new_settings[ static::ID ]['style'], [ 'vivid', 'natural' ], true ) ) {
				$new_settings[ static::ID ]['style'] = sanitize_text_field( $new_settings[ static::ID ]['style'] );
			} else {
				$new_settings[ static::ID ]['style'] = $settings[ static::ID ]['style'];
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
				'num'     => $settings['number_of_images'] ?? 1,
				'quality' => $settings['quality'] ?? 'standard',
				'size'    => $settings['image_size'] ?? '1024x1024',
				'style'   => $settings['style'] ?? 'vivid',
				'format'  => 'url',
			]
		);

		// Force proper image size for those that had been using DALL·E 2 and haven't updated settings.
		if ( ! in_array( $args['size'], [ '1024x1024', '1792x1024', '1024x1792' ], true ) ) {
			$args['size'] = '1024x1024';
		}

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
				'model'           => 'dall-e-3',
				'n'               => 1,
				'quality'         => sanitize_text_field( $args['quality'] ),
				'response_format' => sanitize_text_field( $args['format'] ),
				'size'            => sanitize_text_field( $args['size'] ),
				'style'           => sanitize_text_field( $args['style'] ),
			]
		);

		$responses = [];

		// DALL·E 3 doesn't support multiple images in a single request so make one request per image.
		for ( $i = 0; $i < $args['num']; $i++ ) {
			$responses[] = $request->post(
				$this->dalle_url,
				[
					'body' => wp_json_encode( $body ),
				]
			);
		}

		set_transient( 'classifai_openai_dalle_latest_response', $responses[ array_key_last( $responses ) ], DAY_IN_SECONDS * 30 );

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
			}
		}

		return $cleaned_responses;
	}

	/**
	 * Returns the debug information for the provider settings.
	 *
	 * @return array
	 */
	public function get_debug_information(): array {
		$settings          = $this->feature_instance->get_settings();
		$provider_settings = $settings[ static::ID ];
		$debug_info        = [];

		if ( $this->feature_instance instanceof ImageGeneration ) {
			$debug_info[ __( 'Number of images', 'classifai' ) ] = $provider_settings['number_of_images'] ?? 1;
			$debug_info[ __( 'Quality', 'classifai' ) ]          = $provider_settings['quality'] ?? 'standard';
			$debug_info[ __( 'Size', 'classifai' ) ]             = $provider_settings['image_size'] ?? '1024x1024';
			$debug_info[ __( 'Style', 'classifai' ) ]            = $provider_settings['style'] ?? 'vivid';
			$debug_info[ __( 'Latest response:', 'classifai' ) ] = $this->get_formatted_latest_response( get_transient( 'classifai_openai_dalle_latest_response' ) );
		}

		return apply_filters(
			'classifai_' . self::ID . '_debug_information',
			$debug_info,
			$settings,
			$this->feature_instance
		);
	}
}
