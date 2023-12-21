<?php
namespace Classifai\Admin;

use Classifai\Providers\Azure\ComputerVision;
use Classifai\Providers\Azure\TextToSpeech;
use Classifai\Providers\OpenAI\ChatGPT;
use Classifai\Providers\OpenAI\Embeddings;
use Classifai\Providers\OpenAI\Whisper;
use Classifai\Providers\OpenAI\Whisper\Transcribe;
use Classifai\Providers\Watson\NLU;

use function Classifai\get_post_types_for_language_settings;
use function Classifai\get_supported_post_types;
use function Classifai\get_tts_supported_post_types;

/**
 * Handle bulk actions.
 */
class BulkActions {

	/**
	 * Check to see if we can register this class.
	 *
	 * @return bool
	 */
	public function can_register() {
		return is_admin();
	}

	/**
	 * @var SavePostHandler Triggers a classification with Watson
	 */
	private $save_post_handler;

	/**
	 * @var \Classifai\Providers\Azure\ComputerVision
	 */
	private $computer_vision;

	/**
	 * @var \Classifai\Providers\OpenAI\ChatGPT
	 */
	private $chat_gpt;

	/**
	 * @var \Classifai\Providers\OpenAI\Embeddings
	 */
	private $embeddings;

	/**
	 * @var \Classifai\Providers\OpenAI\Whisper
	 */
	private $whisper;

	/**
	 * @var \Classifai\Providers\Azure\TextToSpeech
	 */
	private $text_to_speech;

	/**
	 * @var \Classifai\Providers\Watson\NLU
	 */
	private $ibm_watson_nlu;

	/**
	 * Register the actions needed.
	 */
	public function register() {
		$this->register_language_processing_hooks();
		$this->register_image_processing_hooks();

		add_action( 'admin_notices', [ $this, 'bulk_action_admin_notice' ] );
	}

	/**
	 * Register bulk actions for language processing.
	 */
	public function register_language_processing_hooks() {
		$this->chat_gpt       = new ChatGPT( false );
		$this->embeddings     = new Embeddings( false );
		$this->text_to_speech = new TextToSpeech( false );
		$this->ibm_watson_nlu = new NLU( false );

		$embeddings_post_types     = [];
		$nlu_post_types            = [];
		$text_to_speech_post_types = [];
		$chat_gpt_post_types       = [];

		// Set up the NLU post types if the feature is enabled. Otherwise clear.
		if (
			$this->ibm_watson_nlu &&
			$this->ibm_watson_nlu->is_feature_enabled( 'content_classification' )
		) {
			$nlu_post_types = get_supported_post_types();
		} else {
			$this->ibm_watson_nlu = null;
		}

		// Set up the NLU post types if the feature is enabled. Otherwise clear.
		if (
			$this->text_to_speech &&
			$this->text_to_speech->is_feature_enabled( 'content_classification' )
		) {
			$text_to_speech_post_types = get_tts_supported_post_types();
		} else {
			$this->text_to_speech = null;
		}

		// Set up the save post handler if we have any post types.
		if ( ! empty( $nlu_post_types ) || ! empty( $text_to_speech_post_types ) ) {
			$this->save_post_handler = new SavePostHandler();
		}

		// Set up the ChatGPT post types if the feature is enabled. Otherwise clear our handler.
		if (
			$this->chat_gpt &&
			$this->chat_gpt->is_feature_enabled( 'excerpt_generation' )
		) {
			$chat_gpt_post_types = array_keys( get_post_types_for_language_settings() );
		} else {
			$this->chat_gpt = null;
		}

		// Set up the embeddings post types if the feature is enabled. Otherwise clear our embeddings handler.
		if ( $this->embeddings && $this->embeddings->is_feature_enabled( 'classification' ) ) {
			$embeddings_post_types = $this->embeddings->supported_post_types();
		} else {
			$this->embeddings = null;
		}

		// Merge our post types together and make them unique.
		$post_types = array_unique( array_merge( $chat_gpt_post_types, $embeddings_post_types, $nlu_post_types, $text_to_speech_post_types ) );

		if ( empty( $post_types ) ) {
			return;
		}

		foreach ( $post_types as $post_type ) {
			add_filter( "bulk_actions-edit-$post_type", [ $this, 'register_bulk_actions' ] );
			add_filter( "handle_bulk_actions-edit-$post_type", [ $this, 'bulk_action_handler' ], 10, 3 );

			if ( is_post_type_hierarchical( $post_type ) ) {
				add_filter( 'page_row_actions', [ $this, 'register_row_action' ], 10, 2 );
			} else {
				add_filter( 'post_row_actions', [ $this, 'register_row_action' ], 10, 2 );
			}
		}
	}

