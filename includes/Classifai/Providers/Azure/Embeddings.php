<?php
/**
 * Azure OpenAI Embeddings integration
 */

namespace Classifai\Providers\Azure;

use Classifai\Embeddings\HandlesEmbeddingsLifecycle;
use Classifai\Embeddings\HasEmbeddingsStorage;
use Classifai\Features\Classification;
use Classifai\Features\Feature;
use Classifai\EmbeddingsScheduler;
use WP_Error;

use function Classifai\safe_wp_remote_post;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Embeddings extends OpenAI {

	use HasEmbeddingsStorage;
	use HandlesEmbeddingsLifecycle;

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
	public $nlu_features = array();

	/**
	 * Scheduler instance.
	 *
	 * @var EmbeddingsScheduler|null
	 */
	private static $scheduler_instance = null;

	/**
	 * Legacy meta key still used by older installs / for backfill fallback.
	 *
	 * @return string
	 */
	protected function legacy_embedding_meta_key(): string {
		return 'classifai_azure_openai_embeddings';
	}

	/**
	 * Filter prefix used to build per-provider hook/filter names.
	 *
	 * @return string
	 */
	protected function embeddings_filter_prefix(): string {
		return 'classifai_azure_openai_embeddings';
	}

	/**
	 * Action Scheduler action that processes term-embedding batches.
	 *
	 * @return string
	 */
	protected function embeddings_term_job_action(): string {
		return 'classifai_schedule_generate_azure_embedding_job';
	}

	/**
	 * Action Scheduler action that processes post-embedding batches.
	 *
	 * Azure does not currently support RecommendedContent; return empty to no-op.
	 *
	 * @return string
	 */
	protected function embeddings_post_job_action(): string {
		return '';
	}

	/**
	 * OpenAI Embeddings constructor.
	 *
	 * @param Feature $feature_instance The feature instance.
	 */
	public function __construct( $feature_instance = null ) {
		$this->feature_instance = $feature_instance;

		if (
			$this->feature_instance &&
			method_exists( $this->feature_instance, 'get_supported_taxonomies' )
		) {
			$settings   = get_option( $this->feature_instance->get_option_name(), array() );
			$post_types = isset( $settings['post_types'] ) ? $settings['post_types'] : array( 'post' => 1 );

			foreach ( $this->feature_instance->get_supported_taxonomies( $post_types ) as $tax => $label ) {
				$this->nlu_features[ $tax ] = array(
					'feature'           => $label,
					'threshold'         => __( 'Threshold (%)', 'classifai' ),
					'threshold_default' => 75,
					'taxonomy'          => __( 'Taxonomy', 'classifai' ),
					'taxonomy_default'  => $tax,
				);
			}
		}
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
		add_filter( 'classifai_feature_classification_get_default_settings', array( $this, 'modify_default_feature_settings' ), 10, 2 );

		$feature = new Classification();

		self::$scheduler_instance = new EmbeddingsScheduler(
			'classifai_schedule_generate_azure_embedding_job',
			__( 'Azure OpenAI Embeddings', 'classifai' )
		);
		self::$scheduler_instance->init();
		add_action( 'classifai_schedule_generate_azure_embedding_job', array( $this, 'generate_term_embedding_job' ), 10, 4 );

		if (
			! $feature->is_feature_enabled() ||
			$feature->get_feature_provider_instance()::ID !== static::ID
		) {
			return;
		}

		add_action( 'created_term', array( $this, 'generate_embeddings_for_term' ) ); /** @phpstan-ignore return.void (function is used in multiple contexts and needs to return data if called directly) */
		add_action( 'edited_term', array( $this, 'update_embeddings_for_term' ) );
		add_action( 'wp_ajax_get_post_classifier_embeddings_preview_data', array( $this, 'get_post_classifier_embeddings_preview_data' ) );
	}

	/**
	 * Modify the default settings for the classification feature.
	 *
	 * @param array          $settings Current settings.
	 * @param Classification $feature_instance The feature instance.
	 * @return array
	 */
	public function modify_default_feature_settings( array $settings, $feature_instance ): array {
		remove_filter( 'classifai_feature_classification_get_default_settings', array( $this, 'modify_default_feature_settings' ), 10 );

		if ( $feature_instance->get_settings( 'provider' ) !== static::ID ) {
			return $settings;
		}

		add_filter( 'classifai_feature_classification_get_default_settings', array( $this, 'modify_default_feature_settings' ), 10, 2 );

		$defaults = array();

		foreach ( array_keys( $feature_instance->get_supported_taxonomies() ) as $tax ) {
			$enabled = 'category' === $tax ? true : false;

			$defaults[ $tax ]                = $enabled;
			$defaults[ $tax . '_threshold' ] = 75;
			$defaults[ $tax . '_taxonomy' ]  = $tax;
		}

		return array_merge( $settings, $defaults );
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
		$credentials = $this->get_credentials( $feature->get_settings() ?? array() );
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
	protected function authenticate_credentials( array $settings = array() ) {
		$credentials = $this->get_credentials( $settings );
		$rtn         = false;

		// This does basically the same thing that prep_api_url does but when running authentication,
		// we don't have settings saved yet, which prep_api_url needs.
		$endpoint = trailingslashit( $credentials['endpoint_url'] ?? '' ) . str_replace( '{deployment-id}', $credentials['deployment'] ?? '', $this->embeddings_url );
		$endpoint = add_query_arg( 'api-version', $this->api_version, $endpoint );

		$request = safe_wp_remote_post(
			$endpoint,
			array(
				'headers' => array(
					'api-key'      => $credentials['api_key'] ?? '',
					'Content-Type' => 'application/json',
				),
				'body'    => wp_json_encode(
					array(
						'input'      => 'This is a test',
						'dimensions' => $this->get_dimensions(),
					)
				),
				'use_vip' => true,
			)
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
			array(
				'input'      => $text,
				'dimensions' => $this->get_dimensions(),
			),
			$text
		);

		// Make our API request.
		$response = safe_wp_remote_post(
			$this->prep_api_url( $feature ),
			array(
				'headers' => array(
					'api-key'      => $this->get_credential( 'api_key' ) ?? '',
					'Content-Type' => 'application/json',
				),
				'body'    => wp_json_encode( $body ),
				'timeout' => 60, // phpcs:ignore WordPressVIPMinimum.Performance.RemoteRequestTimeout.timeout_timeout
			)
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

		$return = array();

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
	public function generate_embeddings( array $strings = array(), $feature = null ) {
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
			array(
				'input'      => $strings,
				'dimensions' => $this->get_dimensions(),
			),
			$strings
		);

		// Make our API request.
		$response = safe_wp_remote_post(
			$this->prep_api_url( $feature ),
			array(
				'headers' => array(
					'api-key'      => $this->get_credential( 'api_key' ) ?? '',
					'Content-Type' => 'application/json',
				),
				'body'    => wp_json_encode( $body ),
				'timeout' => 60, // phpcs:ignore WordPressVIPMinimum.Performance.RemoteRequestTimeout.timeout_timeout
			)
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

		$return = array();

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
	 * Returns the debug information for the provider settings.
	 *
	 * @return array
	 */
	public function get_debug_information(): array {
		$settings   = $this->feature_instance->get_settings();
		$debug_info = array();

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
