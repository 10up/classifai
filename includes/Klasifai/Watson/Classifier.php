<?php

namespace Klasifai\Watson;

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
	 * The request object to make Watson api requests
	 */
	public $request;

	/**
	 * The NLU API endpoint
	 */
	public $endpoint = 'https://gateway.watsonplatform.net/natural-language-understanding/api/v1/analyze?version=2017-02-27';

	/**
	 * Classifies the text specified using IBM Watson NLU API.
	 *
	 * https://www.ibm.com/watson/developercloud/natural-language-understanding/api/v1/#post-analyze
	 *
	 * @param string $text The plain text to classify
	 * @param array $options NLU classification options
	 * @param array $request_options Extra options to pass to the underlying HTTP request
	 * @return array|WP_Error
	 */
	public function classify( $text, $options = [], $request_options = [] ) {
		$body = $this->get_body( $text, $options );

		$request_options['body'] = $body;
		$request = $this->get_request();

		if ( empty( $request_options['timeout'] ) && ! empty( $options['timeout'] ) ) {
			$request_options['timeout'] = $options['timeout'];
		} else {
			$request_options['timeout'] = WATSON_TIMEOUT;
		}

		$classified_data = $request->post( $this->endpoint, $request_options );
		/**
		 * Filter the classified data returned from the API call.
		 */
		return apply_filters( 'klasifai_classified_data', $classified_data );
	}

	/* helpers */

	/**
	 * Initializes or returns the api request object to use for making
	 * NLU api calls.
	 *
	 * @return \Klasifai\Watson\APIRequest
	 */
	function get_request() {
		if ( empty( $this->request ) ) {
			$this->request = new APIRequest();
		}

		return $this->request;
	}

	/**
	 * Prepares the NLU Request body JSON from the arguments specified.
	 *
	 * @param string $text The plain text to classify
	 * @param array $options The NLU classification options
	 * @return array
	 */
	function get_body( $text, $options = [] ) {
		$options['text'] = $text;

		if ( empty( $options['language'] ) ) {
			$options['language'] = 'en';
		}

		if ( empty( $options['features'] ) ) {
			$options['features'] = [
				'categories'    => (object) [],
				'keywords'      => [
					'emotion'   => false,
					'sentiment' => false,
					'limit'     => 10,
				],
				'concepts' => (object) [],
				'entities' => (object) [],
			];
		}

		return json_encode( $options );
	}

}
