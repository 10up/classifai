<?php

namespace Classifai\Features;

use Classifai\Admin\SimilarTermsListTable;
use Classifai\Services\LanguageProcessing;
use Classifai\Providers\OpenAI\Embeddings as OpenAIEmbeddings;
use Classifai\Providers\Azure\Embeddings as AzureEmbeddings;
use Classifai\Providers\OpenAI\EmbeddingCalculations;
use Classifai\TermCleanupScheduler;
use WP_Error;

use function Classifai\is_elasticpress_installed;

/**
 * Class TermCleanup
 */
class TermCleanup extends Feature {

	/**
	 * ID of the current feature.
	 *
	 * @var string
	 */
	const ID = 'feature_term_cleanup';

	/**
	 * Setting page URL.
	 *
	 * @var string
	 */
	private $setting_page_url;

	/**
	 * Background process instance.
	 *
	 * @var TermCleanupScheduler
	 */
	private $background_process;

	/**
	 * Transient key for notices.
	 *
	 * @var string
	 */
	private $notices_transient_key = 'classifai_term_cleanup_notices';

	/**
	 * EPIntegration instance.
	 *
	 * @var EPIntegration
	 */
	private $ep_integration;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->label = __( 'Term Cleanup', 'classifai' );

		// Contains all providers that are registered to the service.
		$this->provider_instances = $this->get_provider_instances( LanguageProcessing::get_service_providers() );

