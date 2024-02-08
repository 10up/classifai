<?php
/**
 * OpenAI Embeddings integration
 */

namespace Classifai\Providers\OpenAI;

use Classifai\Providers\Provider;
use Classifai\Providers\OpenAI\APIRequest;
use Classifai\Providers\OpenAI\Tokenizer;
use Classifai\Providers\OpenAI\EmbeddingCalculations;
use Classifai\Normalizer;
use Classifai\Features\Classification;
use Classifai\Features\Feature;
use WP_Error;

class Embeddings extends Provider {

	use \Classifai\Providers\OpenAI\OpenAI;

	const ID = 'openai_embeddings';

	/**
	 * OpenAI Embeddings URL
	 *
	 * @var string
	 */
	protected $api_url = 'https://api.openai.com/v1/embeddings';

	/**
	 * OpenAI Embeddings model
	 *
	 * @var string
	 */
	protected $model = 'text-embedding-ada-002';

	/**
	 * Maximum number of tokens our model supports
	 *
	 * @var int
	 */
	protected $max_tokens = 8191;

	/**
	 * NLU features that are supported by this provider.
	 *
	 * @var array
	 */
	public $nlu_features = [];

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
			foreach ( $this->feature_instance->get_supported_taxonomies() as $tax => $label ) {
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
				'description'   => sprintf(
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

		return $common_settings;
	}

	/**
	 * Register what we need for the plugin.
	 *
	 * This only fires if can_register returns true.
	 */
	public function register() {
		add_filter( 'classifai_feature_classification_get_default_settings', [ $this, 'modify_default_feature_settings' ], 10, 2 );

		$feature = new Classification();

		if (
			! $feature->is_feature_enabled() ||
			$feature->get_feature_provider_instance()::ID !== static::ID
		) {
			return;
		}

		add_action( 'created_term', [ $this, 'generate_embeddings_for_term' ] );
		add_action( 'edited_terms', [ $this, 'generate_embeddings_for_term' ] );
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
		remove_filter( 'classifai_feature_classification_get_default_settings', [ $this, 'modify_default_feature_settings' ], 10, 2 );

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
		$settings = $this->feature_instance->get_settings();

		$api_key_settings                            = $this->sanitize_api_key_settings( $new_settings, $settings );
		$new_settings[ static::ID ]['api_key']       = $api_key_settings[ static::ID ]['api_key'];
		$new_settings[ static::ID ]['authenticated'] = $api_key_settings[ static::ID ]['authenticated'];

		return $new_settings;
	}

	/**
	 * Get the threshold for the similarity calculation.
	 *
	 * @since 2.5.0
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
	 *
	 * @since 2.5.0
	 *
	 * @return array
	 */
	public function get_post_classifier_embeddings_preview_data(): array {
		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : false;

		if ( ! $nonce || ! wp_verify_nonce( $nonce, 'classifai-previewer-action' ) ) {
			wp_send_json_error( esc_html__( 'Failed nonce check.', 'classifai' ) );
		}

		$post_id = filter_input( INPUT_POST, 'post_id', FILTER_SANITIZE_NUMBER_INT );

		$embeddings       = $this->generate_embeddings( $post_id, 'post' );
		$embeddings_terms = [];

		// Add terms to this item based on embedding data.
		if ( $embeddings && ! is_wp_error( $embeddings ) ) {
			$embeddings_terms = $this->get_terms( $embeddings );
		}

		return wp_send_json_success( $embeddings_terms );
	}

	/**
	 * Trigger embedding generation for content being saved.
	 *
	 * @param int $post_id ID of post being saved.
	 * @return array|WP_Error
	 */
	public function generate_embeddings_for_post( int $post_id ) {
		// Don't run on autosaves.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return new WP_Error( 'invalid', esc_html__( 'Classification will not work during an autosave.', 'classifai' ) );
		}

		// Ensure the user has permissions to edit.
		if ( ! current_user_can( 'edit_post', $post_id ) && ( ! defined( 'WP_CLI' ) || ! WP_CLI ) ) {
			return new WP_Error( 'invalid', esc_html__( 'User does not have permission to classify this item.', 'classifai' ) );
		}

		/**
		 * Filter whether ClassifAI should classify a post.
		 *
		 * Default is true, return false to skip classifying a post.
		 *
		 * @since 1.2.0
		 * @hook classifai_should_classify_post
		 *
		 * @param {bool} $should_classify Whether the post should be classified. Default `true`, return `false` to skip
		 *                                classification for this post.
		 * @param {int}  $post_id         The ID of the post to be considered for classification.
		 *
		 * @return {bool} Whether the post should be classified.
		 */
		$should_classify = apply_filters( 'classifai_should_classify_post', true, $post_id );
		if ( ! $should_classify ) {
			return new WP_Error( 'invalid', esc_html__( 'Classification is disabled for this item.', 'classifai' ) );
		}

		$embeddings = $this->generate_embeddings( $post_id, 'post' );

		// Add terms to this item based on embedding data.
		if ( $embeddings && ! is_wp_error( $embeddings ) ) {
			update_post_meta( $post_id, 'classifai_openai_embeddings', array_map( 'sanitize_text_field', $embeddings ) );
		}

		return $embeddings;
	}

