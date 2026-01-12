<?php

namespace Classifai\Features;

use Classifai\Services\LanguageProcessing;
use Classifai\Providers\OpenAI\SpeechToText as OpenAISpeechToText;
use Classifai\Providers\ElevenLabs\SpeechToText as ElevenLabsSpeechToText;
use WP_Error;
use WP_REST_Server;
use WP_REST_Request;

use function Classifai\get_asset_info;
use function Classifai\clean_input;
use function Classifai\safe_wp_remote_get;
use function Classifai\is_remote_url;
use function Classifai\is_local_path;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class AudioTranscriptsGeneration
 */
class AudioTranscriptsGeneration extends Feature {
	/**
	 * ID of the current feature.
	 *
	 * @var string
	 */
	const ID = 'feature_audio_transcripts_generation';

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->label = __( 'Audio Transcripts Generation', 'classifai' );

		// Contains all providers that are registered to the service.
		$this->provider_instances = $this->get_provider_instances( LanguageProcessing::get_service_providers() );

		// Contains just the providers this feature supports.
		$this->supported_providers = [
			OpenAISpeechToText::ID     => __( 'OpenAI Audio Transcription', 'classifai' ),
			ElevenLabsSpeechToText::ID => __( 'ElevenLabs Audio Transcription', 'classifai' ),
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
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
		add_action( 'add_meta_boxes_attachment', [ $this, 'setup_attachment_meta_box' ] );
		add_action( 'edit_attachment', [ $this, 'maybe_transcribe_audio' ] ); /** @phpstan-ignore return.void (function is used in multiple contexts and needs to return data if called directly) */
		add_action( 'add_attachment', [ $this, 'transcribe_audio' ] ); /** @phpstan-ignore return.void (function is used in multiple contexts and needs to return data if called directly) */

		add_filter( 'attachment_fields_to_edit', [ $this, 'add_buttons_to_media_modal' ], 10, 2 );
	}

