<?php

namespace Classifai\Features;

use Classifai\Services\LanguageProcessing;
use Classifai\Providers\Watson\NLU;
use Classifai\Providers\OpenAI\Embeddings;
use WP_REST_Server;
use WP_REST_Request;
use WP_Error;

use function Classifai\get_post_statuses_for_language_settings;
use function Classifai\get_post_types_for_language_settings;

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
			NLU::ID        => __( 'IBM Watson NLU', 'classifai' ),
			Embeddings::ID => __( 'OpenAI Embeddings', 'classifai' ),
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
		// foreach ( [ 'category', 'keyword', 'concept', 'entity' ] as $feature ) {
		// 	if ( ! get_feature_enabled( $feature ) ) {
		// 		continue;
		// 	}

		// 	$taxonomy   = get_feature_taxonomy( $feature );
		// 	$permission = check_term_permissions( $taxonomy );

		// 	if ( is_wp_error( $permission ) ) {
		// 		return $permission;
		// 	}
		// }

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
	 * Get the description for the enable field.
	 *
	 * @return string
	 */
	public function get_enable_description(): string {
		return esc_html__( 'Enables the automatic content classification of posts.', 'classifai' );
	}

	/**
	 * Add any needed custom fields.
	 */
	public function add_custom_settings_fields() {
		$settings      = $this->get_settings();
		$post_statuses = get_post_statuses_for_language_settings();

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

		$post_types        = get_post_types_for_language_settings();
		$post_type_options = array();

		foreach ( $post_types as $post_type ) {
			$post_type_options[ $post_type->name ] = $post_type->label;
		}

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
			'post_statuses' => [
				'publish' => 1,
			],
			'post_types'    => [
				'post' => 1,
			],
			'provider'      => NLU::ID,
		];
	}

	/**
	 * Sanitizes the default feature settings.
	 *
	 * @param array $new_settings Settings being saved.
	 * @return array
	 */
	public function sanitize_default_feature_settings( array $new_settings ): array {
		$settings = $this->get_settings();

		$new_settings['post_statuses'] = isset( $new_settings['post_statuses'] ) ? array_map( 'sanitize_text_field', $new_settings['post_statuses'] ) : $settings['post_statuses'];
		$new_settings['post_types']    = isset( $new_settings['post_types'] ) ? array_map( 'sanitize_text_field', $new_settings['post_types'] ) : $settings['post_types'];

		return $new_settings;
	}

	/**
	 * Runs the feature.
	 *
	 * @param mixed ...$args Arguments required by the feature depending on the provider selected.
	 * @return mixed
	 */
	public function run( ...$args ) {
		$settings          = $this->get_settings();
		$provider_id       = $settings['provider'] ?? NLU::ID;
		$provider_instance = $this->get_feature_provider_instance( $provider_id );
		$result            = '';

		if ( NLU::ID === $provider_instance::ID ) {
			/** @var NLU $provider_instance */
			$result = call_user_func_array(
				[ $provider_instance, 'classify' ],
				[ ...$args ]
			);
		} elseif ( Embeddings::ID === $provider_instance::ID ) {
			/** @var Embeddings $provider_instance */
			$result = call_user_func_array(
				[ $provider_instance, 'generate_embeddings_for_post' ],
				[ ...$args ]
			);
		}

		return apply_filters(
			'classifai_' . static::ID . '_run',
			$result,
			$provider_instance,
			$args,
			$this
		);
	}

	/**
	 * The list of post types that support classification.
	 *
	 * @return array
	 */
	public function get_supported_post_types(): array {
		$settings   = $this->get_settings();
		$post_types = [];

		foreach ( $settings['post_types'] as $post_type => $enabled ) {
			if ( ! empty( $enabled ) ) {
				$post_types[] = $post_type;
			}
		}

		/**
		 * Filter post types supported for classification.
		 *
		 * @since 3.0.0
		 * @hook classifai_feature_classification_post_types
		 *
		 * @param {array} $post_types Array of post types to be classified.
		 *
		 * @return {array} Array of post types.
		 */
		$post_types = apply_filters( 'classifai_' . static::ID . '_post_types', $post_types );

		return $post_types;
	}

	/**
	 * The list of post statuses that support classification.
	 *
	 * @return array
	 */
	public function get_supported_post_statuses(): array {
		$settings      = $this->get_settings();
		$post_statuses = [];

		foreach ( $settings['post_statuses'] as $post_status => $enabled ) {
			if ( ! empty( $enabled ) ) {
				$post_statuses[] = $post_status;
			}
		}

		/**
		 * Filter post statuses supported for classification.
		 *
		 * @since 3.0.0
		 * @hook classifai_feature_classification_post_statuses
		 *
		 * @param {array} $post_types Array of post statuses to be classified.
		 *
		 * @return {array} Array of post statuses.
		 */
		$post_statuses = apply_filters( 'classifai_' . static::ID . '_post_statuses', $post_statuses );

		return $post_statuses;
	}

	/**
	 * Get all feature taxonomies.
	 *
	 * @return array|WP_Error
	 */
	public function get_all_feature_taxonomies() {
		// Get all feature taxonomies.
		$feature_taxonomies = [];
		foreach ( [ 'category', 'keyword', 'concept', 'entity' ] as $feature ) {
			if ( get_feature_enabled( $feature ) ) {
				$taxonomy   = get_feature_taxonomy( $feature );
				$permission = check_term_permissions( $taxonomy );

				if ( is_wp_error( $permission ) ) {
					return $permission;
				}

				if ( 'post_tag' === $taxonomy ) {
					$taxonomy = 'tags';
				}

				if ( 'category' === $taxonomy ) {
					$taxonomy = 'categories';
				}

				$feature_taxonomies[] = $taxonomy;
			}
		}

		return $feature_taxonomies;
	}
}
