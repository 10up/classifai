<?php
namespace Classifai\Providers\Leonardo;

use Classifai\Providers\Provider;
use Classifai\Features\ImageGeneration;
use WP_Error;

class Leonardo extends Provider {
	const ID = 'leonardo';

	public function __construct( $feature_instance = null ) {
		$this->feature_instance = $feature_instance;
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
						'model'            => '6b645e3a-d64f-4341-a6d8-7a3690fbf042',
						'number_of_images' => 1,
						'preset'           => 'debdf72a-91a4-467b-bf61-cc02bdeb69c6',
					]
				);
		}

		return $common_settings;
	}

	public function sanitize_settings( array $new_settings ): array {
		$settings = $this->feature_instance->get_settings();

		// Ensure proper validation of credentials happens here.
		$new_settings[ static::ID ]['api_key']       = sanitize_text_field( $new_settings[ static::ID ]['api_key'] ?? $settings[ static::ID ]['api_key'] );
		$new_settings[ static::ID ]['authenticated'] = true;

		if ( $this->feature_instance instanceof ImageGeneration ) {
			$new_settings[ static::ID ]['number_of_images'] = absint( $new_settings[ static::ID ]['number_of_images'] ?? $settings[ static::ID ]['number_of_images'] );
			$new_settings[ static::ID ]['model']            = sanitize_text_field( $new_settings[ static::ID ]['model'] ?? $settings[ static::ID ]['model'] );
			$new_settings[ static::ID ]['preset']           = sanitize_text_field( $new_settings[ static::ID ]['preset'] ?? $settings[ static::ID ]['preset'] );
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

	public function generate_image( string $prompt = '', array $args = [] ) {
		if ( ! $prompt ) {
			return new WP_Error( 'prompt_required', esc_html__( 'A prompt is required to generate an image.', 'classifai' ) );
		}

		$image_generation = new ImageGeneration();
		$settings         = $image_generation->get_settings( static::ID );
	}
}
