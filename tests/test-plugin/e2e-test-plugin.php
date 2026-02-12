<?php
/**
 * Plugin name: ClassifAI Cypress Test Request Mock plugin
 */

// Mock the ClassifAI HTTP request calls and provide known response.
add_filter( 'pre_http_request', 'classifai_test_mock_http_requests', 10, 3 );

// Mock the AWS Polly API request calls and provide known response.
add_filter( 'classifai_aws_polly_pre_connect_to_service', 'classifai_mock_aws_polly_connect_to_service' );
add_filter( 'classifai_aws_polly_pre_synthesize_speech', 'classifai_mock_aws_polly_pre_synthesize_speech' );

// Disable ElasticPress admin bar.
add_filter( 'ep_admin_bar_should_display', '__return_false' );

/**
 * Mock ClassifAI's HTTP requests.
 *
 * @param boolean $preempt     Whether to preempt an HTTP request's return value.
 * @param array   $parsed_args HTTP request arguments.
 * @param string  $url         The request URL.
 * @return boolean|array
 */
function classifai_test_mock_http_requests( $preempt, $parsed_args, $url ) {
	$response = '';

	if ( strpos( $url, 'http://e2e-test-nlu-server.test/v1/analyze' ) !== false ) {
		$response = file_get_contents( __DIR__ . '/nlu.json' );
	} elseif (
		strpos( $url, 'https://api.openai.com/v1/models' ) !== false ||
		strpos( $url, 'https://api.x.ai/v1/language-models' ) !== false ||
		strpos( $url, 'https://api.together.xyz/v1/models' ) !== false
	) {
		$response = file_get_contents( __DIR__ . '/models.json' );
	} elseif ( strpos( $url, 'https://api.elevenlabs.io/v1/models' ) !== false ) {
		$response = file_get_contents( __DIR__ . '/elevenlabs-models.json' );
	} elseif ( strpos( $url, 'https://api.openai.com/v1/completions' ) !== false || strpos( $url, 'https://api.x.ai/v1/completions' ) !== false ) {
		$response = file_get_contents( __DIR__ . '/chatgpt.json' );
	} elseif (
		strpos( $url, 'https://api.openai.com/v1/chat/completions' ) !== false ||
		strpos( $url, 'https://api.x.ai/v1/chat/completions' ) !== false ||
		strpos( $url, 'https://e2e-test-azure-openai.test/openai/deployments' ) !== false
	) {
		$response  = file_get_contents( __DIR__ . '/chatgpt.json' );
		$body_json = $parsed_args['body'] ?? false;

		if ( $body_json ) {
			$body     = json_decode( $body_json, JSON_OBJECT_AS_ARRAY );
			$messages = isset( $body['messages'] ) ? $body['messages'] : [];
			$prompt   = count( $messages ) > 0 ? $messages[0]['content'] : '';

			if ( str_contains( $prompt, 'Increase the content' ) || str_contains( $prompt, 'Decrease the content' ) ) {
				$response = file_get_contents( __DIR__ . '/resize-content.json' );
			} else if ( str_contains( $prompt, 'This is a custom excerpt prompt' ) ) {
				$response = file_get_contents( __DIR__ . '/chatgpt-custom-excerpt-prompt.json' );
			} else if ( str_contains( $prompt, 'This is a custom title prompt' ) ) {
				$response = file_get_contents( __DIR__ . '/chatgpt-custom-title-prompt.json' );
			} else if ( str_contains( $prompt, 'This is a custom shrink prompt' ) || str_contains( $prompt, 'This is a custom grow prompt' ) ) {
				$response = file_get_contents( __DIR__ . '/resize-content-custom-prompt.json' );
			} else if ( str_contains( $prompt, 'provide a summary that captures all the important points' ) ) {
				$response = file_get_contents( __DIR__ . '/chatgpt-key-takeaways.json' );
			}
		}
	} elseif ( strpos( $url, 'https://api.openai.com/v1/moderations' ) !== false ) {
		$response = file_get_contents( __DIR__ . '/moderation.json' );
	} elseif (
		strpos( $url, 'https://api.openai.com/v1/audio/transcriptions' ) !== false ||
		strpos( $url, 'https://api.elevenlabs.io/v1/speech-to-text' ) !== false
	) {
		$response = file_get_contents( __DIR__ . '/whisper.json' );
	} elseif ( strpos( $url, 'https://api.openai.com/v1/images/generations' ) !== false || strpos( $url, 'https://api.together.xyz/v1/images/generations' ) !== false ) {
		$response = file_get_contents( __DIR__ . '/dalle.json' );
	} elseif ( strpos( $url, 'https://service.com/cognitiveservices/voices/list' ) !== false ) {
		return array(
			'response'    => array(
				'code' => 200,
			),
			'body' => file_get_contents( __DIR__ . '/text-to-speech-voices.json' ),
		);
	} elseif ( strpos( $url, 'https://api.elevenlabs.io/v1/voices' ) !== false ) {
		$response = file_get_contents( __DIR__ . '/text-to-speech-elevenlabs-voices.json' );
	} elseif (
		strpos( $url, 'https://service.com/cognitiveservices/v1' ) !== false
		|| strpos( $url, 'https://api.openai.com/v1/audio/speech' ) !== false
		|| strpos( $url, 'https://api.elevenlabs.io/v1/text-to-speech' ) !== false
	) {
		return array(
			'response' => array(
				'code' => 200,
			),
			'headers'  => array(
				'content-type' => 'audio/mpeg',
			),
			'body'     => file_get_contents( __DIR__ . '/text-to-speech.txt' ),
		);
	} elseif (
		strpos( $url, 'https://api.openai.com/v1/embeddings' ) !== false ||
		strpos( $url, 'https://e2e-test-azure-openai-embeddings.test/openai/deployments' ) !== false ||
		strpos( $url, 'http://localhost:11434/api/embed' ) !== false
	) {
		$response = file_get_contents( __DIR__ . '/embeddings.json' );
	} elseif ( strpos( $url, 'http://e2e-test-image-processing.test/computervision/imageanalysis:analyze?api-version=2024-02-01' ) !== false ) {
		$response = file_get_contents( __DIR__ . '/image_analyze.json' );
	} elseif ( strpos( $url, 'http://e2e-test-image-processing.test/vision/v3.2/generateThumbnail' ) !== false ) {
		$response = file_get_contents( '/var/www/html/wp-content/plugins/classifai/assets/img/icon-256x256.png' );
	} elseif ( strpos( $url, 'http://e2e-test-image-processing.test/pdf-read-result' ) !== false ) {
		$response = file_get_contents( __DIR__ . '/pdf.json' );
	} elseif ( strpos( $url, 'http://e2e-test-image-processing.test/vision/v3.2/read' ) !== false ) {
		return array(
			'headers'     => array(
				'Operation-Location' => 'http://e2e-test-image-processing.test/pdf-read-result',
			),
			'response'    => array(
				'code' => 202,
			),
			'status_code' => 200,
			'success'     => 1,
			'body'        => '',
		);
	} elseif ( strpos( $url, 'https://generativelanguage.googleapis.com/v1beta/models/imagen-4.0-generate-preview-06-06:predict' ) !== false ) {
		$response = file_get_contents( __DIR__ . '/imagen.json' );
	} elseif ( strpos( $url, 'https://generativelanguage.googleapis.com/v1beta' ) !== false ) {
		$response  = file_get_contents( __DIR__ . '/geminiapi.json' );
		$body_json = $parsed_args['body'] ?? false;

		if ( $body_json ) {
			$body     = json_decode( $body_json, JSON_OBJECT_AS_ARRAY );
			$contents = isset( $body['contents'] ) ? $body['contents'] : [];
			$parts    = isset( $contents[0]['parts'] ) ? $contents[0]['parts'] : [];
			$prompt   = $parts['text'] ?? '';

			if ( str_contains( $prompt, 'Increase the content' ) || str_contains( $prompt, 'Decrease the content' ) ) {
				$response = file_get_contents( __DIR__ . '/geminiapi-resize-content.json' );
			}
		}
	} elseif ( strpos( $url, 'http://localhost:11434/api/tags' ) !== false ) {
		$response = file_get_contents( __DIR__ . '/ollama-models.json' );
	} elseif( strpos( $url, 'http://localhost:11434/api/chat' ) !== false ) {
		$response  = file_get_contents( __DIR__ . '/ollama-chat.json' );
		$body_json = $parsed_args['body'] ?? false;

		if ( $body_json ) {
			$body     = json_decode( $body_json, JSON_OBJECT_AS_ARRAY );
			$messages = isset( $body['messages'] ) ? $body['messages'] : [];
			$prompt   = count( $messages ) > 0 ? $messages[0]['content'] : '';

			if ( str_contains( $prompt, 'Increase the content' ) || str_contains( $prompt, 'Decrease the content' ) ) {
				$response = file_get_contents( __DIR__ . '/ollama-chat-resize.json' );
			} else if ( str_contains( $prompt, 'Write an SEO-friendly title' ) ) {
				$response = file_get_contents( __DIR__ . '/ollama-structured-title.json' );
			}
		}
	} elseif ( strpos( $url, 'http://127.0.0.1:7860/sdapi/v1/sd-models' ) !== false ) {
		$response = file_get_contents( __DIR__ . '/stable-diffusion-models.json' );
	} elseif ( strpos( $url, 'http://127.0.0.1:7860/sdapi/v1/txt2img' ) !== false ) {
		$response = file_get_contents( __DIR__ . '/stable-diffusion.json' );
	}

	if ( ! empty( $response ) ) {
		return classifai_test_prepare_response( $response );
	}

	return $preempt;
}

