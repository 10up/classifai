<?php
/**
 * Service definition for Language Processing
 */

namespace Classifai\Services;

use Classifai\Admin\SavePostHandler;

class LanguageProcessing extends Service {

	/**
	 * LanguageProcessing constructor.
	 */
	public function __construct() {
		parent::__construct( __( 'Language Processing', 'classifai' ), 'language_processing', [ 'Classifai\Providers\Watson\NLU', 'Classifai\Providers\Azure\TextToSpeech' ] );
	}

	/**
	 * Init service for Language Processing.
	 */
	public function init() {
		parent::init();
		add_action( 'rest_api_init', [ $this, 'register_endpoints' ] );
	}

	/**
	 * Create endpoints for Language Processing.
	 *
	 * @since 1.8.0
	 */
	public function register_endpoints() {
		register_rest_route(
			'classifai/v1',
			'generate-tags/(?P<id>\d+)',
			[
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => [ $this, 'generate_post_tags' ],
				'args'                => array(
					'id' => array(
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
						'description'       => esc_html__( 'Post ID to generate tags.', 'classifai' ),
					),
				),
				'permission_callback' => [ $this, 'generate_post_tags_permissions_check' ],
			]
		);

		register_rest_route(
			'classifai/v1',
			'synthesize-speech/(?P<id>\d+)',
			[
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'synthesize_speech_from_text' ),
				'args'                => array(
					'id' => array(
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
						'description'       => esc_html__( 'Post ID to generate tags.', 'classifai' ),
					),
				),
				'permission_callback' => [ $this, 'speech_synthesis_permissions_check' ],
			]
		);
	}

	/**
	 * Handle request to generate tags for given post ID.
	 *
	 * @param \WP_REST_Request $request The full request object.
	 *
	 * @return array|bool|string|\WP_Error
	 */
	public function generate_post_tags( $request ) {
		try {
			$post_id = $request->get_param( 'id' );

			if ( empty( $post_id ) ) {
				return new \WP_Error( 'post_id_required', esc_html__( 'Post ID is required to classify post.', 'classifai' ) );
			}

			$taxonomy_terms    = [];
			$features          = [ 'category', 'keyword', 'concept', 'entity' ];
			$save_post_handler = new SavePostHandler();

			// Process post content.
			$result = $save_post_handler->classify( $post_id );
			if ( is_wp_error( $result ) ) {
				return $result;
			}

			foreach ( $features as $feature ) {
				$taxonomy = \Classifai\get_feature_taxonomy( $feature );
				$terms    = wp_get_object_terms( $post_id, $taxonomy );
				if ( ! is_wp_error( $terms ) ) {
					foreach ( $terms as $term ) {
						$taxonomy_terms[ $taxonomy ][] = $term->term_id;
					}
				}
			}

			// Return taxonomy terms.
			return [ 'terms' => $taxonomy_terms ];
		} catch ( \Exception $e ) {
			return new \WP_Error( 'request_failed', $e->getMessage() );
		}
	}

	/**
	 * Check if a given request has access to generate tags
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|bool
	 */
	public function generate_post_tags_permissions_check( $request ) {
		$post_id = $request->get_param( 'id' );
		if ( ! empty( $post_id ) && current_user_can( 'edit_post', $post_id ) ) {
			$post_type     = get_post_type( $post_id );
			$post_status   = get_post_status( $post_id );
			$supported     = \Classifai\get_supported_post_types();
			$post_statuses = \Classifai\get_supported_post_statuses();

			// Check if processing allowed.
			if ( ! in_array( $post_status, $post_statuses, true ) || ! in_array( $post_type, $supported, true ) || ! \Classifai\language_processing_features_enabled() ) {
				return new \WP_Error( 'not_enabled', esc_html__( 'Language Processing not enabled for current post.', 'classifai' ) );
			}
			return true;
		}
		return false;
	}

	/**
	 * Generates text to speech for a post using REST.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return boolean
	 */
	public function synthesize_speech_from_text( $request ) {
		$post_id           = $request->get_param( 'id' );
		$save_post_handler = new SavePostHandler();
		$attachment_id     = $save_post_handler->synthesize_speech( $post_id );

		if ( is_wp_error( $attachment_id ) ) {
			return false;
		}

		return $attachment_id;
	}

	/**
	 * Check if a given request has access to generate audio for the post.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|bool
	 */
	public function speech_synthesis_permissions_check( $request ) {
		return true;
		$post_id = $request->get_param( 'id' );

		if ( empty( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return false;
		}

		if ( ! empty( $post_id ) && current_user_can( 'edit_post', $post_id ) ) {
			$post_type = get_post_type( $post_id );
			$supported = \Classifai\get_supported_post_types_for_azure_speech_to_text();

			// Check if processing allowed.
			if ( ! in_array( $post_type, $supported, true ) ) {
				return new \WP_Error( 'not_enabled', esc_html__( 'Azure Speech synthesis is not enabled for current post.', 'classifai' ) );
			}
		}

		return true;
	}
}
