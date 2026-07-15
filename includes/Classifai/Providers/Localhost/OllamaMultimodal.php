<?php
/**
 * Ollama Multimodal integration
 */

namespace Classifai\Providers\Localhost;

use Classifai\Features\DescriptiveTextGenerator;
use Classifai\Features\ImageTagsGenerator;
use Classifai\Features\ImageTextExtraction;
use Classifai\Providers\OpenAI\APIRequest;
use WP_Error;

use function Classifai\get_largest_size_and_dimensions_image_url;
use function Classifai\get_modified_image_source_url;
use function Classifai\computer_vision_max_filesize;
use function Classifai\get_default_prompt;
use function Classifai\safe_file_get_contents;

/**
 * Ollama Multimodal class
 */
class OllamaMultimodal extends Ollama {

	/**
	 * The Provider ID.
	 */
	const ID = 'ollama_multimodal';

	/**
	 * Connects to Ollama and retrieves supported models.
	 *
	 * @param array $args Overridable args.
	 * @return array
	 */
	public function get_models( array $args = array() ): array {
		$models = parent::get_models( $args );

		$supported_models = array(
			'bakllava',
			'deepseek-ocr',
			'devstral-small-2',
			'gemini-3-flash-preview',
			'gemma3',
			'gemma4',
			'glm-ocr',
			'granite3.2-vision',
			'kimi-k2.5',
			'kimi-k2.6',
			'llama3.2-vision',
			'llama4',
			'llava',
			'llava-llama3',
			'llava-phi3',
			'medgemma',
			'medgemma1.5',
			'minicpm-v',
			'minicpm-v4.5',
			'minicpm-v4.6',
			'minimax-m3',
			'ministral-3',
			'moondream',
			'mistral-large-3',
			'mistral-medium-3.5',
			'mistral-small3.1',
			'mistral-small3.2',
			'nemotron3',
			'qwen2.5vl',
			'qwen3-vl',
			'qwen3.5',
			'qwen3.6',
			'translategemma',
		);

		// Ensure our model list only contains the ones we support.
		foreach ( $models as $key => $model ) {
			$model = explode( ':', $model );

			if ( ! in_array( $model[0], $supported_models, true ) ) {
				unset( $models[ $key ] );
			}
		}

		return $models;
	}

	/**
	 * Returns the default settings for this provider.
	 *
	 * @return array
	 */
	public function get_default_provider_settings(): array {
		$common_settings = parent::get_default_provider_settings();

		/**
		 * Default values for feature specific settings.
		 */
		switch ( $this->feature_instance::ID ) {
			case DescriptiveTextGenerator::ID:
			case ImageTagsGenerator::ID:
			case ImageTextExtraction::ID:
				$common_settings['prompt'] = array(
					array(
						'title'    => esc_html__( 'ClassifAI default', 'classifai' ),
						'prompt'   => $this->feature_instance->get_prompt( 'default' ),
						'original' => 1,
						'default'  => 1,
					),
				);
				break;
		}

		return $common_settings;
	}

	/**
	 * Generic request handler for multimodal LLMs run by Ollama.
	 *
	 * @param string $url Request URL.
	 * @param array  $body Request body.
	 * @return string|WP_Error
	 */
	public function request( string $url, array $body ) {
		// Make our API request.
		$request  = new APIRequest( '', $this->feature_instance::ID, $this );
		$response = $request->post(
			$url,
			array(
				'body' => wp_json_encode( $body ),
			)
		);

		// Return the error if we have one.
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		// If we have a message, return it.
		$return = '';
		if ( isset( $response['response'] ) ) {
			$return = sanitize_text_field( trim( $response['response'], ' "\'' ) );
		}

		return $return;
	}

