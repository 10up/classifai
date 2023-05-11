<?php
/**
 * Service definition for Recommended Content
 */

namespace Classifai\Services;

use function Classifai\find_provider_class;
use WP_REST_Server;
use WP_REST_Request;
use WP_Error;

class Personalizer extends Service {

	/**
	 * Personalizer constructor.
	 */
	public function __construct() {
		parent::__construct(
			__( 'Recommended Content', 'classifai' ),
			'personalizer',
			[
				'Classifai\Providers\Azure\Personalizer',
			]
		);
	}

	/**
	 * Register the rest API endpoints
	 */
	public function init() {
		parent::init();
		add_action( 'rest_api_init', [ $this, 'register_endpoints' ] );
		add_action( 'wp_ajax_classifai_render_recommended_content', [ $this, 'ajax_render_recommended_content' ] );
		add_action( 'wp_ajax_nopriv_classifai_render_recommended_content', [ $this, 'ajax_render_recommended_content' ] );
		add_action( 'save_post', [ $this, 'maybe_clear_transient' ] );
	}

	/**
	 * Render Recommended Content over AJAX.
	 *
	 * @return void
	 */
	public function ajax_render_recommended_content() {
		check_ajax_referer( 'classifai-recommended-block', 'security' );

		if ( ! isset( $_POST['contentPostType'] ) || empty( $_POST['contentPostType'] ) ) {
			esc_html_e( 'No results found.', 'classifai' );
			exit();
		}

		$attributes = array(
			'displayLayout'          => isset( $_POST['displayLayout'] ) ? sanitize_text_field( $_POST['displayLayout'] ) : 'grid',
			'contentPostType'        => sanitize_text_field( $_POST['contentPostType'] ),
			'excludeId'              => isset( $_POST['excludeId'] ) ? absint( $_POST['excludeId'] ) : 0,
			'displayPostExcerpt'     => isset( $_POST['displayPostExcerpt'] ) ? filter_var( $_POST['displayPostExcerpt'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE ) : false,
			'displayAuthor'          => isset( $_POST['displayAuthor'] ) ? filter_var( $_POST['displayAuthor'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE ) : false,
			'displayPostDate'        => isset( $_POST['displayPostDate'] ) ? filter_var( $_POST['displayPostDate'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE ) : false,
			'displayFeaturedImage'   => isset( $_POST['displayFeaturedImage'] ) ? filter_var( $_POST['displayFeaturedImage'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE ) : true,
			'addLinkToFeaturedImage' => isset( $_POST['addLinkToFeaturedImage'] ) ? filter_var( $_POST['addLinkToFeaturedImage'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE ) : false,
			'columns'                => isset( $_POST['columns'] ) ? absint( $_POST['columns'] ) : 3,
			'numberOfItems'          => isset( $_POST['numberOfItems'] ) ? absint( $_POST['numberOfItems'] ) : 3,
		);

		if ( isset( $_POST['taxQuery'] ) && ! empty( $_POST['taxQuery'] ) ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			foreach ( $_POST['taxQuery'] as $key => $value ) {
				$attributes['taxQuery'][ $key ] = array_map( 'absint', $value );
			}
		}

		$provider = find_provider_class( $this->provider_classes ?? [], 'Personalizer' );

		if ( ! is_wp_error( $provider ) ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo $provider->render_recommended_content( $attributes );
		}

		exit();
	}

	/**
	 * Create endpoints for services
	 */
	public function register_endpoints() {
		register_rest_route(
			'classifai/v1',
			'personalizer/reward/(?P<eventId>[a-zA-Z0-9-]+)',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'reward_endpoint_callback' ],
				'args'                => [
					'eventId'  => [
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
						'description'       => esc_html__( 'Event ID to track', 'classifai' ),
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
						'validate_callback' => 'rest_validate_request_arg',
						'description'       => esc_html__( 'Reward we want to send', 'classifai' ),
					],
					'route'    => [
						'required'          => false,
						'type'              => 'string',
						'default'           => 'reward',
						'sanitize_callback' => 'sanitize_text_field',
						'validate_callback' => 'rest_validate_request_arg',
						'description'       => esc_html__( 'Route we want to call', 'classifai' ),
					],
				],
				'permission_callback' => [ $this, 'reward_permissions_check' ],
			]
		);
	}

	/**
	 * Single call back to pass the route callback to the provider.
	 *
	 * @param WP_REST_Request $request The full request object.
	 *
	 * @return array|bool|string|WP_Error
	 */
	public function reward_endpoint_callback( WP_REST_Request $request ) {
		$response = true;
		$event_id = $request->get_param( 'eventId' );
		$reward   = ( '1' === $request->get_param( 'rewarded' ) ) ? 1 : 0;
		$route    = $request->get_param( 'route' ) ?? false;

		// If no args, respond 404.
		if ( false === $route ) {
			return new WP_Error( 'no_route', esc_html__( 'No route indicated for the provider class to use.', 'classifai' ), [ 'status' => 404 ] );
		}

		if ( 'reward' === $route ) {
			if ( empty( $event_id ) ) {
				return new WP_Error( 'bad_request', esc_html__( 'Event ID required.', 'classifai' ), [ 'status' => 400 ] );
			}

			// Find the right provider class.
			$provider = find_provider_class( $this->provider_classes ?? [], 'Personalizer' );

			// Ensure we have a provider class. Should never happen but :shrug:
			if ( is_wp_error( $provider ) ) {
				return $provider;
			}

			// Send reward to personalizer.
			$response = $provider->personalizer_send_reward( $event_id, $reward );
		}

		return rest_ensure_response( $response );
	}

	/**
	 * Check if a given request has access to send reward.
	 *
	 * This check ensures that we are properly authenticated.
	 * TODO: add additional checks here, maybe a nonce check or rate limiting?
	 *
	 * @return WP_Error|bool
	 */
	public function reward_permissions_check() {
		$settings = \Classifai\get_plugin_settings( 'language_processing', 'Personalizer' );

		// Check if valid authentication is in place.
		if ( empty( $settings ) || ( isset( $settings['authenticated'] ) && false === $settings['authenticated'] ) ) {
			return new WP_Error( 'auth', esc_html__( 'Please set up valid authentication with Azure.', 'classifai' ) );
		}

		return true;
	}

	/**
	 * Maybe clear transients for recent actions.
	 *
	 * @param int $post_id Post Id.
	 * @return void
	 */
	public function maybe_clear_transient( $post_id ) {
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
}
