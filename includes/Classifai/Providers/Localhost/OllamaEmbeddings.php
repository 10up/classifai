<?php
/**
 * Ollama Embeddings integration
 */

namespace Classifai\Providers\Localhost;

use Classifai\Embeddings\HandlesEmbeddingsLifecycle;
use Classifai\Embeddings\HasEmbeddingsStorage;
use Classifai\Embeddings\MigrationRunner;
use Classifai\Features\Classification;
use Classifai\Providers\OpenAI\APIRequest;
use Classifai\Features\Feature;
use Classifai\EmbeddingsScheduler;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Ollama Embeddings class
 */
class OllamaEmbeddings extends Ollama {

	use HasEmbeddingsStorage;
	use HandlesEmbeddingsLifecycle;

	/**
	 * The Provider ID.
	 */
	const ID = 'ollama_embeddings';

	/**
	 * Maximum number of terms we process.
	 *
	 * @var int
	 */
	protected $max_terms = 5000;

	/**
	 * The models we support.
	 *
	 * @var array
	 */
	protected $models = [
		'all-minilm'              => [
			'dims'   => 384,
			'tokens' => 512,
		],
		'nomic-embed-text'        => [
			'dims'   => 768,
			'tokens' => 2048,
		],
		'mxbai-embed-large'       => [
			'dims'   => 1024,
			'tokens' => 512,
		],
		'snowflake-arctic-embed'  => [
			'dims'   => 1024,
			'tokens' => 512,
		],
		'snowflake-arctic-embed2' => [
			'dims'   => 1024,
			'tokens' => 8192,
		],
		'bge-m3'                  => [
			'dims'   => 1024,
			'tokens' => 8192,
		],
		'bge-large'               => [
			'dims'   => 1024,
			'tokens' => 512,
		],
		'granite-embedding'       => [
			'dims'   => 384,
			'tokens' => 512,
		],
	];

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
	 * Legacy meta key still used by older installs / for backfill fallback.
	 *
	 * @return string
	 */
	protected function legacy_embedding_meta_key(): string {
		return 'classifai_ollama_embeddings';
	}

	/**
	 * Filter prefix used to build per-provider hook/filter names.
	 *
	 * @return string
	 */
	protected function embeddings_filter_prefix(): string {
		return 'classifai_ollama_embeddings';
	}

	/**
	 * Action Scheduler action that processes term-embedding batches.
	 *
	 * @return string
	 */
	protected function embeddings_term_job_action(): string {
		return 'classifai_schedule_generate_embedding_job';
	}

	/**
	 * Action Scheduler action that processes post-embedding batches.
	 *
	 * Ollama does not currently support RecommendedContent; return empty to no-op.
	 *
	 * @return string
	 */
	protected function embeddings_post_job_action(): string {
		return '';
	}

	/**
	 * Ollama model is set in feature settings; reads pick that up so model swaps
	 * land in a separate row instead of mixing dimensions in one bucket.
	 *
	 * @return string
	 */
	protected function embeddings_model_id(): string {
		if ( ! empty( $this->feature_instance ) ) {
			$settings = $this->feature_instance->get_settings();
			$model    = $settings[ static::ID ]['model'] ?? '';
			if ( '' !== $model ) {
				return (string) $model;
			}
		}

		return MigrationRunner::DEFAULT_MODELS[ static::ID ] ?? 'default';
	}

	/**
	 * Ollama Embeddings constructor.
	 *
	 * @param \Classifai\Features\Feature $feature_instance The feature instance.
	 */
	public function __construct( $feature_instance = null ) {
		parent::__construct( $feature_instance );

		// Setup our NLU features if using the Classification feature.
		if (
			$this->feature_instance &&
			Classification::ID === $this->feature_instance::ID &&
			method_exists( $this->feature_instance, 'get_supported_taxonomies' )
		) {
			$settings   = get_option( $this->feature_instance->get_option_name(), [] );
			$post_types = isset( $settings['post_types'] ) ? $settings['post_types'] : [ 'post' => 1 ];

			foreach ( $this->feature_instance->get_supported_taxonomies( $post_types ) as $tax => $label ) {
				$this->nlu_features[ $tax ] = [
					'feature'           => $label,
					'threshold'         => __( 'Threshold (%)', 'classifai' ),
					'threshold_default' => 75,
					'taxonomy'          => __( 'Taxonomy', 'classifai' ),
					'taxonomy_default'  => $tax,
				];
			}
		}
	}

	/**
	 * Get the currently configured model for this provider.
	 *
	 * Returns the empty string when no feature_instance is attached so callers
	 * can fall back to defaults.
	 *
	 * @return string
	 */
	protected function get_current_model(): string {
		if ( empty( $this->feature_instance ) ) {
			return '';
		}

		$settings = $this->feature_instance->get_settings();
		return (string) ( $settings[ static::ID ]['model'] ?? '' );
	}

