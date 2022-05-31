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
		add_action( 'wp_ajax_render_recommended_content', [ $this, 'ajax_render_recommended_content' ] );
		add_action( 'wp_ajax_nopriv_render_recommended_content', [ $this, 'ajax_render_recommended_content' ] );
	}

	/**
	 * Render Recommended content over AJAX.
	 *
	 * @return void
	 */
	public function ajax_render_recommended_content() {
		check_ajax_referer( 'classifai-recommended-block', 'security' );
		$attributes = array(
			'contentPostType'        => sanitize_text_field( $_POST['contentPostType'] ),
			'displayPostExcept'      => filter_var( $_POST['displayPostExcept'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE ),
			'displayAuthor'          => filter_var( $_POST['displayAuthor'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE ),
			'displayPostDate'        => filter_var( $_POST['displayPostDate'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE ),
			'displayFeaturedImage'   => filter_var( $_POST['displayFeaturedImage'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE ),
			'addLinkToFeaturedImage' => filter_var( $_POST['addLinkToFeaturedImage'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE ),
		);
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
}