	/**
	 * Register any needed endpoints.
	 */
	public function register_endpoints() {
		register_rest_route(
			'classifai/v1',
			'generate-transcript/(?P<id>\d+)',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'rest_endpoint_callback' ],
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

		if ( ! $this->is_feature_enabled() ) {
			return new WP_Error( 'not_enabled', esc_html__( 'Audio transciption is not currently enabled.', 'classifai' ) );
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

		if ( strpos( $route, '/classifai/v1/generate-transcript' ) === 0 ) {
			$result = $this->run( $request->get_param( 'id' ), 'transcript' );

			if ( ! is_wp_error( $result ) ) {
				$result = $this->add_transcription( $result, $request->get_param( 'id' ) );
			}

			return rest_ensure_response( $result );
		}

		return parent::rest_endpoint_callback( $request );
	}

	/**
	 * Enqueue assets.
	 */
	public function enqueue_admin_assets() {
		wp_enqueue_script(
			'classifai-plugin-media-processing-js',
			CLASSIFAI_PLUGIN_URL . 'dist/classifai-plugin-media-processing.js',
			array_merge( get_asset_info( 'classifai-plugin-media-processing', 'dependencies' ), array( 'jquery', 'media-editor', 'lodash' ) ),
			get_asset_info( 'classifai-plugin-media-processing', 'version' ),
			true
		);
	}

	/**
	 * Add new buttons to the media modal.
	 *
	 * @param array         $form_fields Existing form fields.
	 * @param \WP_Post|null $attachment  Attachment object.
	 * @return array
	 */
	public function add_buttons_to_media_modal( array $form_fields, ?\WP_Post $attachment ): array {
		if ( null === $attachment || ! $this->should_process( $attachment->ID ) ) {
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
	public function setup_attachment_meta_box( \WP_Post $post ) {
		if ( ! $this->should_process( $post->ID ) ) {
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
	public function attachment_meta_box( \WP_Post $post ) {
		$text = empty( get_the_content( null, false, $post ) ) ? __( 'Transcribe', 'classifai' ) : __( 'Re-transcribe', 'classifai' );

		wp_nonce_field( 'classifai_audio_transcript_meta_action', 'classifai_audio_transcript_meta' );
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
	 * Transcribe audio and save transcription result.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return string|WP_Error
	 */
	public function transcribe_audio( int $attachment_id ) {
		$result = $this->run( $attachment_id, 'transcript' );

		if ( ! is_wp_error( $result ) ) {
			$result = $this->add_transcription( $result, $attachment_id );
		}

		return $result;
	}

	/**
	 * Transcribe audio on attachment save, if option is selected.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return WP_Error|string|null
	 */
	public function maybe_transcribe_audio( int $attachment_id ) {
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $attachment_id ) ) {
			return;
		}

		if ( empty( $_POST['classifai_audio_transcript_meta'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['classifai_audio_transcript_meta'] ) ), 'classifai_audio_transcript_meta_action' ) ) {
			return;
		}

		if ( clean_input( 'retranscribe' ) ) {
			// Remove to avoid infinite loop.
			remove_action( 'edit_attachment', [ $this, 'maybe_transcribe_audio' ] );

			return $this->transcribe_audio( $attachment_id );
		}
	}

	/**
	 * Get the description for the enable field.
	 *
	 * @return string
	 */
	public function get_enable_description(): string {
		return esc_html__( 'Automatically generate transcripts for supported audio files.', 'classifai' );
	}

	/**
	 * Returns the default settings for the feature.
	 *
	 * @return array
	 */
	public function get_feature_default_settings(): array {
		return [
			'provider' => OpenAISpeechToText::ID,
		];
	}

	/**
	 * Should this attachment be processed.
	 *
	 * Ensure the file is a supported format and is under the maximum file size.
	 *
	 * @param int $attachment_id Attachment ID to process.
	 * @return bool
	 */
	public function should_process( int $attachment_id ): bool {
		$settings          = $this->get_settings();
		$provider_id       = $settings['provider'];
		$provider_instance = $this->get_feature_provider_instance( $provider_id );

		$mime_type          = get_post_mime_type( $attachment_id );
		$matched_extensions = explode( '|', array_search( $mime_type, wp_get_mime_types(), true ) );
		$process            = false;

		foreach ( $matched_extensions as $ext ) {
			if ( in_array( $ext, $provider_instance->file_formats ?? [ 'mp3' ], true ) ) {
				$process = true;
			}
		}

		// If we have a proper file format, check the file size.
		if ( $process ) {
			$filesize = filesize( get_attached_file( $attachment_id ) );
			if ( ! $filesize || $filesize > $provider_instance->max_file_size ?? 25 * MB_IN_BYTES ) {
				$process = false;
			}
		}

		return $process;
	}

	/**
	 * Add the transcribed text to the attachment.
	 *
	 * @param string $text Transcription result.
	 * @param int    $attachment_id Attachment ID.
	 * @return string|WP_Error
	 */
	public function add_transcription( string $text = '', int $attachment_id = 0 ) {
		if ( empty( $text ) ) {
			return new WP_Error( 'invalid_result', esc_html__( 'The transcription result is invalid.', 'classifai' ) );
		}

		/**
		 * Filter the text result returned from OpenAI Audio API.
		 *
		 * @since 2.2.0
		 * @hook classifai_whisper_transcribe_result
		 *
		 * @param string $text Text extracted from the response.
		 * @param int    $attachment_id The attachment ID.
		 *
		 * @return string
		 */
		$text = apply_filters( 'classifai_whisper_transcribe_result', $text, $attachment_id );

		$update = wp_update_post(
			[
				'ID'           => (int) $attachment_id,
				'post_content' => wp_kses_post( $text ),
			],
			true
		);

		if ( is_wp_error( $update ) ) {
			return $update;
		} else {
			return $text;
		}
	}

	/**
	 * Downloads a remote audio file and saves it to a temporary directory.
	 *
	 * This function performs the following:
	 * 1. Downloads the remote file using wp_safe_remote_get().
	 * 2. Streams the file into memory using php://temp for secure handling.
	 * 3. Validates that the file is a supported audio type by checking its MIME type using `finfo`, if available.
	 * 4. If valid, saves the file to a temporary directory under wp-content/uploads/classifai-temp/.
	 * 5. Returns the local file path, or a WP_Error on failure.
	 *
	 * @param string $url Remote URL to an audio file.
	 * @return string|WP_Error The path to the saved file on success, or WP_Error on failure.
	 */
	public static function remote_url_to_path( string $url ) {
		$upload_dir = wp_upload_dir();
		$temp_dir   = trailingslashit( $upload_dir['basedir'] ) . 'classifai-temp/';

		if ( ! file_exists( $temp_dir ) ) {
			wp_mkdir_p( $temp_dir );
		}

		$response = safe_wp_remote_get(
			$url,
			[
				'timeout' => 10, // phpcs:ignore WordPressVIPMinimum.Performance.RemoteRequestTimeout.timeout_timeout
			]
		);

		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'download_failed', __( 'Failed to download remote file: ', 'classifai' ) . $response->get_error_message() );
		}

		$body = wp_remote_retrieve_body( $response );

		if ( empty( $body ) ) {
			return new WP_Error( 'empty_download', __( 'Downloaded file is empty.', 'classifai' ) );
		}

		/**
		 * Using `php://temp` to stream the remote file into memory (or to a secure system temp file if it exceeds the memory limit).
		 *
		 * WP_Filesystem does not support PHP stream wrappers like `php://temp`, so native file functions (fopen, fwrite, fclose)
		 * are required here. This usage is safe because PHP handles any overflow using the system's temp directory (e.g., /tmp),
		 * which is outside the web root in properly configured environments.
		 *
		 * phpcs:ignore comments are used to silence warnings about native file functions in this controlled context.
		 */
		$stream = fopen( 'php://temp', 'r+' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen, WordPress.WP.AlternativeFunctions.file_system_read_fopen
		fwrite( $stream, $body ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite, WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_fwrite
		rewind( $stream );

		// Determine file type using finfo if the function is available.
		$real_mime_type = false;

		if ( function_exists( 'finfo_open' ) ) {
			$finfo          = finfo_open( FILEINFO_MIME_TYPE );
			$real_mime_type = finfo_buffer( $finfo, $body );
			finfo_close( $finfo ); // phpcs:ignore PHPCompatibility.FunctionUse.RemovedFunctions.finfo_closeDeprecated
		}

		$supported_audio_mime_types = [
			'audio/mpeg',
			'audio/mp4',
			'audio/x-m4a',
			'audio/wav',
			'audio/x-wav',
			'audio/x-pn-wav',
			'audio/webm',
		];

		if ( ! in_array( $real_mime_type, $supported_audio_mime_types, true ) ) {
			return new WP_Error(
				'unsupported_audio_content',
				sprintf(
					// translators: %s The detected MIME type.
					__( 'File content does not match supported audio types. Detected: %s', 'classifai' ),
					$real_mime_type
				)
			);
		}

		// Passed MIME check, now write to disk.
		$filename = wp_basename( wp_parse_url( $url, PHP_URL_PATH ) );

		if ( empty( $filename ) ) {
			$filename = 'audio_' . time() . '.tmp';
		}

		$temp_file_path = $temp_dir . $filename;

		global $wp_filesystem;

		// Initialize the WordPress filesystem.
		if ( ! $wp_filesystem ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			WP_Filesystem();
		}

		rewind( $stream );
		$file_contents = stream_get_contents( $stream );
		fclose( $stream ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose

		$bytes_written = $wp_filesystem->put_contents( $temp_file_path, $file_contents );

		if ( false === $bytes_written ) {
			return new WP_Error( 'file_write_failed', __( 'Failed to write temporary file.', 'classifai' ) );
		}

		// Secondary check using wp_check_filetype if needed.
		$filetype  = wp_check_filetype( $temp_file_path );
		$extension = strtolower( $filetype['ext'] ?? '' );

		if ( empty( $extension ) ) {
			// Clean up file if created.
			$wp_filesystem->delete( $temp_file_path );
			return new WP_Error( 'filetype_unknown', __( 'Could not determine file type.', 'classifai' ) );
		}

		return $temp_file_path;
	}

	/**
	 * Generates a transcript from a given attachment ID.
	 *
	 * Validates that the current user can edit the attachment,
	 * ensures the feature is enabled, and checks whether the attachment
	 * meets the processing criteria (e.g., correct file type and size).
	 *
	 * @param int   $attachment_id Attachment post ID.
	 * @param array $args          Optional arguments to pass to the route.
	 * @return string|WP_Error Transcription result on success, or WP_Error on failure.
	 */
	public function transcribe_from_attachment( int $attachment_id = 0, array $args = [] ) {
		if ( $attachment_id && ! current_user_can( 'edit_post', $attachment_id ) && ( ! defined( 'WP_CLI' ) || ! WP_CLI ) ) {
			return new WP_Error( 'no_permission', esc_html__( 'User does not have permission to edit this attachment.', 'classifai' ) );
		}

		if ( ! $this->is_feature_enabled() ) {
			return new WP_Error( 'not_enabled', esc_html__( 'Transcript generation is disabled. Please check your settings.', 'classifai' ) );
		}

		if ( ! $this->should_process( $attachment_id ) ) {
			return new WP_Error( 'process_error', esc_html__( 'Attachment does not meet processing requirements. Ensure the file type and size meet requirements.', 'classifai' ) );
		}

		$settings          = $this->get_settings();
		$provider_id       = $settings['provider'];
		$provider_instance = $this->get_feature_provider_instance( $provider_id );

		if ( ! $provider_instance || ! method_exists( $provider_instance, 'transcribe_audio' ) ) {
			return new WP_Error( 'provider_error', esc_html__( 'Provider instance not found.', 'classifai' ) );
		}

		return $provider_instance->transcribe_audio(
			get_attached_file( $attachment_id ),
			array_merge( $args, array( 'attachment_id' => $attachment_id ) )
		);
	}

	/**
	 * Generates a transcript from a file path or remote URL.
	 *
	 * If the path is a remote URL, it is downloaded to a temporary
	 * location and deleted after processing. If it's a local path
	 * and the file exists, it is processed directly.
	 *
	 * @param string $path Absolute local path or remote URL to an audio file.
	 * @param array  $args  Optional arguments to pass to the route.
	 * @return string|WP_Error Transcription result on success, or WP_Error on failure.
	 */
	public function transcribe_from_path( string $path, array $args = [] ) {
		$settings          = $this->get_settings();
		$provider_id       = $settings['provider'];
		$provider_instance = $this->get_feature_provider_instance( $provider_id );

		if ( ! $provider_instance || ! method_exists( $provider_instance, 'transcribe_audio' ) ) {
			return new WP_Error( 'provider_error', esc_html__( 'Provider instance not found.', 'classifai' ) );
		}

		$result = '';

		if ( is_remote_url( $path ) ) {
			$temp_file_path = self::remote_url_to_path( $path );

			if ( is_wp_error( $temp_file_path ) ) {
				return $temp_file_path;
			}

			$result = $provider_instance->transcribe_audio( $temp_file_path, $args );
			wp_delete_file( $temp_file_path );
		} elseif ( is_local_path( $path ) ) {
			if ( file_exists( $path ) ) {
				return $provider_instance->transcribe_audio( $path, $args );

			} else {
				return $result;
			}
		}

		return $result;
	}

	/**
	 * Generates feature setting data required for migration from
	 * ClassifAI < 3.0.0 to 3.0.0
	 *
	 * @return array
	 */
	public function migrate_settings() {
		$old_settings = get_option( 'classifai_openai_whisper', array() );
		$new_settings = $this->get_settings();

		if ( isset( $old_settings['enable_transcripts'] ) ) {
			$new_settings['status'] = $old_settings['enable_transcripts'];
		}

		$new_settings['provider'] = 'openai_whisper';

		if ( isset( $old_settings['api_key'] ) ) {
			$new_settings['openai_whisper']['api_key'] = $old_settings['api_key'];
		}

		if ( isset( $old_settings['authenticated'] ) ) {
			$new_settings['openai_whisper']['authenticated'] = $old_settings['authenticated'];
		}

		if ( isset( $old_settings['speech_to_text_users'] ) ) {
			$new_settings['users'] = $old_settings['speech_to_text_users'];
		}

		if ( isset( $old_settings['speech_to_text_roles'] ) ) {
			$new_settings['roles'] = $old_settings['speech_to_text_roles'];
		}

		if ( isset( $old_settings['speech_to_text_user_based_opt_out'] ) ) {
			$new_settings['user_based_opt_out'] = $old_settings['speech_to_text_user_based_opt_out'];
		}

		return $new_settings;
	}
}
