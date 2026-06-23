<?php

namespace Classifai\Features;

use Classifai\Services\LanguageProcessing;
use Classifai\Providers\Azure\Speech;
use Classifai\Providers\AWS\AmazonPolly;
use Classifai\Providers\OpenAI\TextToSpeech as OpenAITTS;
use Classifai\Providers\ElevenLabs\TextToSpeech as ElevenLabsTTS;
use Classifai\Normalizer;
use WP_REST_Server;
use WP_REST_Request;
use WP_Error;

use function Classifai\get_asset_info;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class TextToSpeech
 */
class TextToSpeech extends Feature {
	/**
	 * ID of the current feature.
	 *
	 * @var string
	 */
	const ID = 'feature_text_to_speech_generation';

	/**
	 * Meta key to get/set the ID of the speech audio file.
	 *
	 * @var string
	 */
	const AUDIO_ID_KEY = '_classifai_post_audio_id';

	/**
	 * Meta key to get/set the timestamp indicating when the speech was generated.
	 * Used for cache-busting as the audio filename remains static for a given post.
	 *
	 * @var string
	 */
	const AUDIO_TIMESTAMP_KEY = '_classifai_post_audio_timestamp';

	/**
	 * Meta key to hide/unhide already generated audio file.
	 *
	 * @var string
	 */
	const DISPLAY_GENERATED_AUDIO = '_classifai_display_generated_audio';

	/**
	 * Meta key to get/set the audio hash that helps to indicate if there is any need
	 * for the audio file to be regenerated or not.
	 *
	 * @var string
	 */
	const AUDIO_HASH_KEY = '_classifai_post_audio_hash';

	/**
	 * Meta key to opt a single post out of on-demand audio generation.
	 *
	 * Only meaningful when the feature is in `on_demand` mode. When set, the
	 * front-end "listen" player is not rendered for the post.
	 *
	 * @var string
	 */
	const DISABLE_ON_DEMAND_KEY = '_classifai_disable_on_demand_audio';

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->label = __( 'Text to Speech', 'classifai' );

		// Contains all providers that are registered to the service.
		$this->provider_instances = $this->get_provider_instances( LanguageProcessing::get_service_providers() );

