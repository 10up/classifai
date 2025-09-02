<?php

namespace Classifai\Providers;

use WP_Error;
use WordPress\AiClient\AiClient;
use WordPress\AiClient\Providers\Http\DTO\ApiKeyRequestAuthentication;
use function Classifai\safe_wp_remote_get;
use function Classifai\safe_wp_remote_post;

/**
 * Abstract base class for AI API requests.
 *
 * This class provides a unified interface for making API requests to various AI providers,
 * with support for both traditional HTTP requests and the AI Client SDK.
 *
 * @since x.x.x
 */
abstract class APIRequest {

	/**
	 * Provider ID for this API request class.
	 *
	 * @var string
	 */
	protected const PROVIDER_ID = '';

	/**
	 * The API key for authentication.
	 *
	 * @var string
	 */
	protected $api_key;

	/**
	 * The Feature name making the request.
	 *
	 * @var string
	 */
	protected $feature;

	/**
	 * Default timeout for requests.
	 *
	 * @var int
	 */
	protected $timeout = 90;

	/**
	 * Whether to use the AI Client SDK.
	 *
	 * @var bool
	 */
	protected $use_client = false;

	/**
	 * AI Client instance.
	 *
	 * @var AiClient|null
	 */
	protected $ai_client = null;

	/**
	 * Constructor.
	 *
	 * @param string $api_key    The API key for authentication.
	 * @param string $feature    The feature name making the request.
	 * @param bool   $use_client Whether to use the AI Client SDK.
	 */
	public function __construct( string $api_key = '', string $feature = '', bool $use_client = false ) {
		$this->api_key    = $api_key;
		$this->feature    = $feature;
		$this->use_client = $use_client;

		if ( $this->use_client ) {
			$this->initialize_client();
		}
	}

	/**
	 * Initialize the AI Client.
	 *
	 * @since x.x.x
	 */
	protected function initialize_client() {
		try {
			$this->ai_client = new AiClient();
			$registry        = AiClient::defaultRegistry();

			$registry->setProviderRequestAuthentication(
				static::PROVIDER_ID,
				new ApiKeyRequestAuthentication( $this->api_key )
			);
		} catch ( \Exception $e ) {
			// Fallback to traditional HTTP requests if AI Client fails.
			$this->use_client = false;
		}
	}

	/**
	 * Make a GET request.
	 *
	 * @param string $url     The API URL.
	 * @param array  $options Request options.
	 * @return array|WP_Error
	 */
	public function get( string $url, array $options = [] ) {
		$url     = $this->filter_url( 'get', $url, $options );
		$options = $this->filter_options( 'get', $options, $url );

		$this->add_headers( $options );

		$response = safe_wp_remote_get( $url, $options );
		$result   = $this->get_result( $response );

		return $this->filter_response( 'get', $result, $url, $options );
	}

	/**
	 * Make a POST request.
	 *
	 * @param string $url     The API URL.
	 * @param array  $options Request options.
	 * @return array|WP_Error
	 */
	public function post( string $url = '', array $options = [] ) {
		$options = wp_parse_args( $options, array( 'timeout' => $this->timeout ) );

		$url     = $this->filter_url( 'post', $url, $options );
		$options = $this->filter_options( 'post', $options, $url );

		$this->add_headers( $options );

		$response = safe_wp_remote_post( $url, $options );
		$result   = $this->get_result( $response );

		return $this->filter_response( 'post', $result, $url, $options );
	}

