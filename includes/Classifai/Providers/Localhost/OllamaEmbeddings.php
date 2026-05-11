<?php
/**
 * Ollama Embeddings integration
 */

namespace Classifai\Providers\Localhost;

use Classifai\Admin\Notifications;
use Classifai\Features\Classification;
use Classifai\Providers\Embeddings\EmbeddingsProviderTrait;
use Classifai\Providers\OpenAI\APIRequest;
use Classifai\Features\Feature;
use Classifai\EmbeddingsScheduler;
use WP_Error;

use function Classifai\should_use_legacy_settings_panel;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Ollama Embeddings class
 */
class OllamaEmbeddings extends Ollama {

	use EmbeddingsProviderTrait;

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
	 * Ollama Embeddings constructor.
	 *
	 * @param \Classifai\Features\Feature $feature_instance The feature instance.
	 */
	public function __construct( $feature_instance = null ) {
		parent::__construct( $feature_instance );
		$this->populate_nlu_features_from_supported_taxonomies( true );
	}

	/**
	 * @param Feature|null $feature Feature context.
	 * @return int
	 */
	protected function get_max_tokens_for_embedding_chunking( ?Feature $feature = null ): int {
		$feature  = $feature ?? new Classification();
		$settings = $feature->get_settings( static::ID );

		return (int) $this->get_max_tokens( $settings['model'] ?? '' );
	}

	/**
	 * Get the number of dimensions for the embeddings.
	 *
	 * @param string $model The model to use.
	 * @return int
	 */
	public function get_dimensions( string $model = '' ): int {
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
	 * @param string $model The model to use.
	 * @return int
	 */
	public function get_max_tokens( string $model = '' ): int {
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
		add_action( 'classifai_schedule_generate_embedding_job', [ $this, 'generate_embedding_job' ], 10, 4 );

		if (
			( $this->feature_instance && Classification::ID !== $this->feature_instance::ID ) ||
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
	 * Regenerate embeddings.
	 *
	 * This will regenerate embeddings for all terms
	 * and delete existing post embeddings. Useful to run
	 * anytime the model or dimensions are changed.
	 */
	public function regenerate_embeddings() {
		$feature  = new Classification();
		$settings = $feature->get_settings();

		if (
			! $feature->is_feature_enabled() ||
			$feature->get_feature_provider_instance()::ID !== static::ID
		) {
			return;
		}

		// Regenerate embeddings for all terms.
		foreach ( array_keys( $this->nlu_features ) as $feature_name ) {
			if ( isset( $settings[ $feature_name ] ) && 1 === (int) $settings[ $feature_name ] ) {
				$this->trigger_taxonomy_update( $feature_name, true );
			}
		}

		$meta_key        = $this->get_embeddings_meta_key();
		$embedding_posts = get_posts(
			[
				'post_type'      => 'any',
				'posts_per_page' => -1, // phpcs:ignore WordPress.WP.PostsPerPageNoUnlimited.posts_per_page_posts_per_page
				'fields'         => 'ids',
				'meta_key'       => $meta_key, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_compare'   => 'EXISTS',
			]
		);

		foreach ( $embedding_posts as $post_id ) {
			delete_post_meta( $post_id, $meta_key );
		}

		// Hide the admin notice.
		update_option( 'classifai_hide_embeddings_notice', true, false );

		// Set a notice to let the user know the embeddings have been regenerated.
		$notifications = new Notifications();
		$notifications->set_notice(
			esc_html__( 'Embeddings have been regenerated.', 'classifai' ),
			'success',
		);

		// Redirect to the same page but remove the nonce so we don't run this again.
		$redirect_url = admin_url( 'tools.php?page=classifai#/language_processing/feature_classification' );
		if ( should_use_legacy_settings_panel() ) {
			$redirect_url = admin_url( 'tools.php?page=classifai&tab=language_processing&feature=feature_classification' );
		}
		wp_safe_redirect( $redirect_url );
		exit;
	}

	/**
	 * Schedules the job to generate embedding data for all terms within a taxonomy.
	 *
	 * @param string $taxonomy Taxonomy slug.
	 * @param bool   $all      Whether to generate embeddings for all terms or just those without embeddings.
	 * @param array  $args     Overridable query args for get_terms()
	 * @param int    $user_id  The user ID to run this as.
	 */
	public function trigger_taxonomy_update( string $taxonomy = '', bool $all = false, array $args = [], int $user_id = 0 ) {
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
		 * @since 3.3.0
		 * @hook classifai_ollama_embeddings_terms_per_job
		 *
		 * @param int $number Number of terms to process per job.
		 *
		 * @return int Filtered number of terms to process per job.
		 */
		$number = apply_filters( 'classifai_ollama_embeddings_terms_per_job', 100 );

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
		if ( function_exists( 'as_has_scheduled_action' ) && ! \as_has_scheduled_action( 'classifai_schedule_generate_embedding_job', $job_args ) ) {
			$terms = get_terms( $default_args );

			if ( is_wp_error( $terms ) || empty( $terms ) ) {
				return;
			}
		}

		if ( function_exists( 'as_enqueue_async_action' ) ) {
			\as_enqueue_async_action( 'classifai_schedule_generate_embedding_job', $job_args );
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
	 * Common entry point for all REST endpoints for this provider.
	 *
	 * @param int    $post_id       The post ID we're processing.
	 * @param string $route_to_call The name of the route we're going to be processing.
	 * @param array  $args          Optional arguments to pass to the route.
	 * @return array|string|WP_Error
	 */
	public function rest_endpoint_callback( $post_id, string $route_to_call = '', array $args = [] ) {
		if ( ! $post_id || ! get_post( $post_id ) ) {
			return new WP_Error( 'post_id_required', esc_html__( 'A valid post ID is required.', 'classifai' ) );
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
	 * Get embeddings generation status.
	 *
	 * @return bool
	 */
	public function is_embeddings_generation_in_progress(): bool {
		return self::$scheduler_instance->is_embeddings_generation_in_progress();
	}
}
