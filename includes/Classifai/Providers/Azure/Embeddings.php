<?php
/**
 * Azure OpenAI Embeddings integration
 */

namespace Classifai\Providers\Azure;

use Classifai\Providers\OpenAI\EmbeddingCalculations;
use Classifai\Providers\OpenAI\Tokenizer;
use Classifai\Normalizer;
use Classifai\Features\Classification;
use Classifai\Features\Feature;
use Classifai\EmbeddingsScheduler;
use WP_Error;

class Embeddings extends OpenAI {
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

		if (
			$this->feature_instance &&
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
		 * @param {int} $dimensions The default dimensions.
		 *
		 * @return {int} The dimensions.
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
		 * @param {int} $model The default maximum tokens.
		 *
		 * @return {int} The maximum tokens.
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
		 * @param {int} $terms The default maximum terms.
		 *
		 * @return {int} The maximum terms.
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
	protected function prep_api_url( Feature $feature = null ): string {
		$settings   = $feature->get_settings( static::ID );
		$endpoint   = $settings['endpoint_url'] ?? '';
		$deployment = $settings['deployment'] ?? '';

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
	 * @param string $url Endpoint URL.
	 * @param string $api_key Api Key.
	 * @param string $deployment Deployment name.
	 * @return bool|WP_Error
	 */
	protected function authenticate_credentials( string $url, string $api_key, string $deployment ) {
		$rtn = false;

		// This does basically the same thing that prep_api_url does but when running authentication,
		// we don't have settings saved yet, which prep_api_url needs.
		$endpoint = trailingslashit( $url ) . str_replace( '{deployment-id}', $deployment, $this->embeddings_url );
		$endpoint = add_query_arg( 'api-version', $this->api_version, $endpoint );

		$request = wp_remote_post(
			$endpoint,
			[
				'headers' => [
					'api-key'      => $api_key,
					'Content-Type' => 'application/json',
				],
				'body'    => wp_json_encode(
					[
						'input'      => 'This is a test',
						'dimensions' => $this->get_dimensions(),
					]
				),
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
	 * Get the threshold for the similarity calculation.
	 *
	 * @param string $taxonomy Taxonomy slug.
	 * @return float
	 */
	public function get_threshold( string $taxonomy = '' ): float {
		$settings  = ( new Classification() )->get_settings();
		$threshold = 1;

		if ( ! empty( $taxonomy ) ) {
			$threshold = isset( $settings[ $taxonomy . '_threshold' ] ) ? $settings[ $taxonomy . '_threshold' ] : 75;
		}

		// Convert $threshold (%) to decimal.
		$threshold = 1 - ( (float) $threshold / 100 );

		/**
		 * Filter the threshold for the similarity calculation.
		 *
		 * @since 2.5.0
		 * @hook classifai_threshold
		 *
		 * @param {float} $threshold The threshold to use.
		 * @param {string} $taxonomy The taxonomy to get the threshold for.
		 *
		 * @return {float} The threshold to use.
		 */
		return apply_filters( 'classifai_threshold', $threshold, $taxonomy );
	}

	/**
	 * Get the data to preview terms.
	 */
	public function get_post_classifier_embeddings_preview_data() {
		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : false;

		if ( ! $nonce || ! wp_verify_nonce( $nonce, 'classifai-previewer-action' ) ) {
			wp_send_json_error( esc_html__( 'Failed nonce check.', 'classifai' ) );
		}

		$post_id = filter_input( INPUT_POST, 'post_id', FILTER_SANITIZE_NUMBER_INT );

		$embeddings       = $this->generate_embeddings_for_post( $post_id, true );
		$embeddings_terms = [];

		// Add terms to this item based on embedding data.
		if ( $embeddings && ! is_wp_error( $embeddings ) ) {
			$embeddings_terms = $this->get_terms( $embeddings );

			if ( is_wp_error( $embeddings_terms ) ) {
				wp_send_json_error( $embeddings_terms->get_error_message() );
			}
		}

		wp_send_json_success( $embeddings_terms );
	}

	/**
	 * Trigger embedding generation for content being saved.
	 *
	 * @param int  $post_id ID of post being saved.
	 * @param bool $force Whether to force generation of embeddings even if they already exist. Default false.
	 * @return array|WP_Error
	 */
	public function generate_embeddings_for_post( int $post_id, bool $force = false ) {
		// Don't run on autosaves.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return new WP_Error( 'invalid', esc_html__( 'Classification will not work during an autosave.', 'classifai' ) );
		}

		// Ensure the user has permissions to edit.
		if ( ! current_user_can( 'edit_post', $post_id ) && ( ! defined( 'WP_CLI' ) || ! WP_CLI ) ) {
			return new WP_Error( 'invalid', esc_html__( 'User does not have permission to classify this item.', 'classifai' ) );
		}

		/**
		 * Filter whether ClassifAI should classify an item.
		 *
		 * Default is true, return false to skip classifying.
		 *
		 * @since 3.1.0
		 * @hook classifai_azure_openai_embeddings_should_classify
		 *
		 * @param {bool}   $should_classify Whether the item should be classified. Default `true`, return `false` to skip.
		 * @param {int}    $id   The ID of the item to be considered for classification.
		 * @param {string} $type The type of item to be considered for classification.
		 *
		 * @return {bool} Whether the item should be classified.
		 */
		if ( ! apply_filters( 'classifai_azure_openai_embeddings_should_classify', true, $post_id, 'post' ) ) {
			return new WP_Error( 'invalid', esc_html__( 'Classification is disabled for this item.', 'classifai' ) );
		}

		// Try to use the stored embeddings first.
		if ( ! $force ) {
			$embeddings = get_post_meta( $post_id, 'classifai_azure_openai_embeddings', true );

			if ( ! empty( $embeddings ) ) {
				return $embeddings;
			}
		}

		// Chunk the post content down.
		$embeddings     = [];
		$content        = $this->get_normalized_content( $post_id, 'post' );
		$content_chunks = $this->chunk_content( $content );

		// Get the embeddings for each chunk.
		if ( ! empty( $content_chunks ) ) {
			$tokenizer    = new Tokenizer( $this->get_max_tokens() );
			$total_tokens = $tokenizer->tokens_in_content( $content );

			// If we have a lot of tokens, we need to get embeddings for each chunk individually.
			if ( $this->max_tokens < $total_tokens ) {
				foreach ( $content_chunks as $chunk ) {
					$embedding = $this->generate_embedding( $chunk );

					if ( $embedding && ! is_wp_error( $embedding ) ) {
						$embeddings[] = array_map( 'floatval', $embedding );
					}
				}
			} else {
				// Otherwise let's get all embeddings in a single request.
				$all_embeddings = $this->generate_embeddings( $content_chunks );

				if ( $all_embeddings && ! is_wp_error( $all_embeddings ) ) {
					$embeddings = array_map(
						function ( $embedding ) {
							return array_map( 'floatval', $embedding );
						},
						$all_embeddings
					);
				}
			}
		}

		// Store the embeddings for future use.
		if ( ! empty( $embeddings ) ) {
			update_post_meta( $post_id, 'classifai_azure_openai_embeddings', $embeddings );
		}

		return $embeddings;
	}

	/**
	 * Add terms to a post based on embeddings.
	 *
	 * @param int   $post_id ID of post to set terms on.
	 * @param array $embeddings Embeddings data.
	 * @param bool  $link Whether to link the terms or not.
	 * @return array|WP_Error
	 */
	public function set_terms( int $post_id = 0, array $embeddings = [], bool $link = true ) {
		if ( ! $post_id || ! get_post( $post_id ) ) {
			return new WP_Error( 'post_id_required', esc_html__( 'A valid post ID is required to set terms.', 'classifai' ) );
		}

		if ( empty( $embeddings ) ) {
			return new WP_Error( 'data_required', esc_html__( 'Valid embedding data is required to set terms.', 'classifai' ) );
		}

		$embeddings_similarity = [];

		// Iterate through all of our embedding chunks and run our similarity calculations.
		foreach ( $embeddings as $embedding ) {
			$embeddings_similarity = array_merge( $embeddings_similarity, $this->get_embeddings_similarity( $embedding ) );
		}

		// Ensure we have some results.
		if ( empty( $embeddings_similarity ) ) {
			return new WP_Error( 'invalid', esc_html__( 'No matching terms found.', 'classifai' ) );
		}

		// Sort the results by similarity.
		usort(
			$embeddings_similarity,
			function ( $a, $b ) {
				return $a['similarity'] <=> $b['similarity'];
			}
		);

		// Remove duplicates based on the term_id field.
		$uniques               = array_unique( array_column( $embeddings_similarity, 'term_id' ) );
		$embeddings_similarity = array_intersect_key( $embeddings_similarity, $uniques );

		$sorted_results = [];

		// Sort the results into taxonomy buckets.
		foreach ( $embeddings_similarity as $item ) {
			$sorted_results[ $item['taxonomy'] ][] = $item;
		}

		$return = [];

		/**
		 * If $link is true, immediately link all the terms
		 * to the item.
		 *
		 * If it is false, build an array of term data that
		 * can be used to display the terms in the UI.
		 */
		foreach ( $sorted_results as $tax => $terms ) {
			if ( $link ) {
				wp_set_object_terms( $post_id, array_map( 'absint', array_column( $terms, 'term_id' ) ), $tax, false );
			} else {
				$terms_to_link = [];

				foreach ( $terms as $term ) {
					$found_term = get_term( $term['term_id'] );

					if ( $found_term && ! is_wp_error( $found_term ) ) {
						$terms_to_link[ $found_term->name ] = $term['term_id'];
					}
				}

				$return[ $tax ] = $terms_to_link;
			}
		}

		return empty( $return ) ? $embeddings_similarity : $return;
	}

	/**
	 * Determine which terms best match a post based on embeddings.
	 *
	 * @param array $embeddings An array of embeddings data.
	 * @return array|WP_Error
	 */
	public function get_terms( array $embeddings = [] ) {
		if ( empty( $embeddings ) ) {
			return new WP_Error( 'data_required', esc_html__( 'Valid embedding data is required to get terms.', 'classifai' ) );
		}

		$embeddings_similarity = [];

		// Iterate through all of our embedding chunks and run our similarity calculations.
		foreach ( $embeddings as $embedding ) {
			$embeddings_similarity = array_merge( $embeddings_similarity, $this->get_embeddings_similarity( $embedding, false ) );
		}

		// Ensure we have some results.
		if ( empty( $embeddings_similarity ) ) {
			return new WP_Error( 'invalid', esc_html__( 'No matching terms found.', 'classifai' ) );
		}

		// Sort the results by similarity.
		usort(
			$embeddings_similarity,
			function ( $a, $b ) {
				return $a['similarity'] <=> $b['similarity'];
			}
		);

		// Remove duplicates based on the term_id field.
		$uniques               = array_unique( array_column( $embeddings_similarity, 'term_id' ) );
		$embeddings_similarity = array_intersect_key( $embeddings_similarity, $uniques );

		$sorted_results = [];

		// Sort the results into taxonomy buckets.
		foreach ( $embeddings_similarity as $item ) {
			$sorted_results[ $item['taxonomy'] ][] = $item;
		}

		// Prepare the results.
		$results = [];

		foreach ( $sorted_results as $tax => $terms ) {
			// Get the taxonomy name.
			$taxonomy = get_taxonomy( $tax );
			$tax_name = $taxonomy->labels->singular_name;

			// Initialize the taxonomy bucket in results.
			$results[ $tax ] = [
				'label' => $tax_name,
				'data'  => [],
			];

			foreach ( $terms as $term ) {
				// Convert $similarity to percentage.
				$similarity = round( ( 1 - $term['similarity'] ), 10 );

				// Store the results.
				$results[ $tax ]['data'][] = [
					'label' => get_term( $term['term_id'] )->name,
					'score' => $similarity,
				];
			}
		}

		return $results;
	}

	/**
	 * Get the similarity between an embedding and all terms.
	 *
	 * @param array $embedding Embedding data.
	 * @param bool  $consider_threshold Whether to consider the threshold setting.
	 * @return array
	 */
	private function get_embeddings_similarity( array $embedding, bool $consider_threshold = true ): array {
		$feature              = new Classification();
		$embedding_similarity = [];
		$taxonomies           = $feature->get_all_feature_taxonomies();
		$calculations         = new EmbeddingCalculations();

		foreach ( $taxonomies as $tax ) {
			$exclude = [];

			if ( is_numeric( $tax ) ) {
				continue;
			}

			if ( 'tags' === $tax ) {
				$tax = 'post_tag';
			}

			if ( 'categories' === $tax ) {
				$tax = 'category';

				// Exclude the uncategorized term.
				$uncat_term = get_term_by( 'name', 'Uncategorized', 'category' );
				if ( $uncat_term ) {
					$exclude = [ $uncat_term->term_id ];
				}
			}

			$terms = get_terms(
				[
					'taxonomy'   => $tax,
					'orderby'    => 'count',
					'order'      => 'DESC',
					'hide_empty' => false,
					'fields'     => 'ids',
					'meta_key'   => 'classifai_azure_openai_embeddings', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
					'number'     => $this->get_max_terms(),
					'exclude'    => $exclude, // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude
				]
			);

			if ( is_wp_error( $terms ) || empty( $terms ) ) {
				continue;
			}

			// Get threshold setting for this taxonomy.
			$threshold = $this->get_threshold( $tax );

			// Get embedding similarity for each term.
			foreach ( $terms as $term_id ) {
				if ( ! current_user_can( 'assign_term', $term_id ) && ( ! defined( 'WP_CLI' ) || ! WP_CLI ) ) {
					continue;
				}

				$term_embedding = get_term_meta( $term_id, 'classifai_azure_openai_embeddings', true );

				if ( ! empty( $term_embedding ) ) {
					// Loop through the chunks and run a similarity calculation on each.
					foreach ( $term_embedding as $chunk ) {
						$similarity = $calculations->cosine_similarity( $embedding, $chunk );

						if ( false !== $similarity && ( ! $consider_threshold || $similarity <= $threshold ) ) {
							$embedding_similarity[] = [
								'taxonomy'   => $tax,
								'term_id'    => $term_id,
								'similarity' => $similarity,
							];
						}
					}
				}
			}
		}

		return $embedding_similarity;
	}

	/**
	 * Schedules the job to generate embedding data for all terms within a taxonomy.
	 *
	 * @param string $taxonomy Taxonomy slug.
	 * @param bool   $all Whether to generate embeddings for all terms or just those without embeddings.
	 * @param array  $args     Overrideable query args for get_terms()
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
		 * @param {int} $number Number of terms to process per job.
		 *
		 * @return {int} Filtered number of terms to process per job.
		 */
		$number = apply_filters( 'classifai_azure_openai_embeddings_terms_per_job', 100 );

		$default_args = [
			'taxonomy'     => $taxonomy,
			'orderby'      => 'count',
			'order'        => 'DESC',
			'hide_empty'   => false,
			'fields'       => 'ids',
			'meta_key'     => 'classifai_azure_openai_embeddings', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
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
	 * @param array  $args     Overrideable query args for get_terms()
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
	 * Trigger embedding generation for term being saved.
	 *
	 * @param int     $term_id ID of term being saved.
	 * @param bool    $force   Whether to force generation of embeddings even if they already exist. Default false.
	 * @param Feature $feature The feature instance.
	 * @return array|WP_Error
	 */
	public function generate_embeddings_for_term( int $term_id, bool $force = false, Feature $feature = null ) {
		// Ensure the user has permissions to edit.
		if ( ! current_user_can( 'edit_term', $term_id ) ) {
			return new WP_Error( 'invalid', esc_html__( 'User does not have valid permissions to edit this term.', 'classifai' ) );
		}

		$term = get_term( $term_id );

		if ( ! is_a( $term, '\WP_Term' ) ) {
			return new WP_Error( 'invalid', esc_html__( 'This is not a valid term.', 'classifai' ) );
		}

		if ( ! $feature ) {
			$feature = new Classification();
		}

		$taxonomies = $feature->get_all_feature_taxonomies();

		if ( in_array( 'tags', $taxonomies, true ) ) {
			$taxonomies[] = 'post_tag';
		}

		if ( in_array( 'categories', $taxonomies, true ) ) {
			$taxonomies[] = 'category';
		}

		// Ensure this term is part of a taxonomy we support.
		if ( ! in_array( $term->taxonomy, $taxonomies, true ) ) {
			return new WP_Error( 'invalid', esc_html__( 'This taxonomy is not supported.', 'classifai' ) );
		}

		/**
		 * Filter whether ClassifAI should classify an item.
		 *
		 * Default is true, return false to skip classifying.
		 *
		 * @since 3.1.0
		 * @hook classifai_azure_openai_embeddings_should_classify
		 *
		 * @param {bool}   $should_classify Whether the item should be classified. Default `true`, return `false` to skip.
		 * @param {int}    $id   The ID of the item to be considered for classification.
		 * @param {string} $type The type of item to be considered for classification.
		 *
		 * @return {bool} Whether the item should be classified.
		 */
		if ( ! apply_filters( 'classifai_azure_openai_embeddings_should_classify', true, $term_id, 'term' ) ) {
			return new WP_Error( 'invalid', esc_html__( 'Classification is disabled for this item.', 'classifai' ) );
		}

		// Try to use the stored embeddings first.
		$embeddings = get_term_meta( $term_id, 'classifai_azure_openai_embeddings', true );

		if ( ! empty( $embeddings ) && ! $force ) {
			return $embeddings;
		}

		// Chunk the term content down.
		$embeddings     = [];
		$content        = $this->get_normalized_content( $term_id, 'term' );
		$content_chunks = $this->chunk_content( $content );

		// Get the embeddings for each chunk.
		if ( ! empty( $content_chunks ) ) {
			foreach ( $content_chunks as $chunk ) {
				$embedding = $this->generate_embedding( $chunk, $feature );

				if ( $embedding && ! is_wp_error( $embedding ) ) {
					$embeddings[] = array_map( 'floatval', $embedding );
				}
			}
		}

		// Store the embeddings for future use.
		if ( ! empty( $embeddings ) ) {
			update_term_meta( $term_id, 'classifai_azure_openai_embeddings', $embeddings );
		}

		return $embeddings;
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

		$settings = $feature->get_settings();

		// Ensure the feature is enabled.
		if ( ! $feature->is_feature_enabled() ) {
			return new WP_Error( 'not_enabled', esc_html__( 'Classification is disabled or OpenAI authentication failed. Please check your settings.', 'classifai' ) );
		}

		/**
		 * Filter the request body before sending to OpenAI.
		 *
		 * @since 3.1.0
		 * @hook classifai_azure_openai_embeddings_request_body
		 *
		 * @param {array} $body Request body that will be sent to OpenAI.
		 * @param {string} $text Text we are getting embeddings for.
		 *
		 * @return {array} Request body.
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
		$response = wp_remote_post(
			$this->prep_api_url( $feature ),
			[
				'headers' => [
					'api-key'      => $settings[ static::ID ]['api_key'],
					'Content-Type' => 'application/json',
				],
				'body'    => wp_json_encode( $body ),
				'timeout' => 60, // phpcs:ignore WordPressVIPMinimum.Performance.RemoteRequestTimeout.timeout_timeout
			]
		);
		$response = $this->get_result( $response );

		set_transient( 'classifai_azure_openai_embeddings_latest_response', $response, DAY_IN_SECONDS * 30 );

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

		$settings = $feature->get_settings();

		// Ensure the feature is enabled.
		if ( ! $feature->is_feature_enabled() ) {
			return new WP_Error( 'not_enabled', esc_html__( 'Classification is disabled or OpenAI authentication failed. Please check your settings.', 'classifai' ) );
		}

		/**
		 * Filter the request body before sending to OpenAI.
		 *
		 * @since 3.1.0
		 * @hook classifai_azure_openai_embeddings_request_body
		 *
		 * @param {array} $body Request body that will be sent to OpenAI.
		 * @param {array} $strings Array of text we are getting embeddings for.
		 *
		 * @return {array} Request body.
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
		$response = wp_remote_post(
			$this->prep_api_url( $feature ),
			[
				'headers' => [
					'api-key'      => $settings[ static::ID ]['api_key'],
					'Content-Type' => 'application/json',
				],
				'body'    => wp_json_encode( $body ),
				'timeout' => 60, // phpcs:ignore WordPressVIPMinimum.Performance.RemoteRequestTimeout.timeout_timeout
			]
		);
		$response = $this->get_result( $response );

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
	 * Chunk content into smaller pieces with an overlap.
	 *
	 * @param string $content Content to chunk.
	 * @param int    $chunk_size Size of each chunk, in words.
	 * @param int    $overlap_size Overlap size for each chunk, in words.
	 * @return array
	 */
	public function chunk_content( string $content = '', int $chunk_size = 150, $overlap_size = 25 ): array {
		// Remove multiple whitespaces.
		$content = preg_replace( '/\s+/', ' ', $content );

		// Split text by single whitespace.
		$words = explode( ' ', $content );

		$chunks     = [];
		$text_count = count( $words );

		// Iterate through and chunk data with an overlap.
		for ( $i = 0; $i < $text_count; $i += $chunk_size ) {
			// Join a set of words into a string.
			$chunk = implode(
				' ',
				array_slice(
					$words,
					max( $i - $overlap_size, 0 ),
					$chunk_size + $overlap_size
				)
			);

			array_push( $chunks, $chunk );
		}

		return $chunks;
	}

	/**
	 * Get our content, ensuring it is normalized.
	 *
	 * @param int    $id ID of item to get content from.
	 * @param string $type Type of content. Default 'post'.
	 * @return string
	 */
	public function get_normalized_content( int $id = 0, string $type = 'post' ): string {
		$normalizer = new Normalizer();
		$content    = '';

		// Get the content depending on the type.
		switch ( $type ) {
			case 'post':
				// This will include the post_title and post_content.
				$content = $normalizer->normalize( $id );
				break;
			case 'term':
				$content = '';
				$term    = get_term( $id );

				if ( is_a( $term, '\WP_Term' ) ) {
					$content = $term->name . ' ' . $term->slug . ' ' . $term->description;
				}

				break;
		}

		/**
		 * Filter content that will get sent to OpenAI.
		 *
		 * @since 3.1.0
		 * @hook classifai_azure_openai_embeddings_content
		 *
		 * @param {string} $content Content that will be sent to OpenAI.
		 * @param {int} $post_id ID of post we are submitting.
		 * @param {string} $type Type of content.
		 *
		 * @return {string} Content.
		 */
		return apply_filters( 'classifai_azure_openai_embeddings_content', $content, $id, $type );
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
				$debug_info[ "Taxonomy ($tax threshold)" ] = absint( $settings[ $tax . '_threshold' ] );
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
