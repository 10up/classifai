<?php

namespace Classifai\Providers\Watson;

use Classifai\Features\Classification;
use Classifai\Providers\Watson\NLU;
use Classifai\Normalizer;

/**
 * PostClassifier classifies and links WP posts to Taxonomy Terms based
 * on IBM Watson NLU API output.
 */
class PostClassifier {

	/**
	 * API Request object that sends requests to Watson NLU.
	 *
	 * @var APIRequest $api_request The API request object.
	 */
	public $api_request;

	/**
	 * Converts post content to plain text for classification.
	 *
	 * @var Normalizer $normalizer Normalizer object.
	 */
	public $normalizer;

	/**
	 * Classifier object that takes plain text.
	 *
	 * @var Classifier $classifier Classifier object.
	 */
	public $classifier;

	/**
	 * Links results from NLU API with Taxonomy Terms.
	 *
	 * @var Linker $linker Linker object.
	 */
	public $linker;

	/**
	 * Classifies the specified post_id and returns the NLU API output.
	 * The features option can be used to override the configured
	 * settings.
	 *
	 * @param int   $post_id The post to classify
	 * @param array $opts The classification options
	 * @return array|\WP_Error
	 */
	public function classify( int $post_id, array $opts = [] ) {
		$classifier = $this->get_classifier();
		$normalizer = $this->get_normalizer();

		if ( empty( $opts['features'] ) ) {
			$opts['features'] = $this->get_features();
		}

		$text_to_classify = $normalizer->normalize( $post_id );

		if ( ! empty( $text_to_classify ) ) {
			return $classifier->classify( $text_to_classify, $opts );
		} else {
			return new \WP_Error( 'invalid', esc_html__( 'No text found.', 'classifai' ) );
		}
	}

	/**
	 * Classifies the specified post using Watson NLU and also links to
	 * Taxonomy terms if output was valid.
	 *
	 * @param int   $post_id    The post to classify
	 * @param array $opts       The classification options
	 * @param bool  $link_terms Whether to link the terms or not.
	 * @return object|bool
	 */
	public function classify_and_link( int $post_id, array $opts = [], bool $link_terms = true ) {
		$output = $this->classify( $post_id, $opts );

		if ( is_wp_error( $output ) ) {
			return $output;
		} elseif ( empty( $output ) ) {
			return false;
		} else {
			$link_output = $this->link( $post_id, $output, $opts, $link_terms );

			return $link_terms ? $output : $link_output;
		}
	}

	/**
	 * Links the Watson NLU response output to Taxonomy Terms
	 *
	 * @param int   $post_id The post id.
	 * @param array $output  The classification results from Watson NLU.
	 * @param array $opts    Link options.
	 * @param bool  $link_terms Whether to link the terms or not.
	 * @return array The linked output.
	 */
	public function link( int $post_id, array $output, array $opts = [], bool $link_terms = true ): array {
		$linker = $this->get_linker();

		return $linker->link( $post_id, $output, $opts, $link_terms );
	}

	/* helpers */

	/**
	 * Lazy init api request object.
	 *
	 * @return APIRequest
	 */
	public function get_api_request(): APIRequest {
		if ( is_null( $this->api_request ) ) {
			$this->api_request = new APIRequest();
		}

		return $this->api_request;
	}

	/**
	 * Lazy init normalizer object.
	 *
	 * @return Normalizer
	 */
	public function get_normalizer(): Normalizer {
		if ( is_null( $this->normalizer ) ) {
			$this->normalizer = new Normalizer();
		}

		return $this->normalizer;
	}

	/**
	 * Lazy init linker object.
	 *
	 * @return Linker
	 */
	public function get_linker(): Linker {
		if ( is_null( $this->linker ) ) {
			$this->linker = new Linker();
		}

		return $this->linker;
	}

	/**
	 * Lazy init classifier object.
	 *
	 * @return Classifier
	 */
	public function get_classifier(): Classifier {
		if ( is_null( $this->classifier ) ) {
			$this->classifier          = new Classifier();
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
	public function get_features(): array {
		$classification = new Classification();
		$settings       = $classification->get_settings();
		$features       = [];

		if ( $settings['category'] ) {
			$features['categories'] = (object) [];
		}

		if ( $settings['keyword'] ) {
			$features['keywords'] = [
				'emotion'   => false,
				'sentiment' => false,
				'limit'     => defined( 'WATSON_KEYWORD_LIMIT' ) ? WATSON_KEYWORD_LIMIT : 10,
			];
		}

		if ( $settings['concept'] ) {
			$features['concepts'] = (object) [];
		}

		if ( $settings['entity'] ) {
			$features['entities'] = (object) [];
		}

		return $features;
	}
}
