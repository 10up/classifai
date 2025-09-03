<?php

namespace Classifai\Providers;

use WP_Error;
use WordPress\AiClient\AiClient;
use WordPress\AiClient\Providers\Http\DTO\ApiKeyRequestAuthentication;
use WordPress\AiClient\Providers\Models\DTO\ModelConfig;
use WordPress\AiClient\Files\Enums\FileTypeEnum;

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
	 * Makes an authorized POST request with form data.
	 *
	 * @param string $url The OpenAI API URL.
	 * @param array  $body The body of the request.
	 * @return array|WP_Error
	 */
	public function post_form( string $url = '', array $body = [] ) {
		$url = $this->filter_url( 'post_form', $url, [] );

		$boundary = wp_generate_password( 24, false );
		$payload  = $this->build_form_data( $body, $boundary );

		$options = $this->filter_options(
			'post_form',
			[
				'body'    => $payload,
				'headers' => [
					'Content-Type' => 'multipart/form-data; boundary=' . $boundary,
				],
				'timeout' => 60, // phpcs:ignore WordPressVIPMinimum.Performance.RemoteRequestTimeout.timeout_timeout
			],
			$url
		);

		$this->add_headers( $options );

		$response = safe_wp_remote_post( $url, $options );
		$result   = $this->get_result( $response );

		return $this->filter_response( 'post_form', $result, $url, $options );
	}

	/**
	 * Build the form data for the request.
	 *
	 * @param array  $body     The body of the request.
	 * @param string $boundary The boundary of the request.
	 * @return string The form data.
	 */
	protected function build_form_data( array $body, string $boundary ): string {
		$payload = '';

		// Take all our POST fields and transform them to work with form-data.
		foreach ( $body as $name => $value ) {
			$payload .= '--' . $boundary;
			$payload .= "\r\n";

			if ( 'file' === $name ) {
				$payload .= 'Content-Disposition: form-data; name="file"; filename="' . basename( $value ) . '"' . "\r\n";
				$payload .= "\r\n";
				$payload .= file_get_contents( $value ); // phpcs:ignore
			} else {
				$payload .= 'Content-Disposition: form-data; name="' . esc_attr( $name ) .
					'"' . "\r\n\r\n";
				$payload .= esc_attr( $value );
			}

			$payload .= "\r\n";
		}

		$payload .= '--' . $boundary . '--';

		return $payload;
	}

	/**
	 * Make a request using the AI Client SDK.
	 *
	 * @param string|null $user_prompt Prompt to send.
	 * @param array       $options Options to send.
	 * @return array|WP_Error
	 */
	public function client( $user_prompt = null, array $options = [] ) {
		if ( ! $this->use_client || ! $this->ai_client ) {
			return new WP_Error( 'ai_client_not_available', __( 'AI Client is not available', 'classifai' ) );
		}

		$output = 'text';

		// Get the system prompt from messages.
		if ( ! isset( $options['system_instruction'] ) && isset( $options['messages'] ) ) {
			$options['system_instruction'] = $this->get_prompt_from_messages( $options['messages'], 'system' );
		}

		// Get the user prompt from messages.
		if ( empty( $user_prompt ) && isset( $options['messages'] ) ) {
			$user_prompt = $this->get_prompt_from_messages( $options['messages'], 'user' );

			// If the user prompt is an image, save the image URL to attach later.
			if ( isset( $user_prompt[0]['type'] ) && 'image_url' === $user_prompt[0]['type'] ) {
				$options['image_url'] = $user_prompt[0]['image_url']['url'] ?? null;

				$user_prompt = null;
			}
		}

		unset( $options['messages'] );

		try {
			$model_config       = new ModelConfig();
			$using_model_config = false;

			$prompt_builder = AiClient::prompt( $user_prompt );
			$prompt_builder = $prompt_builder->usingProvider( static::PROVIDER_ID );

			if ( ! empty( $options['model'] ) ) {
				$registry            = AiClient::defaultRegistry();
				$provider_class_name = $registry->getProviderClassName( static::PROVIDER_ID );
				$prompt_builder      = $prompt_builder->usingModel( $provider_class_name::model( $options['model'] ) );

				if ( str_contains( $options['model'], 'image' ) ) {
					$output = 'image';
				}
			}

			if ( ! empty( $options['system_instruction'] ) ) {
				$prompt_builder = $prompt_builder->usingSystemInstruction( (string) $options['system_instruction'] );
			}

			if ( ! empty( $options['image_url'] ) ) {
				$prompt_builder = $prompt_builder->withFile( $options['image_url'] );
			}

			if ( ! empty( $options['n'] ) ) {
				$prompt_builder = $prompt_builder->usingCandidateCount( (int) $options['n'] );
			}

			if ( ! empty( $options['temperature'] ) ) {
				$prompt_builder = $prompt_builder->usingTemperature( (float) $options['temperature'] );
			}

			if ( ! empty( $options['max_tokens'] ) ) {
				$prompt_builder = $prompt_builder->usingMaxTokens( (int) $options['max_tokens'] );
			}

			if ( ! empty( $options['quality'] ) ) {
				$model_config->setCustomOption( 'quality', $options['quality'] );
				$using_model_config = true;
			}

			if ( ! empty( $options['style'] ) ) {
				$model_config->setCustomOption( 'style', $options['style'] );
				$using_model_config = true;
			}

			if ( ! empty( $options['size'] ) ) {
				$model_config->setCustomOption( 'size', $options['size'] );
				$using_model_config = true;
			}

			if ( ! empty( $options['response_format'] ) ) {
				if ( ! empty( $options['response_format']['json_schema'] ) && is_array( $options['response_format']['json_schema'] ) ) {
					$prompt_builder = $prompt_builder->asJsonResponse( $options['response_format']['json_schema'] );
				}

				if ( is_string( $options['response_format'] ) ) {
					if ( 'b64_json' === $options['response_format'] ) {
						$prompt_builder = $prompt_builder->asOutputFileType( FileTypeEnum::inline() );

						$output = 'image';
					}
				}
			}

			if ( $using_model_config ) {
				$prompt_builder = $prompt_builder->usingModelConfig( $model_config );
			}

			$count = $options['n'] ?? 1;

			if ( 'text' === $output ) {
				return $this->get_client_result( $prompt_builder->generateTexts( (int) $count ), 'text' );
			} elseif ( 'image' === $output ) {
				return $this->get_client_result( $prompt_builder->generateImages( (int) $count ), 'image' );
			}

			return [];
		} catch ( \Exception $e ) {
			return new WP_Error( 'ai_client_error', $e->getMessage() );
		}
	}

	/**
	 * Get the prompt from the messages array.
	 *
	 * This is mostly here to maintain backwards compatibility
	 * with how we used to handle messages and how the PHP SDK
	 * expects them.
	 *
	 * @param array  $messages Messages to get the prompt from.
	 * @param string $prompt_type Prompt type to get.
	 * @return string
	 */
	protected function get_prompt_from_messages( array $messages, string $prompt_type = 'system' ) {
		$prompt = array_values(
			array_filter(
				$messages,
				function ( $message ) use ( $prompt_type ) {
					return $prompt_type === $message['role'];
				}
			)
		);

		return ! empty( $prompt[0]['content'] ) ? $prompt[0]['content'] : '';
	}

	/**
	 * Process the AI Client response.
	 *
	 * @param array  $response The AI Client response.
	 * @param string $type The type of response.
	 * @return array|WP_Error
	 */
	protected function get_client_result( array $response, string $type = 'text' ) {
		if ( empty( $response ) ) {
			return new WP_Error( 'no_choices', __( 'No choices were returned from the AI provider', 'classifai' ) );
		}

		$results = [];
		foreach ( $response as $choice ) {
			if ( 'image' === $type ) {
				$results[] = $this->sanitize_choice( $choice->getBase64Data() );
			} else {
				$results[] = $this->sanitize_choice( $choice );
			}
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
