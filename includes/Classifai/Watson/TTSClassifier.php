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
class TTSClassifier {

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
	 * @var output_file_path The path to the output file.
	 *
	 * The output file path
	 */
	public $output_file_path;


	/**
	 * Generate the API Url
	 *
	 * @return string
	 */
	public function get_endpoint() {
		if ( empty( $this->endpoint ) ) {
			$base_url       = trailingslashit( \Classifai\get_watson_tts_api_url() ) . 'v1/synthesize';
			$this->endpoint = esc_url( add_query_arg( array( 'version' => WATSON_TTS_VERSION ), $base_url ) );
		}
		return $this->endpoint;
	}

	/**
	 * Convert Text to Speech
	 *
	 * @param  mixed $text The text to be converted to speech.
	 * @param  int   $post_id The post ID.
	 *
	 * @return string $output_file_path || WP_Error
	 */
	public function text_to_speech( $text, int $post_id ) {

		// url with voice
		$request = $this->get_request();
		$request_options = array();
		$request_options['body'] = $text;
		$request_options['headers'] = array(
			'Content-Type' => 'application/json',
			'Accept' => 'audio/mp3',
		);
		$output_file = $request->post( $this->get_endpoint(), $request_options );

		if ( filesize( $this->output_file_path ) < 1000 ) {
			// probably there is an error and error string is saved to file,
			// open file and read the string
			// if error key exists in the string, delete generated file and throw exception
			$content = file_get_contents( $this->output_file_path );

			if ( $content === false ) {
				throw new Exception( 'Error:' . $this->output_file_path . ' could not be opened' );
			}
			$debug_content = json_decode( $content, true );

			if ( array_key_exists( 'error', $debug_content ) ) {
				// deleted file created, because it is corrupt
				unlink( $this->output_file_path );

				// throw exception of the returned error
				throw new Exception( 'Error:' . $debug_content['error'] . ' code: ' . $debug_content['code'] );
			}
		}

		if ( ! $result || ! is_file( $this->output_file_path ) ) {
			throw new Exception( 'Error creating file' );
		}

		return $this->output_file_path;
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
	public function classify( $text, $options = array(), $request_options = array() ) {
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
	public function get_body( $text, $options = array() ) {
		$options['text'] = $text;

		if ( empty( $options['language'] ) ) {
			$options['language'] = 'en';
		}

		if ( empty( $options['features'] ) ) {
			$options['features'] = array(
				'categories' => (object) array(),
				'keywords'   => array(
					'emotion'   => false,
					'sentiment' => false,
					'limit'     => 10,
				),
				'concepts'   => (object) array(),
				'entities'   => (object) array(),
			);
		}

		return wp_json_encode( $options );
	}

}
