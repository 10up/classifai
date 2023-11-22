<?php
/**
 * Plugin name: ClassifAI Cypress Test Request Mock plugin
 */

// Mock the ClassifAI HTTP request calls and provide known response.
add_filter( 'pre_http_request', 'classifai_test_mock_http_requests', 10, 3 );

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
	} elseif ( strpos( $url, 'https://api.openai.com/v1/completions' ) !== false ) {
		$response = file_get_contents( __DIR__ . '/chatgpt.json' );
	} elseif ( strpos( $url, 'https://api.openai.com/v1/chat/completions' ) !== false ) {
		$response  = file_get_contents( __DIR__ . '/chatgpt.json' );
		$body_json = isset( $parsed_args['body'] ) ? wp_unslash( $parsed_args['body'] ) : false;

		if ( $body_json ) {
			$body     = json_decode( $body_json, JSON_OBJECT_AS_ARRAY );
			$messages = isset( $body['messages'] ) ? $body['messages'] : [];
			$prompt   = count( $messages ) > 0 ? $messages[0]['content'] : '';

			if ( str_contains( $prompt, 'Increase the content' ) || str_contains( $prompt, 'Decrease the content' ) ) {
				$response = file_get_contents( __DIR__ . '/resize-content.json' );
			} else if ( 'This is a custom excerpt prompt' === $prompt ) {
				$response = file_get_contents( __DIR__ . '/chatgpt-custom-excerpt-prompt.json' );
			} else if ( 'This is a custom title prompt' === $prompt ) {
				$response = file_get_contents( __DIR__ . '/chatgpt-custom-title-prompt.json' );
			} else if ( 'This is a custom shrink prompt' === $prompt || 'This is a custom grow prompt' === $prompt ) {
				$response = file_get_contents( __DIR__ . '/resize-content-custom-prompt.json' );
			}
		}
	} elseif ( strpos( $url, 'https://api.openai.com/v1/audio/transcriptions' ) !== false ) {
		$response = file_get_contents( __DIR__ . '/whisper.json' );
	} elseif ( strpos( $url, 'https://api.openai.com/v1/images/generations' ) !== false ) {
		$response = file_get_contents( __DIR__ . '/dalle.json' );
	} elseif ( strpos( $url, 'https://service.com/cognitiveservices/voices/list' ) !== false ) {
		return array(
			'response'    => array(
				'code' => 200,
			),
			'body' => file_get_contents( __DIR__ . '/text-to-speech-voices.json' ),
		);
	} elseif ( strpos( $url, 'https://service.com/cognitiveservices/v1' ) !== false ) {
		return array(
			'response'    => array(
				'code' => 200,
			),
			'body' => file_get_contents( __DIR__ . '/text-to-speech.txt' ),
		);
	} elseif ( strpos( $url, 'https://api.openai.com/v1/embeddings' ) !== false ) {
		$response = file_get_contents( __DIR__ . '/embeddings.json' );
	} elseif ( strpos( $url, 'http://e2e-test-image-processing.test/vision/v3.2/analyze' ) !== false ) {
		$response = file_get_contents( __DIR__ . '/image_analyze.json' );
	} elseif ( strpos( $url, 'http://e2e-test-image-processing.test/vision/v3.2/ocr' ) !== false ) {
		$response = file_get_contents( __DIR__ . '/ocr.json' );
	} elseif ( strpos( $url, 'http://e2e-test-image-processing.test/vision/v3.2/generateThumbnail' ) !== false ) {
		$response = file_get_contents( __DIR__ . '../classifai/assets/img/icon256x256.png' );
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
