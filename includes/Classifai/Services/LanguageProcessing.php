<?php
/**
 * Service definition for Language Processing
 */

namespace Classifai\Services;

use Classifai\Admin\SavePostHandler;
use Classifai\Providers\Azure\TextToSpeech;
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
			[
				'Classifai\Providers\Watson\NLU',
				'Classifai\Providers\OpenAI\ChatGPT',
				'Classifai\Providers\OpenAI\Embeddings',
				'Classifai\Providers\OpenAI\Whisper',
				'Classifai\Providers\Azure\TextToSpeech',
			]
		);
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
			'classifai/v1/openai',
			'generate-excerpt/(?P<id>\d+)',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'generate_post_excerpt' ],
				'args'                => [
					'id' => [
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
						'description'       => esc_html__( 'Post ID to generate excerpt for.', 'classifai' ),
					],
				],
				'permission_callback' => [ $this, 'generate_post_excerpt_permissions_check' ],
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

		register_rest_route(
			'classifai/v1/openai',
			'generate-transcript/(?P<id>\d+)',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'generate_audio_transcript' ],
				'args'                => [
					'id' => [
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
						'description'       => esc_html__( 'Attachment ID to generate transcript for.', 'classifai' ),
					],
				],
				'permission_callback' => [ $this, 'generate_audio_transcript_permissions_check' ],
			]
		);

		register_rest_route(
			'classifai/v1/openai',
			'generate-title/(?P<id>\d+)',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'generate_post_title' ],
				'args'                => [
					'id' => [
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
						'description'       => esc_html__( 'Post ID to generate title for.', 'classifai' ),
					],
					'n'  => [
						'type'              => 'integer',
						'minimum'           => 1,
						'maximum'           => 10,
						'sanitize_callback' => 'absint',
						'validate_callback' => 'rest_validate_request_arg',
						'description'       => esc_html__( 'Number of titles to generate', 'classifai' ),
					],
				],
				'permission_callback' => [ $this, 'generate_post_title_permissions_check' ],
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
	 * Handle request to generate excerpt for given post ID.
	 *
	 * @param WP_REST_Request $request The full request object.
	 * @return \WP_REST_Response|WP_Error
	 */
	public function generate_post_excerpt( WP_REST_Request $request ) {
		$post_id = $request->get_param( 'id' );

		// Find the right provider class.
		$provider = find_provider_class( $this->provider_classes ?? [], 'ChatGPT' );

		// Ensure we have a provider class. Should never happen but :shrug:
		if ( is_wp_error( $provider ) ) {
			return $provider;
		}

		return rest_ensure_response( $provider->rest_endpoint_callback( $post_id, 'excerpt' ) );
	}

	/**
	 * Check if a given request has access to generate an excerpt.
	 *
	 * This check ensures we have a proper post ID, the current user
	 * making the request has access to that post, that we are
	 * properly authenticated with OpenAI and that excerpt generation
	 * is turned on.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|bool
	 */
	public function generate_post_excerpt_permissions_check( WP_REST_Request $request ) {
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

		$settings = \Classifai\get_plugin_settings( 'language_processing', 'ChatGPT' );

		// Check if valid authentication is in place.
		if ( empty( $settings ) || ( isset( $settings['authenticated'] ) && false === $settings['authenticated'] ) ) {
			return new WP_Error( 'auth', esc_html__( 'Please set up valid authentication with OpenAI.', 'classifai' ) );
		}

		// Check if excerpt generation is turned on.
		if ( empty( $settings ) || ( isset( $settings['enable_excerpt'] ) && 'no' === $settings['enable_excerpt'] ) ) {
			return new WP_Error( 'not_enabled', esc_html__( 'Excerpt generation not currently enabled.', 'classifai' ) );
		}

		// Check if the current user's role is allowed.
		$roles      = $settings['roles'] ?? [];
		$user_roles = wp_get_current_user()->roles ?? [];

		if ( empty( $roles ) || ! empty( array_diff( $user_roles, $roles ) ) ) {
			return false;
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
			$supported = TextToSpeech::get_supported_post_types();

			// Check if processing allowed.
			if ( ! in_array( $post_type, $supported, true ) ) {
				return new WP_Error( 'not_enabled', esc_html__( 'Azure Speech synthesis is not enabled for current post.', 'classifai' ) );
			}

			return true;
		}

		return false;
	}

	/**
	 * Handle request to generate a transcript for given attachment ID.
	 *
	 * @param WP_REST_Request $request The full request object.
	 * @return \WP_REST_Response|WP_Error
	 */
	public function generate_audio_transcript( WP_REST_Request $request ) {
		$attachment_id = $request->get_param( 'id' );
		$provider      = '';

		// Find the right provider class.
		foreach ( $this->provider_classes as $provider_class ) {
			if ( 'Whisper' === $provider_class->provider_service_name ) {
				$provider = $provider_class;
			}
		}

		// Ensure we have a provider class. Should never happen but :shrug:
		if ( ! $provider ) {
			return new WP_Error( 'provider_class_required', esc_html__( 'Provider class not found.', 'classifai' ) );
		}

		return rest_ensure_response( $provider->transcribe_audio( $attachment_id ) );
	}

	/**
	 * Check if a given request has access to generate a transcript.
	 *
	 * This check ensures we have a valid user with proper capabilities
	 * making the request, that we are properly authenticated with OpenAI
	 * and that transcription is turned on.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|bool
	 */
	public function generate_audio_transcript_permissions_check( WP_REST_Request $request ) {
		$attachment_id = $request->get_param( 'id' );
		$post_type     = get_post_type_object( 'attachment' );

		// Ensure attachments are allowed in REST endpoints.
		if ( empty( $post_type ) || empty( $post_type->show_in_rest ) ) {
			return false;
		}

		// Ensure we have a logged in user that can upload and change files.
		if ( empty( $attachment_id ) || ! current_user_can( 'edit_post', $attachment_id ) || ! current_user_can( 'upload_files' ) ) {
			return false;
		}

		$settings = \Classifai\get_plugin_settings( 'language_processing', 'Whisper' );

		// Check if valid authentication is in place.
		if ( empty( $settings ) || ( isset( $settings['authenticated'] ) && false === $settings['authenticated'] ) ) {
			return new WP_Error( 'auth', esc_html__( 'Please set up valid authentication with OpenAI.', 'classifai' ) );
		}

		// Check if transcription is turned on.
		if ( empty( $settings ) || ( isset( $settings['enable_transcripts'] ) && 'no' === $settings['enable_transcripts'] ) ) {
			return new WP_Error( 'not_enabled', esc_html__( 'Transcription is not currently enabled.', 'classifai' ) );
		}

		// Check if the current user's role is allowed.
		$roles      = $settings['roles'] ?? [];
		$user_roles = wp_get_current_user()->roles ?? [];

		if ( empty( $roles ) || ! empty( array_diff( $user_roles, $roles ) ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Handle request to generate title for given post ID.
	 *
	 * @param WP_REST_Request $request The full request object.
	 * @return \WP_REST_Response|WP_Error
	 */
	public function generate_post_title( WP_REST_Request $request ) {
		$post_id  = $request->get_param( 'id' );
		$provider = '';

		// Find the right provider class.
		foreach ( $this->provider_classes as $provider_class ) {
			if ( 'ChatGPT' === $provider_class->provider_service_name ) {
				$provider = $provider_class;
			}
		}

		// Ensure we have a provider class. Should never happen but :shrug:
		if ( ! $provider ) {
			return new WP_Error( 'provider_class_required', esc_html__( 'Provider class not found.', 'classifai' ) );
		}

		return rest_ensure_response(
			$provider->rest_endpoint_callback(
				$post_id,
				'title',
				[
					'num' => $request->get_param( 'n' ),
				]
			)
		);
	}

	/**
	 * Check if a given request has access to generate a title.
	 *
	 * This check ensures we have a proper post ID, the current user
	 * making the request has access to that post, that we are
	 * properly authenticated with OpenAI and that title generation
	 * is turned on.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|bool
	 */
	public function generate_post_title_permissions_check( WP_REST_Request $request ) {
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

		$settings = \Classifai\get_plugin_settings( 'language_processing', 'ChatGPT' );

		// Check if valid authentication is in place.
		if ( empty( $settings ) || ( isset( $settings['authenticated'] ) && false === $settings['authenticated'] ) ) {
			return new WP_Error( 'auth', esc_html__( 'Please set up valid authentication with OpenAI.', 'classifai' ) );
		}

		// Check if title generation is turned on.
		if ( empty( $settings ) || ( isset( $settings['enable_titles'] ) && 'no' === $settings['enable_titles'] ) ) {
			return new WP_Error( 'not_enabled', esc_html__( 'Title generation not currently enabled.', 'classifai' ) );
		}

		// Check if the current user's role is allowed.
		$roles      = $settings['title_roles'] ?? [];
		$user_roles = wp_get_current_user()->roles ?? [];

		if ( empty( $roles ) || ! empty( array_diff( $user_roles, $roles ) ) ) {
			return false;
		}

		return true;
	}

}
