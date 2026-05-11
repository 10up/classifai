<?php
/**
 * Azure OpenAI Embeddings integration
 */

namespace Classifai\Providers\Azure;

use Classifai\Providers\Embeddings\EmbeddingsProviderTrait;
use Classifai\Features\Classification;
use Classifai\Features\Feature;
use Classifai\EmbeddingsScheduler;
use WP_Error;

use function Classifai\safe_wp_remote_post;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Embeddings extends OpenAI {

	use EmbeddingsProviderTrait;

	const ID = 'azure_openai_embeddings';

	/**
	 * Embeddings URL fragment.
	 *
	 * @var string
	 */
	protected $embeddings_url = 'openai/deployments/{deployment-id}/embeddings';

	/**
	 * Embeddings API version.
	 *
	 * @var string
	 */
	protected $api_version = '2024-02-01';

	/**
	 * Maximum number of tokens our model supports.
	 *
	 * @var int
	 */
	protected $max_tokens = 8191;

	/**
	 * Number of dimensions for the embeddings.
	 *
	 * @var int
	 */
	protected $dimensions = 512;

	/**
	 * Maximum number of terms we process.
	 *
	 * @var int
	 */
	protected $max_terms = 5000;

	/**
	 * NLU features that are supported by this provider.
	 *
	 * @var array
	 */
	public $nlu_features = [];

	/**
	 * Scheduler instance.
	 *
	 * @var EmbeddingsScheduler|null
	 */
	private static $scheduler_instance = null;

	/**
	 * OpenAI Embeddings constructor.
	 *
	 * @param Feature $feature_instance The feature instance.
	 */
	public function __construct( $feature_instance = null ) {
		$this->feature_instance = $feature_instance;
		$this->populate_nlu_features_from_supported_taxonomies( false );
	}

	/**
	 * Get the number of dimensions for the embeddings.
	 *
	 * @return int
	 */
	public function get_dimensions(): int {
		/**
		 * Filter the dimensions we want for each embedding.
		 *
		 * Useful if you want to increase or decrease the length
		 * of each embedding.
		 *
		 * @since 3.1.0
		 * @hook classifai_azure_openai_embeddings_dimensions
		 *
		 * @param int $dimensions The default dimensions.
		 *
		 * @return int The dimensions.
		 */
		return apply_filters( 'classifai_azure_openai_embeddings_dimensions', $this->dimensions );
	}

	/**
	 * Get the maximum number of tokens.
	 *
	 * @return int
	 */
	public function get_max_tokens(): int {
		/**
		 * Filter the max number of tokens.
		 *
		 * Useful if you want to change to a different model
		 * that uses a different number of tokens, or be more
		 * strict on the amount of tokens that can be used.
		 *
		 * @since 3.1.0
		 * @hook classifai_azure_openai_embeddings_max_tokens
		 *
		 * @param int $model The default maximum tokens.
		 *
		 * @return int The maximum tokens.
		 */
		return apply_filters( 'classifai_azure_openai_embeddings_max_tokens', $this->max_tokens );
	}

	/**
	 * Get the maximum number of terms we process.
	 *
	 * @return int
	 */
	public function get_max_terms(): int {
		/**
		 * Filter the max number of terms.
		 *
		 * Default for this is 5000 but this filter can be used to change
		 * this, either decreasing to help with performance or increasing
		 * to ensure we consider more terms.
		 *
		 * @since 3.1.0
		 * @hook classifai_azure_openai_embeddings_max_terms
		 *
		 * @param int $terms The default maximum terms.
		 *
		 * @return int The maximum terms.
		 */
		return apply_filters( 'classifai_azure_openai_embeddings_max_terms', $this->max_terms );
	}

	/**
	 * Register what we need for the plugin.
	 *
	 * This only fires if can_register returns true.
	 */
	public function register() {
		add_filter( 'classifai_feature_classification_get_default_settings', [ $this, 'modify_default_feature_settings' ], 10, 2 );

		$feature = new Classification();

		self::$scheduler_instance = new EmbeddingsScheduler(
			'classifai_schedule_generate_azure_embedding_job',
			__( 'Azure OpenAI Embeddings', 'classifai' )
		);
		self::$scheduler_instance->init();
		add_action( 'classifai_schedule_generate_azure_embedding_job', [ $this, 'generate_embedding_job' ], 10, 4 );

		if (
			! $feature->is_feature_enabled() ||
			$feature->get_feature_provider_instance()::ID !== static::ID
		) {
			return;
		}

		add_action( 'created_term', [ $this, 'generate_embeddings_for_term' ] ); /** @phpstan-ignore return.void (function is used in multiple contexts and needs to return data if called directly) */
		add_action( 'edited_terms', [ $this, 'generate_embeddings_for_term' ] ); /** @phpstan-ignore return.void (function is used in multiple contexts and needs to return data if called directly) */
		add_action( 'wp_ajax_get_post_classifier_embeddings_preview_data', array( $this, 'get_post_classifier_embeddings_preview_data' ) );
	}

	/**
	 * Modify the default settings for the classification feature.
	 *
	 * @param array   $settings Current settings.
	 * @param Feature $feature_instance The feature instance.
	 * @return array
	 */
	public function modify_default_feature_settings( array $settings, $feature_instance ): array {
		remove_filter( 'classifai_feature_classification_get_default_settings', [ $this, 'modify_default_feature_settings' ], 10 );

		if ( $feature_instance->get_settings( 'provider' ) !== static::ID ) {
			return $settings;
		}

		add_filter( 'classifai_feature_classification_get_default_settings', [ $this, 'modify_default_feature_settings' ], 10, 2 );

		return $this->merge_supported_taxonomy_defaults( $settings, $feature_instance );
	}

	/**
	 * Sanitization for the options being saved.
	 *
	 * @param array $new_settings Array of settings about to be saved.
	 * @return array The sanitized settings to be saved.
	 */
	public function sanitize_settings( array $new_settings ): array {
		$new_settings = parent::sanitize_settings( $new_settings );

		// Trigger embedding generation for all terms in enabled taxonomies if the feature is on.
		if ( isset( $new_settings['status'] ) && 1 === (int) $new_settings['status'] ) {
			foreach ( array_keys( $this->nlu_features ) as $feature_name ) {
				if ( isset( $new_settings[ $feature_name ] ) && 1 === (int) $new_settings[ $feature_name ] ) {
					$this->trigger_taxonomy_update( $feature_name );
				}
			}
		}

		return $new_settings;
	}

	/**
	 * Build and return the API endpoint based on settings.
	 *
	 * @param Feature $feature Feature instance
	 * @return string
	 */
	protected function prep_api_url( ?Feature $feature = null ): string {
		$credentials = $this->get_credentials( $feature->get_settings() ?? [] );
		$endpoint    = $credentials['endpoint_url'] ?? '';
		$deployment  = $credentials['deployment'] ?? '';

		if ( ! $endpoint ) {
			return '';
		}

		if ( $deployment ) {
			$endpoint = trailingslashit( $endpoint ) . str_replace( '{deployment-id}', $deployment, $this->embeddings_url );
			$endpoint = add_query_arg( 'api-version', $this->api_version, $endpoint );
		}

		return $endpoint;
	}

	/**
	 * Authenticates our credentials.
	 *
	 * @param array $settings Settings being saved
	 * @return bool|WP_Error
	 */
	protected function authenticate_credentials( array $settings = [] ) {
		$credentials = $this->get_credentials( $settings );
		$rtn         = false;

		// This does basically the same thing that prep_api_url does but when running authentication,
		// we don't have settings saved yet, which prep_api_url needs.
		$endpoint = trailingslashit( $credentials['endpoint_url'] ?? '' ) . str_replace( '{deployment-id}', $credentials['deployment'] ?? '', $this->embeddings_url );
		$endpoint = add_query_arg( 'api-version', $this->api_version, $endpoint );

		$request = safe_wp_remote_post(
			$endpoint,
			[
				'headers' => [
					'api-key'      => $credentials['api_key'] ?? '',
					'Content-Type' => 'application/json',
				],
				'body'    => wp_json_encode(
					[
						'input'      => 'This is a test',
						'dimensions' => $this->get_dimensions(),
					]
				),
				'use_vip' => true,
			]
		);

		if ( ! is_wp_error( $request ) ) {
			$response = json_decode( wp_remote_retrieve_body( $request ) );
			if ( ! empty( $response->error ) ) {
				$rtn = new WP_Error( 'auth', $response->error->message );
			} else {
				$rtn = true;
			}
		} else {
			$rtn = $request;
		}

		return $rtn;
	}

	/**
	 * Schedules the job to generate embedding data for all terms within a taxonomy.
	 *
	 * @param string $taxonomy Taxonomy slug.
	 * @param bool   $all Whether to generate embeddings for all terms or just those without embeddings.
	 * @param array  $args     Overridable query args for get_terms()
	 * @param int    $user_id  The user ID to run this as.
	 */
	private function trigger_taxonomy_update( string $taxonomy = '', bool $all = false, array $args = [], int $user_id = 0 ) {
		$feature = new Classification();

		if (
			! $feature->is_feature_enabled() ||
			$feature->get_feature_provider_instance()::ID !== static::ID
		) {
			return;
		}

		$exclude = [];

		// Exclude the uncategorized term.
		if ( 'category' === $taxonomy ) {
			$uncat_term = get_term_by( 'name', 'Uncategorized', 'category' );
			if ( $uncat_term ) {
				$exclude = [ $uncat_term->term_id ];
			}
		}

		/**
		 * Filter the number of terms to process in a batch.
		 *
		 * @since 3.1.0
		 * @hook classifai_azure_openai_embeddings_terms_per_job
		 *
		 * @param int $number Number of terms to process per job.
		 *
		 * @return int Filtered number of terms to process per job.
		 */
		$number = apply_filters( 'classifai_azure_openai_embeddings_terms_per_job', 100 );

		$default_args = [
			'taxonomy'     => $taxonomy,
			'orderby'      => 'count',
			'order'        => 'DESC',
			'hide_empty'   => false,
			'fields'       => 'ids',
			'meta_key'     => $this->get_embeddings_meta_key(), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'meta_compare' => 'NOT EXISTS',
			'number'       => $number,
			'offset'       => 0,
			'exclude'      => $exclude, // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude
		];

		$default_args = array_merge( $default_args, $args );

		// If we want all terms, remove our meta query.
		if ( $all ) {
			unset( $default_args['meta_key'], $default_args['meta_compare'] );
		} else {
			unset( $default_args['offset'] );
		}

		if ( 0 === $user_id ) {
			$user_id = get_current_user_id();
		}

		$job_args = [
			'taxonomy' => $taxonomy,
			'all'      => $all,
			'args'     => $default_args,
			'user_id'  => $user_id,
		];

		// We return early and don't schedule the job if there are no terms.
		if ( function_exists( 'as_has_scheduled_action' ) && ! \as_has_scheduled_action( 'classifai_schedule_generate_azure_embedding_job', $job_args ) ) {
			$terms = get_terms( $default_args );

			if ( is_wp_error( $terms ) || empty( $terms ) ) {
				return;
			}
		}

		if ( function_exists( 'as_enqueue_async_action' ) ) {
			\as_enqueue_async_action( 'classifai_schedule_generate_azure_embedding_job', $job_args );
		}
	}

	/**
	 * Job to generate embedding data for all terms within a taxonomy.
	 *
	 * @param string $taxonomy Taxonomy slug.
	 * @param bool   $all      Whether to generate embeddings for all terms or just those without embeddings.
	 * @param array  $args     Overridable query args for get_terms()
	 * @param int    $user_id  The user ID to run this as.
	 */
	public function generate_embedding_job( string $taxonomy = '', bool $all = false, array $args = [], int $user_id = 0 ) {

		if ( $user_id > 0 ) {
			// We set this as current_user_can() fails when this function runs
			// under the context of Action Scheduler.
			wp_set_current_user( $user_id );
		}

		$terms = get_terms( $args );
		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return;
		}

		// Re-orders the keys.
		$terms   = array_values( $terms );
		$exclude = [];

		// Generate embedding data for each term.
		foreach ( $terms as $term_id ) {
			/** @var int $term_id */
			$has_generated = $this->generate_embeddings_for_term( $term_id, $all );

			if ( is_wp_error( $has_generated ) ) {
				$exclude[] = $term_id;
			}
		}

		if ( $all && isset( $args['offset'] ) && isset( $args['number'] ) ) {
			$args['offset'] = $args['offset'] + $args['number'];
		}

		if ( ! empty( $exclude ) ) {
			$args['exclude'] = array_merge( $args['exclude'], $exclude ); // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude
		}

		$this->trigger_taxonomy_update( $taxonomy, $all, $args, $user_id );
	}

	/**
	 * Generate an embedding for a particular piece of text.
	 *
	 * @param string       $text    Text to generate the embedding for.
	 * @param Feature|null $feature Feature instance.
	 * @return array|boolean|WP_Error
	 */
	public function generate_embedding( string $text = '', $feature = null ) {
		if ( ! $feature ) {
			$feature = new Classification();
		}

		// Ensure the feature is enabled.
		if ( ! $feature->is_feature_enabled() ) {
			return new WP_Error( 'not_enabled', esc_html__( 'Classification is disabled or OpenAI authentication failed. Please check your settings.', 'classifai' ) );
		}

		// Ensure we have a valid Feature instance.
		$backup_feature_instance = $this->feature_instance;
		$this->feature_instance  = $feature;

		/**
		 * Filter the request body before sending to OpenAI.
		 *
		 * @since 3.1.0
		 * @hook classifai_azure_openai_embeddings_request_body
		 *
		 * @param array  $body Request body that will be sent to OpenAI.
		 * @param string $text Text we are getting embeddings for.
		 *
		 * @return array Request body.
		 */
		$body = apply_filters(
			'classifai_azure_openai_embeddings_request_body',
			[
				'input'      => $text,
				'dimensions' => $this->get_dimensions(),
			],
			$text
		);

		// Make our API request.
		$response = safe_wp_remote_post(
			$this->prep_api_url( $feature ),
			[
				'headers' => [
					'api-key'      => $this->get_credential( 'api_key' ) ?? '',
					'Content-Type' => 'application/json',
				],
				'body'    => wp_json_encode( $body ),
				'timeout' => 60, // phpcs:ignore WordPressVIPMinimum.Performance.RemoteRequestTimeout.timeout_timeout
			]
		);
		$response = $this->get_result( $response );

		set_transient( 'classifai_azure_openai_embeddings_latest_response', $response, DAY_IN_SECONDS * 30 );

		// Restore the existing Feature instance.
		$this->feature_instance = $backup_feature_instance;

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( empty( $response['data'] ) ) {
			return new WP_Error( 'no_data', esc_html__( 'No data returned from Azure OpenAI.', 'classifai' ) );
		}

		$return = [];

		// Parse out the embeddings response.
		foreach ( $response['data'] as $data ) {
			if ( ! isset( $data['embedding'] ) || ! is_array( $data['embedding'] ) ) {
				continue;
			}

			$return = $data['embedding'];

			break;
		}

		return $return;
	}

	/**
	 * Generate embeddings for an array of text.
	 *
	 * @param array        $strings Array of text to generate embeddings for.
	 * @param Feature|null $feature Feature instance.
	 * @return array|boolean|WP_Error
	 */
	public function generate_embeddings( array $strings = [], $feature = null ) {
		if ( ! $feature ) {
			$feature = new Classification();
		}

		// Ensure the feature is enabled.
		if ( ! $feature->is_feature_enabled() ) {
			return new WP_Error( 'not_enabled', esc_html__( 'Classification is disabled or OpenAI authentication failed. Please check your settings.', 'classifai' ) );
		}

		// Ensure we have a valid Feature instance.
		$backup_feature_instance = $this->feature_instance;
		$this->feature_instance  = $feature;

		/**
		 * Filter the request body before sending to OpenAI.
		 *
		 * @since 3.1.0
		 * @hook classifai_azure_openai_embeddings_request_body
		 *
		 * @param array $body    Request body that will be sent to OpenAI.
		 * @param array $strings Array of text we are getting embeddings for.
		 *
		 * @return array Request body.
		 */
		$body = apply_filters(
			'classifai_azure_openai_embeddings_request_body',
			[
				'input'      => $strings,
				'dimensions' => $this->get_dimensions(),
			],
			$strings
		);

		// Make our API request.
		$response = safe_wp_remote_post(
			$this->prep_api_url( $feature ),
			[
				'headers' => [
					'api-key'      => $this->get_credential( 'api_key' ) ?? '',
					'Content-Type' => 'application/json',
				],
				'body'    => wp_json_encode( $body ),
				'timeout' => 60, // phpcs:ignore WordPressVIPMinimum.Performance.RemoteRequestTimeout.timeout_timeout
			]
		);
		$response = $this->get_result( $response );

		// Restore the existing Feature instance.
		$this->feature_instance = $backup_feature_instance;

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( empty( $response['data'] ) ) {
			return new WP_Error( 'no_data', esc_html__( 'No data returned from OpenAI.', 'classifai' ) );
		}

		$return = [];

		// Parse out the embeddings response.
		foreach ( $response['data'] as $data ) {
			if ( ! isset( $data['embedding'] ) || ! is_array( $data['embedding'] ) ) {
				continue;
			}

			$return[] = $data['embedding'];
		}

		return $return;
	}

	/**
	 * Common entry point for all REST endpoints for this provider.
	 *
	 * @param int    $post_id The Post Id we're processing.
	 * @param string $route_to_call The route we are processing.
	 * @param array  $args Optional arguments to pass to the route.
	 * @return string|WP_Error
	 */
	public function rest_endpoint_callback( $post_id = 0, string $route_to_call = '', array $args = [] ) {
		if ( ! $post_id || ! get_post( $post_id ) ) {
			return new WP_Error( 'post_id_required', esc_html__( 'A valid post ID is required to run classification.', 'classifai' ) );
		}

		$route_to_call = strtolower( $route_to_call );
		$return        = '';

		// Handle all of our routes.
		switch ( $route_to_call ) {
			case 'classify':
				$return = $this->generate_embeddings_for_post( $post_id, true );
				break;
		}

		return $return;
	}

	/**
	 * Returns the debug information for the provider settings.
	 *
	 * @return array
	 */
	public function get_debug_information(): array {
		$settings   = $this->feature_instance->get_settings();
		$debug_info = [];

		if ( $this->feature_instance instanceof Classification ) {
			foreach ( array_keys( $this->feature_instance->get_supported_taxonomies() ) as $tax ) {
				$debug_info[ "Taxonomy ($tax)" ]           = Feature::get_debug_value_text( $settings[ $tax ], 1 );
				$debug_info[ "Taxonomy ($tax threshold)" ] = floatval( $settings[ $tax . '_threshold' ] );
			}

			$debug_info[ __( 'Latest response', 'classifai' ) ] = $this->get_formatted_latest_response( get_transient( 'classifai_azure_openai_embeddings_latest_response' ) );
		}

		return apply_filters(
			'classifai_' . self::ID . '_debug_information',
			$debug_info,
			$settings,
			$this->feature_instance
		);
	}

	/**
	 * Get embeddings generation status.
	 *
	 * @return bool
	 */
	public function is_embeddings_generation_in_progress(): bool {
		return self::$scheduler_instance->is_embeddings_generation_in_progress();
	}
}
