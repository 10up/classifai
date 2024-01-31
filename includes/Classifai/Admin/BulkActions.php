<?php
namespace Classifai\Admin;

use Classifai\Features\AudioTranscriptsGeneration;
use Classifai\Features\Classification;
use Classifai\Features\DescriptiveTextGenerator;
use Classifai\Features\ExcerptGeneration;
use Classifai\Features\ImageCropping;
use Classifai\Features\ImageTagsGenerator;
use Classifai\Features\ImageTextExtraction;
use Classifai\Features\PDFTextExtraction;
use Classifai\Features\TextToSpeech;

use function Classifai\attachment_is_pdf;

/**
 * Handle bulk actions.
 */
class BulkActions {

	/**
	 * Array of language processing features.
	 *
	 * @var \Classifai\Features\Feature[]
	 */
	private $language_processing_features = [];

	/**
	 * Array of media processing features.
	 *
	 * @var \Classifai\Features\Feature[]
	 */
	private $media_processing_features = [];

	/**
	 * Check to see if we can register this class.
	 *
	 * @return bool
	 */
	public function can_register(): bool {
		return is_admin();
	}

	/**
	 * Register the actions needed.
	 */
	public function register() {
		$this->register_language_processing_hooks();
		$this->register_image_processing_hooks();

		add_action( 'admin_notices', [ $this, 'bulk_action_admin_notice' ] );
	}

	/**
	 * Register hooks for the features.
	 */
	public function register_language_processing_hooks() {
		$this->language_processing_features = [
			new Classification(),
			new ExcerptGeneration(),
			new TextToSpeech(),
		];

		foreach ( $this->language_processing_features as $feature ) {
			if ( ! $feature->is_feature_enabled() ) {
				continue;
			}

			$settings = $feature->get_settings();

			if ( ! isset( $settings['post_types'] ) ) {
				continue;
			}

			foreach ( $settings['post_types'] as $key => $post_type ) {
				add_filter( "bulk_actions-edit-$post_type", [ $this, 'register_language_processing_actions' ] );
				add_filter( "handle_bulk_actions-edit-$post_type", [ $this, 'language_processing_actions_handler' ], 10, 3 );

				if ( is_post_type_hierarchical( $post_type ) ) {
					add_filter( 'page_row_actions', [ $this, 'register_language_processing_row_action' ], 10, 2 );
				} else {
					add_filter( 'post_row_actions', [ $this, 'register_language_processing_row_action' ], 10, 2 );
				}
			}
		}
	}

	/**
	 * Register Language Processing bulk actions.
	 *
	 * @param array $bulk_actions Current bulk actions.
	 * @return array
	 */
	public function register_language_processing_actions( array $bulk_actions ): array {
		foreach ( $this->language_processing_features as $feature ) {
			if ( ! $feature->is_feature_enabled() ) {
				continue;
			}

			$bulk_actions[ $feature::ID ] = $feature->get_label();

			switch ( $feature::ID ) {
				case Classification::ID:
					$bulk_actions[ $feature::ID ] = esc_html__( 'Classify', 'classifai' );
					break;

				case ExcerptGeneration::ID:
					$bulk_actions[ $feature::ID ] = esc_html__( 'Generate Excerpt', 'classifai' );
					break;

				case TextToSpeech::ID:
					$bulk_actions[ $feature::ID ] = esc_html__( 'Generate audio (text to speech)', 'classifai' );
					break;
			}
		}

		return $bulk_actions;
	}

	/**
	 * Handle language processing bulk actions.
	 *
	 * @param string $redirect_to Redirect URL after bulk actions.
	 * @param string $doaction    Action ID.
	 * @param array  $post_ids    Post ids to apply bulk actions to.
	 * @return string
	 */
	public function language_processing_actions_handler( string $redirect_to, string $doaction, array $post_ids ): string {
		$feature_ids = array_map(
			function ( $feature ) {
				return $feature::ID;
			},
			$this->language_processing_features
		);

		if (
			empty( $post_ids ) ||
			! in_array( $doaction, $feature_ids, true )
		) {
			return $redirect_to;
		}

		foreach ( $post_ids as $post_id ) {
			switch ( $doaction ) {
				case Classification::ID:
					( new Classification() )->run( $post_id );
					$action = $doaction;
					break;

				case ExcerptGeneration::ID:
					$excerpt = ( new ExcerptGeneration() )->run( $post_id, 'excerpt' );
					$action  = $doaction;

					if ( ! is_wp_error( $excerpt ) ) {
						wp_update_post(
							[
								'ID'           => $post_id,
								'post_excerpt' => $excerpt,
							]
						);
					}
					break;

				case TextToSpeech::ID:
					$tts     = new TextToSpeech();
					$results = $tts->run( $post_id, 'synthesize' );

					if ( $results && ! is_wp_error( $results ) ) {
						$tts->save( $results, $post_id );
					}
					$action = $doaction;
					break;
			}
		}

		$args_to_remove = array_map(
			function ( $feature ) {
				return "bulk_{$feature}";
			},
			$feature_ids
		);

		$redirect_to = remove_query_arg( $args_to_remove, $redirect_to );
		$redirect_to = add_query_arg( rawurlencode( "bulk_{$action}" ), count( $post_ids ), $redirect_to );

		return esc_url_raw( $redirect_to );
	}

