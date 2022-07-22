<?php
/**
 * Service definition for Language Processing
 */

namespace Classifai\Services;

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
	 * Render Recommended content over AJAX.
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

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $this->provider_classes[0]->render_recommended_content( $attributes );
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
				'methods'             => 'GET',
				'callback'            => [ $this, 'provider_endpoint_callback' ],
				'args'                => [ 'route' => [ 'reward' ] ],
				'permission_callback' => '__return_true',
			]
		);
	}

	/**
	 * Single call back to pass the route callback to the provider.
	 *
	 * @param \WP_REST_Request $request The full request object.
	 *
	 * @return array|bool|string|\WP_Error
	 */
	public function provider_endpoint_callback( $request ) {
		$response   = true;
		$event_id   = $request->get_param( 'eventId' );
		$attributes = $request->get_attributes();
		$route      = empty( $attributes['args']['route'] ) ? false : $attributes['args']['route'][0];

		// If no args, respond 404
		if ( false === $route ) {
			return new \WP_Error( 'no route', 'No route indicated for the provider class to use.', array( 'status' => 404 ) );
		}

		if ( 'reward' === $route ) {
			if ( empty( $event_id ) ) {
				return new \WP_Error( 'Bad Request', 'Event ID required.', array( 'status' => 400 ) );
			}

			// Send reward to personalizer.
			if ( isset( $this->provider_classes[0] ) ) {
				$response = $this->provider_classes[0]->personalizer_send_reward( $event_id );
			}
		}

		return $response;
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