	/**
	 * Add terms to a post based on embeddings.
	 *
	 * @param int   $post_id ID of post to set terms on.
	 * @param array $embedding Embedding data.
	 * @param bool  $link Whether to link the terms or not.
	 * @return array|WP_Error
	 */
	public function set_terms( int $post_id = 0, array $embedding = [], bool $link = true ) {
		if ( ! $post_id || ! get_post( $post_id ) ) {
			return new WP_Error( 'post_id_required', esc_html__( 'A valid post ID is required to set terms.', 'classifai' ) );
		}

		if ( empty( $embedding ) ) {
			return new WP_Error( 'data_required', esc_html__( 'Valid embedding data is required to set terms.', 'classifai' ) );
		}

		$embedding_similarity = $this->get_embeddings_similarity( $embedding );

		if ( empty( $embedding_similarity ) ) {
			return new WP_Error( 'invalid', esc_html__( 'No matching terms found.', 'classifai' ) );
		}

		$return = [];

		/**
		 * If $link is true, immediately link all the terms
		 * to the item.
		 *
		 * If it is false, build an array of term data that
		 * can be used to display the terms in the UI.
		 */
		foreach ( $embedding_similarity as $tax => $terms ) {
			if ( $link ) {
				wp_set_object_terms( $post_id, array_map( 'absint', array_keys( $terms ) ), $tax, false );
			} else {
				$terms_to_link = [];

				foreach ( array_keys( $terms ) as $term_id ) {
					$term = get_term( $term_id );

					if ( $term && ! is_wp_error( $term ) ) {
						$terms_to_link[ $term->name ] = $term_id;
					}
				}

				$return[ $tax ] = $terms_to_link;
			}
		}

		return empty( $return ) ? $embedding_similarity : $return;
	}

	/**
	 * Get the terms of a post based on embeddings.
	 *
	 * @param array $embedding Embedding data.
	 * @return array|WP_Error
	 */
	public function get_terms( array $embedding = [] ) {
		if ( empty( $embedding ) ) {
			return new WP_Error( 'data_required', esc_html__( 'Valid embedding data is required to get terms.', 'classifai' ) );
		}

		$embedding_similarity = $this->get_embeddings_similarity( $embedding, false );

		if ( empty( $embedding_similarity ) ) {
			return new WP_Error( 'invalid', esc_html__( 'No matching terms found.', 'classifai' ) );
		}

		// Sort terms based on similarity.
		$index  = 0;
		$result = [];

		foreach ( $embedding_similarity as $tax => $terms ) {
			// Get the taxonomy name.
			$taxonomy = get_taxonomy( $tax );
			$tax_name = $taxonomy->labels->singular_name;

			// Sort embeddings from lowest to highest.
			asort( $terms );

			// Return the terms.
			$result[ $index ] = new \stdClass();

			$result[ $index ]->{$tax_name} = [];

			$term_added = 0;
			foreach ( $terms as $term_id => $similarity ) {
				// Convert $similarity to percentage.
				$similarity = round( ( 1 - $similarity ), 10 );

				$result[ $index ]->{$tax_name}[] = [// phpcs:ignore Squiz.PHP.DisallowMultipleAssignments.Found
					'label' => get_term( $term_id )->name,
					'score' => $similarity,
				];
				++$term_added;
			}

			++$index;
		}

		return $result;
	}

