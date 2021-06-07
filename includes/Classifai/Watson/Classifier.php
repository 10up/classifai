<?php

namespace Classifai\Watson;

/**
 * The Classifier object uses the IBM Watson NLU API to classify plain
 * text into NLU features. The low level API Request object is used here
 * and uses the same Watson constant credentials.
 *
 * Usage:
 *
 * $classifier = new Classifier();
 * $classifier->classify( 'Hello World', $watson_options );
 */
class Classifier {

	/**
	 * @var $request
	 * The request object to make Watson api requests
	 */
	public $request;

	/**
	 * @var $endpoint
	 *
	 * The NLU API endpoint
	 */
	public $endpoint;


	/**
	 * Generate the API Url
	 *
	 * @return string
	 */
	public function get_endpoint() {
		if ( empty( $this->endpoint ) ) {
			$base_url       = trailingslashit( \Classifai\get_watson_api_url() ) . 'v1/analyze';
			$this->endpoint = esc_url( add_query_arg( [ 'version' => WATSON_NLU_VERSION ], $base_url ) );
		}
		return $this->endpoint;
	}

	/**
	 * Classifies the text specified using IBM Watson NLU API.
	 *
	 * https://www.ibm.com/watson/developercloud/natural-language-understanding/api/v1/#post-analyze
	 *
	 * @param string $text The plain text to classify
	 * @param array  $options NLU classification options
	 * @param array  $request_options Extra options to pass to the underlying HTTP request
	 *
	 * @return array|WP_Error
	 */
	public function classify( $text, $options = [], $request_options = [] ) {
		$body = $this->get_body( $text, $options );

		$request_options['body'] = $body;
		$request                 = $this->get_request();

		if ( empty( $request_options['timeout'] ) && ! empty( $options['timeout'] ) ) {
			$request_options['timeout'] = $options['timeout'];
		} else {
			$request_options['timeout'] = WATSON_TIMEOUT;
		}

		$classified_data = $request->post( $this->get_endpoint(), $request_options );
		set_transient( 'classifai_watson_nlu_latest_response', $classified_data, DAY_IN_SECONDS * 30 );
		/**
		 * Filter the classified data returned from the API call.
		 *
		 * @since 1.0.0
		 * @hook classifai_classified_data
		 *
		 * @param {array} $classified_data The classified data.
		 *
		 * @return {array} The filtered classified data.
		 */
		return apply_filters( 'classifai_classified_data', $classified_data );
	}

	/* helpers */

	/**
	 * Initializes or returns the api request object to use for making
	 * NLU api calls.
	 *
	 * @return \Classifai\Watson\APIRequest
	 */
	public function get_request() {
		if ( empty( $this->request ) ) {
			$this->request = new APIRequest();
		}

		return $this->request;
	}

	/**
	 * Prepares the NLU Request body JSON from the arguments specified.
	 *
	 * @param string $text The plain text to classify
	 * @param array  $options The NLU classification options
	 * @return array
	 */
	public function get_body( $text, $options = [] ) {
		$options['text'] = $text;

		if ( empty( $options['language'] ) ) {
			$options['language'] = 'en';
		}

		if ( empty( $options['features'] ) ) {
			$options['features'] = [
				'categories' => (object) [],
				'keywords'   => [
					'emotion'   => false,
					'sentiment' => false,
					'limit'     => 10,
				],
				'concepts'   => (object) [],
				'entities'   => (object) [],
			];
		}

		return wp_json_encode( $options );
	}

}
