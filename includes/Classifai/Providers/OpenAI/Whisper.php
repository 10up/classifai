<?php
/**
 * OpenAI Whisper (speech to text) integration
 */

namespace Classifai\Providers\OpenAI;

use Classifai\Features\AudioTranscriptsGeneration;
use Classifai\Providers\Provider;
use Classifai\Providers\OpenAI\Whisper\Transcribe;
use function Classifai\clean_input;
use function Classifai\get_asset_info;
use WP_REST_Server;
use WP_REST_Request;
use WP_Error;

class Whisper extends Provider {

	use \Classifai\Providers\OpenAI\OpenAI;

	/**
	 * ID of the current provider.
	 *
	 * @var string
	 */
	const ID = 'openai_whisper';

	/**
	 * OpenAI Whisper constructor.
	 *
	 * @param \Classifai\Features\Feature $feature_instance The feature instance.
	 */
	public function __construct( $feature_instance = null ) {
		parent::__construct(
			'OpenAI Whisper',
			'Whisper',
			'openai_whisper'
		);

		// Features provided by this provider.
		$this->features = array(
			'speech_to_text' => __( 'Generate transcripts', 'classifai' ),
		);

		// Set the onboarding options.
		$this->onboarding_options = array(
			'title'    => __( 'OpenAI Whisper', 'classifai' ),
			'fields'   => array( 'api-key' ),
			'features' => array(
				'enable_transcripts' => __( 'Generate transcripts from audio files', 'classifai' ),
			),
		);

		$this->feature_instance = $feature_instance;

		add_action( 'rest_api_init', [ $this, 'register_endpoints' ] );
	}

	/**
	 * Register what we need for the plugin.
	 *
	 * This only fires if can_register returns true.
	 */
	public function register() {
		if ( ( new AudioTranscriptsGeneration() )->is_feature_enabled() ) {
			add_action( 'add_attachment', [ $this, 'transcribe_audio' ] );
			add_filter( 'attachment_fields_to_edit', [ $this, 'add_buttons_to_media_modal' ], 10, 2 );
			add_action( 'add_meta_boxes_attachment', [ $this, 'setup_attachment_meta_box' ] );
			add_action( 'edit_attachment', [ $this, 'maybe_transcribe_audio' ] );
			add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_media_scripts' ] );
		}
	}

	public function render_provider_fields() {
		$settings = $this->feature_instance->get_settings( static::ID );

		add_settings_field(
			static::ID . '_api_key',
			esc_html__( 'API Key', 'classifai' ),
			[ $this->feature_instance, 'render_input' ],
			$this->feature_instance->get_option_name(),
			$this->feature_instance->get_option_name() . '_section',
			[
				'option_index'  => static::ID,
				'label_for'     => 'api_key',
				'input_type'    => 'password',
				'default_value' => $settings['api_key'],
				'class'         => 'classifai-provider-field hidden' . ' provider-scope-' . static::ID, // Important to add this.
			]
		);

		do_action( 'classifai_' . static::ID . '_render_provider_fields', $this );
	}

	public function get_default_provider_settings() {
		$common_settings = [
			'api_key'       => '',
			'authenticated' => false,
		];

		switch ( $this->feature_instance::ID ) {
			case AudioTranscriptsGeneration::ID:
				return $common_settings;
		}

		return [];
	}

	public function sanitize_settings( $new_settings ) {
		$settings                                    = $this->feature_instance->get_settings();
		$api_key_settings                            = $this->sanitize_api_key_settings( $new_settings, $settings );
		$new_settings[ static::ID ]['api_key']       = $api_key_settings[ static::ID ]['api_key'];
		$new_settings[ static::ID ]['authenticated'] = $api_key_settings[ static::ID ]['authenticated'];

		return $new_settings;
	}

	public function setup_fields_sections() {}

	public function reset_settings() {}

	public function enqueue_media_scripts() {
		wp_enqueue_script(
			'classifai-media-script',
			CLASSIFAI_PLUGIN_URL . 'dist/media.js',
			array_merge( get_asset_info( 'media', 'dependencies' ), array( 'jquery', 'media-editor', 'lodash' ) ),
			get_asset_info( 'media', 'version' ),
			true
		);
	}

	/**
	 * Start the audio transcription process.
	 *
	 * @param int $attachment_id Attachment ID to process.
	 * @return WP_Error|bool
	 */
	public function transcribe_audio( $attachment_id = 0 ) {
		if ( $attachment_id && ! current_user_can( 'edit_post', $attachment_id ) ) {
			return new \WP_Error( 'no_permission', esc_html__( 'User does not have permission to edit this attachment.', 'classifai' ) );
		}

		$feature = new AudioTranscriptsGeneration();
		$enabled = $feature->is_feature_enabled();

		if ( is_wp_error( $enabled ) ) {
			return $enabled;
		}

		$settings   = $feature->get_settings( static::ID );
		$transcribe = new Transcribe( intval( $attachment_id ), $settings );

		return $transcribe->process();
	}