/**
 * Prepare mock response for given request.
 *
 * @param string $response Response.
 */
function classifai_test_prepare_response( $response ) {
	return array(
		'headers'     => array(),
		'cookies'     => array(),
		'filename'    => null,
		'response'    => array(
			'code' => 200,
		),
		'status_code' => 200,
		'success'     => 1,
		'body'        => $response,
	);
}

// Enable PDF scan on upload.
if ( ! defined( 'FS_METHOD' ) ) {
	define( 'FS_METHOD', 'direct' );
}

// Add a route to clean taxonomy terms.
add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'classifai/v1',
			'clean/taxonomy-terms',
			array(
				'methods'             => 'GET',
				'callback'            => function() {
					$taxonomies = ['watson-category', 'watson-concept', 'watson-entity', 'watson-keyword'];

					foreach ( $taxonomies as $taxonomy ) {
						$terms = get_terms(
							array(
								'taxonomy'   => $taxonomy,
								'hide_empty' => false,
							)
						);

						foreach ( $terms as $term ) {
							wp_delete_term( $term->term_id, $taxonomy );
						}
					}

					return true;
				},
				'permission_callback' => '__return_true',
			)
		);
	}
);

// AWS Polly API mock for connect to service.
function classifai_mock_aws_polly_connect_to_service() {
	$voices = file_get_contents( __DIR__ . '/amazon-polly-voices.json' );
	return json_decode( $voices, true );
}

// AWS Polly API mock for synthesize speech.
function classifai_mock_aws_polly_pre_synthesize_speech() {
	return file_get_contents( __DIR__ . '/text-to-speech.txt' );
}