		// Contains just the providers this feature supports.
		$this->supported_providers = [
			OpenAIEmbeddings::ID => __( 'OpenAI Embeddings', 'classifai' ),
			AzureEmbeddings::ID  => __( 'Azure OpenAI Embeddings', 'classifai' ),
		];
	}

	/**
	 * Set up necessary hooks.
	 *
	 * This will always fire even if the Feature is not enabled.
	 */
	public function setup() {
		parent::setup();

		if ( $this->is_configured() && $this->is_enabled() ) {
			// Check if ElasticPress plugin is installed and use EP selected.
			if ( is_elasticpress_installed() && '1' === $this->get_settings( 'use_ep' ) ) {
				$this->ep_integration = new TermCleanupEPIntegration( $this );
				$this->ep_integration->init();
			}
		}

		$this->setting_page_url = admin_url( 'tools.php?page=classifai-term-cleanup' );

		$this->background_process = new TermCleanupScheduler( 'classifai_schedule_term_cleanup_job' );
		$this->background_process->init();
	}

	/**
	 * Set up necessary hooks.
	 *
	 * This will only fire if the Feature is enabled.
	 */
	public function feature_setup() {
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );

		// Register the settings page for the Feature.
		add_action( 'admin_menu', [ $this, 'register_admin_menu_item' ] );
		add_action( 'admin_post_classifai_init_term_cleanup', [ $this, 'start_term_cleanup_process' ] );
		add_action( 'admin_post_classifai_cancel_term_cleanup', [ $this, 'cancel_term_cleanup_process' ] );
		add_action( 'admin_post_classifai_merge_term', [ $this, 'merge_term' ] );
		add_action( 'admin_post_classifai_skip_similar_term', [ $this, 'skip_similar_term' ] );

		// Ajax action handler
		add_action( 'wp_ajax_classifai_get_term_cleanup_status', [ $this, 'get_term_cleanup_status' ] );

		// Admin notices
		add_action( 'admin_notices', [ $this, 'render_notices' ] );
	}

	/**
	 * Enqueue the admin scripts.
	 *
	 * @param string $hook_suffix The current admin page.
	 */
	public function enqueue_admin_assets( string $hook_suffix ) {
		if ( 'tools_page_classifai-term-cleanup' !== $hook_suffix ) {
			return;
		}

		wp_localize_script(
			'classifai-admin-script',
			'classifai_term_cleanup_params',
			array(
				'ajax_url'   => esc_url( admin_url( 'admin-ajax.php' ) ),
				'ajax_nonce' => wp_create_nonce( 'classifai-term-cleanup-status' ),
			)
		);
	}

	/**
	 * Register a sub page under the Tools menu.
	 */
	public function register_admin_menu_item() {
		// Don't register the menu if no taxonomies are enabled.
		if ( empty( $this->get_all_feature_taxonomies() ) ) {
			return;
		}

		add_submenu_page(
			'tools.php',
			__( 'Term Cleanup', 'classifai' ),
			__( 'Term Cleanup', 'classifai' ),
			'manage_options',
			'classifai-term-cleanup',
			[ $this, 'render_settings_page' ]
		);
	}

	/**
	 * Render the settings page for the Term Cleanup Feature.
	 */
	public function render_settings_page() {
		$active_tax     = isset( $_GET['tax'] ) ? sanitize_text_field( wp_unslash( $_GET['tax'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$all_taxonomies = $this->get_taxonomies();
		$taxonomies     = $this->get_all_feature_taxonomies();
		?>

		<div class="classifai-content">
			<?php
			include_once CLASSIFAI_PLUGIN_DIR . '/includes/Classifai/Admin/templates/classifai-header.php';

			if ( $active_tax && ! in_array( $active_tax, $taxonomies, true ) ) {
				?>
				<p>
					<?php
					esc_html_e( 'Term Cleanup Feature not enabled for this taxonomy.', 'classifai' );
					?>
				</p>
				</div>
				<?php
				return;
			}
			?>

			<div class="classifai-settings-wrapper">
				<div class="classifai-wrap wrap classifai classifai-term-cleanup">
					<h2><?php esc_html_e( 'Term Cleanup', 'classifai' ); ?></h2>
					<h2 class="nav-tab-wrapper">
						<?php
						foreach ( $taxonomies as $name ) {
							// If we don't have an active taxonomy, set the first one as active.
							if ( ! $active_tax ) {
								$active_tax = $name;
							}

							$label  = $all_taxonomies[ $name ];
							$active = $active_tax === $name ? 'nav-tab-active' : '';
							$url    = add_query_arg( 'tax', $name, $this->setting_page_url );
							?>

							<a href="<?php echo esc_url( $url ); ?>" class="nav-tab <?php echo esc_attr( $active ); ?>">
								<?php echo esc_html( $label ); ?>
							</a>

							<?php
						}
						?>
					</h2>
					<div class="classifai-term-cleanup-wrapper classifai-wrapper">
						<div class="classifai-term-cleanup-content-wrapper">
							<h3 class="screen-reader-text"><?php echo esc_html( $all_taxonomies[ $active_tax ] ); ?></h3>
						<?php
						if ( $this->background_process && $this->background_process->in_progress() ) {
							$this->render_background_processing_status( $active_tax );
						} else {
							$plural_label   = strtolower( $this->get_taxonomy_label( $active_tax, true ) );
							$singular_label = strtolower( $this->get_taxonomy_label( $active_tax, false ) );

							// translators: %s: Taxonomy name.
							$submit_label = sprintf( __( 'Find similar %s', 'classifai' ), esc_attr( $plural_label ) );
							?>
							<p>
								<?php
								// translators: %s: Taxonomy name.
								printf( esc_html__( 'Identify potential %s duplicates to merge together', 'classifai' ), esc_html( $singular_label ) );
								?>
							</p>
							<div class="submit-wrapper">
								<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
									<input type="hidden" name="action" value="classifai_init_term_cleanup" />
									<input type="hidden" name="classifai_term_cleanup_taxonomy" value="<?php echo esc_attr( $active_tax ); ?>" />
									<?php wp_nonce_field( 'classifai_term_cleanup', 'classifai_term_cleanup_nonce' ); ?>
									<input type="submit" name="submit" id="submit" class="button button-primary" value="<?php echo esc_attr( $submit_label ); ?>">
								</form>
							</div>
							<?php
						}
						?>
							<div>
								<br/>
								<?php
								$this->render_similar_terms( $active_tax );
								?>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Get the description for the enable field.
	 *
	 * @return string
	 */
	public function get_enable_description(): string {
		return esc_html__( 'A Term Cleanup page will be added under Tools that can be used to clean up terms.', 'classifai' );
	}

	/**
	 * Returns the default settings for the feature.
	 *
	 * @return array
	 */
	public function get_feature_default_settings(): array {
		$tax_settings = [];
		$taxonomies   = $this->get_taxonomies();

		foreach ( $taxonomies as $name => $label ) {
			if ( 'category' === $name ) {
				$tax_settings[ $name ] = 1;
			} else {
				$tax_settings[ $name ] = 0;
			}

			$tax_settings[ "{$name}_threshold" ] = 75;
		}

		$settings = [
			'provider'   => OpenAIEmbeddings::ID,
			'use_ep'     => is_elasticpress_installed() ? 1 : 0,
			'taxonomies' => $tax_settings,
		];

		return $settings;
	}

	/**
	 * Sanitizes the default feature settings.
	 *
	 * @param array $new_settings Settings being saved.
	 * @return array
	 */
	public function sanitize_default_feature_settings( array $new_settings ): array {
		if ( empty( $new_settings['use_ep'] ) || 1 !== (int) $new_settings['use_ep'] ) {
			$new_settings['use_ep'] = 'no';
		} else {
			$new_settings['use_ep'] = '1';
		}

		return $new_settings;
	}

	/**
	 * Get meta key for embeddings.
	 *
	 * @return string
	 */
	public function get_embeddings_meta_key(): string {
		$provider = $this->get_feature_provider_instance();
		$meta_key = 'classifai_openai_embeddings';

		if ( $provider instanceof AzureEmbeddings ) {
			$meta_key = 'classifai_azure_openai_embeddings';
		}

		/**
		 * Filter the meta key for embeddings.
		 *
		 * @since 3.2.0
		 * @hook classifai_feature_term_cleanup_embeddings_meta_key
		 *
		 * @param {string}      $meta_key Meta key for embeddings.
		 * @param {TermCleanup} $this     Feature instance.
		 *
		 * @return {string} Meta key for embeddings.
		 */
		return apply_filters( 'classifai_' . static::ID . '_embeddings_meta_key', $meta_key, $this );
	}

	/**
	 * Get all feature taxonomies.
	 *
	 * @return array
	 */
	public function get_all_feature_taxonomies(): array {
		$taxonomies = $this->get_taxonomies();
		$settings   = $this->get_settings( 'taxonomies' );

		$enabled_taxonomies = [];
		foreach ( $taxonomies as $name => $label ) {
			if ( isset( $settings[ $name ] ) && (bool) $settings[ $name ] ) {
				$enabled_taxonomies[] = $name;
			}
		}

		return $enabled_taxonomies;
	}

	/**
	 * Start the term cleanup process.
	 */
	public function start_term_cleanup_process() {
		if (
			empty( $_POST['classifai_term_cleanup_nonce'] ) ||
			! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['classifai_term_cleanup_nonce'] ) ), 'classifai_term_cleanup' )
		) {
			wp_die( esc_html__( 'You don\'t have permission to perform this operation.', 'classifai' ) );
		}

		if ( ! $this->is_feature_enabled() ) {
			wp_die( esc_html__( 'You don\'t have permission to perform this operation.', 'classifai' ) );
		}

		$settings = $this->get_settings( 'taxonomies' );
		$taxonomy = isset( $_POST['classifai_term_cleanup_taxonomy'] ) ? sanitize_text_field( wp_unslash( $_POST['classifai_term_cleanup_taxonomy'] ) ) : '';
		$thresold = isset( $settings[ $taxonomy . '_threshold' ] ) ? absint( $settings[ $taxonomy . '_threshold' ] ) : 75;

		if ( empty( $taxonomy ) ) {
			wp_die( esc_html__( 'Invalid taxonomy.', 'classifai' ) );
		}

		// Clear previously found similar terms.
		$args = [
			'taxonomy'     => $taxonomy,
			'hide_empty'   => false,
			'fields'       => 'ids',
			'meta_key'     => 'classifai_similar_terms', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'meta_compare' => 'EXISTS',
		];

		$terms = get_terms( $args );

		if ( ! empty( $terms ) ) {
			foreach ( $terms as $term_id ) {
				delete_term_meta( $term_id, 'classifai_similar_terms' );
			}
		}

		$job_args = [
			[
				'taxonomy'             => $taxonomy,
				'thresold'             => $thresold,
				'action'               => 'term_cleanup',
				'embeddings_generated' => false,
				'processed'            => 0,
				'term_id'              => 0,
				'offset'               => 0,
				'started_by'           => get_current_user_id(),
				'job_id'               => str_replace( '-', '', wp_generate_uuid4() ),
			],
		];

		$this->background_process->schedule( $job_args );

		$this->add_notice(
			__( 'Process for finding similar terms has started.', 'classifai' ),
			'info'
		);

		// Redirect back to the settings page.
		wp_safe_redirect( add_query_arg( 'tax', $taxonomy, $this->setting_page_url ) );
		exit;
	}

	/**
	 * Cancel the term cleanup process.
	 */
	public function cancel_term_cleanup_process() {
		// Check the nonce for security
		if (
			empty( $_GET['_wpnonce'] ) ||
			! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'classifai_cancel_term_cleanup' )
		) {
			wp_die( esc_html__( 'You don\'t have permission to perform this operation.', 'classifai' ) );
		}

		$taxonomy = isset( $_GET['taxonomy'] ) ? sanitize_text_field( wp_unslash( $_GET['taxonomy'] ) ) : '';

		$unschedule = $this->background_process->unschedule();

		if ( $unschedule ) {
			// Add a notice to inform the user that the process will be cancelled soon.
			$this->add_notice(
				__( 'Process for the finding similar terms will be cancelled soon.', 'classifai' ),
				'info'
			);
		}

		// Redirect back to the settings page.
		wp_safe_redirect( add_query_arg( 'tax', $taxonomy, $this->setting_page_url ) );
		exit;
	}

	/**
	 * Get the max number of terms to process.
	 *
	 * @return int
	 */
	public function get_max_terms(): int {
		return 100;
	}

	/**
	 * Generate embeddings for the terms.
	 *
	 * @param string $taxonomy Taxonomy to process.
	 * @return bool|WP_Error True if embeddings were generated, false otherwise.
	 */
	public function generate_embeddings( string $taxonomy ) {
		$exclude = [];

		// Exclude the uncategorized term.
		if ( 'category' === $taxonomy ) {
			// Exclude the uncategorized term.
			$uncat_term = get_term_by( 'name', 'Uncategorized', 'category' );
			if ( $uncat_term ) {
				$exclude = [ $uncat_term->term_id ];
			}
		}

		$meta_key = sanitize_text_field( $this->get_embeddings_meta_key() );
		$args     = [
			'taxonomy'     => $taxonomy,
			'orderby'      => 'count',
			'order'        => 'DESC',
			'hide_empty'   => false,
			'fields'       => 'ids',
			'meta_key'     => $meta_key, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'meta_compare' => 'NOT EXISTS',
			'number'       => $this->get_max_terms(),
			'exclude'      => $exclude, // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude
		];

		$terms = get_terms( $args );

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return false;
		}

		$provider = $this->get_feature_provider_instance();

		// Generate embedding data for each term.
		foreach ( $terms as $term_id ) {
			$result = $provider->generate_embeddings_for_term( $term_id, false, $this );

			/**
			 * Fires when an embedding is generated for a term.
			 *
			 * @since 3.2.0
			 * @hook classifai_feature_term_cleanup_generate_embedding
			 *
			 * @param {int}            $term_id ID of term.
			 * @param {array|WP_Error} $result  Result of embedding generation.
			 * @param {TermCleanup}    $this    Feature instance.
			 */
			do_action( 'classifai_feature_term_cleanup_generate_embedding', $term_id, $result, $this );

			if ( is_wp_error( $result ) ) {
				return $result;
			}
		}

		return true;
	}

	/**
	 * Get similar terms.
	 *
	 * @param string $taxonomy Taxonomy to process.
	 * @param int    $thresold Thresold to consider terms as duplicates.
	 * @param array  $args     Additional arguments.
	 * @return array|bool|WP_Error
	 */
	public function get_similar_terms( string $taxonomy, int $thresold, array $args = [] ) {
		if ( class_exists( '\\ElasticPress\\Feature' ) && '1' === $this->get_settings( 'use_ep' ) ) {
			return $this->get_similar_terms_using_elasticpress( $taxonomy, $thresold, $args );
		}

		return $this->get_similar_terms_using_wpdb( $taxonomy, $thresold, $args );
	}

	/**
	 * Get similar terms using WPDB.
	 *
	 * This method is used to get similar terms using MySQL database.
	 * This method is slower than using ElasticPress but can be used
	 * when ElasticPress is not installed or not in use.
	 *
	 * @param string $taxonomy Taxonomy to process.
	 * @param int    $thresold Thresold to consider terms as duplicates.
	 * @param array  $args     Additional arguments.
	 * @return array|bool
	 */
	public function get_similar_terms_using_wpdb( string $taxonomy, int $thresold, array $args = [] ) {
		$processed = $args['processed'] ?? 0;
		$term_id   = $args['term_id'] ?? 0;
		$offset    = $args['offset'] ?? 0;
		$meta_key  = sanitize_text_field( $this->get_embeddings_meta_key() );

		if ( ! $term_id ) {
			$params = [
				'taxonomy'     => $taxonomy,
				'orderby'      => 'count',
				'order'        => 'DESC',
				'hide_empty'   => false,
				'fields'       => 'ids',
				'meta_key'     => $meta_key, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_compare' => 'EXISTS',
				'number'       => 1,
				'offset'       => $processed,
			];

			if ( is_taxonomy_hierarchical( $taxonomy ) ) {
				$params['parent'] = 0;
			}

			$terms = get_terms( $params );

			if ( is_wp_error( $terms ) || empty( $terms ) ) {
				return false;
			}

			$term_id         = $terms[0];
			$offset          = 0;
			$args['term_id'] = $term_id;
			$args['offset']  = $offset;
		}

		$meta_key       = sanitize_text_field( $this->get_embeddings_meta_key() );
		$term_embedding = get_term_meta( $term_id, $meta_key, true );

		if ( 1 === count( $term_embedding ) ) {
			$term_embedding = $term_embedding[0];
		}

		global $wpdb;
		$limit    = apply_filters( 'classifai_term_cleanup_compare_limit', 2000, $taxonomy );
		$meta_key = sanitize_text_field( $this->get_embeddings_meta_key() );

		// SQL query to retrieve term meta using joins
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Running a custom query to get 1k terms embeddings at a time.
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT DISTINCT t.term_id, tm.meta_value, tt.count
				FROM {$wpdb->terms} AS t
				INNER JOIN {$wpdb->term_taxonomy} AS tt ON t.term_id = tt.term_id
				INNER JOIN {$wpdb->termmeta} AS tm ON t.term_id = tm.term_id
				WHERE tt.taxonomy = %s
				AND tm.meta_key = %s
				AND t.term_id != %d
				AND tt.parent = 0
				ORDER BY tt.count DESC
				LIMIT %d OFFSET %d",
				$taxonomy,
				$meta_key,
				$term_id,
				$limit,
				absint( $offset + $processed ) // Add the processed terms counts to the offset to skip already processed terms.
			)
		);
		$count   = count( $results );

		$calculations  = new EmbeddingCalculations();
		$similar_terms = [];

		foreach ( $results as $index => $result ) {
			// Skip if the term is the same as the term we are comparing.
			if ( $term_id === $result->term_id ) {
				continue;
			}

			$compare_term_id   = $result->term_id;
			$compare_embedding = maybe_unserialize( $result->meta_value );

			if ( 1 === count( $compare_embedding ) ) {
				$compare_embedding = $compare_embedding[0];
			}

			$similarity = $calculations->cosine_similarity( $term_embedding, $compare_embedding );
			if ( false !== $similarity && ( 1 - $similarity ) >= ( $thresold / 100 ) ) {
				$similar_terms[ $compare_term_id ] = 1 - $similarity;
			}
		}

		if ( ! empty( $similar_terms ) ) {
			$existing_similar_terms = get_term_meta( $term_id, 'classifai_similar_terms', true );

			if ( is_array( $existing_similar_terms ) ) {
				$similar_terms = $existing_similar_terms + $similar_terms;
			}

			update_term_meta( $term_id, 'classifai_similar_terms', $similar_terms );
		}

		if ( $count < $limit ) {
			$args['processed'] = $processed + 1;
			$args['term_id']   = 0;
			$args['offset']    = 0;
		} else {
			$args['offset'] = $offset + $limit;
		}

		return $args;
	}

	/**
	 * Get similar terms using Elasticsearch via ElasticPress.
	 *
	 * @param string $taxonomy Taxonomy to process.
	 * @param int    $thresold Thresold to consider terms as duplicates.
	 * @param array  $args     Additional arguments.
	 * @return array|bool|WP_Error
	 */
	public function get_similar_terms_using_elasticpress( string $taxonomy, int $thresold, array $args = [] ) {
		$processed = $args['processed'] ?? 0;
		$meta_key  = sanitize_text_field( $this->get_embeddings_meta_key() );

		$params = [
			'taxonomy'     => $taxonomy,
			'orderby'      => 'count',
			'order'        => 'DESC',
			'hide_empty'   => false,
			'fields'       => 'ids',
			'meta_key'     => $meta_key, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'meta_compare' => 'EXISTS',
			'number'       => 10,
			'offset'       => $processed,
		];

		if ( is_taxonomy_hierarchical( $taxonomy ) ) {
			$params['parent'] = 0;
		}

		$terms = get_terms( $params );

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return false;
		}

		if ( ! $this->ep_integration ) {
			$this->ep_integration = new TermCleanupEPIntegration( $this );
		}

		foreach ( $terms as $term_id ) {
			// Find similar terms for the term.
			$search_results = $this->ep_integration->exact_knn_search( $term_id, 'term', 500, $thresold );

			if ( is_wp_error( $search_results ) ) {
				return $search_results;
			}

			$similar_terms    = [];
			$filtered_results = array_filter(
				$search_results,
				function ( $result ) use ( $taxonomy ) {
					return $result['taxonomy'] === $taxonomy;
				}
			);

			foreach ( $filtered_results as $index => $result ) {
				$compare_term_id        = $result['term_id'];
				$existing_similar_terms = get_term_meta( $compare_term_id, 'classifai_similar_terms', true );

				// Skip if it is already present in the similar terms list of the term we are comparing.
				if ( ! empty( $existing_similar_terms ) && isset( $existing_similar_terms[ $term_id ] ) ) {
					continue;
				}

				$similar_terms[ $compare_term_id ] = $result['score'];
			}

			if ( ! empty( $similar_terms ) ) {
				$existing_similar_terms = get_term_meta( $term_id, 'classifai_similar_terms', true );

				if ( is_array( $existing_similar_terms ) ) {
					$similar_terms = $existing_similar_terms + $similar_terms;
				}

				update_term_meta( $term_id, 'classifai_similar_terms', $similar_terms );
			}

			$args['processed'] = $args['processed'] + 1;
		}

		$args['term_id'] = 0;

		return $args;
	}

	/**
	 * Get the background processing status.
	 *
	 * @param string $taxonomy Taxonomy to process.
	 * @return array
	 */
	public function get_background_processing_status( string $taxonomy ): array {
		if ( ! $this->background_process ) {
			return [];
		}

		$args = $this->background_process->get_args();

		if ( ! empty( $args ) ) {
			foreach ( $args as $arg ) {
				if ( 'term_cleanup' === $arg['action'] && $taxonomy === $arg['taxonomy'] ) {
					return $arg;
				}
			}
		}

		return [];
	}

	/**
	 * Render the processing status.
	 *
	 * @param string $taxonomy Taxonomy to process.
	 */
	public function render_background_processing_status( $taxonomy ) {
		$status = $this->get_background_processing_status( $taxonomy );

		if ( empty( $status ) ) {
			?>
			<p>
				<?php
				esc_html_e( 'Background process for finding similar terms is running for another taxonomy.', 'classifai' );
				?>
			<?php
			return;
		}

		$is_embeddings_generated = (bool) $status['embeddings_generated'];
		$processed               = $status['processed'] ?? 0;
		$args                    = array(
			'action'   => 'classifai_cancel_term_cleanup',
			'taxonomy' => $taxonomy,
		);
		$cancel_url              = add_query_arg( $args, wp_nonce_url( admin_url( 'admin-post.php' ), 'classifai_cancel_term_cleanup' ) );
		$label                   = strtolower( $this->get_taxonomy_label( $taxonomy, true ) );
		?>

		<div class="classifai-term-cleanup-process-status" data-taxonomy="<?php echo esc_attr( $taxonomy ); ?>">
			<h4 style="font-size: 1.1em;">
				<?php
				// translators: %s: Taxonomy name.
				printf( esc_html__( 'Finding similar %s...', 'classifai' ), esc_html( $label ) );
				?>
			</h4>

			<?php
			if ( $is_embeddings_generated ) {
				?>
				<p>
					<span class="dashicons dashicons-yes-alt"></span>
					<?php
					// translators: %1$s: Taxonomy name.
					printf( esc_html__( 'Embeddings are generated for %s.', 'classifai' ), esc_html( $label ) );
					?>
				</p>
				<p>
					<span class="spinner is-active" style="float:none; margin: 0px; vertical-align: bottom;"></span>
					<?php
					$page_url = add_query_arg( 'tax', $taxonomy, $this->setting_page_url );
					$refresh  = sprintf(
						// translators: %s: Refresh the page link.
						esc_html__( '%s to see these results.', 'classifai' ),
						'<a href="' . esc_url( $page_url ) . '">' . esc_html__( 'Refresh the page', 'classifai' ) . '</a>'
					);
					echo wp_kses_post(
						sprintf(
							/* translators: %1$s: Taxonomy name, %d: Number of terms processed */
							__( 'Finding similar %1$s, <strong>%2$d</strong> %1$s processed. %3$s', 'classifai' ),
							esc_html( $label ),
							absint( $processed ),
							( absint( $processed ) > 0 ) ? $refresh : ''
						)
					);
					?>
				</p>
				<?php
			} else {
				$meta_key  = sanitize_text_field( $this->get_embeddings_meta_key() );
				$generated = wp_count_terms(
					[
						'taxonomy'     => $taxonomy,
						'hide_empty'   => false,
						'meta_key'     => $meta_key, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
						'meta_compare' => 'EXISTS',
					]
				);
				?>
				<p>
					<span class="spinner is-active" style="float:none; margin: 0px; vertical-align: bottom;"></span>
					<?php
					echo wp_kses_post(
						sprintf(
							/* translators: %1$s: Taxonomy name, %d: Number of terms processed */
							__( 'Generating embeddings, <strong>%2$d</strong> %1$s processed.', 'classifai' ),
							esc_html( $label ),
							absint( $generated )
						)
					);
					?>
				</p>
				<?php
			}
			?>

			<a href="<?php echo esc_url( $cancel_url ); ?>" class="button button-link button-link-delete"><?php esc_html_e( 'Cancel', 'classifai' ); ?></a>
		</div>

		<?php
	}

	/**
	 * Render similar terms for the given taxonomy.
	 *
	 * @param string $taxonomy Taxonomy to display similar terms for.
	 */
	public function render_similar_terms( $taxonomy ) {
		$label = $this->get_taxonomy_label( $taxonomy, true );
		$count = wp_count_terms(
			[
				'taxonomy'     => $taxonomy,
				'hide_empty'   => false,
				'meta_key'     => 'classifai_similar_terms', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_compare' => 'EXISTS',
			]
		);

		if ( $count > 0 ) {
			?>
			<h3 style="margin-bottom: 0px;">
				<?php
				// translators: %s: Taxonomy name.
				printf( esc_html__( 'Similar %s', 'classifai' ), esc_html( $label ) );
				?>
			</h3>
			<form id="import-history" method="get">
				<input type="hidden" name="page" value="classifai-term-cleanup">
				<input type="hidden" name="tax" value="<?php echo esc_attr( $taxonomy ); ?>">
				<?php
				$list_table = new SimilarTermsListTable( $taxonomy );
				$list_table->prepare_items();
				$list_table->search_box( esc_html__( 'Search', 'classifai' ), 'search-term' );
				$list_table->display();
				?>
			</form>
			<?php
		}
	}

	/**
	 * Get taxonomy labels.
	 *
	 * @param string $taxonomy Taxonomy to get labels for.
	 * @param bool   $plural   Whether to get plural label.
	 * @return string
	 */
	public function get_taxonomy_label( $taxonomy, $plural = false ): string {
		$tax    = get_taxonomy( $taxonomy );
		$labels = get_taxonomy_labels( $tax );

		if ( $plural ) {
			$label = $labels->name ?? __( 'Terms', 'classifai' );
		} else {
			$label = $labels->singular_name ?? __( 'Term', 'classifai' );
		}

		return $label;
	}

	/**
	 * Ajax handler for refresh compare status.
	 */
	public function get_term_cleanup_status() {
		// Check the nonce for security
		check_ajax_referer( 'classifai-term-cleanup-status', 'nonce' );

		$data     = array(
			'is_running' => false,
			'status'     => '',
		);
		$taxonomy = isset( $_POST['taxonomy'] ) ? sanitize_text_field( wp_unslash( $_POST['taxonomy'] ) ) : '';

		if ( empty( $taxonomy ) ) {
			$data['error'] = __( 'Taxonomy is required', 'classifai' );
			wp_send_json_error( $data );
		}

		if ( $this->background_process->in_progress() ) {
			$data['is_running'] = true;
			ob_start();
			$this->render_background_processing_status( $taxonomy );
			$data['status'] = ob_get_clean();
		}

		wp_send_json_success( $data );
	}

	/**
	 * Merge term.
	 */
	public function merge_term() {
		// Check the nonce for security
		if (
			empty( $_GET['_wpnonce'] ) ||
			! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'classifai_merge_term' )
		) {
			wp_die( esc_html__( 'You don\'t have permission to perform this operation.', 'classifai' ) );
		}

		$taxonomy  = isset( $_GET['taxonomy'] ) ? sanitize_text_field( wp_unslash( $_GET['taxonomy'] ) ) : '';
		$to        = isset( $_GET['to'] ) ? absint( wp_unslash( $_GET['to'] ) ) : 0;
		$from      = isset( $_GET['from'] ) ? absint( wp_unslash( $_GET['from'] ) ) : 0;
		$to_term   = get_term( $to, $taxonomy );
		$from_term = get_term( $from, $taxonomy );
		$args      = [
			'tax'   => $taxonomy,
			's'     => isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : false,
			'paged' => isset( $_GET['paged'] ) ? absint( wp_unslash( $_GET['paged'] ) ) : false,
		];
		$redirect  = add_query_arg( $args, $this->setting_page_url );

		if ( empty( $taxonomy ) || empty( $to ) || empty( $from ) ) {
			$this->add_notice(
				__( 'Invalid request.', 'classifai' ),
				'error'
			);

			// Redirect back to the settings page.
			wp_safe_redirect( $redirect );
			exit;
		}

		if ( $to === $from ) {
			$this->add_notice(
				__( 'Cannot merge term with itself.', 'classifai' ),
				'error'
			);

			// Redirect back to the settings page.
			wp_safe_redirect( $redirect );
			exit;
		}

		/**
		 * Fires before terms are merged together.
		 *
		 * @since 3.2.0
		 * @hook classifai_feature_term_cleanup_pre_merge_term
		 *
		 * @param {int}    $from     Term ID being merged.
		 * @param {int}    $to       Term ID we're merging into.
		 * @param {string} $taxonomy Taxonomy of terms being merged.
		 */
		do_action( 'classifai_feature_term_cleanup_pre_merge_term', $from, $to, $taxonomy );

		$ret = wp_delete_term(
			$from,
			$taxonomy,
			array(
				'default'       => $to,
				'force_default' => true,
			)
		);

		/**
		 * Fires after terms are merged together.
		 *
		 * @since 3.2.0
		 * @hook classifai_feature_term_cleanup_post_merge_term
		 *
		 * @param {int}               $from     Term ID being merged.
		 * @param {int}               $to       Term ID we're merging into.
		 * @param {string}            $taxonomy Taxonomy of terms being merged.
		 * @param {bool|int|WP_Error} $ret      Result of merge process.
		 */
		do_action( 'classifai_feature_term_cleanup_post_merge_term', $from, $to, $taxonomy, $ret );

		if ( is_wp_error( $ret ) ) {
			$this->add_notice(
				// translators: %s: Error message.
				sprintf( __( 'Error merging terms: %s.', 'classifai' ), $ret->get_error_message() ),
				'error'
			);
		}

		$this->add_notice(
			// translators: %1$s: From term name, %2$s: To term name.
			sprintf( __( 'Merged term "%1$s" into "%2$s".', 'classifai' ), $from_term->name, $to_term->name ),
			'success'
		);

		// Redirect back to the settings page.
		wp_safe_redirect( $redirect );
		exit;
	}

	/**
	 * Skip similar term.
	 */
	public function skip_similar_term() {
		// Check the nonce for security
		if (
			empty( $_GET['_wpnonce'] ) ||
			! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'classifai_skip_similar_term' )
		) {
			wp_die( esc_html__( 'You don\'t have permission to perform this operation.', 'classifai' ) );
		}

		$taxonomy     = isset( $_GET['taxonomy'] ) ? sanitize_text_field( wp_unslash( $_GET['taxonomy'] ) ) : '';
		$term         = isset( $_GET['term'] ) ? absint( wp_unslash( $_GET['term'] ) ) : 0;
		$similar_term = isset( $_GET['similar_term'] ) ? absint( wp_unslash( $_GET['similar_term'] ) ) : 0;
		$args         = [
			'tax'   => $taxonomy,
			's'     => isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : false,
			'paged' => isset( $_GET['paged'] ) ? absint( wp_unslash( $_GET['paged'] ) ) : false,
		];
		$redirect     = add_query_arg( $args, $this->setting_page_url );

		/**
		 * Fires before a term is skipped.
		 *
		 * @since 3.2.0
		 * @hook classifai_feature_term_cleanup_pre_skip_term
		 *
		 * @param {int}    $term         Term ID being skipped.
		 * @param {int}    $similar_term Term ID that matched.
		 * @param {string} $taxonomy     Taxonomy of terms being merged.
		 */
		do_action( 'classifai_feature_term_cleanup_pre_skip_term', $term, $similar_term, $taxonomy );

		// SKip/Ignore the similar term.
		$term_meta = get_term_meta( $term, 'classifai_similar_terms', true );
		if ( is_array( $term_meta ) && isset( $term_meta[ $similar_term ] ) ) {
			unset( $term_meta[ $similar_term ] );
			if ( empty( $term_meta ) ) {
				delete_term_meta( $term, 'classifai_similar_terms' );
			} else {
				update_term_meta( $term, 'classifai_similar_terms', $term_meta );
			}
		}

		/**
		 * Fires after a term is skipped.
		 *
		 * @since 3.2.0
		 * @hook classifai_feature_term_cleanup_post_skip_term
		 *
		 * @param {int}    $term         Term ID being skipped.
		 * @param {int}    $similar_term Term ID that matched.
		 * @param {string} $taxonomy     Taxonomy of terms being merged.
		 */
		do_action( 'classifai_feature_term_cleanup_post_skip_term', $term, $similar_term, $taxonomy );

		$this->add_notice(
			esc_html__( 'Skipped similar term.', 'classifai' ),
			'success'
		);

		// Redirect back to the settings page.
		wp_safe_redirect( $redirect );
		exit;
	}

	/**
	 * Add a notice to be displayed.
	 *
	 * @param string $message Message to display.
	 * @param string $type    Type of notice.
	 */
	public function add_notice( $message, $type = 'success' ) {
		$notices = get_transient( $this->notices_transient_key );

		if ( ! is_array( $notices ) ) {
			$notices = [];
		}

		$notices[] = array(
			'message' => $message,
			'type'    => $type,
		);

		set_transient( $this->notices_transient_key, $notices, 300 );
	}

	/**
	 * Render notices.
	 */
	public function render_notices() {
		$notices = get_transient( $this->notices_transient_key );

		if ( ! empty( $notices ) ) {
			foreach ( $notices as $notice ) {
				?>
				<div class="notice notice-<?php echo esc_attr( $notice['type'] ); ?> is-dismissible">
					<p>
						<?php echo wp_kses_post( $notice['message'] ); ?>
					</p>
				</div>
				<?php
			}
			delete_transient( $this->notices_transient_key );
		}
	}
}
