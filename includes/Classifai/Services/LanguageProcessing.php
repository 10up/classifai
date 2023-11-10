<?php
/**
 * Service definition for Language Processing
 */

namespace Classifai\Services;

use Classifai\Admin\SavePostHandler;
use Classifai\Features\AudioTranscriptsGeneration;
use Classifai\Features\ExcerptGeneration;
use Classifai\Features\ContentResizing;
use Classifai\Features\TitleGeneration;

use function Classifai\find_provider_class;
use WP_REST_Server;
use WP_REST_Request;
use WP_Error;

class LanguageProcessing extends Service {

	/**
	 * LanguageProcessing constructor.
	 */
	public function __construct() {
		parent::__construct(
			__( 'Language Processing', 'classifai' ),
			'language_processing',
			$this->register_service_providers()
		);
	}

	/**
	 * Init service for Language Processing.
	 */
	public function init() {
		parent::init();
		add_action( 'rest_api_init', [ $this, 'register_endpoints' ] );
	}

	public function register_service_providers() {
		return apply_filters(
			'classifai_language_processing_service_providers',
			[
				// 'Classifai\Providers\Watson\NLU',
				'Classifai\Providers\OpenAI\ChatGPT',
				// 'Classifai\Providers\OpenAI\Embeddings',
				'Classifai\Providers\OpenAI\Whisper',
				'Classifai\Providers\Azure\Speech',
			]
		);
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
				'methods'             => WP_REST_Server::READABLE,
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
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'synthesize_speech_from_text' ),
				'args'                => array(
					'id' => array(
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
						'description'       => esc_html__( 'ID of post to run text to speech conversion on.', 'classifai' ),
					),
				),
				'permission_callback' => [ $this, 'speech_synthesis_permissions_check' ],
			]
		);
	}

	/**
	 * Handle request to generate tags for given post ID.
	 *
	 * @param WP_REST_Request $request The full request object.
	 *
	 * @return array|bool|string|WP_Error
	 */
	public function generate_post_tags( WP_REST_Request $request ) {
		try {
			$post_id = $request->get_param( 'id' );

			if ( empty( $post_id ) ) {
				return new WP_Error( 'post_id_required', esc_html__( 'Post ID is required to classify post.', 'classifai' ) );
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
			return rest_ensure_response( [ 'terms' => $taxonomy_terms ] );
		} catch ( \Exception $e ) {
			return new WP_Error( 'request_failed', $e->getMessage() );
		}
	}

	/**
	 * Check if a given request has access to generate tags
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|bool
	 */
	public function generate_post_tags_permissions_check( WP_REST_Request $request ) {
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
		foreach ( [ 'category', 'keyword', 'concept', 'entity' ] as $feature ) {
			if ( ! \Classifai\get_feature_enabled( $feature ) ) {
				continue;
			}

			$taxonomy   = \Classifai\get_feature_taxonomy( $feature );
			$permission = $this->check_term_permissions( $taxonomy );

			if ( is_wp_error( $permission ) ) {
				return $permission;
			}
		}

		$post_status   = get_post_status( $post_id );
		$supported     = \Classifai\get_supported_post_types();
		$post_statuses = \Classifai\get_supported_post_statuses();

		// Check if processing allowed.
		if ( ! in_array( $post_status, $post_statuses, true ) || ! in_array( $post_type, $supported, true ) || ! \Classifai\language_processing_features_enabled() ) {
			return new WP_Error( 'not_enabled', esc_html__( 'Language Processing not enabled for current post.', 'classifai' ) );
		}

		return true;
	}

	/**
	 * Generates text to speech for a post using REST.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return \WP_REST_Response|WP_Error
	 */
	public function synthesize_speech_from_text( WP_REST_Request $request ) {
		$post_id           = $request->get_param( 'id' );
		$save_post_handler = new SavePostHandler();
		$attachment_id     = $save_post_handler->synthesize_speech( $post_id );

		if ( is_wp_error( $attachment_id ) ) {
			return rest_ensure_response(
				array(
					'success' => false,
					'code'    => $attachment_id->get_error_code(),
					'message' => $attachment_id->get_error_message(),
				)
			);
		}

		return rest_ensure_response(
			array(
				'success'  => true,
				'audio_id' => $attachment_id,
			)
		);
	}

	/**
	 * Check if a given request has access to generate audio for the post.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|bool
	 */
	public function speech_synthesis_permissions_check( WP_REST_Request $request ) {
		$post_id = $request->get_param( 'id' );

		if ( ! empty( $post_id ) && current_user_can( 'edit_post', $post_id ) ) {
			$post_type = get_post_type( $post_id );
			$supported = \Classifai\get_tts_supported_post_types();

			// Check if processing allowed.
			if ( ! in_array( $post_type, $supported, true ) ) {
				return new WP_Error( 'not_enabled', esc_html__( 'Azure Speech synthesis is not enabled for current post.', 'classifai' ) );
			}

			return true;
		}

		return false;
	}
}
