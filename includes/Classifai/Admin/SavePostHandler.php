<?php

namespace Classifai\Admin;

use \Classifai\Providers\Azure\TextToSpeech;
use \Classifai\Watson\Normalizer;

/**
 * Classifies Posts based on the current ClassifAI configuration.
 */
class SavePostHandler {

	/**
	 * @var $classifier \Classifai\PostClassifier Lazy loaded classifier object
	 */
	public $classifier;

	/**
	 * Enables the classification on save post behaviour.
	 */
	public function register() {
		add_filter( 'removable_query_args', [ $this, 'classifai_removable_query_args' ] );
		add_action( 'save_post', [ $this, 'did_save_post' ] );
		add_action( 'admin_notices', [ $this, 'show_error_if' ] );
		add_action( 'admin_post_classifai_classify_post', array( $this, 'classifai_classify_post' ) );
	}

	/**
	 * Check to see if we can register this class.
	 */
	public function can_register() {

		$should_register = false;
		if ( $this->is_configured() && ( is_admin() || $this->is_rest_route() ) ) {
			$should_register = true;
		}

		/**
		 * Filter whether ClassifAI should register this class.
		 *
		 * @since 1.8.0
		 * @hook classifai_should_register_save_post_handler
		 *
		 * @param  {bool} $should_register Whether the class should be registered.
		 * @return {bool} Whether the class should be registered.
		 */
		$should_register = apply_filters( 'classifai_should_register_save_post_handler', $should_register );

		return $should_register;
	}

	/**
	 * Check if ClassifAI is properly configured.
	 *
	 * @return bool
	 */
	public function is_configured() {
		return ! empty( get_option( 'classifai_configured' ) ) && ! empty( get_option( 'classifai_watson_nlu' )['credentials']['watson_url'] );
	}

