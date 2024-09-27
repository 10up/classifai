<?php

namespace Classifai\Features;

use Classifai\Services\Personalizer as PersonalizerService;
use Classifai\Providers\Azure\Personalizer as PersonalizerProvider;
use Classifai\Providers\AWS\AmazonPersonalize as PersonalizeProvider;
use Classifai\Blocks;
use WP_REST_Server;
use WP_REST_Request;
use WP_Error;

/**
 * Class RecommendedContent
 */
class RecommendedContent extends Feature {
	/**
	 * ID of the current feature.
	 *
	 * @var string
	 */
	const ID = 'feature_recommended_content';

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->label = __( 'Recommended Content', 'classifai' );

		// Contains all providers that are registered to the service.
		$this->provider_instances = $this->get_provider_instances( PersonalizerService::get_service_providers() );

		// Contains just the providers this feature supports.
		$this->supported_providers = [
			PersonalizeProvider::ID  => __( 'Amazon AWS Personalize', 'classifai' ),
			PersonalizerProvider::ID => __( 'Microsoft Azure AI Personalizer', 'classifai' ),
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
	 *
	 * This only runs if is_feature_enabled() returns true.
	 */
	public function feature_setup() {
		// Register the block.
		Blocks\setup();

		// AJAX callback for rendering recommended content.
		add_action( 'wp_ajax_classifai_render_recommended_content', [ $this, 'ajax_render_recommended_content' ] );
		add_action( 'wp_ajax_nopriv_classifai_render_recommended_content', [ $this, 'ajax_render_recommended_content' ] );

		add_action( 'save_post', [ $this, 'maybe_clear_transient' ] );
	}

	/**
	 * Register any needed endpoints.
	 */
	public function register_endpoints() {
		register_rest_route(
			'classifai/v1',
			'personalizer/reward/(?P<itemId>\d+)',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'rest_endpoint_callback' ],
				'args'                => [
					'itemId'   => [
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
						'description'       => esc_html__( 'Item ID to track', 'classifai' ),
					],
					'event'    => [
						'required'          => false,
						'type'              => 'object',
						'properties'        => [
							'id'   => [
								'type' => 'string',
							],
							'type' => [
								'type' => 'string',
							],
						],
						'sanitize_callback' => 'sanitize_text_field',
						'description'       => esc_html__( 'Event details to track', 'classifai' ),
					],
					'rewarded' => [
						'required'          => false,
						'type'              => 'string',
						'enum'              => [
							'0',
							'1',
						],
						'default'           => '0',
						'sanitize_callback' => 'sanitize_text_field',
						'description'       => esc_html__( 'Reward value we want to send', 'classifai' ),
					],
				],
				'permission_callback' => [ $this, 'permissions_check' ],
			]
		);
	}

	/**
	 * Check if a given request has access to send reward.
	 *
	 * This check ensures that we are properly authenticated.
	 * TODO: add additional checks here, maybe a nonce check or rate limiting?
	 *
	 * @return WP_Error|bool
	 */
	public function permissions_check() {
		// Check if valid authentication is in place.
		if ( ! $this->is_enabled() ) {
			return new WP_Error( 'not_enabled', esc_html__( 'Recommended Content not currently enabled.', 'classifai' ) );
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

		if ( strpos( $route, '/classifai/v1/personalizer/reward' ) === 0 ) {
			return rest_ensure_response(
				$this->run(
					$request->get_param( 'itemId' ),
					'reward',
					[
						'event'  => $request->get_param( 'event' ),
						'reward' => $request->get_param( 'rewarded' ),
					]
				)
			);
		}

		return parent::rest_endpoint_callback( $request );
	}

	/**
	 * Render recommended content over AJAX.
	 */
	public function ajax_render_recommended_content() {
		check_ajax_referer( 'classifai-recommended-block', 'security' );

		if ( ! isset( $_POST['contentPostType'] ) || empty( $_POST['contentPostType'] ) ) {
			esc_html_e( 'No results found.', 'classifai' );
			exit();
		}

		$attributes = [
			'displayLayout'          => isset( $_POST['displayLayout'] ) ? sanitize_text_field( wp_unslash( $_POST['displayLayout'] ) ) : 'grid',
			'contentPostType'        => sanitize_text_field( wp_unslash( $_POST['contentPostType'] ) ),
			'excludeId'              => isset( $_POST['excludeId'] ) ? absint( $_POST['excludeId'] ) : 0,
			'displayPostExcerpt'     => isset( $_POST['displayPostExcerpt'] ) ? filter_var( wp_unslash( $_POST['displayPostExcerpt'] ), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE ) : false,
			'displayAuthor'          => isset( $_POST['displayAuthor'] ) ? filter_var( wp_unslash( $_POST['displayAuthor'] ), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE ) : false,
			'displayPostDate'        => isset( $_POST['displayPostDate'] ) ? filter_var( wp_unslash( $_POST['displayPostDate'] ), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE ) : false,
			'displayFeaturedImage'   => isset( $_POST['displayFeaturedImage'] ) ? filter_var( wp_unslash( $_POST['displayFeaturedImage'] ), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE ) : true,
			'addLinkToFeaturedImage' => isset( $_POST['addLinkToFeaturedImage'] ) ? filter_var( wp_unslash( $_POST['addLinkToFeaturedImage'] ), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE ) : false,
			'columns'                => isset( $_POST['columns'] ) ? absint( $_POST['columns'] ) : 3,
			'numberOfItems'          => isset( $_POST['numberOfItems'] ) ? absint( $_POST['numberOfItems'] ) : 3,
		];

		if ( isset( $_POST['taxQuery'] ) && ! empty( $_POST['taxQuery'] ) ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
			foreach ( $_POST['taxQuery'] as $key => $value ) {
				$attributes['taxQuery'][ $key ] = array_map( 'absint', $value );
			}
		}

		$provider_instance = $this->get_feature_provider_instance();

		echo $provider_instance->render_recommended_content( $attributes ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		exit();
	}

	/**
	 * Maybe clear transients for recent actions.
	 *
	 * @param int $post_id Post Id.
	 */
	public function maybe_clear_transient( int $post_id ) {
		global $wpdb;

		$post_type = get_post_type( $post_id );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$transients = $wpdb->get_col( $wpdb->prepare( "SELECT `option_name` FROM {$wpdb->options} WHERE  option_name LIKE %s", '_transient_classifai_actions_' . $post_type . '%' ) );

		// Delete all transients
		if ( ! empty( $transients ) ) {
			foreach ( $transients as $transient ) {
				delete_transient( str_replace( '_transient_', '', $transient ) );
			}
		}
	}

	/**
	 * Get the description for the enable field.
	 *
	 * @return string
	 */
	public function get_enable_description(): string {
		return esc_html__( 'Enables the ability to generate recommended content data for the block.', 'classifai' );
	}

	/**
	 * Returns the default settings for the feature.
	 *
	 * @return array
	 */
	public function get_feature_default_settings(): array {
		return [
			'provider' => PersonalizerProvider::ID,
		];
	}

	/**
	 * Generates feature setting data required for migration from
	 * ClassifAI < 3.0.0 to 3.0.0
	 *
	 * @return array
	 */
	public function migrate_settings() {
		$old_settings = get_option( 'classifai_personalizer', array() );
		$new_settings = $this->get_default_settings();

		if ( isset( $old_settings['enable_recommended_content'] ) ) {
			$new_settings['status'] = $old_settings['enable_recommended_content'];
		}

		$new_settings['provider'] = PersonalizerProvider::ID;

		if ( isset( $old_settings['url'] ) ) {
			$new_settings[ PersonalizerProvider::ID ]['endpoint_url'] = $old_settings['url'];
		}

		if ( isset( $old_settings['api_key'] ) ) {
			$new_settings[ PersonalizerProvider::ID ]['api_key'] = $old_settings['api_key'];
		}

		if ( isset( $old_settings['authenticated'] ) ) {
			$new_settings[ PersonalizerProvider::ID ]['authenticated'] = $old_settings['authenticated'];
		}

		if ( isset( $old_settings['recommended_content_roles'] ) ) {
			$new_settings['roles'] = $old_settings['recommended_content_roles'];
		}

		if ( isset( $old_settings['recommended_content_users'] ) ) {
			$new_settings['users'] = $old_settings['recommended_content_users'];
		}

		if ( isset( $old_settings['recommended_content_user_based_opt_out'] ) ) {
			$new_settings['user_based_opt_out'] = $old_settings['recommended_content_user_based_opt_out'];
		}

		return $new_settings;
	}
}
