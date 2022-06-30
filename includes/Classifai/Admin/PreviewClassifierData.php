<?php

namespace Classifai\Admin;

/**
 * Class for registering data necessary
 */
class PreviewClassifierData {
	/**
	 * Constructor function.
	 */
	public function __construct() {
		add_action( 'wp_ajax_get_post_classifier_preview_data', array( $this, 'get_post_classifier_preview_data' ) );
	}

	/**
	 * Returns classifier data for previewing.
	 */
	public function get_post_classifier_preview_data() {
		$post_id = filter_input( INPUT_POST, 'post_id', FILTER_SANITIZE_NUMBER_INT );

		$classifier      = new \Classifai\Watson\Classifier();
		$post_classifier = new \Classifai\PostClassifier();
		$normalizer      = new \Classifai\Watson\Normalizer();

		if ( empty( $opts['features'] ) ) {
			$opts['features'] = $post_classifier->get_features();
		}

		$text_to_classify        = $normalizer->normalize( $post_id );
		$body                    = $classifier->get_body( $text_to_classify, $opts );
		$request_options['body'] = $body;
		$request                 = $classifier->get_request();

		$classified_data = $request->post( $classifier->get_endpoint(), $request_options );

		echo wp_json_encode( $classified_data );
		die;
	}
}