	/**
	 * Register bulk actions for the Computer Vision provider.
	 */
	public function register_image_processing_hooks() {
		$this->computer_vision = new ComputerVision( false );
		$this->whisper         = new Whisper( false );

		add_filter( 'bulk_actions-upload', [ $this, 'register_media_bulk_actions' ] );
		add_filter( 'handle_bulk_actions-upload', [ $this, 'media_bulk_action_handler' ], 10, 3 );
		add_filter( 'media_row_actions', [ $this, 'register_media_row_action' ], 10, 2 );
	}

	/**
	 * Register language processing bulk actions.
	 *
	 * @param array $bulk_actions Current bulk actions.
	 *
	 * @return array
	 */
	public function register_bulk_actions( $bulk_actions ) {
		$nlu_post_types = get_supported_post_types();

		if (
			(
				is_a( $this->ibm_watson_nlu, '\Classifai\Providers\Watson\NLU' ) &&
				$this->ibm_watson_nlu->is_feature_enabled( 'content_classification' ) &&
				! empty( $nlu_post_types )
			) ||
			(
				is_a( $this->embeddings, '\Classifai\Providers\OpenAI\Embeddings' ) &&
				$this->embeddings->is_feature_enabled( 'classification' ) &&
				! empty( $this->embeddings->supported_post_types() )
			)
		) {
			$bulk_actions['classify'] = __( 'Classify', 'classifai' );
		}

		if (
			is_a( $this->chat_gpt, '\Classifai\Providers\OpenAI\ChatGPT' ) &&
			in_array( get_current_screen()->post_type, array_keys( get_post_types_for_language_settings() ), true ) &&
			$this->chat_gpt->is_feature_enabled( 'excerpt_generation' )
		) {
			$bulk_actions['generate_excerpt'] = __( 'Generate excerpt', 'classifai' );
		}

		if (
			is_a( $this->text_to_speech, '\Classifai\Providers\Azure\TextToSpeech' ) &&
			in_array( get_current_screen()->post_type, get_tts_supported_post_types(), true ) &&
			$this->text_to_speech->is_feature_enabled( 'text_to_speech' )
		) {
			$bulk_actions['text_to_speech'] = __( 'Text to speech', 'classifai' );
		}

		return $bulk_actions;
	}

	/**
	 * Register Classifai media bulk actions.
	 *
	 * @param array $bulk_actions Current bulk actions.
	 *
	 * @return array
	 */
	public function register_media_bulk_actions( $bulk_actions ) {
		$whisper_enabled = $this->whisper->is_feature_enabled( 'speech_to_text' );

		if (
			$this->computer_vision->is_feature_enabled( 'image_tagging' ) ||
			$this->computer_vision->is_feature_enabled( 'image_captions' )
		) {
			$bulk_actions['scan_image'] = __( 'Scan image', 'classifai' );
		}

		if ( $this->computer_vision && $this->computer_vision->is_feature_enabled( 'smart_cropping' ) ) {
			$bulk_actions['smart_crop'] = __( 'Smart crop', 'classifai' );
		}

		if ( ! is_wp_error( $whisper_enabled ) ) {
			$bulk_actions['transcribe'] = __( 'Transcribe audio', 'classifai' );
		}

		return $bulk_actions;
	}

