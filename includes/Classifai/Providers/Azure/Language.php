<?php
/**
 * Azure Language Provider
 *
 * @package Classifai
 */

namespace Classifai\Providers\Azure;

use Classifai\Providers\Provider;
use Classifai\Features\ExcerptGeneration;
use WP_Error;
use function Classifai\safe_wp_remote_get;


class Language extends Provider {
	/**
	 * The Provider ID.
	 *
	 * Required and should be unique.
	 */
	const ID = 'azure_language';

	/**
	 * The Provider Name.
	 *
	 * Required and should be unique.
	 */
	const API_VERSION = '2023-04-01';

	/**
	 * Analyze Text endpoint.
	 *
	 * @var string
	 */
	const ANALYZE_TEXT_ENDPOINT = '/language/analyze-text/jobs';

	/**
	 * MyProvider constructor.
	 *
	 * @param \Classifai\Features\Feature $feature_instance The feature instance.
	 */
	public function __construct( $feature_instance = null ) {
		$this->feature_instance = $feature_instance;
	}

	/**
	 * Sanitize the settings for this provider.
	 *
	 * Can also be useful to verify the Provider API connection
	 * works as expected here, returning an error if needed.
	 *
	 * @param array $new_settings The settings array.
	 * @return array
	 */
	public function sanitize_settings( array $new_settings ): array {
		$settings = $this->feature_instance->get_settings();

		$new_settings[ static::ID ]['api_key']       = sanitize_text_field( $new_settings[ static::ID ]['api_key'] ?? $settings[ static::ID ]['api_key'] );
		$new_settings[ static::ID ]['endpoint_url']  = esc_url_raw( $new_settings[ static::ID ]['endpoint_url'] ?? $settings[ static::ID ]['endpoint_url'] );
		$new_settings[ static::ID ]['authenticated'] = false;

		if ( ! empty( $new_settings[ static::ID ]['endpoint_url'] ) && ! empty( $new_settings[ static::ID ]['api_key'] ) ) {
			$new_settings[ static::ID ]['authenticated'] = $this->authenticate_credentials(
				$new_settings[ static::ID ]['endpoint_url'],
				$new_settings[ static::ID ]['api_key']
			);
		}

		if ( ! $new_settings[ static::ID ]['authenticated'] ) {
			add_settings_error(
				'authenticated',
				400,
				esc_html( 'There was an error authenticating with Azure Language Services. Please check your credentials.' ),
				'error'
			);

			// disable the feature if we're unable to authenticate./
			$new_settings['status'] = 0;
		}

		return $new_settings;
	}

	/**
	 * Authenticates our credentials.
	 *
	 * Performs a simple test to ensure the credentials are valid.
	 *
	 * @param string $url Endpoint URL.
	 * @param string $api_key Api Key.
	 *
	 * @return bool|WP_Error
	 */
	protected function authenticate_credentials( string $url, string $api_key ) {
		$rtn = false;

		$endpoint = trailingslashit( $url ) . 'language/:analyze-text';
		$endpoint = add_query_arg( 'api-version', static::API_VERSION, $endpoint );

		$body = [
			'kind'          => 'LanguageDetection',
			'parameters'    => [
				'modelVersion' => 'latest',
			],
			'analysisInput' => [
				'documents' => [
					[
						'id'   => '1',
						'text' => 'Hello world',
					],
				],
			],
		];

		$request = wp_remote_post(
			$endpoint,
			[
				'headers' => [
					'Ocp-Apim-Subscription-Key' => $api_key,
					'Content-Type'              => 'application/json',
				],
				'body'    => wp_json_encode( $body ),
			]
		);

		if ( ! is_wp_error( $request ) ) {
			$response = json_decode( wp_remote_retrieve_body( $request ) );
			if ( ! empty( $response->error ) ) {
				$rtn = new WP_Error( 'auth', $response->error->message );
			} else {
				$rtn = true;
			}
		}

		return $rtn;
	}

	/**
	 * Common entry point for all REST endpoints for this provider.
	 *
	 * All Features will end up calling the rest_endpoint_callback method for their assigned Provider.
	 * This method should validate the route that is being called and then call the appropriate method
	 * for that route. This method typically will validate we have all the required data and if so,
	 * make a request to the appropriate API endpoint.
	 *
	 * @param int    $post_id The Post ID we're processing.
	 * @param string $route_to_call The route we are processing.
	 * @param array  $args Optional arguments to pass to the route.
	 * @return string|WP_Error
	 */
	public function rest_endpoint_callback( $post_id = 0, string $route_to_call = '', array $args = [] ) {
		if ( ! $post_id || ! get_post( $post_id ) ) {
			return new WP_Error( 'post_id_required', esc_html__( 'A valid post ID is required to generate an excerpt.', 'classifai' ) );
		}

		$route_to_call = strtolower( $route_to_call );
		$return        = '';

		switch ( $route_to_call ) {
			case 'excerpt':
				$return = $this->generate_excerpt( $post_id, $args );
				break;
		}

		return $return;
	}

	/**
	 * Generate an excerpt for a given post.
	 *
	 * This service requires two API calls - one to request the summary and another to retrieve the summary.
	 *
	 * @param int   $post_id The Post ID we're processing.
	 * @param array $args Optional arguments to pass to the route.
	 * @return string
	 */
	public function generate_excerpt( $post_id, $args ) {
		$feature      = new ExcerptGeneration();
		$settings     = $feature->get_settings();
		$api_key      = $settings[ static::ID ]['api_key'];
		$endpoint_url = $settings[ static::ID ]['endpoint_url'];
		$post_content = get_post_field( 'post_content', $post_id );

		// Request the summary form the API.
		$summary_request_url = $this->request_summary( $endpoint_url, $api_key, $post_content, $post_id );
		if ( is_wp_error( $summary_request_url ) ) {
			return $summary_request_url;
		}

		// Retrieve the Summary after the job is complete.
		$summary = $this->retrieve_summary( $summary_request_url );

		return $summary;
	}

