<?php

namespace Classifai;

/**
 * PostClassifier classifies and links WP posts to Taxonomy Terms based
 * on IBM Watson NLU API output.
 */
class PostClassifier {

	/**
	 * API Request object that sends requests to Watson NLU
	 */
	public $api_request;

	/**
	 * Converts post content to plain text for classification
	 */
	public $normalizer;

	/**
	 * Classifier object that takes plain text
	 */
	public $classifier;

	/**
	 * Links results from NLU API with Taxonomy Terms
	 */
	public $linker;

	/**
	 * Classifies the specified post_id and returns the NLU API output.
	 * The features option can be used to override the configured
	 * settings.
	 *
	 * @param int $post_id The post to classify
	 * @param array $opts The classification options
	 * @return array
	 */
	public function classify( $post_id, $opts = [] ) {
		$classifier = $this->get_classifier();
		$normalizer = $this->get_normalizer();

		if ( empty( $opts['features'] ) ) {
			$opts['features'] = $this->get_features();
		}

		$text_to_classify = $normalizer->normalize( $post_id );

		if ( ! empty( $text_to_classify ) ) {
			return $classifier->classify( $text_to_classify, $opts );
		} else {
			return false;
		}
	}

	/**
	 * Classifies the specified post using Watson NLU and also links to
	 * Taxonomy terms if output was valid.
	 *
	 * @param int $post_id The post to classify
	 * @param array $opts The classification options
	 * @return array
	 */
	public function classify_and_link( $post_id, $opts = [] ) {
		$output = $this->classify( $post_id, $opts );

		if ( is_wp_error( $output ) ) {
			return $output;
		} else if ( empty( $output ) ) {
			return false;
		} else {
			$this->link( $post_id, $output, $opts );
			return $output;
		}
	}

	/**
	 * Links the Watson NLU response output to Taxonomy Terms
	 */
	public function link( $post_id, $output, $opts = [] ) {
		$linker = $this->get_linker();
		$linker->link( $post_id, $output, $opts );
	}

	/* helpers */

	/**
	 * Lazy init api request object
	 */
	function get_api_request() {
		if ( is_null( $this->api_request ) ) {
			$this->api_request = new Watson\APIRequest();
		}

		return $this->api_request;
	}

	/**
	 * Lazy init normalizer object
	 */
	function get_normalizer() {
		if ( is_null( $this->normalizer ) ) {
			$this->normalizer = new Watson\Normalizer();
		}

		return $this->normalizer;
	}

	/**
	 * Lazy init linker object
	 */
	function get_linker() {
		if ( is_null( $this->linker ) ) {
			$this->linker = new Watson\Linker();
		}

		return $this->linker;
	}

	/**
	 * Lazy init classifier object
	 */
	function get_classifier() {
		if ( is_null( $this->classifier ) ) {
			$this->classifier          = new Watson\Classifier();
			$this->classifier->request = $this->get_api_request();
		}

		return $this->classifier;
	}

	/**
	 * Builds the features to send to Watson NLU based on currently
	 * configured settings.
	 *
	 * @return array
	 */
	function get_features() {
		$features = [];

		if ( get_feature_enabled( 'category' ) ) {
			$features['categories'] = (object) [];
		}

		if ( get_feature_enabled( 'keyword' ) ) {
			$features['keywords'] = [
				'emotion'   => false,
				'sentiment' => false,
				'limit'     => defined( 'WATSON_KEYWORD_LIMIT' ) ? WATSON_KEYWORD_LIMIT : 10,
			];
		}

		if ( get_feature_enabled( 'concept' ) ) {
			$features['concepts'] = (object) [];
		}

		if ( get_feature_enabled( 'entity' ) ) {
			$features['entities'] = (object) [];
		}

		return $features;
	}

}