	/**
	 * If current post type support is enabled in ClassifAI settings, it
	 * is tagged using the IBM Watson classification result.
	 *
	 * Skips classification if running under the Gutenberg Metabox
	 * compatibility request. The classification is performed during the REST
	 * lifecyle when using Gutenberg.
	 *
	 * @param int $post_id The post that was saved
	 */
	public function did_save_post( $post_id ) {
		if ( ! empty( $_GET['classic-editor'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		$supported     = \Classifai\get_supported_post_types();
		$post_type     = get_post_type( $post_id );
		$post_status   = get_post_status( $post_id );
		$post_statuses = \Classifai\get_supported_post_statuses();

		/**
		 * Filter post statuses for post type or ID.
		 *
		 * @since 1.7.1
		 * @hook classifai_post_statuses_for_post_type_or_id
		 *
		 * @param {array} $post_statuses Array of post statuses to be classified with language processing.
		 * @param {string} $post_type The post type.
		 * @param {int} $post_id The post ID.
		 *
		 * @return {array} Array of post statuses.
		 */
		$post_statuses = apply_filters( 'classifai_post_statuses_for_post_type_or_id', $post_statuses, $post_type, $post_id );

		// Process posts in allowed post statuses, supported items and only if features are enabled
		if ( in_array( $post_status, $post_statuses, true ) && in_array( $post_type, $supported, true ) && \Classifai\language_processing_features_enabled() ) {
			// Check if processing content on save is disabled.
			$classifai_process_content = get_post_meta( $post_id, '_classifai_process_content', true );
			if ( 'no' === $classifai_process_content ) {
				return;
			}
			$this->classify( $post_id );
		}
	}

	/**
	 * Classifies the post specified with the PostClassifier object.
	 * Existing terms relationships are removed before classification.
	 *
	 * @param int $post_id the post to classify & link
	 *
	 * @return array
	 */
	public function classify( $post_id ) {
		/**
		 * Filter whether ClassifAI should classify a post.
		 *
		 * Default is true, return false to skip classifying a post.
		 *
		 * @since 1.2.0
		 * @hook classifai_should_classify_post
		 *
		 * @param {bool} $should_classify Whether the post should be classified. Default `true`, return `false` to skip
		 *                                classification for this post.
		 * @param {int}  $post_id         The ID of the post to be considered for classification.
		 *
		 * @return {bool} Whether the post should be classified.
		 */
		$classifai_should_classify_post = apply_filters( 'classifai_should_classify_post', true, $post_id );
		if ( ! $classifai_should_classify_post ) {
			return false;
		}

		$classifier = $this->get_classifier();

		if ( \Classifai\get_feature_enabled( 'category' ) ) {
			wp_delete_object_term_relationships( $post_id, \Classifai\get_feature_taxonomy( 'category' ) );
		}

		if ( \Classifai\get_feature_enabled( 'keyword' ) ) {
			wp_delete_object_term_relationships( $post_id, \Classifai\get_feature_taxonomy( 'keyword' ) );
		}

		if ( \Classifai\get_feature_enabled( 'concept' ) ) {
			wp_delete_object_term_relationships( $post_id, \Classifai\get_feature_taxonomy( 'concept' ) );
		}

		if ( \Classifai\get_feature_enabled( 'entity' ) ) {
			wp_delete_object_term_relationships( $post_id, \Classifai\get_feature_taxonomy( 'entity' ) );
		}

		$output = $classifier->classify_and_link( $post_id );

		if ( is_wp_error( $output ) ) {
			update_post_meta(
				$post_id,
				'_classifai_error',
				wp_json_encode(
					[
						'code'    => $output->get_error_code(),
						'message' => $output->get_error_message(),
					]
				)
			);
		} else {
			// If there is no error, clear any existing error states.
			delete_post_meta( $post_id, '_classifai_error' );
		}

		return $output;
	}

	/**
	 * Synthesizes speech from the post title and content.
	 *
	 * @param int $post_id Post ID.
	 * @return bool|int|WP_Error
	 */
	public function synthesize_speech( $post_id ) {
		if ( empty( $post_id ) ) {
			return new \WP_Error(
				'azure_text_to_speech_post_id_missing',
				esc_html__( 'Post ID missing.', 'classifai' )
			);
		}

		// We skip the user cap check if running under WP-CLI.
		if ( ! current_user_can( 'edit_post', $post_id ) && ( ! defined( 'WP_CLI' ) || ! WP_CLI ) ) {
			return new \WP_Error(
				'azure_text_to_speech_user_not_authorized',
				esc_html__( 'Unauthorized user.', 'classifai' )
			);
		}

		$normalizer          = new Normalizer();
		$settings            = \Classifai\get_plugin_settings( 'language_processing', TextToSpeech::FEATURE_NAME );
		$post                = get_post( $post_id );
		$post_content        = $normalizer->normalize_content( $post->post_content, $post->post_title, $post_id );
		$content_hash        = get_post_meta( $post_id, TextToSpeech::AUDIO_HASH_KEY, true );
		$saved_attachment_id = (int) get_post_meta( $post_id, TextToSpeech::AUDIO_ID_KEY, true );

		// Don't regenerate the audio file it it already exists and the content hasn't changed.
		if ( $saved_attachment_id ) {

			// Check if the audio file exists.
			$audio_attachment_url = wp_get_attachment_url( $saved_attachment_id );

			if ( $audio_attachment_url && ! empty( $content_hash ) && ( md5( $post_content ) === $content_hash ) ) {
				return $saved_attachment_id;
			}
		}

		$voice        = $settings['voice'] ?? '';
		$voice_data   = explode( '|', $voice );
		$voice_name   = '';
		$voice_gender = '';

		// Extract the voice name and gender from the option value.
		if ( 2 === count( $voice_data ) ) {
			$voice_name   = $voice_data[0];
			$voice_gender = $voice_data[1];

			// Return error if voice is not set in settings.
		} else {
			return new \WP_Error(
				'azure_text_to_speech_voice_information_missing',
				esc_html__( 'Voice data not set.', 'classifai' )
			);
		}

		// Create the request body to synthesize speech from text.
		$request_body = sprintf(
			"<speak version='1.0' xml:lang='en-US'><voice xml:lang='en-US' xml:gender='%s' name='%s'>%s</voice></speak>",
			$voice_gender,
			$voice_name,
			$post_content
		);

		// Request parameters.
		$request_params = array(
			'method'  => 'POST',
			'body'    => $request_body,
			'timeout' => 60, // phpcs:ignore WordPressVIPMinimum.Performance.RemoteRequestTimeout.timeout_timeout
			'headers' => array(
				'Ocp-Apim-Subscription-Key' => $settings['credentials']['api_key'],
				'Content-Type'              => 'application/ssml+xml',
				'X-Microsoft-OutputFormat'  => 'audio-16khz-128kbitrate-mono-mp3',
			),
		);

		$remote_url = sprintf( '%s%s', $settings['credentials']['url'], TextToSpeech::API_PATH );
		$response   = wp_remote_post( $remote_url, $request_params );

		if ( is_wp_error( $response ) ) {
			return new \WP_Error(
				'azure_text_to_speech_http_error',
				esc_html( $response->get_error_message() )
			);
		}

		$code          = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );

		// return error if HTTP status code is not 200.
		if ( \WP_Http::OK !== $code ) {
			return new \WP_Error(
				'azure_text_to_speech_unsuccessful_request',
				esc_html__( 'HTTP request unsuccessful.', 'classifai' )
			);
		}

		// If audio already exists for this post, delete it.
		if ( $saved_attachment_id ) {
			wp_delete_attachment( $saved_attachment_id, true );
			delete_post_meta( $post_id, TextToSpeech::AUDIO_ID_KEY );
			delete_post_meta( $post_id, TextToSpeech::AUDIO_TIMESTAMP_KEY );
		}

		// The audio file name.
		$audio_file_name = sprintf(
			'post-as-audio-%1$s.mp3',
			$post_id
		);

		// Upload the audio stream as an .mp3 file.
		$file_data = wp_upload_bits(
			$audio_file_name,
			null,
			$response_body
		);

		if ( isset( $file_data['error'] ) && ! empty( $file_data['error'] ) ) {
			return new \WP_Error(
				'azure_text_to_speech_upload_bits_failure',
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
			return new \WP_Error(
				'azure_text_to_speech_resource_creation_failure',
				esc_html__( 'Audio creation failed.', 'classifai' )
			);
		}

		update_post_meta( $post_id, TextToSpeech::AUDIO_ID_KEY, absint( $attachment_id ) );
		update_post_meta( $post_id, TextToSpeech::AUDIO_TIMESTAMP_KEY, time() );
		update_post_meta( $post_id, TextToSpeech::AUDIO_HASH_KEY, md5( $post_content ) );

		return $attachment_id;
	}

	/**
	 * Lazy initializes the Post Classifier object
	 */
	public function get_classifier() {
		if ( is_null( $this->classifier ) ) {
			$this->classifier = new \Classifai\PostClassifier();
		}

		return $this->classifier;
	}

	/**
	 * Outputs an Admin Notice with the error message if NLU
	 * classification had failed earlier.
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

		$error = get_post_meta( $post_id, '_classifai_error', true );

		if ( ! empty( $error ) ) {
			delete_post_meta( $post_id, '_classifai_error' );
			$error   = (array) json_decode( $error );
			$code    = ! empty( $error['code'] ) ? $error['code'] : 500;
			$message = ! empty( $error['message'] ) ? $error['message'] : 'Unknown NLU API error';

			?>
			<div class="notice notice-error is-dismissible">
				<p>
					Error: Failed to classify content with the IBM Watson NLU API.
				</p>
				<p>
					<?php echo esc_html( $code ); ?>
					-
					<?php echo esc_html( $message ); ?>
				</p>
			</div>
			<?php
		}

		// Display classify post success message for manually classified post.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$classified = isset( $_GET['classifai_classify'] ) ? intval( wp_unslash( $_GET['classifai_classify'] ) ) : 0;
		if ( 1 === $classified ) {
			$post_type       = get_post_type_object( get_post_type( $post ) );
			$post_type_label = esc_html__( 'Post', 'classifai' );
			if ( $post_type ) {
				$post_type_label = $post_type->labels->singular_name;
			}
			?>
			<div class="notice notice-success is-dismissible">
				<p>
					<?php
					// translators: %s is post type label.
					printf( esc_html__( '%s classified successfully.', 'classifai' ), esc_html( $post_type_label ) );
					?>
				</p>
			</div>
			<?php
		}
	}

	/**
	 * We need to determine if we're doing a REST call.
	 *
	 * @return bool
	 */
	public function is_rest_route() {

		if ( ! isset( $_SERVER['REQUEST_URI'] ) ) {
			return false;
		}

		/**
		 * Filter the REST bases. Supports custom post types with a custom REST base.
		 *
		 * @since 1.5.0
		 * @hook classifai_rest_bases
		 *
		 * @param {array} rest_bases Array of REST bases.
		 *
		 * @return {array} The filtered array of REST bases.
		 */
		$rest_bases = apply_filters( 'classifai_rest_bases', array( 'posts', 'pages' ) );

		foreach ( $rest_bases as $rest_base ) {
			if ( false !== strpos( sanitize_text_field( $_SERVER['REQUEST_URI'] ), 'wp-json/wp/v2/' . $rest_base ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Classify post manually.
	 *
	 * @return void
	 */
	public function classifai_classify_post() {
		if ( ! empty( $_GET['classifai_classify_post_nonce'] ) && wp_verify_nonce( sanitize_text_field( $_GET['classifai_classify_post_nonce'] ), 'classifai_classify_post_action' ) ) {
			$post_id = isset( $_GET['post_id'] ) ? absint( $_GET['post_id'] ) : 0;
			if ( $post_id ) {
				$result     = $this->classify( $post_id );
				$classified = array();
				if ( ! is_wp_error( $result ) ) {
					$classified = array( 'classifai_classify' => 1 );
				}
				wp_safe_redirect( esc_url_raw( add_query_arg( $classified, get_edit_post_link( $post_id, 'edit' ) ) ) );
				exit();
			}
		} else {
			wp_die( esc_html__( 'You don\'t have permission to perform this operation.', 'classifai' ) );
		}
	}

	/**
	 * Add "classifai_classify" in list of query variable names to remove.
	 *
	 * @param string[] $removable_query_args An array of query variable names to remove from a URL.
	 * @return string[]
	 */
	public function classifai_removable_query_args( $removable_query_args ) {
		$removable_query_args[] = 'classifai_classify';
		return $removable_query_args;
	}
}