	/**
	 * Register Language Processing row actions.
	 *
	 * @param array    $actions Current row actions.
	 * @param \WP_Post $post    Post object.
	 * @return array
	 */
	public function register_language_processing_row_action( array $actions, \WP_Post $post ): array {
		foreach ( $this->language_processing_features as $feature ) {
			if ( ! $feature->is_feature_enabled() ) {
				continue;
			}

			switch ( $feature::ID ) {
				case Classification::ID:
					$actions[ Classification::ID ] = sprintf(
						'<a href="%s">%s</a>',
						esc_url( wp_nonce_url( admin_url( sprintf( 'edit.php?action=%s&ids=%d&post_type=%s', Classification::ID, $post->ID, $post->post_type ) ), 'bulk-posts' ) ),
						esc_html__( 'Classify', 'classifai' )
					);
					break;

				case ExcerptGeneration::ID:
					$actions[ ExcerptGeneration::ID ] = sprintf(
						'<a href="%s">%s</a>',
						esc_url( wp_nonce_url( admin_url( sprintf( 'edit.php?action=%s&ids=%d&post_type=%s', ExcerptGeneration::ID, $post->ID, $post->post_type ) ), 'bulk-posts' ) ),
						esc_html__( 'Generate excerpt', 'classifai' )
					);
					break;

				case TextToSpeech::ID:
					$actions[ TextToSpeech::ID ] = sprintf(
						'<a href="%s">%s</a>',
						esc_url( wp_nonce_url( admin_url( sprintf( 'edit.php?action=%s&ids=%d&post_type=%s', TextToSpeech::ID, $post->ID, $post->post_type ) ), 'bulk-posts' ) ),
						esc_html__( 'Text to speech', 'classifai' )
					);
					break;
			}
		}

		return $actions;
	}

	/**
	 * Register Image Processing hooks.
	 */
	public function register_image_processing_hooks() {
		$this->media_processing_features = [
			new DescriptiveTextGenerator(),
			new ImageTagsGenerator(),
			new ImageCropping(),
			new ImageTextExtraction(),
			new PDFTextExtraction(),
			new AudioTranscriptsGeneration(),
		];

		add_filter( 'bulk_actions-upload', [ $this, 'register_media_processing_media_bulk_actions' ] );
		add_filter( 'handle_bulk_actions-upload', [ $this, 'media_processing_bulk_action_handler' ], 10, 3 );
		add_filter( 'media_row_actions', [ $this, 'register_media_processing_row_action' ], 10, 2 );
	}

	/**
	 * Register Image Processing bulk actions.
	 *
	 * @param array $bulk_actions Current bulk actions.
	 * @return array
	 */
	public function register_media_processing_media_bulk_actions( array $bulk_actions ): array {
		foreach ( $this->media_processing_features as $feature ) {
			if ( ! $feature->is_feature_enabled() ) {
				continue;
			}

			$bulk_actions[ $feature::ID ] = $feature->get_label();

			switch ( $feature::ID ) {
				case DescriptiveTextGenerator::ID:
					$bulk_actions[ $feature::ID ] = esc_html__( 'Generate descriptive text', 'classifai' );
					break;

				case ImageTagsGenerator::ID:
					$bulk_actions[ $feature::ID ] = esc_html__( 'Generate image tags', 'classifai' );
					break;

				case ImageCropping::ID:
					$bulk_actions[ $feature::ID ] = esc_html__( 'Crop image', 'classifai' );
					break;

				case ImageTextExtraction::ID:
					$bulk_actions[ $feature::ID ] = esc_html__( 'Extract text from images', 'classifai' );
					break;

				case PDFTextExtraction::ID:
					$bulk_actions[ $feature::ID ] = esc_html__( 'Extract text from PDFs', 'classifai' );
					break;

				case AudioTranscriptsGeneration::ID:
					$bulk_actions[ $feature::ID ] = esc_html__( 'Transcribe audio', 'classifai' );
					break;
			}
		}

		return $bulk_actions;
	}

