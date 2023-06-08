<?php
namespace Classifai\Admin;

use Classifai\Providers\Azure\ComputerVision;
use Classifai\Providers\Azure\TextToSpeech;
use Classifai\Providers\OpenAI\Embeddings;
use Classifai\Providers\OpenAI\Whisper;
use Classifai\Providers\OpenAI\Whisper\Transcribe;
use function Classifai\get_supported_post_types;

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
		$this->embeddings          = new Embeddings( false );
		$embedding_settings        = $this->embeddings->get_settings();
		$embeddings_post_types     = [];
		$nlu_post_types            = get_supported_post_types();
		$this->text_to_speech      = new TextToSpeech( false );
		$text_to_speech_post_types = $this->text_to_speech->get_supported_post_types();

		// Set up the save post handler if we have any post types.
		if ( ! empty( $nlu_post_types ) || ! empty( $text_to_speech_post_types ) ) {
			$this->save_post_handler = new SavePostHandler();
		}

		// Set up the embeddings post types if the feature is enabled. Otherwise clear our embeddings handler.
		if ( isset( $embedding_settings['enable_classification'] ) && 1 === (int) $embedding_settings['enable_classification'] ) {
			$embeddings_post_types = $this->embeddings->supported_post_types();
		} else {
			$this->embeddings = null;
		}

		// Clear our TextToSpeech handler if no post types are set up.
		if ( empty( $text_to_speech_post_types ) ) {
			$this->text_to_speech = null;
		}

		// Merge our post types together and make them unique.
		$post_types = array_unique( array_merge( $embeddings_post_types, $nlu_post_types, $text_to_speech_post_types ) );

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
			! empty( $nlu_post_types ) ||
			( is_a( $this->embeddings, '\Classifai\Providers\OpenAI\Embeddings' ) && ! empty( $this->embeddings->supported_post_types() ) )
		) {
			$bulk_actions['classify'] = __( 'Classify', 'classifai' );
		}

		if (
			is_a( $this->text_to_speech, '\Classifai\Providers\Azure\TextToSpeech' ) &&
			in_array( get_current_screen()->post_type, $this->text_to_speech->get_supported_post_types(), true )
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
		$computer_vision_settings = $this->computer_vision->get_settings();
		$whisper_enabled          = $this->whisper->is_feature_enabled();

		if (
			'no' !== $computer_vision_settings['enable_image_tagging'] ||
			! empty( $this->computer_vision->get_alt_text_settings() )
		) {
			$bulk_actions['scan_image'] = __( 'Scan image', 'classifai' );
		}

		if ( isset( $computer_vision_settings['enable_smart_cropping'] ) && '1' === $computer_vision_settings['enable_smart_cropping'] ) {
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
			! in_array( $doaction, [ 'classify', 'text_to_speech' ], true )
		) {
			return $redirect_to;
		}

		$action = '';

		foreach ( $post_ids as $post_id ) {
			if ( 'classify' === $doaction ) {
				// Handle NLU classification.
				if ( is_a( $this->save_post_handler, '\Classifai\Admin\SavePostHandler' ) ) {
					$action = 'classified';
					$this->save_post_handler->classify( $post_id );
				}

				// Handle OpenAI Embeddings classification.
				if ( is_a( $this->embeddings, '\Classifai\Providers\OpenAI\Embeddings' ) ) {
					$action = 'classified';
					$this->embeddings->generate_embeddings_for_post( $post_id );
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

		$redirect_to = remove_query_arg( [ 'bulk_classified', 'bulk_text_to_speech', 'bulk_scanned', 'bulk_cropped', 'bulk_transcribed' ], $redirect_to );
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
		$text_to_speech = ! empty( $_GET['bulk_text_to_speech'] ) ? intval( wp_unslash( $_GET['bulk_text_to_speech'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$post_type      = ! empty( $_GET['post_type'] ) ? sanitize_text_field( wp_unslash( $_GET['post_type'] ) ) : 'post'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$scanned        = ! empty( $_GET['bulk_scanned'] ) ? intval( wp_unslash( $_GET['bulk_scanned'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$cropped        = ! empty( $_GET['bulk_cropped'] ) ? intval( wp_unslash( $_GET['bulk_cropped'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$transcribed    = ! empty( $_GET['bulk_transcribed'] ) ? intval( wp_unslash( $_GET['bulk_transcribed'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( ! $classified && ! $text_to_speech && ! $scanned && ! $cropped && ! $transcribed ) {
			return;
		}

		if ( $classified ) {
			$classified_posts_count = $classified;
			$post_type              = $post_type;
			$action                 = __( 'Classified', 'classifai' );
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

		if ( is_a( $this->text_to_speech, '\Classifai\Providers\Azure\TextToSpeech' ) ) {
			if ( in_array( $post->post_type, $this->text_to_speech->get_supported_post_types(), true ) ) {
				$actions['text_to_speech'] = sprintf(
					'<a href="%s">%s</a>',
					esc_url( wp_nonce_url( admin_url( sprintf( 'edit.php?action=text_to_speech&ids=%d&post_type=%s', $post->ID, $post->post_type ) ), 'bulk-posts' ) ),
					esc_html__( 'Text to speech', 'classifai' )
				);
			}
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
		$whisper_enabled  = $this->whisper->is_feature_enabled( $post->ID );

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
