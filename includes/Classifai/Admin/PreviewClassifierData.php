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
		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : false;

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
		$classified_data = $this->filter_classify_preview_data( $classified_data );

		wp_send_json_success( $classified_data );
	}

	/**
	 * Searches and returns posts.
	 */
	public function get_post_search_results() {
		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : false;

		if ( ! ( $nonce && wp_verify_nonce( $nonce, 'classifai-previewer-nonce' ) ) ) {
			wp_send_json_error( esc_html__( 'Failed nonce check.', 'classifai' ) );
		}

		$search_term   = isset( $_POST['search'] ) ? sanitize_text_field( wp_unslash( $_POST['search'] ) ) : '';
		$post_types    = isset( $_POST['post_types'] ) ? explode( ',', sanitize_text_field( wp_unslash( $_POST['post_types'] ) ) ) : 'post';
		$post_statuses = isset( $_POST['post_status'] ) ? explode( ',', sanitize_text_field( wp_unslash( $_POST['post_status'] ) ) ) : 'publish';

		$posts = get_posts(
			array(
				'post_type'   => $post_types,
				'post_status' => $post_statuses,
				's'           => $search_term,
			)
		);

		wp_send_json_success( $posts );
	}

	/**
	 * Filter classifier preview based on the feature settings.
	 *
	 * @param array $classified_data The classified data.
	 */
	public function filter_classify_preview_data( $classified_data ) {
		if ( is_wp_error( $classified_data ) ) {
			return $classified_data;
		}

		$classify_existing_terms = 'existing_terms' === \Classifai\get_classification_method();
		if ( ! $classify_existing_terms ) {
			return $classified_data;
		}

		$features = [
			'category' => 'categories',
			'concept'  => 'concepts',
			'entity'   => 'entities',
			'keyword'  => 'keywords',
		];
		foreach ( $features as $key => $feature ) {
			$taxonomy = \Classifai\get_feature_taxonomy( $key );
			if ( ! $taxonomy ) {
				continue;
			}

			if ( ! isset( $classified_data[ $feature ] ) || empty( $classified_data[ $feature ] ) ) {
				continue;
			}

			// Handle categories feature.
			if ( 'categories' === $feature ) {
				$classified_data[ $feature ] = array_filter(
					$classified_data[ $feature ],
					function ( $item ) use ( $taxonomy ) {
						$keep  = false;
						$parts = explode( '/', $item['label'] );
						$parts = array_filter( $parts );
						if ( ! empty( $parts ) ) {
							foreach ( $parts as $part ) {
								$term = get_term_by( 'name', $part, $taxonomy );
								if ( ! empty( $term ) ) {
									$keep = true;
									break;
								}
							}
						}
						return $keep;
					}
				);
				// Reset array keys.
				$classified_data[ $feature ] = array_values( $classified_data[ $feature ] );
				continue;
			}

			$classified_data[ $feature ] = array_filter(
				$classified_data[ $feature ],
				function ( $item ) use ( $taxonomy, $key ) {
					$name = $item['text'];
					if ( 'keyword' === $key ) {
						$name = preg_replace( '#^[a-z]+ ([A-Z].*)$#', '$1', $name );
					} elseif ( 'entity' === $key ) {
						if ( ! empty( $item['disambiguation'] ) && ! empty( $item['disambiguation']['name'] ) ) {
							$name = $item['disambiguation']['name'];
						}
					}
					$term = get_term_by( 'name', $name, $taxonomy );
					return ! empty( $term );
				}
			);
			// Reset array keys.
			$classified_data[ $feature ] = array_values( $classified_data[ $feature ] );
		}

		return $classified_data;
	}
}