	/**
	 * Handle Image Processing bulk actions.
	 *
	 * @param string $redirect_to       Redirect URL after bulk actions.
	 * @param string $doaction          Action ID.
	 * @param array  $attachment_ids    Attachment ids to apply bulk actions to.
	 * @return string
	 */
	public function media_processing_bulk_action_handler( string $redirect_to, string $doaction, array $attachment_ids ): string {
		$feature_ids = array_map(
			function ( $feature ) {
				return $feature::ID;
			},
			$this->media_processing_features
		);

		if (
			empty( $attachment_ids ) ||
			! in_array( $doaction, $feature_ids, true )
		) {
			return $redirect_to;
		}

		foreach ( $attachment_ids as $attachment_id ) {
			$current_meta = wp_get_attachment_metadata( $attachment_id );

			switch ( $doaction ) {
				case DescriptiveTextGenerator::ID:
					if ( wp_attachment_is_image( $attachment_id ) ) {
						$desc_text        = new DescriptiveTextGenerator();
						$desc_text_result = $desc_text->run( $attachment_id, 'descriptive_text' );

						if ( $desc_text_result && ! is_wp_error( $desc_text_result ) ) {
							$desc_text->save( $desc_text_result, $attachment_id );
						}
					}
					break;

				case ImageTagsGenerator::ID:
					if ( wp_attachment_is_image( $attachment_id ) ) {
						$image_tags  = new ImageTagsGenerator();
						$tags_result = $image_tags->run( $attachment_id, 'tags' );

						if ( ! empty( $tags_result ) && ! is_wp_error( $tags_result ) ) {
							$image_tags->save( $tags_result, $attachment_id );
						}
					}
					break;

				case ImageCropping::ID:
					if ( wp_attachment_is_image( $attachment_id ) ) {
						$crop        = new ImageCropping();
						$crop_result = $crop->run( $attachment_id, 'crop', $current_meta );
						if ( ! empty( $crop_result ) && ! is_wp_error( $crop_result ) ) {
							$ocr_meta = $crop->save( $crop_result, $attachment_id );
							wp_update_attachment_metadata( $attachment_id, $ocr_meta );
						}
					}
					break;

				case ImageTextExtraction::ID:
					if ( wp_attachment_is_image( $attachment_id ) ) {
						$ocr        = new ImageTextExtraction();
						$ocr_result = $ocr->run( $attachment_id, 'ocr' );
						if ( $ocr_result && ! is_wp_error( $ocr_result ) ) {
							$ocr->save( $ocr_result, $attachment_id );
						}
					}
					break;

				case PDFTextExtraction::ID:
					if ( attachment_is_pdf( $attachment_id ) ) {
						( new PDFTextExtraction() )->run( $attachment_id, 'read_pdf' );
					}
					break;

				case AudioTranscriptsGeneration::ID:
					if ( wp_attachment_is( 'audio', $attachment_id ) ) {
						( new AudioTranscriptsGeneration() )->run( $attachment_id, 'transcript' );
					}
					break;
			}
		}

		$args_to_remove = array_map(
			function ( $feature ) {
				return "bulk_{$feature}";
			},
			$feature_ids
		);

		$redirect_to = remove_query_arg( $args_to_remove, $redirect_to );
		$redirect_to = add_query_arg( rawurlencode( "bulk_{$doaction}" ), count( $attachment_ids ), $redirect_to );

		return esc_url_raw( $redirect_to );
	}

