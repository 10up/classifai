<?php
/**
 * Plugin name: ClassifAI Cypress Test Request Mock plugin
 */

// Mock the ClassifAI HTTP request calls and provide known response.
add_filter( 'pre_http_request', 'classifai_test_mock_http_requests',  10, 3 );

function classifai_test_mock_http_requests ($preempt, $parsed_args, $url) {
	if ( strpos( $url, 'http://e2e-test-nlu-server.test/v1/analyze' ) !== false ) {
		return classifai_test_prepare_response();
	}
	return $preempt;
}

function classifai_test_prepare_response() {
	return array(
		'headers'     => array(),
		'cookies'     => array(),
		'filename'    => null,
		'response'    => 200,
		'status_code' => 200,
		'success'     => 1,
		'body'        => classifai_test_nlu_response()
	);
}

function classifai_test_nlu_response(){
	return file_get_contents( __DIR__ .  '/nlu.json' );
}