	/**
	 * Get the number of dimensions for the embeddings.
	 *
	 * @param string $model The model to use. Defaults to the currently-configured model.
	 * @return int
	 */
	public function get_dimensions( string $model = '' ): int {
		if ( '' === $model ) {
			$model = $this->get_current_model();
		}

		$model = explode( ':', $model );
		$dims  = 1024;

		if ( isset( $this->models[ $model[0] ] ) ) {
			$dims = $this->models[ $model[0] ]['dims'];
		}

		/**
		 * Filter the dimensions we want for each embedding.
		 *
		 * Useful if you want to increase or decrease the length
		 * of each embedding.
		 *
		 * @since 3.3.0
		 * @hook classifai_ollama_embeddings_dimensions
		 *
		 * @param int $dimensions The default dimensions.
		 *
		 * @return int The dimensions.
		 */
		return apply_filters( 'classifai_ollama_embeddings_dimensions', $dims );
	}

	/**
	 * Get the maximum number of tokens.
	 *
	 * @param string $model The model to use. Defaults to the currently-configured model.
	 * @return int
	 */
	public function get_max_tokens( string $model = '' ): int {
		if ( '' === $model ) {
			$model = $this->get_current_model();
		}

		$model  = explode( ':', $model );
		$tokens = 1024;

		if ( isset( $this->models[ $model[0] ] ) ) {
			$tokens = $this->models[ $model[0] ]['tokens'];
		}

		/**
		 * Filter the max number of tokens.
		 *
		 * Useful if you want to change to a different model
		 * that uses a different number of tokens, or be more
		 * strict on the amount of tokens that can be used.
		 *
		 * @since 3.3.0
		 * @hook classifai_ollama_embeddings_max_tokens
		 *
		 * @param int $model The default maximum tokens.
		 *
		 * @return int The maximum tokens.
		 */
		return apply_filters( 'classifai_ollama_embeddings_max_tokens', $tokens );
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
		 * @since 3.3.0
		 * @hook classifai_ollama_embeddings_max_terms
		 *
		 * @param int $terms The default maximum terms.
		 *
		 * @return int  The maximum terms.
		 */
		return apply_filters( 'classifai_ollama_embeddings_max_terms', $this->max_terms );
	}

	/**
	 * Connects to Ollama and retrieves supported models.
	 *
	 * @param array $args Overridable args.
	 * @return array
	 */
	public function get_models( array $args = [] ): array {
		$models = parent::get_models( $args );

		$supported_models = [
			'all-minilm',
			'nomic-embed-text',
			'mxbai-embed-large',
			'snowflake-arctic-embed',
			'snowflake-arctic-embed2',
			'bge-m3',
			'bge-large',
			'granite-embedding',
		];

		// Ensure our model list only contains the ones we support.
		foreach ( $models as $key => $model ) {
			$model = explode( ':', $model );

			if ( ! in_array( $model[0], $supported_models, true ) ) {
				unset( $models[ $key ] );
			}
		}

		return $models;
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
			'classifai_schedule_generate_embedding_job',
			__( 'Ollama Embeddings', 'classifai' )
		);
		self::$scheduler_instance->init();
		add_action( 'classifai_schedule_generate_embedding_job', [ $this, 'generate_term_embedding_job' ], 10, 4 );

		if (
			( $this->feature_instance && Classification::ID !== $this->feature_instance::ID ) ||
			! $feature->is_feature_enabled() ||
			$feature->get_feature_provider_instance()::ID !== static::ID
		) {
			return;
		}

		add_action( 'created_term', [ $this, 'generate_embeddings_for_term' ] ); /** @phpstan-ignore return.void (function is used in multiple contexts and needs to return data if called directly) */
		add_action( 'edited_term', [ $this, 'update_embeddings_for_term' ] );
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
		remove_filter( 'classifai_feature_classification_get_default_settings', [ $this, 'modify_default_feature_settings' ], 10 );

		if ( $feature_instance->get_settings( 'provider' ) !== static::ID ) {
			return $settings;
		}

		add_filter( 'classifai_feature_classification_get_default_settings', [ $this, 'modify_default_feature_settings' ], 10, 2 );

		$defaults = [];

		foreach ( array_keys( $feature_instance->get_supported_taxonomies() ) as $tax ) {
			$enabled = 'category' === $tax ? true : false;

			$defaults[ $tax ]                = $enabled;
			$defaults[ $tax . '_threshold' ] = 75;
			$defaults[ $tax . '_taxonomy' ]  = $tax;
		}