	/**
	 * Register Image Processing row actions.
	 *
	 * @param array    $actions An array of action links for each attachment.
	 * @param \WP_Post $post WP_Post object for the current attachment.
	 * @return array
	 */
	public function register_media_processing_row_action( array $actions, \WP_Post $post ): array {
		if ( attachment_is_pdf( $post ) && ( new PDFTextExtraction() )->is_feature_enabled() ) {
			$actions[ PDFTextExtraction::ID ] = sprintf(
				'<a href="%s">%s</a>',
				esc_url( wp_nonce_url( admin_url( sprintf( 'upload.php?action=%s&ids=%d&post_type=%s', PDFTextExtraction::ID, $post->ID, $post->post_type ) ), 'bulk-media' ) ),
				esc_html__( 'Extract text from PDF', 'classifai' )
			);
		}

		if ( wp_attachment_is( 'image' ) ) {
			if ( ( new DescriptiveTextGenerator() )->is_feature_enabled() ) {
				$actions[ DescriptiveTextGenerator::ID ] = sprintf(
					'<a href="%s">%s</a>',
					esc_url( wp_nonce_url( admin_url( sprintf( 'upload.php?action=%s&ids=%d&post_type=%s', DescriptiveTextGenerator::ID, $post->ID, $post->post_type ) ), 'bulk-media' ) ),
					esc_html__( 'Generate descriptive text', 'classifai' )
				);
			}

			if ( ( new ImageTagsGenerator() )->is_feature_enabled() ) {
				$actions[ ImageTagsGenerator::ID ] = sprintf(
					'<a href="%s">%s</a>',
					esc_url( wp_nonce_url( admin_url( sprintf( 'upload.php?action=%s&ids=%d&post_type=%s', ImageTagsGenerator::ID, $post->ID, $post->post_type ) ), 'bulk-media' ) ),
					esc_html__( 'Generate image tags', 'classifai' )
				);
			}

			if ( ( new ImageCropping() )->is_feature_enabled() ) {
				$actions[ ImageCropping::ID ] = sprintf(
					'<a href="%s">%s</a>',
					esc_url( wp_nonce_url( admin_url( sprintf( 'upload.php?action=%s&ids=%d&post_type=%s', ImageCropping::ID, $post->ID, $post->post_type ) ), 'bulk-media' ) ),
					esc_html__( 'Crop image', 'classifai' )
				);
			}

			if ( ( new ImageTextExtraction() )->is_feature_enabled() ) {
				$actions[ ImageTextExtraction::ID ] = sprintf(
					'<a href="%s">%s</a>',
					esc_url( wp_nonce_url( admin_url( sprintf( 'upload.php?action=%s&ids=%d&post_type=%s', ImageTextExtraction::ID, $post->ID, $post->post_type ) ), 'bulk-media' ) ),
					esc_html__( 'Extract text from image', 'classifai' )
				);
			}
		}

		if ( wp_attachment_is( 'audio', $post ) && ( new AudioTranscriptsGeneration() )->is_feature_enabled() ) {
			$actions[ AudioTranscriptsGeneration::ID ] = sprintf(
				'<a href="%s">%s</a>',
				esc_url( wp_nonce_url( admin_url( sprintf( 'upload.php?action=%s&ids=%d&post_type=%s', AudioTranscriptsGeneration::ID, $post->ID, $post->post_type ) ), 'bulk-media' ) ),
				esc_html__( 'Transcribe audio', 'classifai' )
			);
		}

		return $actions;
	}

	/**
	 * Display an admin notice after bulk updates.
	 */
	public function bulk_action_admin_notice() {
		$post_count      = 0;
		$action          = '';
		$post_type       = ! empty( $_GET['post_type'] ) ? sanitize_text_field( wp_unslash( $_GET['post_type'] ) ) : 'post'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$all_feature_ids = array_map(
			function ( $feature ) {
				return $feature::ID;
			},
			array_merge( $this->language_processing_features, $this->media_processing_features )
		);

		foreach ( $all_feature_ids as $feature_id ) {
			$post_count = ! empty( $_GET[ "bulk_{$feature_id}" ] ) ? intval( wp_unslash( $_GET[ "bulk_{$feature_id}" ] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

			if ( $post_count ) {
				$action = $feature_id;
				break;
			}
		}

		if ( ! $action ) {
			return;
		}

		switch ( $feature_id ) {
			case ExcerptGeneration::ID:
				$action_text = __( 'Excerpts generated for', 'classifai' );
				break;

			case TextToSpeech::ID:
				$action_text = __( 'Text to speech conversion done for', 'classifai' );
				break;

			case PDFTextExtraction::ID:
				$action_text = __( 'PDF Text extraction done for', 'classifai' );
				$post_type   = 'file';
				break;

			case DescriptiveTextGenerator::ID:
				$action_text = __( 'Alt text generated for', 'classifai' );
				$post_type   = 'image';
				break;

			case ImageTagsGenerator::ID:
				$action_text = __( 'Tags generated for', 'classifai' );
				$post_type   = 'image';
				break;

			case ImageCropping::ID:
				$action_text = __( 'Cropping done for', 'classifai' );
				$post_type   = 'image';
				break;

			case ImageTextExtraction::ID:
				$action_text = __( 'Text extraction done for', 'classifai' );
				$post_type   = 'image';
				break;

			case AudioTranscriptsGeneration::ID:
				$action_text = __( 'Audio transcribed for', 'classifai' );
				$post_type   = 'file';
				break;

			case Classification::ID:
				$action_text = __( 'Classification done for', 'classifai' );
				break;
		}

		$output  = '<div id="message" class="notice notice-success is-dismissible fade"><p>';
		$output .= sprintf(
			/* translators: %1$s: action, %2$s: number of posts, %3$s: post type*/
			_n(
				'%1$s %2$s %3$s.',
				'%1$s %2$s %3$ss.',
				$post_count,
				'classifai'
			),
			$action_text,
			$post_count,
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
}
