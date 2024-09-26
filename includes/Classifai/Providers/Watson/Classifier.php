<?php

namespace Classifai\Providers\Watson;

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
	 * The request object to make Watson API requests.
	 *
	 * @var APIRequest $request
	 */
	public $request;

	/**
	 * The NLU API endpoint.
	 *
	 * @var string $endpoint
	 */
	public $endpoint;

	/**
	 * Generate the API Url
	 *
	 * @return string
	 */
	public function get_endpoint(): string {
		if ( empty( $this->endpoint ) ) {
			$base_url       = trailingslashit( get_api_url() ) . 'v1/analyze';
			$this->endpoint = esc_url( add_query_arg( [ 'version' => WATSON_NLU_VERSION ], $base_url ) );
		}
		return $this->endpoint;
	}

	/**
	 * Classifies the text specified using IBM Watson NLU API.
	 *
	 * https://cloud.ibm.com/apidocs/natural-language-understanding#analyze
	 *
	 * @param string $text The plain text to classify
	 * @param array  $options NLU classification options
	 * @param array  $request_options Extra options to pass to the underlying HTTP request
	 * @return array|\WP_Error
	 */
	public function classify( string $text, array $options = [], array $request_options = [] ) {
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
	 * Initializes or returns the API request object.
	 *
	 * @return APIRequest
	 */
	public function get_request(): APIRequest {
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
	 * @return string|bool
	 */
	public function get_body( string $text, array $options = [] ) {
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
					'limit'     => defined( 'WATSON_KEYWORD_LIMIT' ) ? WATSON_KEYWORD_LIMIT : 10,
				],
				'concepts'   => (object) [],
				'entities'   => (object) [],
			];
		}

		return wp_json_encode( $options );
	}
}
