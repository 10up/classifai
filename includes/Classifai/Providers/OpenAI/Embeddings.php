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
use WP_Error;

use function Classifai\get_asset_info;
use function Classifai\Providers\Watson\get_supported_post_statuses;
use function Classifai\Providers\Watson\get_supported_post_types;

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

		add_settings_field(
			static::ID . '_number_of_terms',
			esc_html__( 'Number of terms', 'classifai' ),
			[ $this->feature_instance, 'render_input' ],
			$this->feature_instance->get_option_name(),
			$this->feature_instance->get_option_name() . '_section',
			[
				'option_index'   => static::ID,
				'label_for'      => 'number_of_terms',
				'input_type'     => 'number',
				'min'            => 1,
				'step'           => 1,
				'default_values' => $settings['number_of_terms'],
				'description'    => esc_html__( 'Maximum number of terms that will get auto-assigned.', 'classifai' ),
				'class'          => 'classifai-provider-field hidden provider-scope-' . static::ID, // Important to add this.
			]
		);

		add_settings_field(
			static::ID . '_taxonomies',
			esc_html__( 'Taxonomies', 'classifai' ),
			[ $this, 'render_checkbox_group' ],
			$this->feature_instance->get_option_name(),
			$this->feature_instance->get_option_name() . '_section',
			[
				'option_index'   => static::ID,
				'label_for'      => 'taxonomies',
				'options'        => $this->get_taxonomies_for_settings(),
				'default_values' => $settings['taxonomies'],
				'description'    => __( 'Choose which taxonomies will be used for classification.', 'classifai' ),
				'class'          => 'classifai-provider-field hidden provider-scope-' . static::ID, // Important to add this.
			]
		);

		do_action( 'classifai_' . static::ID . '_render_provider_fields', $this );
		add_action( 'classifai_after_feature_settings_form', [ $this, 'render_previewer' ] );
	}

	/**
	 * Renders the previewer window for the feature.
	 *
	 * @param string $active_feature The active feature.
	 */
	public function render_previewer( string $active_feature ) {
		$feature  = new Classification();
		$provider = $feature->get_feature_provider_instance();

		if (
			self::ID !== $provider::ID ||
			$feature::ID !== $active_feature ||
			! $feature->is_feature_enabled()
		) {
			return;
		}
		?>

		<div id="classifai-post-preview-app">
			<?php
			$supported_post_statuses = get_supported_post_statuses();
			$supported_post_types    = get_supported_post_types();

			$posts_to_preview = get_posts(
				array(
					'post_type'      => $supported_post_types,
					'post_status'    => $supported_post_statuses,
					'posts_per_page' => 10,
				)
			);
			?>

			<h2><?php esc_html_e( 'Preview Language Processing', 'classifai' ); ?></h2>
			<div id="classifai-post-preview-controls">
				<select id="classifai-preview-post-selector">
					<?php foreach ( $posts_to_preview as $post ) : ?>
						<option value="<?php echo esc_attr( $post->ID ); ?>"><?php echo esc_html( $post->post_title ); ?></option>
					<?php endforeach; ?>
				</select>
				<?php wp_nonce_field( 'classifai-previewer-action', 'classifai-previewer-nonce' ); ?>
				<button type="button" class="button" id="get-classifier-preview-data-btn">
					<span><?php esc_html_e( 'Preview', 'classifai' ); ?></span>
				</button>
			</div>
			<div id="classifai-post-preview-wrapper">
			</div>
		</div>

		<?php
	}

	/**
	 * Returns the default settings for this provider.
	 *
	 * @return array
	 */
	public function get_default_provider_settings(): array {
		$common_settings = [
			'api_key'         => '',
			'number_of_terms' => 1,
			'authenticated'   => false,
		];

		switch ( $this->feature_instance::ID ) {
			case Classification::ID:
				return array_merge(
					$common_settings,
					[
						'taxonomies' => [
							'category',
						],
					]
				);
		}

		return $common_settings;
	}

	/**
	 * Register what we need for the plugin.
	 *
	 * This only fires if can_register returns true.
	 */
	public function register() {
		$feature = new Classification();

		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );

		if ( ! $feature->is_feature_enabled() || $feature->get_feature_provider_instance()::ID !== static::ID ) {
			return;
		}

		add_action( 'wp_insert_post', [ $this, 'generate_embeddings_for_post' ] );
		add_action( 'created_term', [ $this, 'generate_embeddings_for_term' ] );
		add_action( 'edited_terms', [ $this, 'generate_embeddings_for_term' ] );
		add_action( 'enqueue_block_editor_assets', [ $this, 'enqueue_editor_assets' ], 9 );
		add_filter( 'rest_api_init', [ $this, 'add_process_content_meta_to_rest_api' ] );
		add_action( 'add_meta_boxes', [ $this, 'add_metabox' ] );
		add_action( 'save_post', [ $this, 'save_metabox' ] );
		add_action( 'wp_ajax_get_post_classifier_embeddings_preview_data', array( $this, 'get_post_classifier_embeddings_preview_data' ) );
	}

	/**
	 * Enqueue the admin scripts.
	 */
	public function enqueue_admin_assets() {
		wp_enqueue_script(
			'classifai-language-processing-script',
			CLASSIFAI_PLUGIN_URL . 'dist/language-processing.js',
			get_asset_info( 'language-processing', 'dependencies' ),
			get_asset_info( 'language-processing', 'version' ),
			true
		);

		wp_enqueue_style(
			'classifai-language-processing-style',
			CLASSIFAI_PLUGIN_URL . 'dist/language-processing.css',
			array(),
			get_asset_info( 'language-processing', 'version' ),
			'all'
		);
	}

	/**
	 * Enqueue editor assets.
	 */
	public function enqueue_editor_assets() {
		global $post;

		if ( empty( $post ) ) {
			return;
		}

		wp_enqueue_script(
			'classifai-gutenberg-plugin',
			CLASSIFAI_PLUGIN_URL . 'dist/gutenberg-plugin.js',
			array_merge( get_asset_info( 'gutenberg-plugin', 'dependencies' ), array( 'lodash' ) ),
			get_asset_info( 'gutenberg-plugin', 'version' ),
			true
		);

		wp_add_inline_script(
			'classifai-gutenberg-plugin',
			sprintf(
				'var classifaiEmbeddingData = %s;',
				wp_json_encode(
					[
						'enabled'              => true,
						'supportedPostTypes'   => $this->supported_post_types(),
						'supportedPostStatues' => $this->supported_post_statuses(),
						'noPermissions'        => ! is_user_logged_in() || ! current_user_can( 'edit_post', $post->ID ),
					]
				)
			),
			'before'
		);
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

		if ( $this->feature_instance instanceof Classification ) {
			// Sanitize the taxonomy checkboxes.
			$taxonomies = $this->get_taxonomies_for_settings();
			foreach ( $taxonomies as $taxonomy_key => $taxonomy_value ) {
				if ( isset( $new_settings[ static::ID ]['taxonomies'][ $taxonomy_key ] ) && '0' !== $new_settings[ static::ID ]['taxonomies'][ $taxonomy_key ] ) {
					$new_settings[ static::ID ]['taxonomies'][ $taxonomy_key ] = sanitize_text_field( $new_settings[ static::ID ]['taxonomies'][ $taxonomy_key ] ?? $settings[ static::ID ]['taxonomies'][ $taxonomy_key ] );
					$this->trigger_taxonomy_update( $taxonomy_key );
				} else {
					$new_settings[ static::ID ]['taxonomies'][ $taxonomy_key ] = '0';
				}
			}
		}

		return $new_settings;
	}

	/**
	 * The list of supported post types.
	 *
	 * @return array
	 */
	public function supported_post_types(): array {
		/**
		 * Filter post types supported for embeddings.
		 *
		 * @since 2.2.0
		 * @hook classifai_post_types
		 *
		 * @param {array} $post_types Array of post types to be classified.
		 *
		 * @return {array} Array of post types.
		 */
		return apply_filters( 'classifai_openai_embeddings_post_types', $this->get_supported_post_types( new Classification() ) );
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
			$threshold = isset( $settings['taxonomies'][ $taxonomy . '_threshold' ] ) ? $settings['taxonomies'][ $taxonomy . '_threshold' ] : 75;
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
	 * The list of supported post statuses.
	 *
	 * @return array
	 */
	public function supported_post_statuses(): array {
		/**
		 * Filter post statuses supported for embeddings.
		 *
		 * @since 2.2.0
		 * @hook classifai_openai_embeddings_post_statuses
		 *
		 * @param {array} $post_types Array of post statuses to be classified.
		 *
		 * @return {array} Array of post statuses.
		 */
		return apply_filters( 'classifai_openai_embeddings_post_statuses', $this->get_supported_post_statuses( new Classification() ) );
	}

	/**
	 * The list of supported taxonomies.
	 *
	 * @return array
	 */
	public function supported_taxonomies(): array {
		/**
		 * Filter taxonomies supported for embeddings.
		 *
		 * @since 2.2.0
		 * @hook classifai_openai_embeddings_taxonomies
		 *
		 * @param {array} $taxonomies Array of taxonomies to be classified.
		 *
		 * @return {array} Array of taxonomies.
		 */
		return apply_filters( 'classifai_openai_embeddings_taxonomies', $this->get_supported_taxonomies( new Classification() ) );
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

		$embeddings_terms = $this->generate_embeddings_for_post( $post_id, true );

		return wp_send_json_success( $embeddings_terms );
	}

	/**
	 * Trigger embedding generation for content being saved.
	 *
	 * @param int  $post_id ID of post being saved.
	 * @param bool $dryrun Whether to run the process or just return the data.
	 * @return array|WP_Error
	 */
	public function generate_embeddings_for_post( int $post_id, bool $dryrun = false ) {
		// Don't run on autosaves.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Ensure the user has permissions to edit.
		if ( ! current_user_can( 'edit_post', $post_id ) && ( ! defined( 'WP_CLI' ) || ! WP_CLI ) ) {
			return;
		}

		$post = get_post( $post_id );

		// Only run on supported post types and statuses.
		if (
			! $dryrun
			&& (
				! in_array( $post->post_type, $this->supported_post_types(), true ) ||
				! in_array( $post->post_status, $this->supported_post_statuses(), true )
			)
		) {
			return;
		}

		// Don't run if turned off for this particular post.
		if ( 'no' === get_post_meta( $post_id, '_classifai_process_content', true ) && ! $dryrun ) {
			return;
		}

		$embeddings = $this->generate_embeddings( $post_id, 'post' );

		// Add terms to this item based on embedding data.
		if ( $embeddings && ! is_wp_error( $embeddings ) ) {
			if ( $dryrun ) {
				return $this->get_terms( $embeddings );
			} else {
				update_post_meta( $post_id, 'classifai_openai_embeddings', array_map( 'sanitize_text_field', $embeddings ) );
				return $this->set_terms( $post_id, $embeddings );
			}
		}
	}

	/**
	 * Add terms to a post based on embeddings.
	 *
	 * @param int   $post_id ID of post to set terms on.
	 * @param array $embedding Embedding data.
	 */
	private function set_terms( int $post_id = 0, array $embedding = [] ) {
		if ( ! $post_id || ! get_post( $post_id ) ) {
			return new WP_Error( 'post_id_required', esc_html__( 'A valid post ID is required to set terms.', 'classifai' ) );
		}

		if ( empty( $embedding ) ) {
			return new WP_Error( 'data_required', esc_html__( 'Valid embedding data is required to set terms.', 'classifai' ) );
		}

		$settings             = ( new Classification() )->get_settings();
		$number_to_add        = $settings['number_of_terms'] ?? 1;
		$embedding_similarity = $this->get_embeddings_similarity( $embedding );

		if ( empty( $embedding_similarity ) ) {
			return;
		}

		// Set terms based on similarity.
		foreach ( $embedding_similarity as $tax => $terms ) {
			// Sort embeddings from lowest to highest.
			asort( $terms );

			// Only add the number of terms specified in settings.
			if ( count( $terms ) > $number_to_add ) {
				$terms = array_slice( $terms, 0, $number_to_add, true );
			}

			wp_set_object_terms( $post_id, array_map( 'absint', array_keys( $terms ) ), $tax, false );
		}
	}

	/**
	 * Get the terms of a post based on embeddings.
	 *
	 * @param array $embedding Embedding data.
	 * @return array|WP_Error
	 */
	private function get_terms( array $embedding = [] ) {
		if ( empty( $embedding ) ) {
			return new WP_Error( 'data_required', esc_html__( 'Valid embedding data is required to get terms.', 'classifai' ) );
		}

		$settings             = ( new Classification() )->get_settings();
		$number_to_add        = $settings[ static::ID ]['number_of_terms'] ?? 1;
		$embedding_similarity = $this->get_embeddings_similarity( $embedding, false );

		if ( empty( $embedding_similarity ) ) {
			return;
		}

		// Set terms based on similarity.
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
				// Stop if we have added the number of terms specified in settings.
				if ( $number_to_add <= $term_added ) {
					break;
				}

				// Convert $similarity to percentage.
				$similarity = round( ( 1 - $similarity ), 10 );

				$result[ $index ]->{$tax_name}[] = [// phpcs:ignore Squiz.PHP.DisallowMultipleAssignments.Found
					'label' => get_term( $term_id )->name,
					'score' => $similarity,
				];
				++$term_added;
			}

			// Only add the number of terms specified in settings.
			if ( count( $terms ) > $number_to_add ) {
				$terms = array_slice( $terms, 0, $number_to_add, true );
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
		$embedding_similarity = [];
		$taxonomies           = $this->supported_taxonomies();
		$calculations         = new EmbeddingCalculations();

		foreach ( $taxonomies as $tax ) {
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

		$taxonomies = $this->supported_taxonomies();

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
	 * Add `classifai_process_content` to the REST API for view/edit.
	 */
	public function add_process_content_meta_to_rest_api() {
		$supported_post_types = $this->supported_post_types( new Classification() );

		register_rest_field(
			$supported_post_types,
			'classifai_process_content',
			[
				'get_callback'    => function ( $data ) {
					$process_content = get_post_meta( $data['id'], '_classifai_process_content', true );
					return ( 'no' === $process_content ) ? 'no' : 'yes';
				},
				'update_callback' => function ( $value, $data ) {
					$value = ( 'no' === $value ) ? 'no' : 'yes';
					return update_post_meta( $data->ID, '_classifai_process_content', $value );
				},
				'schema'          => [
					'type'    => 'string',
					'context' => [ 'view', 'edit' ],
				],
			]
		);
	}

	/**
	 * Add metabox.
	 *
	 * @param string $post_type Post type name.
	 */
	public function add_metabox( string $post_type ) {
		if ( ! in_array( $post_type, $this->get_supported_post_types( new Classification() ), true ) ) {
			return;
		}

		\add_meta_box(
			'classifai_language_processing_metabox',
			__( 'ClassifAI Language Processing', 'classifai' ),
			array( $this, 'render_metabox' ),
			null,
			'side',
			'default',
			[
				'__back_compat_meta_box' => true,
			]
		);
	}

	/**
	 * Render metabox.
	 *
	 * @param \WP_Post $post A WordPress post instance.
	 */
	public function render_metabox( \WP_Post $post ) {

		$classifai_process_content = get_post_meta( $post->ID, '_classifai_process_content', true );
		$checked                   = 'no' === $classifai_process_content ? '' : 'checked="checked"';

		// Add nonce.
		wp_nonce_field( 'classifai_language_processing_meta_action', 'classifai_language_processing_meta' );
		wp_nonce_field( 'classifai_embeddings_save_posts', '_nonce' );
		?>
		<div class='classifai-metabox classifai-metabox-embeddings'>
			<p>
				<label for="classifai-process-content" class="classifai-preview-toggle">
					<input type="checkbox" value="yes" name="_classifai_process_content" id="classifai-process-content" <?php echo esc_html( $checked ); ?> />
					<strong><?php esc_html_e( 'Automatically tag content on update', 'classifai' ); ?></strong>
				</label>
			</p>
		</div>
		<?php
	}

	/**
	 * Handles saving the metabox.
	 *
	 * @param int $post_id Current post ID.
	 */
	public function save_metabox( int $post_id ) {

		if ( empty( $_POST['classifai_language_processing_meta'] ) ) {
			return;
		}

		// Add nonce for security and authentication.
		$nonce_action = 'classifai_language_processing_meta_action';

		// Check if nonce is valid.
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['classifai_language_processing_meta'] ) ), $nonce_action ) ) {
			return;
		}

		// Check if user has permissions to save data.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Check if not an autosave.
		if ( wp_is_post_autosave( $post_id ) ) {
			return;
		}

		// Check if not a revision.
		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}

		$classifai_process_content = isset( $_POST['_classifai_process_content'] ) ? sanitize_key( wp_unslash( $_POST['_classifai_process_content'] ) ) : '';

		if ( 'yes' !== $classifai_process_content ) {
			update_post_meta( $post_id, '_classifai_process_content', 'no' );
		} else {
			update_post_meta( $post_id, '_classifai_process_content', 'yes' );
		}
	}

	/**
	 * Render a group of checkboxes.
	 *
	 * @param array $args The args passed to add_settings_field
	 */
	public function render_checkbox_group( array $args = array() ) {
		$setting_index = $this->feature_instance->get_settings( static::ID );
		$options       = $args['options'] ?? [];
		$option_index  = $args['option_index'];

		if ( ! is_array( $options ) ) {
			return;
		}

		// Iterate through all of our options.
		foreach ( $options as $option_value => $option_label ) {
			$value                 = '';
			$default_key           = array_search( $option_value, $args['default_values'], true );
			$option_value_theshold = $option_value . '_threshold';

			// Get saved value, if any.
			if ( isset( $setting_index[ $args['label_for'] ] ) ) {
				$value           = $setting_index[ $args['label_for'] ][ $option_value ] ?? '';
				$threshold_value = $setting_index[ $args['label_for'] ][ $option_value_theshold ] ?? '';
			}

			// If no saved value, check if we have a default value.
			if ( empty( $value ) && '0' !== $value && isset( $args['default_values'][ $default_key ] ) ) {
				$value = $args['default_values'][ $default_key ];
			}

			// Render checkbox.
			printf(
				'<p>
					<label for="%1$s_%2$s_%3$s_%4$s">
						<input type="hidden" name="%1$s[%2$s][%3$s][%4$s]" value="0" />
						<input type="checkbox" id="%1$s_%2$s_%3$s_%4$s" name="%1$s[%2$s][%3$s][%4$s]" value="%4$s" %5$s />
						%6$s
					</label>
				</p>',
				esc_attr( $this->feature_instance->get_option_name() ),
				esc_attr( $option_index ),
				esc_attr( $args['label_for'] ?? '' ),
				esc_attr( $option_value ),
				checked( $value, $option_value, false ),
				esc_html( $option_label )
			);

			// Render Threshold field.
			if ( 'taxonomies' === $args['label_for'] ) {
				$this->render_threshold_field( $args, $option_value_theshold, $threshold_value );
			}
		}

		// Render description, if any.
		if ( ! empty( $args['description'] ) ) {
			printf(
				'<span class="description">%s</span>',
				esc_html( $args['description'] )
			);
		}
	}

	/**
	 * Render a threshold field.
	 *
	 * @since 2.5.0
	 *
	 * @param array  $args         The args passed to add_settings_field
	 * @param string $option_value The option value.
	 * @param string $value        The value.
	 */
	public function render_threshold_field( array $args, string $option_value, string $value ) {
		printf(
			'<p class="threshold_wrapper">
				<label for="%1$s_%2$s_%3$s_%4$s">%5$s</label>
				<br>
				<input type="number" id="%1$s_%2$s_%3$s_%4$s" class="small-text" name="%1$s[%2$s][%3$s][%4$s]" value="%6$s" />
			</p>',
			esc_attr( $this->feature_instance->get_option_name() ),
			esc_attr( $args['option_index'] ),
			esc_attr( $args['label_for'] ?? '' ),
			esc_attr( $option_value ),
			esc_html__( 'Threshold (%)', 'classifai' ),
			$value ? esc_attr( $value ) : 75
		);
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
			$debug_info[ __( 'Number of terms', 'classifai' ) ]                = $provider_settings['number_of_terms'] ?? 1;
			$debug_info[ __( 'Taxonomy (category)', 'classifai' ) ]            = $provider_settings['taxonomies']['category'] ? __( 'Enabled', 'classifai' ) : __( 'Disabled', 'classifai' );
			$debug_info[ __( 'Taxonomy (category threshold)', 'classifai' ) ]  = $provider_settings['taxonomies']['category_threshold'];
			$debug_info[ __( 'Taxonomy (tag)', 'classifai' ) ]                 = $provider_settings['taxonomies']['post_tag'] ? __( 'Enabled', 'classifai' ) : __( 'Disabled', 'classifai' );
			$debug_info[ __( 'Taxonomy (tag threshold)', 'classifai' ) ]       = $provider_settings['taxonomies']['post_tag_threshold'];
			$debug_info[ __( 'Taxonomy (format)', 'classifai' ) ]              = $provider_settings['taxonomies']['post_format'] ? __( 'Enabled', 'classifai' ) : __( 'Disabled', 'classifai' );
			$debug_info[ __( 'Taxonomy (format threshold)', 'classifai' ) ]    = $provider_settings['taxonomies']['post_format_threshold'];
			$debug_info[ __( 'Taxonomy (image tag)', 'classifai' ) ]           = $provider_settings['taxonomies']['classifai-image-tags'] ? __( 'Enabled', 'classifai' ) : __( 'Disabled', 'classifai' );
			$debug_info[ __( 'Taxonomy (image tag threshold)', 'classifai' ) ] = $provider_settings['taxonomies']['classifai-image-tags_threshold'];
			$debug_info[ __( 'Latest response', 'classifai' ) ]                = $this->get_formatted_latest_response( get_transient( 'classifai_openai_embeddings_latest_response' ) );
		}

		return apply_filters(
			'classifai_' . self::ID . '_debug_information',
			$debug_info,
			$settings,
			$this->feature_instance
		);
	}
}