	/**
	 * Get the similarity between an embedding and all terms.
	 *
	 * @since 2.5.0
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
			if ( 'tags' === $tax ) {
				$tax = 'post_tag';
			}

			if ( 'categories' === $tax ) {
				$tax = 'category';
			}

			if ( is_numeric( $tax ) ) {
				continue;
			}

			$terms = get_terms(
				[
					'taxonomy'   => $tax,
					'hide_empty' => false,
					'fields'     => 'ids',
					'meta_key'   => 'classifai_openai_embeddings', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
					// 'number'  => 500, TODO: see if we need a limit here.
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

				$term_embedding = get_term_meta( $term_id, 'classifai_openai_embeddings', true );

				if ( $term_embedding ) {
					$similarity = $calculations->similarity( $embedding, $term_embedding );
					if ( false !== $similarity && ( ! $consider_threshold || $similarity <= $threshold ) ) {
						$embedding_similarity[ $tax ][ $term_id ] = $similarity;
					}
				}
			}
		}

		return $embedding_similarity;
	}

	/**
	 * Generate embedding data for all terms within a taxonomy.
	 *
	 * TODO: this no longer seems to be called
	 *
	 * @param string $taxonomy Taxonomy slug.
	 */
	private function trigger_taxonomy_update( string $taxonomy = '' ) {
		$terms = get_terms(
			[
				'taxonomy'     => $taxonomy,
				'hide_empty'   => false,
				'fields'       => 'ids',
				'meta_key'     => 'classifai_openai_embeddings', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_compare' => 'NOT EXISTS',
				// 'number'  => 500, TODO: see if we need a limit here.
			]
		);

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return;
		}