	/**
	 * Make a request using the AI Client SDK.
	 *
	 * @param string $prompt  The prompt to send.
	 * @param array  $options Additional options.
	 * @return array|WP_Error
	 */
	public function client( string $prompt, array $options = [] ) {
		if ( ! $this->use_client || ! $this->ai_client ) {
			return new WP_Error( 'ai_client_not_available', __( 'AI Client is not available', 'classifai' ) );
		}

		try {
			$prompt_builder = AiClient::prompt( $prompt );
			$prompt_builder = $prompt_builder->usingProvider( static::PROVIDER_ID );

			if ( isset( $options['model'] ) ) {
				$registry            = AiClient::defaultRegistry();
				$provider_class_name = $registry->getProviderClassName( static::PROVIDER_ID );
				$prompt_builder      = $prompt_builder->usingModel( $provider_class_name::model( $options['model'] ) );
			}

			if ( isset( $options['system_instruction'] ) ) {
				$prompt_builder = $prompt_builder->usingSystemInstruction( $options['system_instruction'] );
			}

			if ( isset( $options['temperature'] ) ) {
				$prompt_builder = $prompt_builder->usingTemperature( $options['temperature'] );
			}

			$count    = $options['n'] ?? 1;
			$response = $prompt_builder->generateTexts( $count );

			return $this->get_client_response( $response );
		} catch ( \Exception $e ) {
			return new WP_Error( 'ai_client_error', $e->getMessage() );
		}
	}

	/**
	 * Process the AI Client response.
	 *
	 * @param array $response The AI Client response.
	 * @return array|WP_Error
	 */
	protected function get_client_response( array $response ) {
		if ( empty( $response ) ) {
			return new WP_Error( 'no_choices', __( 'No choices were returned from the AI provider', 'classifai' ) );
		}

		$results = [];
		foreach ( $response as $choice ) {
			$results[] = $this->sanitize_choice( $choice );
		}

		return $results;
	}

	/**
	 * Sanitize a choice from AI response.
	 *
	 * @param string $choice The choice to sanitize.
	 * @return string
	 */
	protected function sanitize_choice( string $choice ): string {
		return sanitize_text_field( trim( $choice, ' "\'' ) );
	}

	/**
	 * Get results from HTTP response.
	 *
	 * @param array|WP_Error $response The HTTP response.
	 * @return array|WP_Error
	 */
	public function get_result( $response ) {
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$headers      = wp_remote_retrieve_headers( $response );
		$content_type = $headers['content-type'] ?? false;
		$body         = wp_remote_retrieve_body( $response );
		$code         = wp_remote_retrieve_response_code( $response );

		// Handle different content types.
		if ( ! $content_type || false !== strpos( $content_type, 'application/json' ) ) {
			return $this->parse_json_response( $body, $code );
		} elseif ( $content_type && false !== strpos( $content_type, 'audio/' ) ) {
			return $response; // Return raw response for audio.
		} else {
			return new WP_Error( 'invalid_content_type', __( 'Invalid content type received', 'classifai' ) );
		}
	}

	/**
	 * Parse JSON response.
	 *
	 * @param string $body Response body.
	 * @param int    $code Response code.
	 * @return array|WP_Error
	 */
	protected function parse_json_response( string $body, int $code ) {
		$json = json_decode( $body, true );

		if ( JSON_ERROR_NONE !== json_last_error() ) {
			return new WP_Error( 'invalid_json', __( 'Invalid JSON: ', 'classifai' ) . json_last_error_msg() );
		}

		if ( ! empty( $json['error'] ) ) {
			$message = is_string( $json['error'] )
				? $json['error']
				: ( $json['error']['message'] ?? __( 'An error occurred', 'classifai' ) );
			return new WP_Error( $code, $message );
		}

		return $json;
	}

	/**
	 * Add authentication and content headers.
	 *
	 * @param array $options Request options, passed by reference.
	 */
	public function add_headers( array &$options = [] ) {
		if ( empty( $options['headers'] ) ) {
			$options['headers'] = array();
		}

		$this->add_auth_header( $options );
		$this->add_content_type_header( $options );
	}

	/**
	 * Add authentication header.
	 *
	 * @param array $options Request options, passed by reference.
	 */
	protected function add_auth_header( array &$options ) {
		$auth_header = $this->get_auth_header();
		if ( $auth_header ) {
			$options['headers'][ $this->get_auth_header_name() ] = $auth_header;
		}
	}

	/**
	 * Add content type header.
	 *
	 * @param array $options Request options, passed by reference.
	 */
	protected function add_content_type_header( array &$options ) {
		if ( ! isset( $options['headers']['Content-Type'] ) ) {
			$options['headers']['Content-Type'] = 'application/json';
		}
	}