	/**
	 * Handle language processing bulk actions.
	 *
	 * @param string $redirect_to Redirect URL after bulk actions.
	 * @param string $doaction    Action ID.
	 * @param array  $post_ids    Post ids to apply bulk actions to.
	 *
	 * @return string
	 */
	public function bulk_action_handler( $redirect_to, $doaction, $post_ids ) {
		if (
			empty( $post_ids ) ||
			! in_array( $doaction, [ 'classify', 'generate_excerpt', 'text_to_speech' ], true )
		) {
			return $redirect_to;
		}

		$action = '';

		foreach ( $post_ids as $post_id ) {
			if ( 'classify' === $doaction ) {
				// Handle NLU classification.
				if (
					is_a( $this->ibm_watson_nlu, '\Classifai\Providers\Watson\NLU' ) &&
					is_a( $this->save_post_handler, '\Classifai\Admin\SavePostHandler' )
				) {
					$action = 'classified';
					$this->save_post_handler->classify( $post_id );
				}

				// Handle OpenAI Embeddings classification.
				if ( is_a( $this->embeddings, '\Classifai\Providers\OpenAI\Embeddings' ) ) {
					$action = 'classified';
					$this->embeddings->generate_embeddings_for_post( $post_id );
				}
			}

			if ( 'generate_excerpt' === $doaction ) {
				if ( is_a( $this->chat_gpt, '\Classifai\Providers\OpenAI\ChatGPT' ) ) {
					$action  = 'excerpt_generated';
					$excerpt = $this->chat_gpt->generate_excerpt( $post_id );
					if ( ! is_wp_error( $excerpt ) ) {
						wp_update_post(
							[
								'ID'           => $post_id,
								'post_excerpt' => $excerpt,
							]
						);
					}
				}
			}

			if ( 'text_to_speech' === $doaction ) {
				// Handle Azure Text to Speech generation.
				if (
					is_a( $this->text_to_speech, '\Classifai\Providers\Azure\TextToSpeech' ) &&
					is_a( $this->save_post_handler, '\Classifai\Admin\SavePostHandler' )
				) {
					$action = 'text_to_speech';
					$this->save_post_handler->synthesize_speech( $post_id );
				}
			}
		}

		$redirect_to = remove_query_arg( [ 'bulk_classified', 'bulk_excerpt_generated', 'bulk_text_to_speech', 'bulk_scanned', 'bulk_cropped', 'bulk_transcribed' ], $redirect_to );
		$redirect_to = add_query_arg( rawurlencode( "bulk_{$action}" ), count( $post_ids ), $redirect_to );

		return esc_url_raw( $redirect_to );
	}

	/**
	 * Handle media bulk actions.
	 *
	 * @param string $redirect_to       Redirect URL after bulk actions.
	 * @param string $doaction          Action ID.
	 * @param array  $attachment_ids    Attachment ids to apply bulk actions to.
	 *
	 * @return string
	 */
	public function media_bulk_action_handler( $redirect_to, $doaction, $attachment_ids ) {
		if (
			empty( $attachment_ids ) ||
			! in_array( $doaction, [ 'scan_image', 'smart_crop', 'transcribe' ], true )
		) {
			return $redirect_to;
		}

		$action = '';

		foreach ( $attachment_ids as $attachment_id ) {
			if ( 'transcribe' === $doaction ) {
				$action = 'transcribed';
				$this->whisper->transcribe_audio( $attachment_id );
				continue;
			}

			$current_meta = wp_get_attachment_metadata( $attachment_id );

			if ( 'smart_crop' === $doaction ) {
				$action = 'cropped';
				$this->computer_vision->smart_crop_image( $current_meta, $attachment_id );
			} elseif ( 'scan_image' === $doaction ) {
				$action = 'scanned';
				$this->computer_vision->generate_image_alt_tags( $current_meta, $attachment_id );
			}
		}

		$redirect_to = remove_query_arg( [ 'bulk_classified', 'bulk_text_to_speech', 'bulk_scanned', 'bulk_cropped', 'bulk_transcribed' ], $redirect_to );
		$redirect_to = add_query_arg( rawurlencode( "bulk_{$action}" ), count( $attachment_ids ), $redirect_to );

		return esc_url_raw( $redirect_to );
	}