		// Generate embedding data for each term.
		foreach ( $terms as $term_id ) {
			$this->generate_embeddings_for_term( $term_id );
		}
	}

	/**
	 * Trigger embedding generation for term being saved.
	 *
	 * @param int $term_id ID of term being saved.
	 */
	public function generate_embeddings_for_term( int $term_id ) {
		// Ensure the user has permissions to edit.
		if ( ! current_user_can( 'edit_term', $term_id ) ) {
			return;
		}

		$term = get_term( $term_id );

		if ( ! is_a( $term, '\WP_Term' ) ) {
			return;
		}

		$feature    = new Classification();
		$taxonomies = $feature->get_all_feature_taxonomies();

		if ( in_array( 'tags', $taxonomies, true ) ) {
			$taxonomies[] = 'post_tag';
		}

		if ( in_array( 'categories', $taxonomies, true ) ) {
			$taxonomies[] = 'category';
		}

		// Ensure this term is part of a taxonomy we support.
		if ( ! in_array( $term->taxonomy, $taxonomies, true ) ) {
			return;
		}

		$embeddings = $this->generate_embeddings( $term_id, 'term' );

		if ( $embeddings && ! is_wp_error( $embeddings ) ) {
			update_term_meta( $term_id, 'classifai_openai_embeddings', array_map( 'sanitize_text_field', $embeddings ) );
		}
	}

	/**
	 * Generate embeddings for a particular item.
	 *
	 * @param int    $id ID of object to generate embeddings for.
	 * @param string $type Type of object. Default 'post'.
	 * @return array|boolean|WP_Error
	 */
	public function generate_embeddings( int $id = 0, $type = 'post' ) {
		$feature  = new Classification();
		$settings = $feature->get_settings();

		// Ensure the feature is enabled.
		if ( ! $feature->is_feature_enabled() ) {
			return new WP_Error( 'not_enabled', esc_html__( 'Classification is disabled or OpenAI authentication failed. Please check your settings.', 'classifai' ) );
		}

		/**
		 * Filter whether ClassifAI should classify an item.
		 *
		 * Default is true, return false to skip classifying.
		 *
		 * @since 2.2.0
		 * @hook classifai_openai_embeddings_should_classify
		 *
		 * @param {bool}   $should_classify Whether the item should be classified. Default `true`, return `false` to skip.
		 * @param {int}    $id         The ID of the item to be considered for classification.
		 * @param {string} $type    The type of item to be considered for classification.
		 *
		 * @return {bool} Whether the post should be classified.
		 */
		if ( ! apply_filters( 'classifai_openai_embeddings_should_classify', true, $id, $type ) ) {
			return false;
		}

		$request = new APIRequest( $settings[ static::ID ]['api_key'] ?? '', $feature->get_option_name() );

		/**
		 * Filter the request body before sending to OpenAI.
		 *
		 * @since 2.2.0
		 * @hook classifai_openai_embeddings_request_body
		 *
		 * @param {array} $body Request body that will be sent to OpenAI.
		 * @param {int} $id ID of item we are getting embeddings for.
		 * @param {string} $type Type of item we are getting embeddings for.
		 *
		 * @return {array} Request body.
		 */
		$body = apply_filters(
			'classifai_openai_embeddings_request_body',
			[
				'model' => $this->model,
				'input' => $this->get_content( $id, $type ),
			],
			$id,
			$type
		);

		// Make our API request.
		$response = $request->post(
			$this->api_url,
			[
				'body' => wp_json_encode( $body ),
			]
		);

		set_transient( 'classifai_openai_embeddings_latest_response', $response, DAY_IN_SECONDS * 30 );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( empty( $response['data'] ) ) {
			return new WP_Error( 'no_data', esc_html__( 'No data returned from OpenAI.', 'classifai' ) );
		}

		// Save the embeddings response.
		foreach ( $response['data'] as $data ) {
			if ( ! isset( $data['embedding'] ) || ! is_array( $data['embedding'] ) ) {
				continue;
			}

			$response = $data['embedding'];

			break;
		}

		return $response;
	}

	/**
	 * Get our content, trimming if needed.
	 *
	 * @param int    $id ID of item to get content from.
	 * @param string $type Type of content. Default 'post'.
	 * @return string
	 */
	public function get_content( int $id = 0, string $type = 'post' ): string {
		$tokenizer  = new Tokenizer( $this->max_tokens );
		$normalizer = new Normalizer();

		// Get the content depending on the type.
		switch ( $type ) {
			case 'post':
				$content = $normalizer->normalize( $id );
				break;
			case 'term':
				$content = '';
				$term    = get_term( $id );

				if ( is_a( $term, '\WP_Term' ) ) {
					$content = $term->name . ' ' . $term->description;
				}

				break;
		}

		// Trim our content, if needed, to stay under the token limit.
		$content = $tokenizer->trim_content( $content, $this->max_tokens );

		/**
		 * Filter content that will get sent to OpenAI.
		 *
		 * @since 2.2.0
		 * @hook classifai_openai_embeddings_content
		 *
		 * @param {string} $content Content that will be sent to OpenAI.
		 * @param {int} $post_id ID of post we are submitting.
		 * @param {string} $type Type of content.
		 *
		 * @return {string} Content.
		 */
		return apply_filters( 'classifai_openai_embeddings_content', $content, $id, $type );
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
				$return = $this->generate_embeddings_for_post( $post_id );
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
		$settings          = $this->feature_instance->get_settings();
		$provider_settings = $settings[ static::ID ];
		$debug_info        = [];

		if ( $this->feature_instance instanceof Classification ) {
			foreach ( array_keys( $this->feature_instance->get_supported_taxonomies() ) as $tax ) {
				$debug_info[ "Taxonomy ($tax)" ]           = Feature::get_debug_value_text( $provider_settings[ $tax ], 1 );
				$debug_info[ "Taxonomy ($tax threshold)" ] = Feature::get_debug_value_text( $provider_settings[ $tax . '_threshold' ], 1 );
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
}