	/**
	 * Add new buttons to the media modal.
	 *
	 * @param array    $form_fields Existing form fields.
	 * @param \WP_Post $attachment Attachment object.
	 * @return array
	 */
	public function add_buttons_to_media_modal( $form_fields, $attachment ) {
		$feature    = new AudioTranscriptsGeneration();
		$settings   = $feature->get_settings();
		$transcribe = new Transcribe( $attachment->ID, $settings[ static::ID ] );

		if ( ! $transcribe->should_process( $attachment->ID ) ) {
			return $form_fields;
		}

		$text = empty( get_the_content( null, false, $attachment ) ) ? __( 'Transcribe', 'classifai' ) : __( 'Re-transcribe', 'classifai' );

		$form_fields['retranscribe'] = [
			'label'        => __( 'Transcribe audio', 'classifai' ),
			'input'        => 'html',
			'html'         => '<button class="button secondary" id="classifai-retranscribe" data-id="' . esc_attr( absint( $attachment->ID ) ) . '">' . esc_html( $text ) . '</button><span class="spinner" style="display:none;float:none;"></span><span class="error" style="display:none;color:#bc0b0b;padding:5px;"></span>',
			'show_in_edit' => false,
		];

		return $form_fields;
	}

	/**
	 * Add metabox on single attachment view to allow for transcription.
	 *
	 * @param \WP_Post $post Post object.
	 */
	public function setup_attachment_meta_box( $post ) {
		$feature    = new AudioTranscriptsGeneration();
		$settings   = $feature->get_settings();
		$transcribe = new Transcribe( $post->ID, $settings[ static::ID ] );

		if ( ! $transcribe->should_process( $post->ID ) ) {
			return;
		}

		add_meta_box(
			'attachment_meta_box',
			__( 'ClassifAI Audio Processing', 'classifai' ),
			[ $this, 'attachment_meta_box' ],
			'attachment',
			'side',
			'high'
		);
	}

	/**
	 * Display the attachment meta box.
	 *
	 * @param \WP_Post $post Post object.
	 */
	public function attachment_meta_box( $post ) {
		$text = empty( get_the_content( null, false, $post ) ) ? __( 'Transcribe', 'classifai' ) : __( 'Re-transcribe', 'classifai' );

		wp_nonce_field( 'classifai_openai_whisper_meta_action', 'classifai_openai_whisper_meta' );
		?>

		<div class="misc-publishing-actions">
			<div class="misc-pub-section">
				<label for="retranscribe">
					<input type="checkbox" value="yes" id="retranscribe" name="retranscribe"/>
					<?php echo esc_html( $text ); ?>
				</label>
			</div>
		</div>

		<?php
	}

	/**
	 * Transcribe audio on attachment save, if option is selected.
	 *
	 * @param int $attachment_id Attachment ID.
	 */
	public function maybe_transcribe_audio( $attachment_id ) {
		if ( $attachment_id && ! current_user_can( 'edit_post', $attachment_id ) ) {
			return new \WP_Error( 'no_permission', esc_html__( 'User does not have permission to edit this attachment.', 'classifai' ) );
		}

		$feature = new AudioTranscriptsGeneration();
		$enabled = $feature->is_feature_enabled();

		if ( is_wp_error( $enabled ) ) {
			return;
		}

		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $attachment_id ) ) {
			return;
		}

		if ( empty( $_POST['classifai_openai_whisper_meta'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['classifai_openai_whisper_meta'] ) ), 'classifai_openai_whisper_meta_action' ) ) {
			return;
		}

		if ( clean_input( 'retranscribe' ) ) {
			// Remove to avoid infinite loop.
			remove_action( 'edit_attachment', [ $this, 'maybe_transcribe_audio' ] );
			$this->transcribe_audio( $attachment_id );
		}
	}

	/**
	 * Provides debug information related to the provider.
	 *
	 * @param array|null $settings Settings array. If empty, settings will be retrieved.
	 * @param boolean    $configured Whether the provider is correctly configured. If null, the option will be retrieved.
	 * @return string|array
	 */
	public function get_provider_debug_information( $settings = null, $configured = null ) {
		if ( is_null( $settings ) ) {
			$settings = $this->sanitize_settings( $this->get_settings() );
		}

		$authenticated     = 1 === intval( $settings['authenticated'] ?? 0 );
		$enable_transcript = 1 === intval( $settings['enable_transcripts'] ?? 0 );

		return [
			__( 'Authenticated', 'classifai' )        => $authenticated ? __( 'yes', 'classifai' ) : __( 'no', 'classifai' ),
			__( 'Generate transcripts', 'classifai' ) => $enable_transcript ? __( 'yes', 'classifai' ) : __( 'no', 'classifai' ),
			__( 'Allowed roles', 'classifai' )        => implode( ', ', $settings['roles'] ?? [] ),
			__( 'Latest response', 'classifai' )      => $this->get_formatted_latest_response( get_transient( 'classifai_openai_whisper_latest_response' ) ),
		];
	}

	public function register_endpoints() {
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
	}

	/**
	 * Handle request to generate a transcript for given attachment ID.
	 *
	 * @param WP_REST_Request $request The full request object.
	 * @return \WP_REST_Response|WP_Error
	 */
	public function generate_audio_transcript( WP_REST_Request $request ) {
		$attachment_id = $request->get_param( 'id' );

		return rest_ensure_response( ( new AudioTranscriptsGeneration() )->run( $attachment_id ) );
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

		if ( ! ( new AudioTranscriptsGeneration() )->is_feature_enabled() ) {
			return new WP_Error( 'not_enabled', esc_html__( 'Audio transciption is not currently enabled.', 'classifai' ) );
		}

		return true;
	}
}
