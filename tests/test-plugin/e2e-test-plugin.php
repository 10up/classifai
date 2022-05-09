<?php
/**
 * Plugin name: ClassifAI Cypress Test Request Mock plugin
 */

// Mock the ClassifAI HTTP request calls and provide known response.
add_filter( 'pre_http_request', 'classifai_test_mock_http_requests',  10, 3 );

/**
 * Mock ClassifAI's HTTP requests.
 *
 * @param boolean $preempt     Whether to preempt an HTTP request's return value.
 * @param array   $parsed_args HTTP request arguments.
 * @param string  $url         The request URL.
 * @return void
 */
function classifai_test_mock_http_requests ( $preempt, $parsed_args, $url ) {
	$response = '';
	if ( strpos( $url, 'http://e2e-test-nlu-server.test/v1/analyze' ) !== false ) {
		$response = file_get_contents( __DIR__ .  '/nlu.json' );
	} elseif ( strpos( $url, 'http://e2e-test-image-processing.test/vision/v3.0/analyze' ) !== false ) {
		$response = file_get_contents( __DIR__ .  '/image_analyze.json' );
	} elseif ( strpos( $url, 'http://e2e-test-image-processing.test/vision/v3.2/ocr' ) !== false ) {
		$response = file_get_contents( __DIR__ .  '/ocr.json' );
	} elseif ( strpos( $url, 'http://e2e-test-image-processing.test/vision/v3.1/generateThumbnail' ) !== false ) {
		$response = file_get_contents( __DIR__ .  '/classifai_thumbnail.png' );
	}
	if ( ! empty( $response ) ) {
		return classifai_test_prepare_response( $response );
	}
	return $preempt;
}

/**
 * Prepare mock response for given request.
 *
 * @param string $response
 * @return void
 */
function classifai_test_prepare_response( $response ) {
	return array(
		'headers'     => array(),
		'cookies'     => array(),
		'filename'    => null,
		'response'    => array(
			'code'        => 200,
		),
		'status_code' => 200,
		'success'     => 1,
		'body'        => $response,
	);
}
