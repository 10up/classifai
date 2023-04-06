<?php
/**
 * OpenAI Embeddings integration
 */

namespace Classifai\Providers\OpenAI;

use Classifai\Providers\Provider;
use Classifai\Providers\OpenAI\APIRequest;
use Classifai\Providers\OpenAI\Tokenizer;
use Classifai\Watson\Normalizer;
use WP_Error;

class Embeddings extends Provider {

	use \Classifai\Providers\OpenAI\OpenAI;

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
	 * OpenAI Embeddings constructor.
	 *
	 * @param string $service The service this class belongs to.
	 */
	public function __construct( $service ) {
		parent::__construct(
			'OpenAI',
			'Embeddings',
			'openai_embeddings',
			$service
		);
	}

	/**
	 * Can the functionality be initialized?
	 *
	 * @return bool
	 */
	public function can_register() {
		$settings = $this->get_settings();

		if ( empty( $settings ) || ( isset( $settings['authenticated'] ) && false === $settings['authenticated'] ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Register what we need for the plugin.
	 *
	 * This only fires if can_register returns true.
	 */
	public function register() {
		$settings = $this->get_settings();

		if ( isset( $settings['enable_classification'] ) && 1 === (int) $settings['enable_classification'] ) {
			add_action( 'wp_insert_post', [ $this, 'generate_embeddings_for_post' ] );
			add_action( 'created_term', [ $this, 'generate_embeddings_for_term' ] ); // TODO: run this only for taxonomy selected in settings.
			add_action( 'edited_terms', [ $this, 'generate_embeddings_for_term' ] );
		}
	}

	/**
	 * Setup fields
	 */
	public function setup_fields_sections() {
		$default_settings = $this->get_default_settings();

		$this->setup_api_fields( $default_settings['api_key'] );

		add_settings_field(
			'enable-classification',
			esc_html__( 'Classify content', 'classifai' ),
			[ $this, 'render_input' ],
			$this->get_option_name(),
			$this->get_option_name(),
			[
				'label_for'     => 'enable_classification',
				'input_type'    => 'checkbox',
				'default_value' => $default_settings['enable_classification'],
				'description'   => __( 'Automatically classify content within your existing category structure.', 'classifai' ),
			]
		);

		// TODO: add settings for post types, post statuses, and taxonomies to classify.
	}

	/**
	 * Sanitization for the options being saved.
	 *
	 * @param array $settings Array of settings about to be saved.
	 *
	 * @return array The sanitized settings to be saved.
	 */
	public function sanitize_settings( $settings ) {
		$new_settings = $this->get_settings();
		$new_settings = array_merge(
			$new_settings,
			$this->sanitize_api_key_settings( $new_settings, $settings )
		);

		if ( empty( $settings['enable_classification'] ) || 1 !== (int) $settings['enable_classification'] ) {
			$new_settings['enable_classification'] = 'no';
		} else {
			$new_settings['enable_classification'] = '1';
		}

		return $new_settings;
	}

	/**
	 * Resets settings for the provider.
	 */
	public function reset_settings() {
		update_option( $this->get_option_name(), $this->get_default_settings() );
	}

	/**
	 * Default settings for ChatGPT
	 *
	 * @return array
	 */
	private function get_default_settings() {
		return [
			'authenticated'         => false,
			'api_key'               => '',
			'enable_classification' => false,
		];
	}

	/**
	 * Provides debug information related to the provider.
	 *
	 * @param array|null $settings Settings array. If empty, settings will be retrieved.
	 * @param boolean    $configured Whether the provider is correctly configured. If null, the option will be retrieved.
	 * @return string|array
	 */
	public function get_provider_debug_information( $settings = null, $configured = null ) {
		if ( is_null( $settings ) ) {
			$settings = $this->sanitize_settings( $this->get_settings() );
		}

		$authenticated         = 1 === intval( $settings['authenticated'] ?? 0 );
		$enable_classification = 1 === intval( $settings['enable_classification'] ?? 0 );

		return [
			__( 'Authenticated', 'classifai' )          => $authenticated ? __( 'yes', 'classifai' ) : __( 'no', 'classifai' ),
			__( 'Classification enabled', 'classifai' ) => $enable_classification ? __( 'yes', 'classifai' ) : __( 'no', 'classifai' ),
			__( 'Latest response', 'classifai' )        => $this->get_formatted_latest_response( 'classifai_openai_embeddings_latest_response' ),
		];
	}

	/**
	 * Trigger embedding generation for content being saved.
	 *
	 * @param int $post_id ID of post being saved.
	 */
	public function generate_embeddings_for_post( $post_id ) {
		// Don't run on autosaves.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Ensure the user has permissions to edit.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$post = get_post( $post_id );

		// Only run on supported post types.
		if ( 'publish' !== $post->post_status ) { // TODO: add setting to support other statuses.
			return;
		}

		// TODO: add custom filter to turn this off

		$embeddings = $this->generate_embeddings( $post_id, 'post' );

		// Add terms to this item based on embedding data.
		if ( ! is_wp_error( $embeddings ) ) {
			$this->set_terms( $post_id, $embeddings );
		}
	}

	/**
	 * Add terms to post based on embeddings.
	 *
	 * @param int   $post_id ID of post to set terms on.
	 * @param array $embedding Embedding data.
	 */
	private function set_terms( int $post_id = 0, array $embedding = [] ) {
		if ( empty( $embedding ) ) {
			return;
		}

		// TODO: term cap checks here?

		$embedding_similarity = [];

		// TODO: loop over taxonomies from settings. Ensure we only pull taxonomies that this post type supports.
		$terms = get_terms(
			[
				'taxonomy'   => 'category',
				'hide_empty' => false,
				'fields'     => 'ids',
				'meta_key'   => 'classifai_openai_embeddings', // TODO: set limit here?
			]
		);

		// TODO: for actual comparison of embeddings, probably need to limit that to a certain number for performance reasons. Run some tests to see what that number should be.

		// TODO: error handling if no terms found.

		// Get embedding similarity for each term.
		foreach ( $terms as $term_id ) {
			$term_embedding = get_term_meta( $term_id, 'classifai_openai_embeddings', true );

			if ( $term_embedding ) {
				$embedding_similarity[ $term_id ] = $this->embedding_similarity( $embedding, $term_embedding );
			}
		}

		if ( empty( $embedding_similarity ) ) {
			return;
		}

		// Sort embeddings from lowest to highest.
		asort( $embedding_similarity );

		$terms_to_add = array_keys( array_slice( $embedding_similarity, 0, 2, true ) ); // TODO: add setting to support number of terms.

		wp_set_object_terms( $post_id, array_map( 'absint', $terms_to_add ), 'category', false ); // TODO: pull taxonomy from settings.
	}

	/**
	 * Trigger embedding generation for term being saved.
	 *
	 * @param int $term_id ID of term being saved.
	 */
	public function generate_embeddings_for_term( $term_id ) {
		// Ensure the user has permissions to edit.
		if ( ! current_user_can( 'edit_term', $term_id ) ) {
			return;
		}

		// TODO: only run this if the taxonomy is supported in settings.

		// TODO: add custom filter to turn this off

		$this->generate_embeddings( $term_id, 'term' );
	}

	/**
	 * Generate embeddings for a particular item.
	 *
	 * @param int    $id ID of object to generate embeddings for.
	 * @param string $type Type of object. Default 'post'.
	 * @return array|WP_Error
	 */
	public function generate_embeddings( int $id = 0, $type = 'post' ) {
		$settings = $this->get_settings();

		// This check should have already run but if someone were to call
		// this method directly, we run it again.
		if ( empty( $settings ) || ( isset( $settings['authenticated'] ) && false === $settings['authenticated'] ) || ( isset( $settings['enable_classification'] ) && 'no' === $settings['enable_classification'] ) ) {
			return new WP_Error( 'not_enabled', esc_html__( 'Classification is disabled or OpenAI authentication failed. Please check your settings.', 'classifai' ) );
		}

		$request = new APIRequest( $settings['api_key'] ?? '' );

		/**
		 * Filter the request body before sending to OpenAI.
		 *
		 * @since x.x.x
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

			switch ( $type ) {
				case 'post':
					update_post_meta( $id, 'classifai_openai_embeddings', array_map( 'sanitize_text_field', $data['embedding'] ) );
					break;
				case 'term':
					update_term_meta( $id, 'classifai_openai_embeddings', array_map( 'sanitize_text_field', $data['embedding'] ) );
					break;
			}

			break;
		}

		return $response;
	}

	/**
	 * Calculate the similarity between two embeddings.
	 *
	 * This code is based on what OpenAI does in their Python SDK.
	 * See https://github.com/openai/openai-python/blob/ede0882939656ce4289cb4f61142e7658bb2dec7/openai/embeddings_utils.py#L141
	 *
	 * @param array $source_embedding Embedding data of the source item.
	 * @param array $compare_embedding Embedding data of the item to compare.
	 * @return float
	 */
	public function embedding_similarity( array $source_embedding = [], array $compare_embedding = [] ) {
		// uv = np.average(u * v, weights=w)
		$combined_average = array_sum(
			array_map(
				function( $x, $y ) {
					return (float) $x * (float) $y;
				},
				$source_embedding,
				$compare_embedding
			)
		) / count( $source_embedding );

		// uu = np.average(np.square(u), weights=w)
		$source_average = array_sum(
			array_map(
				function( $x ) {
					return pow( (float) $x, 2 );
				},
				$source_embedding
			)
		) / count( $source_embedding );

		// vv = np.average(np.square(v), weights=w)
		$compare_average = array_sum(
			array_map(
				function( $x ) {
					return pow( (float) $x, 2 );
				},
				$compare_embedding
			)
		) / count( $compare_embedding );

		// dist = 1.0 - uv / np.sqrt(uu * vv)
		$distance = 1.0 - ( $combined_average / sqrt( $source_average * $compare_average ) );

		// max(0, min(correlation(u, v, w=w, centered=False), 2.0))
		return max( 0, min( abs( (float) $distance ), 2.0 ) );
	}

	/**
	 * Get our content, trimming if needed.
	 *
	 * @param int    $id ID of item to get content from.
	 * @param string $type Type of content. Default 'post'.
	 * @return string
	 */
	public function get_content( int $id = 0, string $type = 'post' ) {
		$tokenizer  = new Tokenizer( $this->max_tokens );
		$normalizer = new Normalizer();

		// Get the content depending on the type.
		switch ( $type ) {
			case 'post':
				$content = $normalizer->normalize( $id );
				break;
			case 'term':
				$term    = get_term( $id );
				$content = is_a( $term, 'WP_Term' ) ? $term->name : '';
				break;
		}

		// Trim our content, if needed, to stay under the token limit.
		$content = $tokenizer->trim_content( $content, $this->max_tokens );

		/**
		 * Filter content that will get sent to OpenAI.
		 *
		 * @since x.x.x
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

}