	/**
	 * Get the authentication header value.
	 * Must be implemented by concrete classes.
	 *
	 * @return string
	 */
	abstract protected function get_auth_header(): string;

	/**
	 * Get the authentication header name.
	 * Must be implemented by concrete classes.
	 *
	 * @return string
	 */
	abstract protected function get_auth_header_name(): string;

	/**
	 * Filter URL before making request.
	 *
	 * @param string $method  HTTP method.
	 * @param string $url     The URL.
	 * @param array  $options Request options.
	 * @return string
	 */
	protected function filter_url( string $method, string $url, array $options ): string {
		$filter_name = sprintf( 'classifai_%s_api_request_%s_url', static::PROVIDER_ID, $method );

		/**
		 * Filter the URL before making request.
		 *
		 * @since x.x.x
		 * @hook classifai_%s_api_request_%s_url
		 *
		 * @param string $url           The URL.
		 * @param array  $options       The options.
		 * @param string $this->feature The feature name.
		 *
		 * @return string The URL.
		 */
		return apply_filters( $filter_name, $url, $options, $this->feature );
	}

	/**
	 * Filter options before making request.
	 *
	 * @param string $method  HTTP method.
	 * @param array  $options Request options.
	 * @param string $url     The URL.
	 * @return array
	 */
	protected function filter_options( string $method, array $options, string $url ): array {
		$filter_name = sprintf( 'classifai_%s_api_request_%s_options', static::PROVIDER_ID, $method );

		/**
		 * Filter the options before making request.
		 *
		 * @since x.x.x
		 * @hook classifai_%s_api_request_%s_options
		 *
		 * @param array  $options       The options.
		 * @param string $url           The URL.
		 * @param string $this->feature The feature name.
		 *
		 * @return array The options.
		 */
		return apply_filters( $filter_name, $options, $url, $this->feature );
	}

	/**
	 * Filter response after receiving it.
	 *
	 * @param string         $method   HTTP method.
	 * @param array|WP_Error $response The response.
	 * @param string         $url      The URL.
	 * @param array          $options  Request options.
	 * @return array|WP_Error
	 */
	protected function filter_response( string $method, $response, string $url, array $options ) {
		$filter_name = sprintf( 'classifai_%s_api_response_%s', static::PROVIDER_ID, $method );

		/**
		 * Filter the response after receiving it.
		 *
		 * @since x.x.x
		 * @hook classifai_%s_api_response_%s
		 *
		 * @param array|WP_Error $response      The response.
		 * @param string         $url           The URL.
		 * @param array          $options       Request options.
		 * @param string         $this->feature The feature name.
		 *
		 * @return array|WP_Error The response.
		 */
		return apply_filters( $filter_name, $response, $url, $options, $this->feature );
	}

	/**
	 * Get the API key.
	 *
	 * @return string
	 */
	public function get_api_key(): string {
		return $this->api_key;
	}

	/**
	 * Get the Feature name.
	 *
	 * @return string
	 */
	public function get_feature(): string {
		return $this->feature;
	}

	/**
	 * Set timeout for requests.
	 *
	 * @param int $timeout Timeout in seconds.
	 */
	public function set_timeout( int $timeout ) {
		$this->timeout = $timeout;
	}

	/**
	 * Enable or disable AI Client usage.
	 *
	 * @param bool $use_client Whether to use AI Client.
	 */
	public function set_use_client( bool $use_client ) {
		$this->use_client = $use_client;
		if ( $use_client && ! $this->ai_client ) {
			$this->initialize_client();
		}
	}

	/**
	 * Check if AI Client is available and initialized.
	 *
	 * @return bool
	 */
	public function is_ai_client_available(): bool {
		return $this->use_client && null !== $this->ai_client;
	}

	/**
	 * Get the Provider ID.
	 *
	 * @return string
	 */
	public function get_provider_id(): string {
		return static::PROVIDER_ID;
	}
}