	/**
	 * Request the summary from the API.
	 *
	 * @param string $endpoint_url The endpoint URL.
	 * @param string $api_key The API key.
	 * @param string $post_content The post content.
	 * @param int    $post_id The post ID.
	 *
	 * @return mixed The summary job URL or WP_Error.
	 */
	public function request_summary( $endpoint_url, $api_key, $post_content, $post_id ) {
		$endpoint_url = add_query_arg(
			'api-version',
			static::API_VERSION,
			$endpoint_url . static::ANALYZE_TEXT_ENDPOINT
		);

		$body = [
			'analysisInput' => [
				'documents' => [
					[
						'id'       => '1',
						'language' => 'en',
						'text'     => $post_content,
					],
				],
			],
			'tasks'         => [
				[
					'kind'       => 'AbstractiveSummarization',
					'taskName'   => 'Classifai Summarization ' . $post_id,
					'parameters' => [
						/**
						 * Filter the summary length.
						 * Possible values are 'oneSentence', 'short', 'medium', 'long'.
						 * Default is 'oneSentence'.
						 *
						 * @since 3.2.0
						 * @hook classifai_azure_language_summary_length
						 * @param {string} $summary_length The summary length.
						 * @return {string} The summary length
						 */
						'summaryLength' => apply_filters( 'classifai_azure_language_summary_length', 'oneSentence' ),
					],
				],
			],
		];

		$request = wp_remote_post(
			$endpoint_url,
			[
				'headers' => [
					'Ocp-Apim-Subscription-Key' => $api_key,
					'Content-Type'              => 'application/json',
				],
				'body'    => wp_json_encode( $body ),
			]
		);

		$headers = wp_remote_retrieve_headers( $request );

		if ( ! is_wp_error( $request ) ) {
			$response = json_decode( wp_remote_retrieve_body( $request ) );
			if ( ! empty( $response->error ) ) {
				return new WP_Error( 'summary_request', $response->error->message );
			} elseif ( empty( $headers['operation-location'] ) ) {
				return new WP_Error( 'summary_request', esc_html__( 'There was an error requesting the summary.', 'classifai' ) );
			} else {
				return $headers['operation-location'];
			}
		}

		return $request;
	}

	/**
	 * Get the text analysis job response
	 *
	 * The endpoint URL is returned in the 'operation-location' header of the initial request.
	 * The job response will return a 'status' property if the job is completed.
	 * If the job is not completed, wait a second and try again.
	 *
	 * @param string $url The URL to analyze.
	 * @see https://learn.microsoft.com/en-us/azure/ai-services/language-service/summarization/quickstart?tabs=text-summarization%2Cmacos&pivots=rest-api
	 * @return mixed
	 */
	private function retrieve_summary( $url ) {
		$api_key = $this->feature_instance->get_settings( static::ID )['api_key'];

		$request = safe_wp_remote_get(
			$url,
			[
				'headers' => [
					'Ocp-Apim-Subscription-Key' => $api_key,
					'Content-Type'              => 'application/json',
				],
			]
		);

		$summary = '';

		if ( ! is_wp_error( $request ) ) {
			$response = json_decode( wp_remote_retrieve_body( $request ) );
			if ( ! empty( $response->error ) ) {
				return new WP_Error( 'auth', $response->error->message );
			}

			$attempts = 0;
			$max_attempts = 10;

			while ( 'succeeded' !== $response->status && $attempts < $max_attempts ) {
				$attempts++;
				sleep( 2 );
				$request  = safe_wp_remote_get(
					$url,
					[
						'headers' => [
							'Ocp-Apim-Subscription-Key' => $api_key,
							'Content-Type'              => 'application/json',
						],
					]
				);
				$response = json_decode( wp_remote_retrieve_body( $request ) );
			}

			// Check if we exceeded max attempts
			if ( $attempts >= $max_attempts && 'succeeded' !== $response->status ) {
				return new WP_Error( 'timeout', esc_html__( 'Summary generation timed out. Please try again.', 'classifai' ) );
			}

			$summary = $response->tasks->items[0]->results->documents[0]->summaries[0]->text;
		}

		return $summary;
	}

	/**
	 * Returns the debug information for the provider settings.
	 *
	 * This is used to display various settings in the Site Health screen.
	 * Not required but useful for debugging.
	 *
	 * @return array
	 */
	public function get_debug_information(): array {
		$settings          = $this->feature_instance->get_settings();
		$provider_settings = $settings[ static::ID ];
		$debug_info        = [];

		if ( $this->feature_instance instanceof ExcerptGeneration ) {
			$debug_info[ __( 'Excerpt length', 'classifai' ) ] = apply_filters( 'classifai_azure_language_summary_length', 'oneSentence' );
			$debug_info[ __( 'Provider', 'classifai' ) ]       = 'Azure Language Services';
			$debug_info[ __( 'Endpoint URL', 'classifai' ) ]   = $provider_settings['endpoint_url'];
		}

		return apply_filters(
			'classifai_' . self::ID . '_debug_information',
			$debug_info,
			$settings,
			$this->feature_instance
		);
	}
}