		// Contains just the providers this feature supports.
		$this->supported_providers = array(
			AmazonPolly::ID   => __( 'Amazon Polly', 'classifai' ),
			Speech::ID        => __( 'Microsoft Azure AI Speech', 'classifai' ),
			OpenAITTS::ID     => __( 'OpenAI Text to Speech', 'classifai' ),
			ElevenLabsTTS::ID => __( 'ElevenLabs', 'classifai' ),
		);
	}

	/**
	 * Set up necessary hooks even if the Feature is not available.
	 */
	public function setup() {
		parent::setup();

		add_action( 'rest_api_init', array( $this, 'register_endpoints' ) );
		add_action( 'classifai_schedule_text_to_speech_job', array( $this, 'generate_text_to_speech_audio' ), 10, 2 );

		if ( $this->is_enabled() ) {
			add_filter( 'the_content', array( $this, 'render_post_audio_controls' ) );
		}
	}

	/**
	 * Set up necessary hooks.
	 */
	public function feature_setup() {
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_editor_assets' ) );
		add_action( 'rest_api_init', array( $this, 'add_meta_to_rest_api' ) );

		foreach ( $this->get_supported_post_types() as $post_type ) {
			add_action( 'rest_insert_' . $post_type, array( $this, 'rest_handle_audio' ), 10, 2 );
		}

		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
		add_action( 'admin_notices', array( $this, 'show_error_if' ) );
		add_action( 'save_post', array( $this, 'save_post_metadata' ), 5 );
		add_action( 'save_post', array( $this, 'maybe_invalidate_on_demand_audio' ), 20 );
		add_action( 'wp_ajax_classifai_get_tts_status', array( $this, 'ajax_get_audio_generation_status' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
	}

	/**
	 * Enqueue the Classic Editor polling script. Loaded in Classic Editor only when the feature is enabled.
	 *
	 * @param string $hook_suffix The current admin page.
	 */
	public function enqueue_admin_assets( string $hook_suffix ) {
		if ( 'post.php' !== $hook_suffix && 'post-new.php' !== $hook_suffix ) {
			return;
		}

		if ( ! $this->is_feature_enabled() ) {
			return;
		}

		$screen = get_current_screen();

		if ( ! $screen || $screen->is_block_editor() ) {
			return;
		}

		if ( ! in_array( $screen->post_type, $this->get_supported_post_types(), true ) ) {
			return;
		}

		wp_enqueue_script(
			'classifai-plugin-classic-text-to-speech',
			CLASSIFAI_PLUGIN_URL . 'dist/classifai-plugin-classic-text-to-speech.js',
			get_asset_info( 'classifai-plugin-classic-text-to-speech', 'dependencies' ),
			get_asset_info( 'classifai-plugin-classic-text-to-speech', 'version' ),
			true
		);
	}

	/**
	 * Enqueue the editor scripts.
	 *
	 * @since 2.4.0 Use get_asset_info to get the asset version and dependencies.
	 */
	public function enqueue_editor_assets() {
		if ( ! $this->is_feature_enabled() ) {
			return;
		}

		$post                 = get_post();
		$supported_post_types = $this->get_supported_post_types();

		// Only enqueue the script if we're on a supported post type.
		if ( empty( $post ) || ! in_array( $post->post_type, $supported_post_types, true ) ) {
			return;
		}

		wp_enqueue_script(
			'classifai-plugin-text-to-speech',
			CLASSIFAI_PLUGIN_URL . 'dist/classifai-plugin-text-to-speech.js',
			array_merge(
				get_asset_info( 'classifai-plugin-text-to-speech', 'dependencies' ),
				array( 'lodash' ),
				array( Feature::PLUGIN_AREA_SCRIPT )
			),
			get_asset_info( 'classifai-plugin-text-to-speech', 'version' ),
			true
		);

		$post_type_label = esc_html__( 'Post', 'classifai' );
		$post_type_obj   = get_post_type_object( $post->post_type );
		if ( $post_type_obj ) {
			$post_type_label = $post_type_obj->labels->singular_name;
		}

		wp_localize_script(
			'classifai-plugin-text-to-speech',
			'classifaiTextToSpeechData',
			array(
				'generationTiming' => $this->get_generation_timing(),
				'enableHelpText'   => $this->get_audio_generation_help_text( $post_type_label ),
			)
		);
	}

	/**
	 * Add audio related fields to rest API for view/edit.
	 */
	public function add_meta_to_rest_api() {
		if ( ! $this->is_feature_enabled() ) {
			return;
		}

		$supported_post_types = $this->get_supported_post_types();

		register_rest_field(
			$supported_post_types,
			'classifai_synthesize_speech',
			array(
				'get_callback'    => function ( $data ) {
					return $this->is_synthesize_speech_enabled( $data['id'] );
				},
				'update_callback' => function ( $value, $data ) {
					// In on-demand mode this toggle is a per-post opt-out; in other
					// modes it only signals save-time generation (handled elsewhere).
					if ( 'on_demand' !== $this->get_generation_timing() ) {
						return;
					}

					if ( $value ) {
						delete_post_meta( $data->ID, self::DISABLE_ON_DEMAND_KEY );
					} else {
						update_post_meta( $data->ID, self::DISABLE_ON_DEMAND_KEY, true );
					}
				},
				'schema'          => array(
					'type'    => 'boolean',
					'context' => array( 'view', 'edit' ),
				),
			)
		);

		register_rest_field(
			$supported_post_types,
			'classifai_display_generated_audio',
			array(
				'get_callback'    => function ( $data ) {
					// Default to display the audio if available.
					if ( metadata_exists( 'post', $data['id'], self::DISPLAY_GENERATED_AUDIO ) ) {
						return (bool) get_post_meta( $data['id'], self::DISPLAY_GENERATED_AUDIO, true );
					}
					return true;
				},
				'update_callback' => function ( $value, $data ) {
					if ( $value ) {
						delete_post_meta( $data->ID, self::DISPLAY_GENERATED_AUDIO );
					} else {
						update_post_meta( $data->ID, self::DISPLAY_GENERATED_AUDIO, false );
					}
				},
				'schema'          => array(
					'type'    => 'boolean',
					'context' => array( 'view', 'edit' ),
				),
			)
		);

		register_rest_field(
			$supported_post_types,
			'classifai_post_audio_id',
			array(
				'get_callback' => function ( $data ) {
					$post_audio_id = get_post_meta( $data['id'], self::AUDIO_ID_KEY, true );
					return (int) $post_audio_id;
				},
				'schema'       => array(
					'type'    => 'integer',
					'context' => array( 'view', 'edit' ),
				),
			)
		);
	}

	/**
	 * Handles audio generation on REST updates / inserts.
	 *
	 * @param \WP_Post        $post     Inserted or updated post object.
	 * @param WP_REST_Request $request  Request object.
	 */
	public function rest_handle_audio( \WP_Post $post, WP_REST_Request $request ) {
		$post_id = (int) $request->get_param( 'id' );

		if ( ! $this->is_feature_enabled() ) {
			return;
		}

		// In on-demand mode audio is generated from the front-end, never on save.
		// The synthesize toggle is persisted as an opt-out via its REST field.
		if ( 'on_demand' === $this->get_generation_timing() ) {
			return;
		}

		// Ensure we have a logged in user that can edit the item.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$job_args = array(
			'post_id'         => $post_id,
			'calling_user_id' => get_current_user_id(),
		);

		// We return early if the job is already scheduled.
		if ( function_exists( 'as_has_scheduled_action' ) && \as_has_scheduled_action( 'classifai_schedule_text_to_speech_job', $job_args, 'classifai' ) ) {
			return;
		}

		$audio_id = get_post_meta( $post_id, self::AUDIO_ID_KEY, true );

		// Since we have dynamic generation option agnostic to meta saves we need a flag to differentiate audio generation accurately
		$process_content = false;
		if (
			( $this->get_audio_generation_initial_state( $post ) && ! $audio_id ) ||
			( $this->get_audio_generation_subsequent_state( $post ) && $audio_id )
		) {
			$process_content = true;
		}

		// Add/update audio if it was requested.
		if (
			( $process_content && null === $request->get_param( 'classifai_synthesize_speech' ) ) ||
			true === $request->get_param( 'classifai_synthesize_speech' )
		) {
			if ( function_exists( 'as_enqueue_async_action' ) ) {
				\as_enqueue_async_action( 'classifai_schedule_text_to_speech_job', $job_args, 'classifai' );
				update_post_meta( $post_id, '_classifai_text_to_speech_scheduled', true );
			} else {
				$this->generate_text_to_speech_audio( $post_id );
			}
		}
	}

	/**
	 * Register any needed endpoints and meta.
	 */
	public function register_endpoints() {
		$post_types = $this->get_supported_post_types();
		foreach ( $post_types as $post_type ) {
			register_meta(
				'post',
				'_classifai_text_to_speech_error',
				array(
					'object_subtype' => $post_type,
					'type'           => 'string',
					'show_in_rest'   => true,
					'single'         => true,
					'auth_callback'  => '__return_true',
				)
			);

			register_meta(
				'post',
				'_classifai_text_to_speech_scheduled',
				array(
					'object_subtype' => $post_type,
					'type'           => 'boolean',
					'show_in_rest'   => true,
					'single'         => true,
					'auth_callback'  => '__return_true',
				)
			);
		}

		register_rest_route(
			'classifai/v1',
			'synthesize-speech/(?P<id>\d+)',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'rest_endpoint_callback' ),
				'args'                => array(
					'id' => array(
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
						'description'       => esc_html__( 'ID of post to run text to speech conversion on.', 'classifai' ),
					),
				),
				'permission_callback' => array( $this, 'speech_synthesis_permissions_check' ),
			)
		);

		register_rest_route(
			'classifai/v1',
			'synthesize-speech-on-demand/(?P<id>\d+)',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'synthesize_speech_on_demand' ),
				'args'                => array(
					'id' => array(
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
						'description'       => esc_html__( 'ID of the published post to generate audio for.', 'classifai' ),
					),
				),
				'permission_callback' => array( $this, 'on_demand_synthesis_permissions_check' ),
			)
		);
	}

	/**
	 * Permission check for the public, front-end on-demand synthesis route.
	 *
	 * Unlike {@see speech_synthesis_permissions_check()}, this route is reachable
	 * by anonymous visitors, so it is gated on the feature being enabled and in
	 * on-demand mode, the target being a published supported post, and a valid
	 * nonce — rather than an `edit_post` capability check.
	 *
	 * Note: nonces for logged-out users are shared and long-lived, so on heavily
	 * page-cached sites they act as a CSRF / casual-bot deterrent rather than a
	 * hard gate. Because audio is generated at most once per post, the cost
	 * ceiling is one generation per published post. Site owners can tighten or
	 * loosen this via the `classifai_tts_on_demand_permission` filter.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return bool|WP_Error
	 */
	public function on_demand_synthesis_permissions_check( WP_REST_Request $request ) {
		$post_id = (int) $request->get_param( 'id' );
		$post    = $post_id ? get_post( $post_id ) : null;

		$allowed = (
			$post instanceof \WP_Post &&
			'publish' === $post->post_status &&
			in_array( $post->post_type, $this->get_supported_post_types(), true ) &&
			$this->is_enabled() &&
			'on_demand' === $this->get_generation_timing() &&
			false !== wp_verify_nonce( (string) $request->get_param( 'nonce' ), 'classifai_synthesize_speech_on_demand' )
		);

		/**
		 * Filter the permission check for on-demand front-end audio generation.
		 *
		 * Return true to allow, or false / a WP_Error to deny. Use this to, for
		 * example, restrict generation to logged-in users only.
		 *
		 * @since x.x.x
		 * @hook classifai_tts_on_demand_permission
		 *
		 * @param bool            $allowed Whether the request is allowed.
		 * @param int             $post_id The post ID audio is being generated for.
		 * @param WP_REST_Request $request The REST request.
		 *
		 * @return bool|WP_Error Whether the request is allowed.
		 */
		return apply_filters( 'classifai_tts_on_demand_permission', $allowed, $post_id, $request );
	}

	/**
	 * Handle a front-end on-demand audio generation request.
	 *
	 * Generates audio synchronously (under the post author's context so the
	 * Provider capability checks pass), stores it as an attachment for reuse, and
	 * returns the playable URL. A short-lived per-post lock collapses concurrent
	 * first-clicks into a single generation.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return \WP_REST_Response
	 */
	public function synthesize_speech_on_demand( WP_REST_Request $request ) {
		$post_id = (int) $request->get_param( 'id' );
		$post    = get_post( $post_id );

		// Audio may already exist (e.g. a concurrent request just finished, or it
		// was generated in the admin) — serve it without regenerating.
		$existing = $this->get_on_demand_audio_response( $post_id );
		if ( false !== $existing ) {
			return rest_ensure_response( $existing );
		}

		$lock_key = 'classifai_tts_on_demand_lock_' . $post_id;

		// Collapse concurrent first-clicks: only one request generates at a time.
		if ( get_transient( $lock_key ) ) {
			return rest_ensure_response(
				array(
					'success'    => false,
					'inProgress' => true,
					'code'       => 'generation_in_progress',
					'message'    => esc_html__( 'Audio is already being generated. Please try again in a moment.', 'classifai' ),
				)
			);
		}

		set_transient( $lock_key, 1, 5 * MINUTE_IN_SECONDS );

		// Generate as the post author so the Provider's `edit_post` capability
		// check passes for anonymous front-end visitors.
		$this->generate_text_to_speech_audio( $post_id, $post ? (int) $post->post_author : null );

		delete_transient( $lock_key );

		$response = $this->get_on_demand_audio_response( $post_id );
		if ( false !== $response ) {
			return rest_ensure_response( $response );
		}

		// Surface any error recorded during generation.
		$message = esc_html__( 'Audio generation failed.', 'classifai' );
		$raw     = get_post_meta( $post_id, '_classifai_text_to_speech_error', true );

		if ( ! empty( $raw ) ) {
			$decoded = (array) json_decode( (string) $raw );
			if ( ! empty( $decoded['message'] ) ) {
				$message = (string) $decoded['message'];
			}
		}

		return rest_ensure_response(
			array(
				'success' => false,
				'code'    => 'generation_failed',
				'message' => $message,
			)
		);
	}

	/**
	 * Build the success response for an existing generated audio file.
	 *
	 * @param int $post_id The post ID.
	 * @return array|false The response array, or false if no playable audio exists.
	 */
	protected function get_on_demand_audio_response( int $post_id ) {
		$audio_id = (int) get_post_meta( $post_id, self::AUDIO_ID_KEY, true );

		if ( ! $audio_id ) {
			return false;
		}

		$url = wp_get_attachment_url( $audio_id );

		if ( ! $url ) {
			return false;
		}

		$timestamp = (int) get_post_meta( $post_id, self::AUDIO_TIMESTAMP_KEY, true );

		if ( $timestamp ) {
			$url = add_query_arg( 'ver', $timestamp, $url );
		}

		return array(
			'success'  => true,
			'audio_id' => $audio_id,
			'url'      => $url,
		);
	}

	/**
	 * Clear stored audio when an on-demand post's content changes.
	 *
	 * In on-demand mode audio isn't regenerated on save, so without this an edit
	 * would leave stale audio in place forever. Deleting the stored audio makes
	 * the next front-end "listen" regenerate it.
	 *
	 * @param int $post_id The post ID being saved.
	 */
	public function maybe_invalidate_on_demand_audio( int $post_id ) {
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || 'revision' === get_post_type( $post_id ) ) {
			return;
		}

		if (
			'on_demand' !== $this->get_generation_timing() ||
			! in_array( get_post_type( $post_id ), $this->get_supported_post_types(), true ) ||
			! $this->is_enabled()
		) {
			return;
		}

		$audio_id = (int) get_post_meta( $post_id, self::AUDIO_ID_KEY, true );

		if ( ! $audio_id ) {
			return;
		}

		$stored_hash  = get_post_meta( $post_id, self::AUDIO_HASH_KEY, true );
		$current_hash = md5( $this->normalize_post_content( $post_id ) );

		// Content unchanged — keep the existing audio.
		if ( ! empty( $stored_hash ) && $stored_hash === $current_hash ) {
			return;
		}

		wp_delete_attachment( $audio_id, true );
		delete_post_meta( $post_id, self::AUDIO_ID_KEY );
		delete_post_meta( $post_id, self::AUDIO_TIMESTAMP_KEY );
		delete_post_meta( $post_id, self::AUDIO_HASH_KEY );
	}

	/**
	 * Check if a given request has access to generate audio for the post.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|bool
	 */
	public function speech_synthesis_permissions_check( WP_REST_Request $request ) {
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

		// Ensure the post type is supported by this feature.
		$supported = $this->get_supported_post_types();
		if ( ! in_array( $post_type, $supported, true ) ) {
			return new WP_Error( 'not_enabled', esc_html__( 'Speech synthesis is not enabled for current item.', 'classifai' ) );
		}

		// Ensure the feature is enabled. Also runs a user check.
		if ( ! $this->is_feature_enabled() ) {
			return new WP_Error( 'not_enabled', esc_html__( 'Speech synthesis is not currently enabled.', 'classifai' ) );
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

		if ( strpos( $route, '/classifai/v1/synthesize-speech' ) === 0 ) {
			$results = $this->run( $request->get_param( 'id' ), 'synthesize' );

			if ( $results && ! is_wp_error( $results ) ) {
				$attachment_id = $this->save( $results, $request->get_param( 'id' ) );

				if ( ! is_wp_error( $attachment_id ) ) {
					return rest_ensure_response(
						array(
							'success'  => true,
							'audio_id' => $attachment_id,
						)
					);
				}
			}

			return rest_ensure_response(
				array(
					'success' => false,
					'code'    => $results->get_error_code(),
					'message' => $results->get_error_message(),
				)
			);
		}

		return parent::rest_endpoint_callback( $request );
	}

	/**
	 * Adds a meta box for Classic content to trigger Text to Speech.
	 *
	 * @param string $post_type The post type.
	 */
	public function add_meta_box( string $post_type ) {
		if (
			! in_array( $post_type, $this->get_supported_post_types(), true ) ||
			! $this->is_feature_enabled()
		) {
			return;
		}

		\add_meta_box(
			'classifai-text-to-speech-meta-box',
			__( 'ClassifAI Text to Speech Processing', 'classifai' ),
			array( $this, 'render_meta_box' ),
			null,
			'side',
			'high',
			array( '__back_compat_meta_box' => true )
		);
	}

	/**
	 * Render meta box content.
	 *
	 * @param \WP_Post $post WP_Post object.
	 */
	public function render_meta_box( \WP_Post $post ) {
		wp_nonce_field( 'classifai_text_to_speech_meta_action', 'classifai_text_to_speech_meta' );

		$is_as_scheduled_job =
			function_exists( 'as_has_scheduled_action' ) &&
			\as_has_scheduled_action(
				'classifai_schedule_text_to_speech_job',
				array(
					'post_id'         => (int) $post->ID,
					'calling_user_id' => get_current_user_id(),
				),
				'classifai'
			);

		if ( $is_as_scheduled_job ) :
			?>
			<p class="classifai-tts-status" data-post-id="<?php echo esc_attr( (string) $post->ID ); ?>" data-nonce="<?php echo esc_attr( wp_create_nonce( 'classifai_tts_status' ) ); ?>">
				<?php esc_html_e( 'Audio generation is in progress…', 'classifai' ); ?>
			</p>
			<?php
		else :
			$this->render_audio_generation_ui( $post );
		endif;
	}

	/**
	 * Render the post-generation UI for the TTS meta box. Used both on initial
	 * render and via AJAX after a queued job completes so the meta box
	 * matches a fresh page load.
	 *
	 * @param \WP_Post $post WP_Post object.
	 */
	public function render_audio_generation_ui( \WP_Post $post ) {
		$source_url = false;
		$audio_id   = get_post_meta( $post->ID, self::AUDIO_ID_KEY, true );

		if ( $audio_id ) {
			$source_url = wp_get_attachment_url( $audio_id );
		}

		$process_content = $this->is_synthesize_speech_enabled( $post );

		$display_audio = true;
		if ( metadata_exists( 'post', $post->ID, self::DISPLAY_GENERATED_AUDIO ) &&
			! (bool) get_post_meta( $post->ID, self::DISPLAY_GENERATED_AUDIO, true ) ) {
			$display_audio = false;
		}

		$post_type_label = esc_html__( 'Post', 'classifai' );
		$post_type       = get_post_type_object( get_post_type( $post ) );
		if ( $post_type ) {
			$post_type_label = $post_type->labels->singular_name;
		}

		?>
		<p>
			<label for="classifai_synthesize_speech">
				<input type="checkbox" value="1" id="classifai_synthesize_speech" name="classifai_synthesize_speech" <?php checked( $process_content ); ?> />
				<?php esc_html_e( 'Enable audio generation', 'classifai' ); ?>
			</label>
			<span class="description">
				<?php echo esc_html( $this->get_audio_generation_help_text( $post_type_label ) ); ?>
			</span>
		</p>

		<p <?php echo $source_url ? '' : 'class="hidden"'; ?>>
			<label for="classifai_display_generated_audio">
				<input type="checkbox" value="1" id="classifai_display_generated_audio" name="classifai_display_generated_audio" <?php checked( $display_audio ); ?> />
				<?php esc_html_e( 'Display audio controls', 'classifai' ); ?>
			</label>
			<span class="description">
				<?php
				esc_html__( 'Controls the display of the audio player on the front-end.', 'classifai' );
				?>
			</span>
		</p>

		<?php
		if ( $source_url ) {
			$cache_busting_url = add_query_arg(
				array(
					'ver' => time(),
				),
				$source_url
			);
			?>
			<p>
				<audio id="classifai-audio-preview" controls controlslist="nodownload" src="<?php echo esc_url( $cache_busting_url ); ?>"></audio>
			</p>
			<?php
		}
	}

	/**
	 * AJAX handler for the Classic Editor meta box's polling. Returns the
	 * current audio generation status for the given post id.
	 */
	public function ajax_get_audio_generation_status() {
		check_ajax_referer( 'classifai_tts_status', 'nonce' );

		$post_id = isset( $_POST['post_id'] ) ? absint( wp_unslash( $_POST['post_id'] ) ) : 0;

		if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid post.', 'classifai' ) ), 403 );
		}

		$in_progress =
			function_exists( 'as_has_scheduled_action' ) &&
			\as_has_scheduled_action(
				'classifai_schedule_text_to_speech_job',
				array(
					'post_id'         => $post_id,
					'calling_user_id' => get_current_user_id(),
				),
				'classifai'
			);

		$error_message = '';
		$raw_error     = get_post_meta( $post_id, '_classifai_text_to_speech_error', true );

		if ( ! empty( $raw_error ) ) {
			$decoded = (array) json_decode( (string) $raw_error );
			if ( ! empty( $decoded['message'] ) ) {
				$error_message = (string) $decoded['message'];
			}
		}

		$html = '';
		if ( ! $in_progress && ! $error_message ) {
			$post = get_post( $post_id );
			if ( $post ) {
				ob_start();
				$this->render_audio_generation_ui( $post );
				$html = (string) ob_get_clean();
			}
		}

		wp_send_json_success(
			array(
				'inProgress' => (bool) $in_progress,
				'error'      => $error_message,
				'html'       => $html,
			)
		);
	}

	/**
	 * Process the meta box save.
	 *
	 * @param int $post_id Post ID.
	 */
	public function save_post_metadata( int $post_id ) {
		if (
			! in_array( get_post_type( $post_id ), $this->get_supported_post_types(), true ) ||
			! $this->is_feature_enabled()
		) {
			return;
		}

		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) || 'revision' === get_post_type( $post_id ) ) {
			return;
		}

		if ( empty( $_POST['classifai_text_to_speech_meta'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['classifai_text_to_speech_meta'] ) ), 'classifai_text_to_speech_meta_action' ) ) {
			return;
		}

		if ( ! isset( $_POST['classifai_display_generated_audio'] ) ) {
			update_post_meta( $post_id, self::DISPLAY_GENERATED_AUDIO, false );
		} else {
			delete_post_meta( $post_id, self::DISPLAY_GENERATED_AUDIO );
		}

		// In on-demand mode the synthesize toggle is a per-post opt-out and never
		// triggers save-time generation.
		if ( 'on_demand' === $this->get_generation_timing() ) {
			if ( isset( $_POST['classifai_synthesize_speech'] ) ) {
				delete_post_meta( $post_id, self::DISABLE_ON_DEMAND_KEY );
			} else {
				update_post_meta( $post_id, self::DISABLE_ON_DEMAND_KEY, true );
			}
			return;
		}

		$job_args = array(
			'post_id'         => (int) $post_id,
			'calling_user_id' => get_current_user_id(),
		);

		// We return early if the job is already scheduled.
		if (
			! isset( $_POST['classifai_synthesize_speech'] )
			|| (
				function_exists( 'as_has_scheduled_action' )
				&& \as_has_scheduled_action( 'classifai_schedule_text_to_speech_job', $job_args, 'classifai' )
			)
		) {
			return;
		}

		// We enqueue the async action to generate the audio, if available.
		if ( function_exists( 'as_enqueue_async_action' ) ) {
			\as_enqueue_async_action( 'classifai_schedule_text_to_speech_job', $job_args, 'classifai' );
			update_post_meta( $post_id, '_classifai_text_to_speech_scheduled', true );
		} else {
			$this->generate_text_to_speech_audio( $post_id );
		}
	}

	/**
	 * Generate the text to speech audio.
	 *
	 * @param int      $post_id The post ID.
	 * @param int|null $calling_user_id The user that made the request.
	 */
	public function generate_text_to_speech_audio( int $post_id, ?int $calling_user_id = null ) {
		$original_user_id = get_current_user_id();

		if ( ! $calling_user_id ) {
			$calling_user_id = $original_user_id;
		}

		// Set the user to the one who started the process, to avoid permission issues.
		wp_set_current_user( (int) $calling_user_id );

		$results = $this->run( $post_id, 'synthesize' );

		if ( $results && ! is_wp_error( $results ) ) {
			$this->save( $results, $post_id );
			delete_post_meta( $post_id, '_classifai_text_to_speech_error' );
		} elseif ( is_wp_error( $results ) ) {
			update_post_meta(
				$post_id,
				'_classifai_text_to_speech_error',
				wp_json_encode(
					array(
						'code'    => $results->get_error_code(),
						'message' => $results->get_error_message(),
					)
				)
			);
		}

		// Restore original user.
		wp_set_current_user( $original_user_id );

		delete_post_meta( $post_id, '_classifai_text_to_speech_scheduled' );
	}

	/**
	 * Save the returned result.
	 *
	 * @param string $result The results to save.
	 * @param int    $post_id The post ID.
	 * @return int|WP_Error
	 */
	public function save( string $result, int $post_id ) {
		$saved_attachment_id = (int) get_post_meta( $post_id, self::AUDIO_ID_KEY, true );

		// The audio file name.
		$audio_file_name = sprintf(
			'post-as-audio-%1$s.mp3',
			$post_id
		);

		// Upload the audio stream as an .mp3 file.
		$file_data = wp_upload_bits(
			$audio_file_name,
			null,
			$result
		);

		if ( isset( $file_data['error'] ) && ! empty( $file_data['error'] ) ) {
			return new WP_Error(
				'text_to_speech_upload_bits_failure',
				esc_html( $file_data['error'] )
			);
		}

		// Insert the audio file as attachment.
		$attachment_id = wp_insert_attachment(
			array(
				'guid'           => $file_data['file'],
				'post_title'     => $audio_file_name,
				'post_mime_type' => $file_data['type'],
			),
			$file_data['file'],
			$post_id
		);

		// Return error if creation of attachment fails.
		if ( ! $attachment_id ) {
			return new WP_Error(
				'text_to_speech_resource_creation_failure',
				esc_html__( 'Audio creation failed.', 'classifai' )
			);
		}

		// If audio already exists for this post, delete it.
		if ( $saved_attachment_id ) {
			wp_delete_attachment( $saved_attachment_id, true );
			delete_post_meta( $post_id, self::AUDIO_ID_KEY );
			delete_post_meta( $post_id, self::AUDIO_TIMESTAMP_KEY );
		}

		update_post_meta( $post_id, self::AUDIO_ID_KEY, absint( $attachment_id ) );
		update_post_meta( $post_id, self::AUDIO_TIMESTAMP_KEY, time() );

		return $attachment_id;
	}

	/**
	 * Adds audio controls to the post that has speech synthesis enabled.
	 *
	 * @param string $content Post content.
	 * @return string
	 */
	public function render_post_audio_controls( string $content ): string {
		$_post = get_post();

		if (
			! $_post instanceof \WP_Post ||
			! is_singular( $_post->post_type ) ||
			! in_array( $_post->post_type, $this->get_supported_post_types(), true )
		) {
			return $content;
		}

		/**
		 * Filter to disable the rendering of the Text to Speech block.
		 *
		 * @since 2.2.0
		 * @hook classifai_disable_post_to_audio_block
		 *
		 * @param bool     $is_disabled Whether to disable the display or not. By default - false.
		 * @param \WP_Post $_post       The Post object.
		 *
		 * @return bool Whether the audio block should be shown.
		 */
		if ( apply_filters( 'classifai_disable_post_to_audio_block', false, $_post ) ) {
			return $content;
		}

		// Respect the audio display settings of the post.
		if ( metadata_exists( 'post', $_post->ID, self::DISPLAY_GENERATED_AUDIO ) &&
			! (bool) get_post_meta( $_post->ID, self::DISPLAY_GENERATED_AUDIO, true ) ) {
			return $content;
		}

		$on_demand            = 'on_demand' === $this->get_generation_timing();
		$audio_attachment_id  = (int) get_post_meta( $_post->ID, self::AUDIO_ID_KEY, true );
		$audio_attachment_url = $audio_attachment_id ? wp_get_attachment_url( $audio_attachment_id ) : '';

		// Respect a per-post opt-out of on-demand generation.
		if ( $on_demand && (bool) get_post_meta( $_post->ID, self::DISABLE_ON_DEMAND_KEY, true ) ) {
			return $content;
		}

		// When audio hasn't been generated yet, only render the player if we're
		// generating on demand. Otherwise there's nothing to play.
		if ( ! $audio_attachment_url && ! ( $on_demand && 'publish' === $_post->post_status ) ) {
			return $content;
		}

		$audio_timestamp = (int) get_post_meta( $_post->ID, self::AUDIO_TIMESTAMP_KEY, true );

		if ( $audio_attachment_url && $audio_timestamp ) {
			$audio_attachment_url = add_query_arg( 'ver', filter_var( $audio_timestamp, FILTER_SANITIZE_NUMBER_INT ), $audio_attachment_url );
		}

		/**
		 * Filters the audio player markup before display.
		 *
		 * Returning a non-false value from this filter will short-circuit building
		 * the block markup and instead will return your custom markup prepended to
		 * the post_content.
		 *
		 * Note that by using this filter, the custom CSS and JS files will no longer
		 * be enqueued, so you'll be responsible for either loading them yourself or
		 * loading custom ones.
		 *
		 * @hook classifai_pre_render_post_audio_controls
		 * @since 2.2.3
		 *
		 * @param bool|string  $markup               Audio markup to use. Defaults to false.
		 * @param string       $content              Content of the current post.
		 * @param \WP_Post     $_post                The Post object.
		 * @param int          $audio_attachment_id  The audio attachment ID.
		 * @param string       $audio_attachment_url The URL to the audio attachment file.
		 *
		 * @return bool|string Custom audio block markup. Will be prepended to the post content.
		 */
		$markup = apply_filters( 'classifai_pre_render_post_audio_controls', false, $content, $_post, $audio_attachment_id, $audio_attachment_url );

		if ( false !== $markup ) {
			return (string) $markup . $content;
		}

		wp_enqueue_script(
			'classifai-plugin-text-to-speech-frontend-js',
			CLASSIFAI_PLUGIN_URL . 'dist/classifai-plugin-text-to-speech-frontend.js',
			get_asset_info( 'classifai-plugin-text-to-speech-frontend', 'dependencies' ),
			get_asset_info( 'classifai-plugin-text-to-speech-frontend', 'version' ),
			true
		);

		wp_enqueue_style(
			'classifai-plugin-text-to-speech-frontend-css',
			CLASSIFAI_PLUGIN_URL . 'dist/classifai-plugin-text-to-speech-frontend.css',
			array( 'dashicons' ),
			get_asset_info( 'classifai-plugin-text-to-speech-frontend', 'version' ),
			'all'
		);

		// Generate-on-demand only applies when no audio exists yet.
		$generate_on_demand = $on_demand && ! $audio_attachment_url;

		ob_start();

		?>
			<div>
				<div class='classifai-listen-to-post-wrapper'>
					<div
						class="class-post-audio-controls"
						tabindex="0"
						role="button"
						aria-label="<?php esc_attr_e( 'Play audio', 'classifai' ); ?>"
						data-aria-pause-audio="<?php esc_attr_e( 'Pause audio', 'classifai' ); ?>"
						data-has-audio="<?php echo $audio_attachment_url ? '1' : '0'; ?>"
						<?php if ( $generate_on_demand ) : ?>
						data-post-id="<?php echo esc_attr( (string) $_post->ID ); ?>"
						data-rest-url="<?php echo esc_url( rest_url( 'classifai/v1/synthesize-speech-on-demand/' . $_post->ID ) ); ?>"
						data-nonce="<?php echo esc_attr( wp_create_nonce( 'classifai_synthesize_speech_on_demand' ) ); ?>"
						data-generating-label="<?php esc_attr_e( 'Generating audio…', 'classifai' ); ?>"
						data-error-label="<?php esc_attr_e( 'Audio could not be generated. Please try again.', 'classifai' ); ?>"
						<?php endif; ?>
					>
						<span class="dashicons dashicons-controls-play"></span>
						<span class="dashicons dashicons-controls-pause"></span>
						<span class="classifai-tts-spinner" aria-hidden="true"></span>
					</div>
					<div class='classifai-post-audio-heading'>
						<?php
							$listen_to_post_text = sprintf(
								/**
								 * Hook to filter the text next to the audio controls on the frontend.
								 *
								 * @since 2.2.0
								 * @hook classifai_listen_to_this_post_text
								 *
								 * @param string The text to filter.
								 * @param int    Post ID.
								 *
								 * @return string Filtered text.
								 */
								apply_filters( 'classifai_listen_to_this_post_text', '%s %s', $_post->ID ),
								esc_html__( 'Listen to this', 'classifai' ),
								esc_html( $_post->post_type )
							);

							echo wp_kses_post( $listen_to_post_text );
						?>
					</div>
				</div>
				<audio id="classifai-post-audio-player"<?php echo $audio_attachment_url ? ' src="' . esc_url( $audio_attachment_url ) . '"' : ''; ?>></audio>
			</div>
		<?php

		return ob_get_clean() . $content;
	}

	/**
	 * Get the description for the enable field.
	 *
	 * @return string
	 */
	public function get_enable_description(): string {
		return esc_html__( 'Generate an audio file from post content and output a "Read to Me" component.', 'classifai' );
	}

	/**
	 * Add any needed custom fields.
	 */
	public function add_custom_settings_fields() {
		$settings          = $this->get_settings();
		$post_types        = \Classifai\get_post_types_for_language_settings();
		$post_type_options = array();

		foreach ( $post_types as $post_type ) {
			$post_type_options[ $post_type->name ] = $post_type->label;
		}

		add_settings_field(
			'post_types',
			esc_html__( 'Allowed post types', 'classifai' ),
			array( $this, 'render_checkbox_group' ),
			$this->get_option_name(),
			$this->get_option_name() . '_section',
			array(
				'label_for'      => 'post_types',
				'options'        => $post_type_options,
				'default_values' => $settings['post_types'],
				'description'    => __( 'Choose which post types support this feature.', 'classifai' ),
			)
		);
	}

	/**
	 * Returns the select options for post types.
	 *
	 * @return array
	 */
	protected function get_post_types_select_options(): array {
		$post_types = \Classifai\get_post_types_for_language_settings();
		$options    = array();

		foreach ( $post_types as $post_type ) {
			$options[ $post_type->name ] = $post_type->label;
		}

		return $options;
	}

	/**
	 * Returns the default settings for the feature.
	 *
	 * @return array
	 */
	public function get_feature_default_settings(): array {
		return array(
			'post_types'        => array(
				'post' => 'post',
			),
			'generation_timing' => 'automatic',
			'provider'          => Speech::ID,
		);
	}

	/**
	 * Returns the supported audio generation timing modes.
	 *
	 * - `automatic`: generate audio when a post is published or updated.
	 * - `manual`: only generate when explicitly turned on for each post.
	 * - `on_demand`: generate the first time a visitor listens on the front-end.
	 *
	 * @return array
	 */
	public function get_generation_timing_options(): array {
		return array(
			'automatic' => __( 'Automatic (on publish or update)', 'classifai' ),
			'manual'    => __( 'Manual (generation needs to be manually turned on for each post)', 'classifai' ),
			'on_demand' => __( 'On demand (generate on first front-end listen)', 'classifai' ),
		);
	}

	/**
	 * Returns the configured audio generation timing mode.
	 *
	 * Falls back to `automatic` for back-compat with installs saved before this
	 * setting existed.
	 *
	 * @return string One of `automatic`, `manual`, `on_demand`.
	 */
	public function get_generation_timing(): string {
		$timing = $this->get_settings( 'generation_timing' );

		return array_key_exists( $timing, $this->get_generation_timing_options() ) ? $timing : 'automatic';
	}

	/**
	 * Help text for the "Enable" toggle, worded for the generation timing mode.
	 *
	 * @param string $post_type_label Singular label of the post type (e.g. "Post").
	 * @return string
	 */
	public function get_audio_generation_help_text( string $post_type_label ): string {
		switch ( $this->get_generation_timing() ) {
			case 'manual':
				/* translators: %s Post type label */
				return sprintf( __( 'Audio won\'t be generated until you enable this and save the %s.', 'classifai' ), $post_type_label );

			case 'on_demand':
				/* translators: %s Post type label */
				return sprintf( __( 'Audio is generated the first time a visitor chooses to listen on the front-end. Turn this off to disable audio for this %s.', 'classifai' ), $post_type_label );

			case 'automatic':
			default:
				/* translators: %s Post type label */
				return sprintf( __( 'ClassifAI will generate audio for this %s when it is published or updated.', 'classifai' ), $post_type_label );
		}
	}

	/**
	 * Whether the per-post "Enable audio generation" toggle should be on.
	 *
	 * In on-demand mode the toggle controls whether the post participates in
	 * on-demand generation at all — it defaults on and is only off when the post
	 * has been explicitly opted out. In other modes it reflects the existing
	 * initial/subsequent generation state.
	 *
	 * @param int|\WP_Post $post Post ID or object.
	 * @return bool
	 */
	public function is_synthesize_speech_enabled( $post ): bool {
		$post_id = $post instanceof \WP_Post ? $post->ID : (int) $post;

		if ( 'on_demand' === $this->get_generation_timing() ) {
			return ! (bool) get_post_meta( $post_id, self::DISABLE_ON_DEMAND_KEY, true );
		}

		$audio_id = get_post_meta( $post_id, self::AUDIO_ID_KEY, true );

		return (
			( $this->get_audio_generation_initial_state( $post ) && ! $audio_id ) ||
			( $this->get_audio_generation_subsequent_state( $post ) && $audio_id )
		);
	}

	/**
	 * Sanitizes the default feature settings.
	 *
	 * @param array $new_settings Settings being saved.
	 * @return array
	 */
	public function sanitize_default_feature_settings( array $new_settings ): array {
		$post_types = \Classifai\get_post_types_for_language_settings();

		foreach ( $post_types as $post_type ) {
			if ( ! isset( $new_settings['post_types'][ $post_type->name ] ) ) {
				$new_settings['post_types'][ $post_type->name ] = '';
			} else {
				$new_settings['post_types'][ $post_type->name ] = sanitize_text_field( $new_settings['post_types'][ $post_type->name ] );
			}
		}

		$timing                            = $new_settings['generation_timing'] ?? 'automatic';
		$new_settings['generation_timing'] = array_key_exists( $timing, $this->get_generation_timing_options() ) ? $timing : 'automatic';

		return $new_settings;
	}

	/**
	 * Initial audio generation state.
	 *
	 * Fetch the initial state of audio generation prior to the audio existing for the post.
	 *
	 * @param  int|\WP_Post|null $post   Optional. Post ID or post object. `null`, `false`, `0` and other PHP falsey values
	 *                                    return the current global post inside the loop. A numerically valid post ID that
	 *                                    points to a non-existent post returns `null`. Defaults to global $post.
	 * @return bool                     The initial state of audio generation. Default true.
	 */
	public function get_audio_generation_initial_state( $post = null ): bool {
		/**
		 * Initial state of the audio generation toggle when no audio already exists for the post.
		 *
		 * @since 2.3.0
		 * @hook classifai_audio_generation_initial_state
		 *
		 * @param bool     $state Initial state of audio generation toggle on a post. Default true.
		 * @param \WP_Post $post  The current Post object.
		 *
		 * @return bool            Initial state the audio generation toggle should be set to when no audio exists.
		 */
		return apply_filters( 'classifai_audio_generation_initial_state', 'automatic' === $this->get_generation_timing(), get_post( $post ) );
	}

	/**
	 * Subsequent audio generation state.
	 *
	 * Fetch the subsequent state of audio generation once audio is generated for the post.
	 *
	 * @param int|\WP_Post|null $post   Optional. Post ID or post object. `null`, `false`, `0` and other PHP falsey values
	 *                                   return the current global post inside the loop. A numerically valid post ID that
	 *                                   points to a non-existent post returns `null`. Defaults to global $post.
	 * @return bool                    The subsequent state of audio generation. Default false.
	 */
	public function get_audio_generation_subsequent_state( $post = null ): bool {
		/**
		 * Subsequent state of the audio generation toggle when audio exists for the post.
		 *
		 * @since 2.3.0
		 * @hook classifai_audio_generation_subsequent_state
		 *
		 * @param bool     $state Subsequent state of audio generation toggle on a post. Default false.
		 * @param \WP_Post $post  The current Post object.
		 *
		 * @return bool            Subsequent state the audio generation toggle should be set to when audio exists.
		 */
		return apply_filters( 'classifai_audio_generation_subsequent_state', false, get_post( $post ) );
	}

	/**
	 * Normalizes the post content for text to speech generation.
	 *
	 * @param int $post_id The post ID.
	 *
	 * @return string The normalized post content.
	 */
	public function normalize_post_content( int $post_id ): string {
		add_filter( 'classifai_pre_normalize', array( $this, 'strip_sub_sup_tags' ) );
		$normalizer   = new Normalizer();
		$post         = get_post( $post_id );
		$post_content = $normalizer->normalize_content( $post->post_content, $post->post_title, $post_id );
		remove_filter( 'classifai_pre_normalize', array( $this, 'strip_sub_sup_tags' ) );

		return $post_content;
	}

	/**
	 * Filters the post content by stripping off HTML subscript and superscript tags
	 * with its content for text to speech generation.
	 *
	 * @param string $post_content The post content.
	 *
	 * @return string The filtered post content.
	 */
	public function strip_sub_sup_tags( string $post_content ): string {
		$post_content = preg_replace( '/<sub>.*?<\/sub>|<sup>.*?<\/sup>/', '', $post_content );
		return $post_content;
	}

	/**
	 * Generates feature setting data required for migration from
	 * ClassifAI < 3.0.0 to 3.0.0
	 *
	 * @return array
	 */
	public function migrate_settings() {
		$old_settings = get_option( 'classifai_azure_text_to_speech', array() );
		$new_settings = $this->get_default_settings();

		if ( isset( $old_settings['enable_text_to_speech'] ) ) {
			$new_settings['status'] = $old_settings['enable_text_to_speech'];
		}

		$new_settings['provider'] = 'ms_azure_text_to_speech';

		if ( isset( $old_settings['credentials']['url'] ) ) {
			$new_settings['ms_azure_text_to_speech']['endpoint_url'] = $old_settings['credentials']['url'];
		}

		if ( isset( $old_settings['credentials']['api_key'] ) ) {
			$new_settings['ms_azure_text_to_speech']['api_key'] = $old_settings['credentials']['api_key'];
		}

		if ( isset( $old_settings['authenticated'] ) ) {
			$new_settings['ms_azure_text_to_speech']['authenticated'] = $old_settings['authenticated'];
		}

		if ( isset( $old_settings['voices'] ) ) {
			$new_settings['ms_azure_text_to_speech']['voices'] = $old_settings['voices'];
		}

		if ( isset( $old_settings['voice'] ) ) {
			$new_settings['ms_azure_text_to_speech']['voice'] = $old_settings['voice'];
		}

		if ( isset( $old_settings['text_to_speech_users'] ) ) {
			$new_settings['users'] = $old_settings['text_to_speech_users'];
		}

		if ( isset( $old_settings['text_to_speech_roles'] ) ) {
			$new_settings['roles'] = $old_settings['text_to_speech_roles'];
		}

		if ( isset( $old_settings['text_to_speech_user_based_opt_out'] ) ) {
			$new_settings['user_based_opt_out'] = $old_settings['text_to_speech_user_based_opt_out'];
		}

		if ( isset( $old_settings['post_types'] ) ) {
			$new_settings['post_types'] = $old_settings['post_types'];
		}

		return $new_settings;
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

		$error = get_post_meta( $post_id, '_classifai_text_to_speech_error', true );

		if ( ! empty( $error ) ) {
			delete_post_meta( $post_id, '_classifai_text_to_speech_error' );
			$error   = (array) json_decode( $error );
			$code    = ! empty( $error['code'] ) ? $error['code'] : 500;
			$message = ! empty( $error['message'] ) ? $error['message'] : 'Unknown API error';

			?>
			<div class="notice notice-error is-dismissible">
				<p>
					<?php esc_html_e( 'Error: Audio generation failed.', 'classifai' ); ?>
				</p>
				<p>
					<?php echo esc_html( $code ); ?>
					-
					<?php echo esc_html( $message ); ?>
				</p>
			</div>
			<?php
		}
	}
}