	/**
	 * Display an admin notice after bulk updates.
	 */
	public function bulk_action_admin_notice() {

		$classified     = ! empty( $_GET['bulk_classified'] ) ? intval( wp_unslash( $_GET['bulk_classified'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$excerpts       = ! empty( $_GET['bulk_excerpt_generated'] ) ? intval( wp_unslash( $_GET['bulk_excerpt_generated'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$text_to_speech = ! empty( $_GET['bulk_text_to_speech'] ) ? intval( wp_unslash( $_GET['bulk_text_to_speech'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$post_type      = ! empty( $_GET['post_type'] ) ? sanitize_text_field( wp_unslash( $_GET['post_type'] ) ) : 'post'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$scanned        = ! empty( $_GET['bulk_scanned'] ) ? intval( wp_unslash( $_GET['bulk_scanned'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$cropped        = ! empty( $_GET['bulk_cropped'] ) ? intval( wp_unslash( $_GET['bulk_cropped'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$transcribed    = ! empty( $_GET['bulk_transcribed'] ) ? intval( wp_unslash( $_GET['bulk_transcribed'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( ! $classified && ! $excerpts && ! $text_to_speech && ! $scanned && ! $cropped && ! $transcribed ) {
			return;
		}

		if ( $classified ) {
			$classified_posts_count = $classified;
			$post_type              = $post_type;
			$action                 = __( 'Classified', 'classifai' );
		} elseif ( $excerpts ) {
			$classified_posts_count = $excerpts;
			$post_type              = $post_type;
			$action                 = __( 'Excerpts generated for', 'classifai' );
		} elseif ( $text_to_speech ) {
			$classified_posts_count = $text_to_speech;
			$post_type              = $post_type;
			$action                 = __( 'Text to speech conversion done for', 'classifai' );
		} elseif ( $scanned ) {
			$classified_posts_count = $scanned;
			$post_type              = 'image';
			$action                 = __( 'Scanned', 'classifai' );
		} elseif ( $cropped ) {
			$classified_posts_count = $cropped;
			$post_type              = 'image';
			$action                 = __( 'Cropped', 'classifai' );
		} elseif ( $transcribed ) {
			$classified_posts_count = $transcribed;
			$post_type              = 'audio';
			$action                 = __( 'Transcribed', 'classifai' );
		}

		$output  = '<div id="message" class="notice notice-success is-dismissible fade"><p>';
		$output .= sprintf(
			/* translators: %1$s: action, %2$s: number of posts, %3$s: post type*/
			_n(
				'%1$s %2$s %3$s.',
				'%1$s %2$s %3$ss.',
				$classified_posts_count,
				'classifai'
			),
			$action,
			$classified_posts_count,
			$post_type
		);
		$output .= '</p></div>';

		echo wp_kses(
			$output,
			[
				'div' => [
					'class' => [],
					'id'    => [],
				],
				'p'   => [],
			]
		);
	}

	/**
	 * Register Classifai row action.
	 *
	 * @param array    $actions Current row actions.
	 * @param \WP_Post $post    Post object.
	 *
	 * @return array
	 */
	public function register_row_action( $actions, $post ) {
		$post_types = [];

		if ( is_a( $this->save_post_handler, '\Classifai\Admin\SavePostHandler' ) ) {
			$post_types = array_merge( $post_types, get_supported_post_types() );
		}

		if ( is_a( $this->embeddings, '\Classifai\Providers\OpenAI\Embeddings' ) ) {
			$post_types = array_merge( $post_types, $this->embeddings->supported_post_types() );
		}

		if ( in_array( $post->post_type, $post_types, true ) ) {
			$actions['classify'] = sprintf(
				'<a href="%s">%s</a>',
				esc_url( wp_nonce_url( admin_url( sprintf( 'edit.php?action=classify&ids=%d&post_type=%s', $post->ID, $post->post_type ) ), 'bulk-posts' ) ),
				esc_html__( 'Classify', 'classifai' )
			);
		}

		if ( is_a( $this->chat_gpt, '\Classifai\Providers\OpenAI\ChatGPT' ) ) {
			if ( in_array( $post->post_type, array_keys( get_post_types_for_language_settings() ), true ) ) {
				$actions['generate_excerpt'] = sprintf(
					'<a href="%s">%s</a>',
					esc_url( wp_nonce_url( admin_url( sprintf( 'edit.php?action=generate_excerpt&ids=%d&post_type=%s', $post->ID, $post->post_type ) ), 'bulk-posts' ) ),
					esc_html__( 'Generate excerpt', 'classifai' )
				);
			}
		}

		if ( is_a( $this->text_to_speech, '\Classifai\Providers\Azure\TextToSpeech' ) && $this->text_to_speech->is_feature_enabled( 'text_to_speech' ) && in_array( $post->post_type, get_tts_supported_post_types(), true ) ) {
			$actions['text_to_speech'] = sprintf(
				'<a href="%s">%s</a>',
				esc_url( wp_nonce_url( admin_url( sprintf( 'edit.php?action=text_to_speech&ids=%d&post_type=%s', $post->ID, $post->post_type ) ), 'bulk-posts' ) ),
				esc_html__( 'Text to speech', 'classifai' )
			);
		}

		return $actions;
	}

	/**
	 * Register media row actions.
	 *
	 * @param array    $actions An array of action links for each attachment.
	 * @param \WP_Post $post WP_Post object for the current attachment.
	 * @return array
	 */
	public function register_media_row_action( $actions, $post ) {
		$whisper_settings = $this->whisper->get_settings();
		$whisper_enabled  = $this->whisper->is_feature_enabled( 'speech_to_text', $post->ID );

		if ( is_wp_error( $whisper_enabled ) ) {
			return $actions;
		}

		$transcribe = new Transcribe( $post->ID, $whisper_settings );

		if ( $transcribe->should_process( $post->ID ) ) {
			$actions['transcribe'] = sprintf(
				'<a href="%s">%s</a>',
				esc_url( wp_nonce_url( admin_url( sprintf( 'upload.php?action=transcribe&ids=%d&post_type=%s', $post->ID, $post->post_type ) ), 'bulk-media' ) ),
				esc_html__( 'Transcribe', 'classifai' )
			);
		}

		return $actions;
	}

}