	/**
	 * Generate descriptive alt text for an image.
	 *
	 * @param string $image_url URL of image to process.
	 * @param int    $attachment_id Post ID for the attachment.
	 * @return string|WP_Error
	 */
	public function generate_alt_text( string $image_url, int $attachment_id ) {
		if ( ! wp_attachment_is_image( $attachment_id ) ) {
			return new WP_Error( 'invalid', esc_html__( 'This attachment can\'t be processed.', 'classifai' ) );
		}

		$feature  = new DescriptiveTextGenerator();
		$settings = $feature->get_settings( static::ID );

		// Ensure things are set up properly.
		if ( empty( $settings ) || ( isset( $settings[ static::ID ]['authenticated'] ) && false === $settings[ static::ID ]['authenticated'] ) || ( ! $feature->is_feature_enabled() ) ) {
			return new WP_Error( 'not_enabled', esc_html__( 'Descriptive text generation is disabled or Ollama authentication failed. Please check your settings.', 'classifai' ) );
		}

		// Download the image so we can encode it.
		$image_data = safe_file_get_contents( $image_url );

		if ( false === $image_data || ! is_string( $image_data ) ) {
			return new WP_Error( 'invalid', esc_html__( 'Image cannot be downloaded.', 'classifai' ) );
		}

		/**
		 * Filter the prompt we will send to Ollama.
		 *
		 * @since 3.3.0
		 * @hook classifai_ollama_descriptive_text_prompt
		 *
		 * @param string $prompt Prompt we are sending to Ollama.
		 * @param int $attachment_id ID of attachment.
		 *
		 * @return string Prompt.
		 */
		$prompt = apply_filters( 'classifai_ollama_descriptive_text_prompt', get_default_prompt( $settings[ static::ID ]['prompt'] ?? array() ) ?? $feature->get_prompt( 'default' ), $attachment_id );

		/**
		 * Filter the request body before sending to Ollama.
		 *
		 * @since 3.3.0
		 * @hook classifai_ollama_descriptive_text_request_body
		 *
		 * @param array $body          Request body that will be sent to Ollama.
		 * @param int   $attachment_id ID of attachment.
		 *
		 * @return array Request body.
		 */
		$body = apply_filters(
			'classifai_ollama_descriptive_text_request_body',
			array(
				'model'  => $settings['model'] ?? '',
				'prompt' => $prompt,
				'images' => array( base64_encode( $image_data ) ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
				'stream' => false,
			),
			$attachment_id
		);

		// Make our API request.
		$response = $this->request( $this->get_api_generate_url( $settings['endpoint_url'] ?? '' ), $body );

		return $response;
	}

	/**
	 * Extract text out of an image.
	 *
	 * @param string $image_url URL of image to process.
	 * @param int    $attachment_id Post ID for the attachment.
	 * @return string|WP_Error
	 */
	public function ocr_processing( string $image_url, int $attachment_id ) {
		if ( ! wp_attachment_is_image( $attachment_id ) ) {
			return new WP_Error( 'invalid', esc_html__( 'This attachment can\'t be processed.', 'classifai' ) );
		}

		$feature  = new ImageTextExtraction();
		$settings = $feature->get_settings( static::ID );

		// Ensure things are set up properly.
		if ( empty( $settings ) || ( isset( $settings[ static::ID ]['authenticated'] ) && false === $settings[ static::ID ]['authenticated'] ) || ( ! $feature->is_feature_enabled() ) ) {
			return new WP_Error( 'not_enabled', esc_html__( 'Image Text Extraction is disabled or Ollama authentication failed. Please check your settings.', 'classifai' ) );
		}

		// Download the image so we can encode it.
		$image_data = safe_file_get_contents( $image_url );

		if ( false === $image_data || ! is_string( $image_data ) ) {
			return new WP_Error( 'invalid', esc_html__( 'Image cannot be downloaded.', 'classifai' ) );
		}

		/**
		 * Filter the prompt we will send to Ollama.
		 *
		 * @since 3.3.0
		 * @hook classifai_ollama_ocr_processing_prompt
		 *
		 * @param string $prompt        Prompt we are sending to Ollama.
		 * @param int    $attachment_id ID of attachment.
		 *
		 * @return string Prompt.
		 */
		$prompt = apply_filters( 'classifai_ollama_ocr_processing_prompt', get_default_prompt( $settings[ static::ID ]['prompt'] ?? array() ) ?? $feature->get_prompt( 'default' ), $attachment_id );

		/**
		 * Filter the request body before sending to Ollama.
		 *
		 * @since 3.3.0
		 * @hook classifai_ollama_ocr_processing_request_body
		 *
		 * @param array $body          Request body that will be sent to Ollama.
		 * @param int   $attachment_id ID of attachment.
		 *
		 * @return array Request body.
		 */
		$body = apply_filters(
			'classifai_ollama_ocr_processing_request_body',
			array(
				'model'  => $settings['model'] ?? '',
				'prompt' => $prompt,
				'images' => array( base64_encode( $image_data ) ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
				'stream' => false,
			),
			$attachment_id
		);

		// Make our API request.
		$response = $this->request( $this->get_api_generate_url( $settings['endpoint_url'] ?? '' ), $body );

		// If no text was found, return an empty string.
		if ( 'none' === strtolower( $response ) ) {
			return '';
		}

		return $response;
	}

	/**
	 * Generate tags for an image.
	 *
	 * @param string $image_url URL of image to process.
	 * @param int    $attachment_id Post ID for the attachment.
	 * @return array|WP_Error
	 */
	public function generate_image_tags( string $image_url, int $attachment_id ) {
		if ( ! wp_attachment_is_image( $attachment_id ) ) {
			return new WP_Error( 'invalid', esc_html__( 'This attachment can\'t be processed.', 'classifai' ) );
		}

		$feature  = new ImageTagsGenerator();
		$settings = $feature->get_settings( static::ID );

		// Ensure things are set up properly.
		if ( empty( $settings ) || ( isset( $settings[ static::ID ]['authenticated'] ) && false === $settings[ static::ID ]['authenticated'] ) || ( ! $feature->is_feature_enabled() ) ) {
			return new WP_Error( 'not_enabled', esc_html__( 'Image tag generation is disabled or Ollama authentication failed. Please check your settings.', 'classifai' ) );
		}

		// Download the image so we can encode it.
		$image_data = safe_file_get_contents( $image_url );

		if ( false === $image_data || ! is_string( $image_data ) ) {
			return new WP_Error( 'invalid', esc_html__( 'Image cannot be downloaded.', 'classifai' ) );
		}

		/**
		 * Filter the prompt we will send to Ollama.
		 *
		 * @since 3.3.0
		 * @hook classifai_ollama_image_tag_prompt
		 *
		 * @param string $prompt        Prompt we are sending to Ollama.
		 * @param int    $attachment_id ID of attachment.
		 *
		 * @return string Prompt.
		 */
		$prompt = apply_filters( 'classifai_ollama_image_tag_prompt', get_default_prompt( $settings[ static::ID ]['prompt'] ?? array() ) ?? $feature->get_prompt( 'default' ), $attachment_id );

		/**
		 * Filter the request body before sending to Ollama.
		 *
		 * @since 3.3.0
		 * @hook classifai_ollama_image_tag_request_body
		 *
		 * @param array $body Request body that will be sent to Ollama.
		 * @param int $attachment_id ID of attachment.
		 *
		 * @return array Request body.
		 */
		$body = apply_filters(
			'classifai_ollama_image_tag_request_body',
			array(
				'model'  => $settings['model'] ?? '',
				'prompt' => $prompt,
				'images' => array( base64_encode( $image_data ) ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
				'format' => array(
					'type'                 => 'object',
					'properties'           => array(
						'tags' => array(
							'type'     => 'array',
							'minItems' => 3,
							'maxItems' => 5,
							'items'    => array(
								'type' => 'string',
							),
						),
					),
					'required'             => array( 'tags' ),
					'additionalProperties' => false,
				),
				'stream' => false,
			),
			$attachment_id
		);

		// Make our API request.
		$response = $this->request( $this->get_api_generate_url( $settings['endpoint_url'] ?? '' ), $body );

		// If we have a response, clean it up.
		if ( ! is_wp_error( $response ) ) {
			// We expect the response to be valid JSON since we requested that schema.
			$image_tags = json_decode( $response, true );

			if ( isset( $image_tags['tags'] ) && is_array( $image_tags['tags'] ) ) {
				$response = array_filter(
					array_map(
						function ( $tag ) {
							return sanitize_text_field( trim( $tag, ' "\'' ) );
						},
						$image_tags['tags']
					)
				);
			}
		}

		// Ensure we have a valid response after processing.
		if ( ! is_array( $response ) || empty( $response ) ) {
			return new WP_Error( 'error', esc_html__( 'No tags found.', 'classifai' ) );
		}

		return $response;
	}

	/**
	 * Common entry point for all REST endpoints for this provider.
	 *
	 * @param int    $attachment_id The attachment ID we're processing.
	 * @param string $route_to_call The name of the route we're going to be processing.
	 * @param array  $args          Optional arguments to pass to the route.
	 * @return array|string|WP_Error|null
	 */
	public function rest_endpoint_callback( $attachment_id, string $route_to_call = '', array $args = array() ) {
		// Check to be sure the post both exists and is an attachment.
		if ( ! get_post( $attachment_id ) || 'attachment' !== get_post_type( $attachment_id ) ) {
			/* translators: %1$s: the attachment ID */
			return new WP_Error( 'incorrect_ID', sprintf( esc_html__( '%1$d is not found or is not an attachment', 'classifai' ), $attachment_id ), array( 'status' => 404 ) );
		}

		$metadata = wp_get_attachment_metadata( $attachment_id );

		if ( ! $metadata || ! is_array( $metadata ) ) {
			return new WP_Error( 'invalid', esc_html__( 'No valid metadata found.', 'classifai' ) );
		}

		$image_url = get_modified_image_source_url( $attachment_id );

		if ( empty( $image_url ) || ! filter_var( $image_url, FILTER_VALIDATE_URL ) ) {
			if ( isset( $metadata['sizes'] ) && is_array( $metadata['sizes'] ) ) {
				$image_url = get_largest_size_and_dimensions_image_url(
					get_attached_file( $attachment_id ),
					wp_get_attachment_url( $attachment_id ),
					$metadata,
					array( 50, 16000 ),
					array( 50, 16000 ),
					computer_vision_max_filesize()
				);
			} else {
				$image_url = wp_get_attachment_url( $attachment_id );
			}
		}

		if ( empty( $image_url ) ) {
			return new WP_Error( 'error', esc_html__( 'Image does not meet size requirements. Please ensure it is at least 50x50 but less than 16000x16000 and smaller than 20MB.', 'classifai' ) );
		}

		switch ( $route_to_call ) {
			case 'descriptive_text':
				return $this->generate_alt_text( $image_url, $attachment_id );

			case 'ocr':
				return $this->ocr_processing( $image_url, $attachment_id );

			case 'tags':
				return $this->generate_image_tags( $image_url, $attachment_id );
		}

		return null;
	}
}