		return array_merge( $settings, $defaults );
	}

	/**
	 * Sanitize the settings for this provider.
	 *
	 * @param array $new_settings The settings array.
	 * @return array
	 */
	public function sanitize_settings( array $new_settings ): array {
		$new_settings = parent::sanitize_settings( $new_settings );

		// Trigger embedding generation for all terms in enabled taxonomies if the feature is on.
		if (
			Classification::ID === $this->feature_instance::ID &&
			$new_settings[ static::ID ]['authenticated'] &&
			isset( $new_settings['status'] ) &&
			1 === (int) $new_settings['status']
		) {
			foreach ( array_keys( $this->nlu_features ) as $feature_name ) {
				if ( isset( $new_settings[ $feature_name ] ) && 1 === (int) $new_settings[ $feature_name ] ) {
					$this->trigger_taxonomy_update( $feature_name );
				}
			}
		}

		// Hide the update notice. This ensures we don't show this for new users.
		update_option( 'classifai_hide_embeddings_notice', true, false );

		return $new_settings;
	}

	/**
	 * Generate an embedding for a particular piece of text.
	 *
	 * @param string       $text Text to generate the embedding for.
	 * @param Feature|null $feature Feature instance.
	 * @return array|boolean|WP_Error
	 */
	public function generate_embedding( string $text = '', $feature = null ) {
		if ( ! $feature ) {
			$feature = new Classification();
		}

		// Ensure we have a valid Feature instance.
		$backup_feature_instance = $this->feature_instance;
		$this->feature_instance  = $feature;

		$settings = $feature->get_settings();

		// Ensure the feature is enabled.
		if ( ! $feature->is_feature_enabled() ) {
			return new WP_Error( 'not_enabled', esc_html__( 'Classification is disabled or Ollama connection failed. Please check your settings.', 'classifai' ) );
		}

		$request = new APIRequest( '', $this->feature_instance::ID, $this );

		/**
		 * Filter the request body before sending to Ollama.
		 *
		 * @since 3.3.0
		 * @hook classifai_ollama_embeddings_request_body
		 *
		 * @param array  $body Request body that will be sent to Ollama.
		 * @param string $text Text we are getting embeddings for.
		 *
		 * @return array Request body.
		 */
		$body = apply_filters(
			'classifai_ollama_embeddings_request_body',
			[
				'model'      => $settings[ static::ID ]['model'] ?? '',
				'input'      => $text,
				'dimensions' => $this->get_dimensions( $settings[ static::ID ]['model'] ?? '' ),
			],
			$text
		);

		// Make our API request.
		$response = $request->post(
			$this->get_api_embeddings_url( $settings[ static::ID ]['endpoint_url'] ?? '' ),
			[
				'body' => wp_json_encode( $body ),
			]
		);

		// Restore the existing Feature instance.
		$this->feature_instance = $backup_feature_instance;

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( empty( $response['embeddings'] ) ) {
			return new WP_Error( 'no_data', esc_html__( 'No data returned from Ollama.', 'classifai' ) );
		}

		$return = [];

		// Parse out the embeddings response.
		foreach ( $response['embeddings'] as $embedding ) {
			if ( ! is_array( $embedding ) ) {
				continue;
			}

			$return = $embedding;

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

		// Ensure we have a valid Feature instance.
		$backup_feature_instance = $this->feature_instance;
		$this->feature_instance  = $feature;

		$settings = $feature->get_settings();

		// Ensure the feature is enabled.
		if ( ! $feature->is_feature_enabled() ) {
			return new WP_Error( 'not_enabled', esc_html__( 'Classification is disabled or Ollama connection failed. Please check your settings.', 'classifai' ) );
		}

		$request = new APIRequest( '', $this->feature_instance::ID, $this );

		/**
		 * Filter the request body before sending to Ollama.
		 *
		 * @since 3.3.0
		 * @hook classifai_ollama_embeddings_request_body
		 *
		 * @param array $body Request body that will be sent to Ollama.
		 * @param array $strings Array of text we are getting embeddings for.
		 *
		 * @return array Request body.
		 */
		$body = apply_filters(
			'classifai_ollama_embeddings_request_body',
			[
				'model'      => $settings[ static::ID ]['model'] ?? '',
				'input'      => $strings,
				'dimensions' => $this->get_dimensions( $settings[ static::ID ]['model'] ?? '' ),
			],
			$strings
		);

		// Make our API request.
		$response = $request->post(
			$this->get_api_embeddings_url( $settings[ static::ID ]['endpoint_url'] ?? '' ),
			[
				'body' => wp_json_encode( $body ),
			]
		);

		// Restore the existing Feature instance.
		$this->feature_instance = $backup_feature_instance;

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( empty( $response['embeddings'] ) ) {
			return new WP_Error( 'no_data', esc_html__( 'No data returned from Ollama.', 'classifai' ) );
		}

		$return = [];

		// Parse out the embeddings response.
		foreach ( $response['embeddings'] as $embedding ) {
			if ( ! is_array( $embedding ) ) {
				continue;
			}

			$return[] = $embedding;
		}

		return $return;
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
