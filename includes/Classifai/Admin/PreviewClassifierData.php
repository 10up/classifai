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
		add_action( 'wp_ajax_classifai_get_post_search_results', array( $this, 'get_post_search_results' ) );
	}

	/**
	 * Returns classifier data for previewing.
	 */
	public function get_post_classifier_preview_data() {
		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( $_POST['nonce'] ) : false;

		if ( ! $nonce || ! wp_verify_nonce( $nonce, 'classifai-previewer-action' ) ) {
			wp_send_json_error( esc_html__( 'Failed nonce check.', 'classifai' ) );
		}

		$post_id    = filter_input( INPUT_POST, 'post_id', FILTER_SANITIZE_NUMBER_INT );
		$classifier = new \Classifai\Watson\Classifier();
		$normalizer = new \Classifai\Watson\Normalizer();

		$text_to_classify        = $normalizer->normalize( $post_id );
		$body                    = $classifier->get_body( $text_to_classify );
		$request_options['body'] = $body;
		$request                 = $classifier->get_request();

		$classified_data = $request->post( $classifier->get_endpoint(), $request_options );

		wp_send_json_success( $classified_data );
	}

	/**
	 * Searches and returns posts.
	 */
	public function get_post_search_results() {
		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( $_POST['nonce'] ) : false;

		if ( ! $nonce || ! wp_verify_nonce( $nonce, 'classifai-previewer-action' ) ) {
			wp_send_json_error( esc_html__( 'Failed nonce check.', 'classifai' ) );
		}

		$search_term   = isset( $_POST['search'] ) ? sanitize_text_field( $_POST['search'] ) : '';
		$post_types    = isset( $_POST['post_types'] ) ? explode( ',', sanitize_text_field( $_POST['post_types'] ) ) : 'post';
		$post_statuses = isset( $_POST['post_status'] ) ? explode( ',', sanitize_text_field( $_POST['post_status'] ) ) : 'publish';

		$posts = get_posts(
			array(
				'post_type'   => $post_types,
				'post_status' => $post_statuses,
				's'           => $search_term,
			)
		);

		wp_send_json_success( $posts );
	}
}

