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
		$response = file_get_contents( __DIR__ . '/../classifai/assets/img/icon-256x256.png' );
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
	} elseif ( strpos( $url, 'https://api.openai.com/v1/organization/admin_api_keys' ) !== false ) {
		$response = file_get_contents( __DIR__ . '/mock-data/openai-admin-api-keys.json' );
	} elseif ( strpos( $url, 'https://api.openai.com/v1/organization/costs' ) !== false ) {
		$response = file_get_contents( __DIR__ . '/mock-data/openai-costs.json' );
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

// Register E2E test helper REST routes for Usage Tracking tests.
add_action(
	'rest_api_init',
	function () {
		// Run usage refresh synchronously and return the updated data.
		register_rest_route(
			'classifai/v1',
			'run-usage-refresh',
			array(
				'methods'             => 'POST',
				'callback'            => function () {
					if ( ! class_exists( 'Classifai\Features\APIUsageTracking' ) ) {
						return new WP_Error( 'class_not_found', 'APIUsageTracking class not found.' );
					}
					$feature = new \Classifai\Features\APIUsageTracking();
					$feature->run_usage_refresh( true );
					return rest_ensure_response(
						array(
							'success' => true,
							'data'    => $feature->get_usage_data(),
						)
					);
				},
				'permission_callback' => '__return_true',
			)
		);

		// Set the usage data option directly.
		register_rest_route(
			'classifai/v1',
			'set-usage-data',
			array(
				'methods'             => 'POST',
				'callback'            => function ( WP_REST_Request $request ) {
					$data = $request->get_json_params();
					update_option( \Classifai\Features\APIUsageTracking::USAGE_DATA_KEY, $data );
					return rest_ensure_response( array( 'success' => true ) );
				},
				'permission_callback' => '__return_true',
			)
		);

		// Set or clear the hard limit reached option.
		register_rest_route(
			'classifai/v1',
			'set-hard-limit',
			array(
				'methods'             => 'POST',
				'callback'            => function ( WP_REST_Request $request ) {
					$reached = (bool) $request->get_param( 'reached' );
					if ( $reached ) {
						update_option( \Classifai\Features\APIUsageTracking::HARD_LIMIT_REACHED_KEY, true );
					} else {
						delete_option( \Classifai\Features\APIUsageTracking::HARD_LIMIT_REACHED_KEY );
					}
					return rest_ensure_response( array( 'success' => true ) );
				},
				'permission_callback' => '__return_true',
			)
		);

		// Merge settings into a specific provider within a feature option.
		register_rest_route(
			'classifai/v1',
			'set-provider-settings',
			array(
				'methods'             => 'POST',
				'callback'            => function ( WP_REST_Request $request ) {
					$body           = $request->get_json_params();
					$feature_option = sanitize_key( $body['feature_option'] ?? '' );
					$provider       = sanitize_key( $body['provider'] ?? '' );
					$settings       = $body['settings'] ?? array();

					if ( empty( $feature_option ) || empty( $provider ) ) {
						return new WP_Error( 'invalid_params', 'Missing required parameters: feature_option, provider.' );
					}

					$existing              = get_option( $feature_option, array() );
					$existing[ $provider ] = array_merge( $existing[ $provider ] ?? array(), $settings );
					update_option( $feature_option, $existing );

					return rest_ensure_response( array( 'success' => true ) );
				},
				'permission_callback' => '__return_true',
			)
		);

		// Run the TTS feature's run() method, temporarily using the OpenAI provider,
		// to verify the hard limit filter blocks the request.
		register_rest_route(
			'classifai/v1',
			'test-tts',
			array(
				'methods'             => 'GET',
				'callback'            => function () {
					if ( ! class_exists( 'Classifai\Features\TextToSpeech' ) ) {
						return new WP_Error( 'class_not_found', 'TextToSpeech class not found.' );
					}

					// Temporarily override TTS settings to use the OpenAI provider so that the
					// classifai_pre_fetch_feature_response filter (hard limit check) applies.
					$feature_option = 'classifai_feature_text_to_speech_generation';
					$saved_settings = get_option( $feature_option, array() );
					$provider_key   = 'openai_text_to_speech';
					$temp_settings  = array_merge(
						$saved_settings,
						array(
							'provider'     => $provider_key,
							$provider_key  => array_merge(
								$saved_settings[ $provider_key ] ?? array(),
								array(
									'authenticated' => true,
									'api_key'       => 'test-key',
								)
							),
						)
					);
					update_option( $feature_option, $temp_settings );

					$feature = new \Classifai\Features\TextToSpeech();
					// Post ID does not matter here; the hard limit filter fires before any post processing.
					$result = $feature->run( 1, 'synthesize' );

					// Restore the original settings.
					update_option( $feature_option, $saved_settings );

					if ( is_wp_error( $result ) ) {
						return rest_ensure_response(
							array(
								'success' => false,
								'code'    => $result->get_error_code(),
								'message' => $result->get_error_message(),
							)
						);
					}

					return rest_ensure_response( array( 'success' => true ) );
				},
				'permission_callback' => '__return_true',
			)
		);
	}
);
