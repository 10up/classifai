<?php

namespace Classifai\Features;

use Classifai\Services\LanguageProcessing;
use Classifai\Providers\Watson\NLU;
use Classifai\Providers\OpenAI\Embeddings as OpenAIEmbeddings;
use Classifai\Providers\Azure\Embeddings as AzureEmbeddings;
use WP_REST_Server;
use WP_REST_Request;
use WP_Error;

use function Classifai\get_post_statuses_for_language_settings;
use function Classifai\get_post_types_for_language_settings;
use function Classifai\check_term_permissions;
use function Classifai\get_classification_feature_enabled;
use function Classifai\get_classification_feature_taxonomy;
use function Classifai\get_asset_info;
use function Classifai\get_classification_mode;

/**
 * Class Classification
 */
class Classification extends Feature {
	/**
	 * ID of the current feature.
	 *
	 * @var string
	 */
	const ID = 'feature_classification';

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->label = __( 'Classification', 'classifai' );

		// Contains all providers that are registered to the service.
		$this->provider_instances = $this->get_provider_instances( LanguageProcessing::get_service_providers() );

		// Contains just the providers this feature supports.
		$this->supported_providers = [
			NLU::ID              => __( 'IBM Watson NLU', 'classifai' ),
			OpenAIEmbeddings::ID => __( 'OpenAI Embeddings', 'classifai' ),
			AzureEmbeddings::ID  => __( 'Azure OpenAI Embeddings', 'classifai' ),
		];
	}

	/**
	 * Set up necessary hooks.
	 *
	 * We utilize this so we can register the REST route.
	 */
	public function setup() {
		parent::setup();
		add_action( 'rest_api_init', [ $this, 'register_endpoints' ] );
	}

	/**
	 * Set up necessary hooks.
	 */
	public function feature_setup() {
		$post_types = $this->get_supported_post_types();
		if ( ! empty( $post_types ) ) {
			foreach ( $post_types as $post_type ) {
				add_action( 'rest_after_insert_' . $post_type, [ $this, 'rest_after_insert' ] );
			}
		}

		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
		add_action( 'enqueue_block_editor_assets', [ $this, 'enqueue_editor_assets' ] );
		add_action( 'classifai_after_feature_settings_form', [ $this, 'render_previewer' ] );
		add_action( 'rest_api_init', [ $this, 'add_process_content_meta_to_rest_api' ] );
		add_action( 'wp_ajax_classifai_get_post_search_results', array( $this, 'get_post_search_results' ) );
		add_filter( 'default_post_metadata', [ $this, 'default_post_metadata' ], 10, 3 );

		// Support the Classic Editor.
		add_action( 'add_meta_boxes', [ $this, 'add_meta_box' ], 10, 2 );
		add_action( 'save_post', [ $this, 'save_meta_box' ] );
		add_action( 'admin_post_classifai_classify_post', array( $this, 'classifai_classify_post' ) );
		add_action( 'admin_notices', [ $this, 'show_error_if' ] );
		add_filter( 'removable_query_args', [ $this, 'removable_query_args' ] );
	}

	/**
	 * Register any needed endpoints.
	 */
	public function register_endpoints() {
		$post_types = $this->get_supported_post_types();
		foreach ( $post_types as $post_type ) {
			register_meta(
				$post_type,
				'_classifai_error',
				[
					'show_in_rest'  => true,
					'single'        => true,
					'auth_callback' => '__return_true',
				]
			);
		}

		register_rest_route(
			'classifai/v1',
			'classify/(?P<id>\d+)',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'rest_endpoint_callback' ],
				'args'                => array(
					'id'        => array(
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
						'description'       => esc_html__( 'Post ID to classify.', 'classifai' ),
					),
					'linkTerms' => array(
						'type'        => 'boolean',
						'description' => esc_html__( 'Whether to link terms or not.', 'classifai' ),
						'default'     => true,
					),
				),
				'permission_callback' => [ $this, 'classify_permissions_check' ],
			]
		);
	}

	/**
	 * Check if a given request has access to run classification.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|bool
	 */
	public function classify_permissions_check( WP_REST_Request $request ) {
		$post_id = $request->get_param( 'id' );

		// Ensure we have a logged in user that can edit the item.
		if ( empty( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return false;
		}

		$post_type     = get_post_type( $post_id );
		$post_type_obj = get_post_type_object( $post_type );

		// Ensure the post type is allowed in REST endpoints.
		if ( ! $post_type || empty( $post_type_obj ) || empty( $post_type_obj->show_in_rest ) ) {
			return false;
		}

		// For all enabled features, ensure the user has proper permissions to add/edit terms.
		$provider_instance = $this->get_feature_provider_instance();
		if ( empty( $provider_instance->nlu_features ) ) {
			return new WP_Error( 'not_enabled', esc_html__( 'Classification not configured correctly for the selected provider.', 'classifai' ) );
		}

		foreach ( $provider_instance->nlu_features as $feature_name => $feature ) {
			if ( ! get_classification_feature_enabled( $feature_name ) ) {
				continue;
			}

			$taxonomy   = get_classification_feature_taxonomy( $feature_name );
			$permission = check_term_permissions( $taxonomy );

			if ( is_wp_error( $permission ) ) {
				return $permission;
			}
		}

		$post_status   = get_post_status( $post_id );
		$supported     = $this->get_supported_post_types();
		$post_statuses = $this->get_supported_post_statuses();

		// Check if processing allowed.
		if (
			! in_array( $post_status, $post_statuses, true ) ||
			! in_array( $post_type, $supported, true ) ||
			! $this->is_feature_enabled()
		) {
			return new WP_Error( 'not_enabled', esc_html__( 'Classification not enabled for current item.', 'classifai' ) );
		}

		return true;
	}

	/**
	 * Generic request handler for all our custom routes.
	 *
	 * @param WP_REST_Request $request The full request object.
	 * @return \WP_REST_Response
	 */
	public function rest_endpoint_callback( WP_REST_Request $request ) {
		$route = $request->get_route();

		if ( strpos( $route, '/classifai/v1/classify' ) === 0 ) {
			$results = $this->run(
				$request->get_param( 'id' ),
				'classify',
				[
					'link_terms' => $request->get_param( 'linkTerms' ),
				]
			);

			// Save results or return the results that need saved.
			if ( ! is_wp_error( $results ) ) {
				$results = $this->save( $request->get_param( 'id' ), $results, $request->get_param( 'linkTerms' ) ?? true );
			}

			return rest_ensure_response(
				[
					'terms'              => $results,
					'feature_taxonomies' => $this->get_all_feature_taxonomies(),
				]
			);
		}

		return parent::rest_endpoint_callback( $request );
	}

	/**
	 * Save or return the classification results.
	 *
	 * If $link is true, we link the terms to the item. If
	 * it is false, we just return the terms that need linked
	 * so they can show in the UI.
	 *
	 * @param int   $post_id The post ID.
	 * @param array $results Term results
	 * @param bool  $link Whether to link the terms or not.
	 * @return array|WP_Error
	 */
	public function save( int $post_id, array $results, bool $link = true ) {
		$provider_instance = $this->get_feature_provider_instance();

		/**
		 * Filter results to be saved.
		 *
		 * @since 3.1.0
		 * @hook classifai_feature_classification_pre_save_results
		 *
		 * @param {array} $supported Term results.
		 * @param {int} $post_id Post ID.
		 * @param {bool} $link Whether to link the terms or not.
		 * @param {object} $this Current instance of the class.
		 *
		 * @return {array} Term results.
		 */
		$results = apply_filters( 'classifai_' . static::ID . '_pre_save_results', $results, $post_id, $link, $this );

		switch ( $provider_instance::ID ) {
			case NLU::ID:
				$results = $provider_instance->link( $post_id, $results, $link );
				break;
			case AzureEmbeddings::ID:
			case OpenAIEmbeddings::ID:
				$results = $provider_instance->set_terms( $post_id, $results, $link );
				break;
		}

		return $results;
	}

	/**
	 * Run classification after an item has been inserted via REST.
	 *
	 * @param \WP_Post $post Post object.
	 */
	public function rest_after_insert( \WP_Post $post ) {
		$supported_post_types = $this->get_supported_post_types();
		$post_statuses        = $this->get_supported_post_statuses();

		// Ensure the post type and status is allowed.
		if (
			! in_array( $post->post_type, $supported_post_types, true ) ||
			! in_array( $post->post_status, $post_statuses, true )
		) {
			return;
		}

		// Check if processing on save is disabled.
		if ( 'no' === get_post_meta( $post->ID, '_classifai_process_content', true ) ) {
			return;
		}

		$results = $this->run( $post->ID, 'classify' );

		if ( ! empty( $results ) && ! is_wp_error( $results ) ) {
			$this->save( $post->ID, $results );
			delete_post_meta( $post->ID, '_classifai_error' );
		} elseif ( is_wp_error( $results ) ) {
			update_post_meta(
				$post->ID,
				'_classifai_error',
				wp_json_encode(
					[
						'code'    => $results->get_error_code(),
						'message' => $results->get_error_message(),
					]
				)
			);
		}
	}

	/**
	 * Enqueue the admin scripts.
	 */
	public function enqueue_admin_assets() {
		wp_enqueue_script(
			'classifai-plugin-classification-previewer-js',
			CLASSIFAI_PLUGIN_URL . 'dist/classifai-plugin-classification-previewer.js',
			get_asset_info( 'classifai-plugin-classification-previewer', 'dependencies' ),
			get_asset_info( 'classifai-plugin-classification-previewer', 'version' ),
			true
		);

		wp_enqueue_style(
			'classifai-plugin-classification-previewer-css',
			CLASSIFAI_PLUGIN_URL . 'dist/classifai-plugin-classification-previewer.css',
			array(),
			get_asset_info( 'classifai-plugin-classification-previewer', 'version' ),
			'all'
		);
	}

	/**
	 * Enqueue editor assets.
	 */
	public function enqueue_editor_assets() {
		global $post;

		wp_enqueue_script(
			'classifai-plugin-classification-ibm-watson-js',
			CLASSIFAI_PLUGIN_URL . 'dist/classifai-plugin-classification-ibm-watson.js',
			get_asset_info( 'classifai-plugin-classification-ibm-watson', 'dependencies' ),
			get_asset_info( 'classifai-plugin-classification-ibm-watson', 'version' ),
			true
		);

		if ( empty( $post ) ) {
			return;
		}

		wp_enqueue_script(
			'classifai-plugin-classification-js',
			CLASSIFAI_PLUGIN_URL . 'dist/classifai-plugin-classification.js',
			array_merge( get_asset_info( 'classifai-plugin-classification', 'dependencies' ), array( 'lodash' ), array( Feature::PLUGIN_AREA_SCRIPT ) ),
			get_asset_info( 'classifai-plugin-classification', 'version' ),
			true
		);

		wp_add_inline_script(
			'classifai-plugin-classification-js',
			sprintf(
				'var classifaiPostData = %s;',
				wp_json_encode(
					[
						'NLUEnabled'           => $this->is_feature_enabled(),
						'supportedPostTypes'   => $this->get_supported_post_types(),
						'supportedPostStatues' => $this->get_supported_post_statuses(),
						'noPermissions'        => ! is_user_logged_in() || ! current_user_can( 'edit_post', $post->ID ),
					]
				)
			),
			'before'
		);
	}

	/**
	 * Add `classifai_process_content` to the REST API for view/edit.
	 */
	public function add_process_content_meta_to_rest_api() {
		$supported_post_types = $this->get_supported_post_types();

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
	 * Searches and returns posts.
	 */
	public function get_post_search_results() {
		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : false;

		if ( ! ( $nonce && wp_verify_nonce( $nonce, 'classifai-previewer-action' ) ) ) {
			wp_send_json_error( esc_html__( 'Failed nonce check.', 'classifai' ) );
		}

		$search_term   = isset( $_POST['search'] ) ? sanitize_text_field( wp_unslash( $_POST['search'] ) ) : '';
		$post_types    = isset( $_POST['post_types'] ) ? explode( ',', sanitize_text_field( wp_unslash( $_POST['post_types'] ) ) ) : 'post';
		$post_statuses = isset( $_POST['post_status'] ) ? explode( ',', sanitize_text_field( wp_unslash( $_POST['post_status'] ) ) ) : 'publish';

		$posts = get_posts(
			array(
				'post_type'   => $post_types,
				'post_status' => $post_statuses,
				's'           => $search_term,
			)
		);

		wp_send_json_success( $posts );
	}

	/**
	 * Add metabox to enable/disable language processing.
	 *
	 * @param string   $post_type Post type.
	 * @param \WP_Post $post WP_Post object.
	 */
	public function add_meta_box( string $post_type, $post ) {
		$supported_post_types = $this->get_supported_post_types();
		$post_statuses        = $this->get_supported_post_statuses();
		$post_status          = get_post_status( $post );

		if (
			in_array( $post_type, $supported_post_types, true ) &&
			in_array( $post_status, $post_statuses, true )
		) {
			add_meta_box(
				'classifai_language_processing_metabox',
				__( 'ClassifAI Language Processing', 'classifai' ),
				[ $this, 'render_meta_box' ],
				null,
				'side',
				'high',
				array( '__back_compat_meta_box' => true )
			);
		}
	}

	/**
	 * Render metabox content.
	 *
	 * @param \WP_Post $post WP_Post object.
	 */
	public function render_meta_box( \WP_Post $post ) {
		wp_nonce_field( 'classifai_language_processing_meta_action', 'classifai_language_processing_meta' );
		$process_content = get_post_meta( $post->ID, '_classifai_process_content', true );
		$process_content = ( 'no' === $process_content ) ? 'no' : 'yes';
		?>

		<p>
			<label for="classifai-process-content">
				<input type="checkbox" value="yes" name="_classifai_process_content" id="classifai-process-content" <?php checked( $process_content, 'yes' ); ?> />
				<?php esc_html_e( 'Automatically tag content on update', 'classifai' ); ?>
			</label>
		</p>

		<div class="classifai-clasify-post-wrapper" style="display: none;">
			<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=classifai_classify_post&post_id=' . $post->ID ), 'classifai_classify_post_action', 'classifai_classify_post_nonce' ) ); ?>" class="button button-classify-post">
				<?php esc_html_e( 'Suggest terms & tags', 'classifai' ); ?>
			</a>
		</div>
		<?php
	}

	/**
	 * Handles saving the metabox.
	 *
	 * @param int $post_id Current post ID.
	 */
	public function save_meta_box( int $post_id ) {
		if (
			wp_is_post_autosave( $post_id ) ||
			wp_is_post_revision( $post_id ) ||
			! current_user_can( 'edit_post', $post_id )
		) {
			return;
		}

		if (
			empty( $_POST['classifai_language_processing_meta'] ) ||
			! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['classifai_language_processing_meta'] ) ), 'classifai_language_processing_meta_action' )
		) {
			return;
		}

		$classifai_process_content = isset( $_POST['_classifai_process_content'] ) ? sanitize_key( wp_unslash( $_POST['_classifai_process_content'] ) ) : '';

		if ( 'yes' !== $classifai_process_content ) {
			update_post_meta( $post_id, '_classifai_process_content', 'no' );
		} else {
			update_post_meta( $post_id, '_classifai_process_content', 'yes' );

			$results = $this->run( $post_id, 'classify' );

			if ( ! empty( $results ) && ! is_wp_error( $results ) ) {
				$this->save( $post_id, $results );
				delete_post_meta( $post_id, '_classifai_error' );
			} elseif ( is_wp_error( $results ) ) {
				update_post_meta(
					$post_id,
					'_classifai_error',
					wp_json_encode(
						[
							'code'    => $results->get_error_code(),
							'message' => $results->get_error_message(),
						]
					)
				);
			}
		}
	}

	/**
	 * Classify post manually.
	 *
	 * Fires when the Classify button is clicked
	 * in the Classic Editor.
	 */
	public function classifai_classify_post() {
		if (
			empty( $_GET['classifai_classify_post_nonce'] ) ||
			! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['classifai_classify_post_nonce'] ) ), 'classifai_classify_post_action' )
		) {
			wp_die( esc_html__( 'You don\'t have permission to perform this operation.', 'classifai' ) );
		}

		$post_id = isset( $_GET['post_id'] ) ? absint( $_GET['post_id'] ) : 0;

		if ( ! $post_id ) {
			exit();
		}

		// Check to see if processing is disabled and overwrite that.
		// Since we are manually classifying, we want to force this.
		$enabled = get_post_meta( $post_id, '_classifai_process_content', true );
		if ( 'yes' !== $enabled ) {
			update_post_meta( $post_id, '_classifai_process_content', 'yes' );
		}

		$results = $this->run( $post_id, 'classify' );

		// Ensure the processing value is changed back to what it was.
		if ( 'yes' !== $enabled ) {
			update_post_meta( $post_id, '_classifai_process_content', 'no' );
		}

		$classified = array();

		if ( ! empty( $results ) && ! is_wp_error( $results ) ) {
			$this->save( $post_id, $results );
			$classified = array( 'classifai_classify' => 1 );
			delete_post_meta( $post_id, '_classifai_error' );
		} elseif ( is_wp_error( $results ) ) {
			update_post_meta(
				$post_id,
				'_classifai_error',
				wp_json_encode(
					[
						'code'    => $results->get_error_code(),
						'message' => $results->get_error_message(),
					]
				)
			);
		}

		wp_safe_redirect( esc_url_raw( add_query_arg( $classified, get_edit_post_link( $post_id, 'edit' ) ) ) );
		exit();
	}

	/**
	 * Outputs an admin notice with the error message if needed.
	 */
	public function show_error_if() {
		global $post;

		if ( empty( $post ) ) {
			return;
		}

		$post_id = $post->ID;

		if ( empty( $post_id ) ) {
			return;
		}

		$error = get_post_meta( $post_id, '_classifai_error', true );

		if ( ! empty( $error ) ) {
			delete_post_meta( $post_id, '_classifai_error' );
			$error   = (array) json_decode( $error );
			$code    = ! empty( $error['code'] ) ? $error['code'] : 500;
			$message = ! empty( $error['message'] ) ? $error['message'] : 'Unknown API error';

			?>
			<div class="notice notice-error is-dismissible">
				<p>
					<?php esc_html_e( 'Error: Failed to classify content.', 'classifai' ); ?>
				</p>
				<p>
					<?php echo esc_html( $code ); ?>
					-
					<?php echo esc_html( $message ); ?>
				</p>
			</div>
			<?php
		}

		// Display classify post success message for manually classified post.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$classified = isset( $_GET['classifai_classify'] ) ? intval( wp_unslash( $_GET['classifai_classify'] ) ) : 0;

		if ( 1 === $classified ) {
			$post_type       = get_post_type_object( get_post_type( $post ) );
			$post_type_label = esc_html__( 'Post', 'classifai' );
			if ( $post_type ) {
				$post_type_label = $post_type->labels->singular_name;
			}
			?>

			<div class="notice notice-success is-dismissible">
				<p>
					<?php
					// translators: %s is post type label.
					printf( esc_html__( '%s classified successfully.', 'classifai' ), esc_html( $post_type_label ) );
					?>
				</p>
			</div>

			<?php
		}
	}

	/**
	 * Sets the default value for the _classifai_process_content meta key.
	 *
	 * @param mixed  $value     The value get_metadata() should return - a single metadata value,
	 *                          or an array of values.
	 * @param int    $object_id Object ID.
	 * @param string $meta_key  Meta key.
	 * @return mixed
	 */
	public function default_post_metadata( $value, int $object_id, string $meta_key ) {
		if ( '_classifai_process_content' === $meta_key ) {
			if ( 'automatic_classification' === get_classification_mode() ) {
				return 'yes';
			} else {
				return 'no';
			}
		}

		return $value;
	}

	/**
	 * Add "classifai_classify" in list of query variable names to remove.
	 *
	 * @param array $removable_query_args An array of query variable names to remove from a URL.
	 * @return array
	 */
	public function removable_query_args( array $removable_query_args ): array {
		$removable_query_args[] = 'classifai_classify';
		return $removable_query_args;
	}

	/**
	 * Renders the previewer window for the feature.
	 *
	 * @param string $active_feature The active feature.
	 */
	public function render_previewer( string $active_feature ) {
		if ( self::ID !== $active_feature || ! $this->is_feature_enabled() ) {
			return;
		}

		$provider_instance       = $this->get_feature_provider_instance();
		$nlu_features            = array();
		$supported_post_statuses = $this->get_supported_post_statuses();
		$supported_post_types    = $this->get_supported_post_types();

		$posts_to_preview = get_posts(
			array(
				'post_type'      => $supported_post_types,
				'post_status'    => $supported_post_statuses,
				'posts_per_page' => 10,
			)
		);

		if ( ! empty( $provider_instance->nlu_features ) ) {
			$nlu_features = $provider_instance->nlu_features;
		}
		?>

		<div id="classifai-post-preview-app">
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
				<?php
				foreach ( $nlu_features as $feature_slug => $feature ) :
					if ( ! get_classification_feature_enabled( $feature_slug ) ) {
						continue;
					}
					?>

					<div class="tax-row tax-row--<?php echo esc_attr( $feature_slug ); ?>">
						<div class="tax-type"><?php echo esc_html( $feature['feature'] ); ?></div>
					</div>

					<?php
				endforeach;
				?>
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
		return esc_html__( 'Enables content classification.', 'classifai' );
	}

	/**
	 * Add any needed custom fields.
	 */
	public function add_custom_settings_fields() {
		$settings          = $this->get_settings();
		$provider_instance = $this->get_feature_provider_instance();
		$nlu_features      = array();
		$post_statuses     = get_post_statuses_for_language_settings();
		$post_types        = get_post_types_for_language_settings();
		$post_type_options = array();

		if ( ! empty( $provider_instance->nlu_features ) ) {
			$nlu_features = $provider_instance->nlu_features;
		}

		foreach ( $post_types as $post_type ) {
			$post_type_options[ $post_type->name ] = $post_type->label;
		}

		add_settings_field(
			'classification_mode',
			esc_html__( 'Classification mode', 'classifai' ),
			[ $this, 'render_radio_group' ],
			$this->get_option_name(),
			$this->get_option_name() . '_section',
			[
				'label_for'     => 'classification_mode',
				'default_value' => $settings['classification_mode'],
				'options'       => array(
					'manual_review'            => __( 'Manual review', 'classifai' ),
					'automatic_classification' => __( 'Automatic classification', 'classifai' ),
				),
			]
		);

		$method_options = array(
			'recommended_terms' => __( 'Recommend terms even if they do not exist on the site', 'classifai' ),
			'existing_terms'    => __( 'Only recommend terms that already exist on the site', 'classifai' ),
		);

		// Embeddings only supports existing terms.
		if ( isset( $settings['provider'] ) && ( OpenAIEmbeddings::ID === $settings['provider'] || AzureEmbeddings::ID === $settings['provider'] ) ) {
			unset( $method_options['recommended_terms'] );
			$settings['classification_method'] = 'existing_terms';
		}

		add_settings_field(
			'classification_method',
			esc_html__( 'Classification method', 'classifai' ),
			[ $this, 'render_radio_group' ],
			$this->get_option_name(),
			$this->get_option_name() . '_section',
			[
				'label_for'     => 'classification_method',
				'default_value' => $settings['classification_method'],
				'options'       => $method_options,
			]
		);

		foreach ( $nlu_features as $classify_by => $labels ) {
			add_settings_field(
				$classify_by,
				esc_html( $labels['feature'] ),
				[ $this, 'render_nlu_feature_settings' ],
				$this->get_option_name(),
				$this->get_option_name() . '_section',
				[
					'feature'       => $classify_by,
					'labels'        => $labels,
					'default_value' => $settings[ $classify_by ],
					'post_types'    => $settings['post_types'],
				]
			);
		}

		add_settings_field(
			'post_statuses',
			esc_html__( 'Post statuses', 'classifai' ),
			[ $this, 'render_checkbox_group' ],
			$this->get_option_name(),
			$this->get_option_name() . '_section',
			[
				'label_for'      => 'post_statuses',
				'options'        => $post_statuses,
				'default_values' => $settings['post_statuses'],
				'description'    => __( 'Choose which post statuses are allowed to use this feature.', 'classifai' ),
			]
		);

		add_settings_field(
			'post_types',
			esc_html__( 'Post types', 'classifai' ),
			[ $this, 'render_checkbox_group' ],
			$this->get_option_name(),
			$this->get_option_name() . '_section',
			[
				'label_for'      => 'post_types',
				'options'        => $post_type_options,
				'default_values' => $settings['post_types'],
				'description'    => __( 'Choose which post types are allowed to use this feature.', 'classifai' ),
			]
		);
	}

	/**
	 * Returns the default settings for the feature.
	 *
	 * @return array
	 */
	public function get_feature_default_settings(): array {
		return [
			'post_statuses'         => [
				'publish' => 'publish',
			],
			'post_types'            => [
				'post' => 'post',
			],
			'classification_mode'   => 'manual_review',
			'classification_method' => 'recommended_terms',
			'provider'              => NLU::ID,
		];
	}

	/**
	 * Sanitizes the default feature settings.
	 *
	 * @param array $new_settings Settings being saved.
	 * @return array
	 */
	public function sanitize_default_feature_settings( array $new_settings ): array {
		$settings          = $this->get_settings();
		$provider_instance = $this->get_feature_provider_instance();

		$new_settings['classification_mode'] = sanitize_text_field( $new_settings['classification_mode'] ?? $settings['classification_mode'] );

		$new_settings['classification_method'] = sanitize_text_field( $new_settings['classification_method'] ?? $settings['classification_method'] );

		// Embeddings only supports existing terms.
		if ( isset( $new_settings['provider'] ) && ( OpenAIEmbeddings::ID === $new_settings['provider'] || AzureEmbeddings::ID === $new_settings['provider'] ) ) {
			$new_settings['classification_method'] = 'existing_terms';
		}

		$new_settings['post_statuses'] = isset( $new_settings['post_statuses'] ) ? array_map( 'sanitize_text_field', $new_settings['post_statuses'] ) : $settings['post_statuses'];

		$new_settings['post_types'] = isset( $new_settings['post_types'] ) ? array_map( 'sanitize_text_field', $new_settings['post_types'] ) : $settings['post_types'];

		if ( ! empty( $provider_instance->nlu_features ) ) {
			foreach ( array_keys( $provider_instance->nlu_features ) as $feature_name ) {
				$new_settings[ $feature_name ]               = absint( $new_settings[ $feature_name ] ?? $settings[ $feature_name ] );
				$new_settings[ "{$feature_name}_threshold" ] = absint( $new_settings[ "{$feature_name}_threshold" ] ?? $settings[ "{$feature_name}_threshold" ] );
				$new_settings[ "{$feature_name}_taxonomy" ]  = sanitize_text_field( $new_settings[ "{$feature_name}_taxonomy" ] ?? $settings[ "{$feature_name}_taxonomy" ] );
			}
		}

		return $new_settings;
	}

	/**
	 * Get all feature taxonomies.
	 *
	 * @return array
	 */
	public function get_all_feature_taxonomies(): array {
		$feature_taxonomies = [];
		$provider_instance  = $this->get_feature_provider_instance();

		if ( empty( $provider_instance->nlu_features ) ) {
			return $feature_taxonomies;
		}

		foreach ( array_keys( $provider_instance->nlu_features ) as $feature_name ) {
			if ( ! get_classification_feature_enabled( $feature_name ) ) {
				continue;
			}

			$taxonomy   = get_classification_feature_taxonomy( $feature_name );
			$permission = check_term_permissions( $taxonomy );

			if ( is_wp_error( $permission ) ) {
				continue;
			}

			if ( 'post_tag' === $taxonomy ) {
				$taxonomy = 'tags';
			}

			if ( 'category' === $taxonomy ) {
				$taxonomy = 'categories';
			}

			$feature_taxonomies[] = $taxonomy;
		}

		return $feature_taxonomies;
	}

	/**
	 * Render the NLU features settings.
	 *
	 * @param array $args Settings for the inputs
	 */
	public function render_nlu_feature_settings( array $args ) {
		$feature = $args['feature'];
		$labels  = $args['labels'];

		$taxonomies = $this->get_supported_taxonomies( $args['post_types'] );
		$features   = $this->get_settings();
		$taxonomy   = isset( $features[ "{$feature}_taxonomy" ] ) ? $features[ "{$feature}_taxonomy" ] : $labels['taxonomy_default'];

		// Enable classification type
		$feature_args = [
			'label_for'  => $feature,
			'input_type' => 'checkbox',
		];

		$threshold_args = [
			'label_for'     => "{$feature}_threshold",
			'input_type'    => 'number',
			'default_value' => $labels['threshold_default'],
		];
		?>

		<legend class="screen-reader-text">
			<?php esc_html_e( 'Classification Taxonomy Settings', 'classifai' ); ?>
		</legend>

		<p>
			<?php $this->render_input( $feature_args ); ?>
			<label for="<?php echo esc_attr( $feature ); ?>">
				<?php esc_html_e( 'Enable', 'classifai' ); ?>
			</label>
		</p>

		<p>
			<label for="<?php echo esc_attr( "{$feature}_threshold" ); ?>">
				<?php echo esc_html( $labels['threshold'] ); ?>
			</label><br/>
			<?php $this->render_input( $threshold_args ); ?>
		</p>

		<?php if ( NLU::ID === $features['provider'] ) : ?>
		<p>
			<label for="classifai-settings-<?php echo esc_attr( "{$feature}_taxonomy" ); ?>">
				<?php echo esc_html( $labels['taxonomy'] ); ?>
			</label><br/>
			<select id="classifai-settings-<?php echo esc_attr( "{$feature}_taxonomy" ); ?>" name="<?php echo esc_attr( $this->get_option_name() ); ?>[<?php echo esc_attr( "{$feature}_taxonomy" ); ?>]">
				<?php foreach ( $taxonomies as $name => $singular_name ) : ?>
					<option value="<?php echo esc_attr( $name ); ?>" <?php selected( $taxonomy, esc_attr( $name ) ); ?>>
						<?php echo esc_html( $singular_name ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</p>
		<?php endif; ?>
		<?php
	}

	/**
	 * Return the list of supported taxonomies
	 *
	 * @param array $post_types Array of supported post types.
	 * @return array
	 */
	public function get_supported_taxonomies( array $post_types = [] ): array {
		$supported_post_types = [];

		if ( ! empty( $post_types ) ) {
			foreach ( $post_types as $post_type => $enabled ) {
				if ( ! empty( $enabled ) ) {
					$supported_post_types[] = $post_type;
				}
			}
		}

		$taxonomies = get_taxonomies( [], 'objects' );
		$taxonomies = array_filter( $taxonomies, 'is_taxonomy_viewable' );
		$supported  = [];

		foreach ( $taxonomies as $taxonomy ) {
			// Remove this taxonomy if it doesn't support at least one of our post types.
			if (
				(
					! empty( $supported_post_types ) &&
					empty( array_intersect( $supported_post_types, $taxonomy->object_type ) )
				) ||
				'post_format' === $taxonomy->name
			) {
				continue;
			}

			$supported[ $taxonomy->name ] = $taxonomy->labels->singular_name;
		}

		/**
		 * Filter taxonomies shown in settings.
		 *
		 * @since 3.0.0
		 * @hook classifai_feature_classification_setting_taxonomies
		 *
		 * @param {array} $supported Array of supported taxonomies.
		 * @param {object} $this Current instance of the class.
		 *
		 * @return {array} Array of taxonomies.
		 */
		return apply_filters( 'classifai_' . static::ID . '_setting_taxonomies', $supported, $this );
	}

	/**
	 * Generates feature setting data required for migration from
	 * ClassifAI < 3.0.0 to 3.0.0
	 *
	 * @return array
	 */
	public function migrate_settings() {
		$old_settings = get_option( 'classifai_watson_nlu', array() );
		$new_settings = $this->get_default_settings();

		if ( isset( $old_settings['authenticated'] ) && $old_settings['authenticated'] ) {
			$new_settings['provider'] = 'ibm_watson_nlu';

			// Status
			if ( isset( $old_settings['enable_content_classification'] ) ) {
				$new_settings['status'] = $old_settings['enable_content_classification'];
			}

			// Post types
			if ( isset( $old_settings['post_types'] ) ) {
				if ( is_array( $old_settings['post_types'] ) ) {
					foreach ( $old_settings['post_types'] as $post_type => $value ) {
						if ( 1 === $value ) {
							$new_settings['post_types'][ $post_type ] = $post_type;
							continue;
						} elseif ( is_null( $value ) ) {
							$new_settings['post_types'][ $post_type ] = '0';
							continue;
						}
						$new_settings['post_types'][ $post_type ] = $value;
					}
				}

				unset( $new_settings['post_types']['attachment'] );
			}

			// Post statuses
			if ( isset( $old_settings['post_statuses'] ) ) {
				if ( is_array( $old_settings['post_statuses'] ) ) {
					foreach ( $old_settings['post_statuses'] as $post_status => $value ) {
						if ( 1 === $value ) {
							$new_settings['post_statuses'][ $post_status ] = $post_status;
							continue;
						} elseif ( is_null( $value ) ) {
							$new_settings['post_statuses'][ $post_status ] = '0';
							continue;
						}
						$new_settings['post_statuses'][ $post_status ] = $value;
					}
				}
			}

			// Roles
			if ( isset( $old_settings['content_classification_roles'] ) ) {
				$new_settings['roles'] = $old_settings['content_classification_roles'];
			}

			// Users
			if ( isset( $old_settings['content_classification_users'] ) ) {
				$new_settings['users'] = $old_settings['content_classification_users'];
			}

			// Provider.
			if ( isset( $old_settings['credentials'] ) && isset( $old_settings['credentials']['watson_url'] ) ) {
				$new_settings['ibm_watson_nlu']['endpoint_url'] = $old_settings['credentials']['watson_url'];
			}

			if ( isset( $old_settings['credentials'] ) && isset( $old_settings['credentials']['watson_username'] ) ) {
				$new_settings['ibm_watson_nlu']['username'] = $old_settings['credentials']['watson_username'];
			}

			if ( isset( $old_settings['credentials'] ) && isset( $old_settings['credentials']['watson_password'] ) ) {
				$new_settings['ibm_watson_nlu']['password'] = $old_settings['credentials']['watson_password'];
			}

			if ( isset( $old_settings['classification_mode'] ) ) {
				$new_settings['classification_mode'] = $old_settings['classification_mode'];
			}

			if ( isset( $old_settings['classification_method'] ) ) {
				$new_settings['classification_method'] = $old_settings['classification_method'];
			}

			if ( isset( $old_settings['features'] ) ) {
				foreach ( $old_settings['features'] as $feature => $value ) {
					$new_settings[ $feature ] = $value;
				}
			}

			if ( isset( $old_settings['authenticated'] ) ) {
				$new_settings['ibm_watson_nlu']['authenticated'] = $old_settings['authenticated'];
			}

			if ( isset( $old_settings['content_classification_user_based_opt_out'] ) ) {
				$new_settings['user_based_opt_out'] = $old_settings['content_classification_user_based_opt_out'];
			}
		} else {
			$old_settings = get_option( 'classifai_openai_embeddings', array() );

			if ( isset( $old_settings['enable_classification'] ) ) {
				$new_settings['status'] = $old_settings['enable_classification'];
			}

			$new_settings['provider'] = 'openai_embeddings';

			if ( isset( $old_settings['api_key'] ) ) {
				$new_settings['openai_embeddings']['api_key'] = $old_settings['api_key'];
			}

			if ( isset( $old_settings['taxonomies'] ) ) {
				foreach ( $old_settings['taxonomies'] as $feature => $value ) {
					$new_settings[ $feature ] = $value;
				}
			}

			if ( isset( $old_settings['authenticated'] ) ) {
				$new_settings['openai_embeddings']['authenticated'] = $old_settings['authenticated'];
			}

			if ( isset( $old_settings['post_statuses'] ) ) {
				$new_settings['post_statuses'] = $old_settings['post_statuses'];
			}

			if ( isset( $old_settings['post_types'] ) ) {
				$new_settings['post_types'] = $old_settings['post_types'];
			}

			if ( isset( $old_settings['classification_roles'] ) ) {
				$new_settings['roles'] = $old_settings['classification_roles'];
			}

			if ( isset( $old_settings['classification_users'] ) ) {
				$new_settings['users'] = $old_settings['classification_users'];
			}

			if ( isset( $old_settings['classification_user_based_opt_out'] ) ) {
				$new_settings['user_based_opt_out'] = $old_settings['classification_user_based_opt_out'];
			}
		}

		return $new_settings;
	}

	/**
	 * Get status of embeddings generation process.
	 *
	 * @return bool
	 */
	public function is_embeddings_generation_in_progress(): bool {
		$is_in_progress    = false;
		$provider_instance = $this->get_feature_provider_instance();
		if ( $provider_instance && method_exists( $provider_instance, 'is_embeddings_generation_in_progress' ) ) {
			$is_in_progress = $provider_instance->is_embeddings_generation_in_progress();
		}
		return $is_in_progress;
	}
}
