<?php
/**
 * OpenAI Embeddings integration
 */

namespace Classifai\Providers\OpenAI;

use Classifai\Embeddings\HandlesEmbeddingsLifecycle;
use Classifai\Embeddings\HasEmbeddingsStorage;
use Classifai\Providers\Provider;
use Classifai\Providers\OpenAI\APIRequest;
use Classifai\Features\Classification;
use Classifai\Features\RecommendedContent;
use Classifai\Features\Feature;
use Classifai\EmbeddingsScheduler;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Embeddings extends Provider {

	use \Classifai\Providers\OpenAI\OpenAI;
	use HasEmbeddingsStorage;
	use HandlesEmbeddingsLifecycle;

	const ID = 'openai_embeddings';

	/**
	 * OpenAI Embeddings URL.
	 *
	 * @var string
	 */
	protected $api_url = 'https://api.openai.com/v1/embeddings';

	/**
	 * OpenAI Embeddings model.
	 *
	 * @var string
	 */
	protected $model = 'text-embedding-3-small';

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
	 * Maximum number of posts we process.
	 *
	 * @var int
	 */
	protected $max_posts = 5000;

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
		return 'classifai_openai_embeddings';
	}

	/**
	 * Filter prefix used to build per-provider hook/filter names.
	 *
	 * @return string
	 */
	protected function embeddings_filter_prefix(): string {
		return 'classifai_openai_embeddings';
	}

	/**
	 * Action Scheduler action that processes term-embedding batches.
	 *
	 * @return string
	 */
	protected function embeddings_term_job_action(): string {
		return 'classifai_generate_term_embedding_job';
	}

	/**
	 * Action Scheduler action that processes post-embedding batches.
	 *
	 * @return string
	 */
	protected function embeddings_post_job_action(): string {
		return 'classifai_generate_post_embedding_job';
	}

	/**
	 * OpenAI Embeddings constructor.
	 *
	 * @param \Classifai\Features\Feature $feature_instance The feature instance.
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
	 * Get the API URL.
	 *
	 * @return string
	 */
	public function get_api_url(): string {
		/**
		 * Filter the API URL.
		 *
		 * @since 3.1.0
		 * @hook classifai_openai_embeddings_api_url
		 *
		 * @param string $url The default API URL.
		 *
		 * @return string The API URL.
		 */
		return apply_filters( 'classifai_openai_embeddings_api_url', $this->api_url );
	}

	/**
	 * Get the model name.
	 *
	 * @return string
	 */
	public function get_model(): string {
		/**
		 * Filter the model name.
		 *
		 * Useful if you want to use a different model, like
		 * text-embedding-3-large.
		 *
		 * @since 3.1.0
		 * @hook classifai_openai_embeddings_model
		 *
		 * @param string $model The default model to use.
		 *
		 * @return string The model to use.
		 */
		return apply_filters( 'classifai_openai_embeddings_model', $this->model );
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
		 * @hook classifai_openai_embeddings_dimensions
		 *
		 * @param int $dimensions The default dimensions.
		 *
		 * @return int  The dimensions.
		 */
		return apply_filters( 'classifai_openai_embeddings_dimensions', $this->dimensions );
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
		 * @hook classifai_openai_embeddings_max_tokens
		 *
		 * @param int $max_tokens The default maximum tokens.
		 *
		 * @return int  The maximum tokens.
		 */
		return apply_filters( 'classifai_openai_embeddings_max_tokens', $this->max_tokens );
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
		 * @hook classifai_openai_embeddings_max_terms
		 *
		 * @param int $max_terms The default maximum terms.
		 *
		 * @return int  The maximum terms.
		 */
		return (int) apply_filters( 'classifai_openai_embeddings_max_terms', $this->max_terms );
	}

	/**
	 * Get the maximum number of posts we process.
	 *
	 * @return int
	 */
	public function get_max_posts(): int {
		/**
		 * Filter the max number of posts.
		 *
		 * Default for this is 5000 but this filter can be used to change
		 * this, either decreasing to help with performance or increasing
		 * to ensure we consider more.
		 *
		 * @since 3.4.0
		 * @hook classifai_openai_embeddings_max_posts
		 *
		 * @param int $max_posts The default maximum posts.
		 *
		 * @return int The maximum posts.
		 */
		return (int) apply_filters( 'classifai_openai_embeddings_max_posts', $this->max_posts );
	}

	/**
	 * Render the provider fields.
	 */
	public function render_provider_fields() {
		$settings = $this->feature_instance->get_settings( static::ID );

		add_settings_field(
			static::ID . '_api_key',
			esc_html__( 'API Key', 'classifai' ),
			[ $this->feature_instance, 'render_input' ],
			$this->feature_instance->get_option_name(),
			$this->feature_instance->get_option_name() . '_section',
			[
				'option_index'  => static::ID,
				'label_for'     => 'api_key',
				'input_type'    => 'password',
				'default_value' => $settings['api_key'],
				'class'         => 'classifai-provider-field hidden provider-scope-' . static::ID, // Important to add this.
				'description'   => $this->feature_instance->is_configured_with_provider( static::ID ) ?
					'' :
					sprintf(
						wp_kses(
							/* translators: %1$s is replaced with the OpenAI sign up URL */
							__( 'Don\'t have an OpenAI account yet? <a title="Sign up for an OpenAI account" href="%1$s">Sign up for one</a> in order to get your API key.', 'classifai' ),
							[
								'a' => [
									'href'  => [],
									'title' => [],
								],
							]
						),
						esc_url( 'https://platform.openai.com/signup' )
					),
			]
		);

		do_action( 'classifai_' . static::ID . '_render_provider_fields', $this );
	}

	/**
	 * Returns the default settings for this provider.
	 *
	 * @return array
	 */
	public function get_default_provider_settings(): array {
		$common_settings = [
			'api_key'       => '',
			'authenticated' => false,
		];

		switch ( $this->feature_instance::ID ) {
			case RecommendedContent::ID:
				return array_merge(
					$common_settings,
					[
						'embedding_threshold' => 75,
					]
				);
		}

		return $common_settings;
	}

	/**
	 * Register what we need for the provider.
	 */
	public function register() {
		// Listen for Action Scheduler callbacks.
		add_action( 'classifai_generate_term_embedding_job', [ $this, 'generate_term_embedding_job' ], 10, 4 );
		add_action( 'classifai_generate_post_embedding_job', [ $this, 'generate_post_embedding_job' ], 10, 4 );

		$classification_feature      = new Classification();
		$recommended_content_feature = new RecommendedContent();

		// Register things needed for the Classification Feature.
		if (
			$classification_feature->is_feature_enabled() &&
			$classification_feature->get_feature_provider_instance()::ID === static::ID
		) {
			// Create action scheduler job to generate embeddings for all terms.
			self::$scheduler_instance = new EmbeddingsScheduler(
				'classifai_generate_term_embedding_job',
				__( 'OpenAI Embeddings', 'classifai' )
			);

			self::$scheduler_instance->init();

			// Register hooks.
			add_action( 'created_term', [ $this, 'generate_embeddings_for_term' ] ); /** @phpstan-ignore return.void (function is used in multiple contexts and needs to return data if called directly) */
			add_action( 'edited_terms', [ $this, 'generate_embeddings_for_term' ] ); /** @phpstan-ignore return.void (function is used in multiple contexts and needs to return data if called directly) */
			add_action( 'wp_ajax_get_post_classifier_embeddings_preview_data', array( $this, 'get_post_classifier_embeddings_preview_data' ) );
			add_action( 'admin_post_classifai_regen_embeddings', [ $this, 'classifai_regen_embeddings' ] );
			add_filter( 'classifai_feature_classification_get_default_settings', [ $this, 'modify_default_classification_feature_settings' ], 10, 2 );
		}

		// Register things needed for the Recommended Content Feature.
		if (
			$recommended_content_feature->is_feature_enabled() &&
			$recommended_content_feature->get_feature_provider_instance()::ID === static::ID
		) {
			// Create action scheduler job to generate embeddings for all posts.
			self::$scheduler_instance = new EmbeddingsScheduler(
				'classifai_generate_post_embedding_job',
				__( 'OpenAI Embeddings', 'classifai' )
			);

			self::$scheduler_instance->init();

			// Register hooks.
			add_action( 'wp_insert_post', [ $this, 'maybe_generated_embeddings_for_post' ], 999 );
		}
	}

	/**
	 * Modify the default settings for the Classification Feature.
	 *
	 * @param array   $settings Current settings.
	 * @param Feature $feature_instance The feature instance.
	 * @return array
	 */
	public function modify_default_classification_feature_settings( array $settings, $feature_instance ): array {
		remove_filter( 'classifai_feature_classification_get_default_settings', [ $this, 'modify_default_classification_feature_settings' ], 10 );

		if ( $feature_instance->get_settings( 'provider' ) !== static::ID ) {
			return $settings;
		}

		add_filter( 'classifai_feature_classification_get_default_settings', [ $this, 'modify_default_classification_feature_settings' ], 10, 2 );

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
		$settings = $this->feature_instance->get_settings();

		$api_key_settings                            = $this->sanitize_api_key_settings( $new_settings, $settings );
		$new_settings[ static::ID ]['api_key']       = $api_key_settings[ static::ID ]['api_key'];
		$new_settings[ static::ID ]['authenticated'] = $api_key_settings[ static::ID ]['authenticated'];

		if ( isset( $new_settings[ static::ID ]['embedding_threshold'] ) ) {
			$new_settings[ static::ID ]['embedding_threshold'] = floatval( $new_settings[ static::ID ]['embedding_threshold'] );
		}

		if (
			$new_settings[ static::ID ]['authenticated'] &&
			isset( $new_settings['status'] ) &&
			1 === (int) $new_settings['status']
		) {
			// Trigger embedding generation for all terms in enabled taxonomies.
			if ( $this->feature_instance instanceof Classification ) {
				foreach ( array_keys( $this->nlu_features ) as $feature_name ) {
					if ( isset( $new_settings[ $feature_name ] ) && 1 === (int) $new_settings[ $feature_name ] ) {
						$this->trigger_taxonomy_update( $feature_name );
					}
				}
			}

			// Trigger embedding generation for all posts.
			if ( $this->feature_instance instanceof RecommendedContent ) {
				$this->trigger_post_update();
			}
		}

		// Hide the update notice. This ensures we don't show this for new users.
		update_option( 'classifai_hide_embeddings_notice', true, false );

		return $new_settings;
	}

	/**
	 * Regenerate embeddings.
	 */
	public function classifai_regen_embeddings() {
		if (
			! isset( $_GET['embeddings_nonce'] ) ||
			! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['embeddings_nonce'] ) ), 'regen_embeddings' )
		) {
			wp_die( esc_html__( 'You do not have permission to perform this operation.', 'classifai' ) );
		}

		$this->regenerate_embeddings();
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
			return new WP_Error( 'not_enabled', esc_html__( 'Embedding generation is disabled or OpenAI authentication failed. Please check your settings.', 'classifai' ) );
		}

		// Ensure we have a valid Feature instance.
		$backup_feature_instance = $this->feature_instance;
		$this->feature_instance  = $feature;

		$request = new APIRequest( '', $this->feature_instance::ID, $this );

		/**
		 * Filter the request body before sending to OpenAI.
		 *
		 * @since 2.2.0
		 * @hook classifai_openai_embeddings_request_body
		 *
		 * @param array  $body Request body that will be sent to OpenAI.
		 * @param string $text Text we are getting embeddings for.
		 *
		 * @return array Request body.
		 */
		$body = apply_filters(
			'classifai_openai_embeddings_request_body',
			[
				'model'      => $this->get_model(),
				'input'      => $text,
				'dimensions' => $this->get_dimensions(),
			],
			$text
		);

		// Make our API request.
		$response = $request->post(
			$this->get_api_url(),
			[
				'body' => wp_json_encode( $body ),
			]
		);

		set_transient( 'classifai_openai_embeddings_latest_response', $response, DAY_IN_SECONDS * 30 );

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
			return new WP_Error( 'not_enabled', esc_html__( 'Embedding generation is disabled or OpenAI authentication failed. Please check your settings.', 'classifai' ) );
		}

		// Ensure we have a valid Feature instance.
		$backup_feature_instance = $this->feature_instance;
		$this->feature_instance  = $feature;

		$request = new APIRequest( '', $this->feature_instance::ID, $this );

		/**
		 * Filter the request body before sending to OpenAI.
		 *
		 * @since 2.2.0
		 * @hook classifai_openai_embeddings_request_body
		 *
		 * @param array $body    Request body that will be sent to OpenAI.
		 * @param array $strings Array of text we are getting embeddings for.
		 *
		 * @return array Request body.
		 */
		$body = apply_filters(
			'classifai_openai_embeddings_request_body',
			[
				'model'      => $this->get_model(),
				'input'      => $strings,
				'dimensions' => $this->get_dimensions(),
			],
			$strings
		);

		// Make our API request.
		$response = $request->post(
			$this->get_api_url(),
			[
				'body' => wp_json_encode( $body ),
			]
		);

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
				$debug_info[ "Taxonomy ($tax threshold)" ] = Feature::get_debug_value_text( $settings[ $tax . '_threshold' ], 1 );
			}

			$debug_info[ __( 'Latest response', 'classifai' ) ] = $this->get_formatted_latest_response( get_transient( 'classifai_openai_embeddings_latest_response' ) );
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
		if ( $this->feature_instance instanceof Classification ) {
			return self::$scheduler_instance->is_embeddings_generation_in_progress( 'classifai_generate_term_embedding_job' );
		}

		if ( $this->feature_instance instanceof RecommendedContent ) {
			return self::$scheduler_instance->is_embeddings_generation_in_progress( 'classifai_generate_post_embedding_job' );
		}

		return false;
	}
}
