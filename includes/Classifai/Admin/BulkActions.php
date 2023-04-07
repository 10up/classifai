<?php
namespace Classifai\Admin;

use Classifai\Providers\Azure\ComputerVision;
use Classifai\Providers\OpenAI\Embeddings;
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
	 * Register the actions needed.
	 */
	public function register() {
		$this->register_nlu_hooks();
		$this->register_embedding_hooks();
		$this->register_computer_vision_hooks();

		add_action( 'admin_notices', [ $this, 'bulk_action_admin_notice' ] );
	}

	/**
	 * Register bulk actions for the NLU provider.
	 */
	public function register_nlu_hooks() {
		$post_types = get_supported_post_types();

		if ( empty( $post_types ) ) {
			return;
		}

		$this->save_post_handler = new SavePostHandler();

		foreach ( $post_types as $post_type ) {
			add_filter( "bulk_actions-edit-$post_type", [ $this, 'register_bulk_actions' ] );
			add_filter( "handle_bulk_actions-edit-$post_type", [ $this, 'bulk_action_handler' ], 10, 3 );

			if ( is_post_type_hierarchical( $post_type ) ) {
				add_action( 'page_row_actions', [ $this, 'register_row_action' ], 10, 2 );
			} else {
				add_action( 'post_row_actions', [ $this, 'register_row_action' ], 10, 2 );
			}
		}
	}

	/**
	 * Register bulk actions for the Embeddings provider.
	 */
	public function register_embedding_hooks() {
		$this->embeddings = new Embeddings( false );
		$settings         = $this->embeddings->get_settings();

		if ( ! isset( $settings['enable_classification'] ) || 1 !== (int) $settings['enable_classification'] ) {
			$this->embeddings = null;
			return;
		}

		foreach ( $this->embeddings->supported_post_types() as $post_type ) {
			add_filter( "bulk_actions-edit-$post_type", [ $this, 'register_bulk_actions' ] );
			add_filter( "handle_bulk_actions-edit-$post_type", [ $this, 'bulk_action_handler' ], 10, 3 );

			if ( is_post_type_hierarchical( $post_type ) ) {
				add_action( 'page_row_actions', [ $this, 'register_row_action' ], 10, 2 );
			} else {
				add_action( 'post_row_actions', [ $this, 'register_row_action' ], 10, 2 );
			}
		}
	}

	/**
	 * Register bulk actions for the Computer Vision provider.
	 */
	public function register_computer_vision_hooks() {
		$this->computer_vision = new ComputerVision( false );

		add_filter( 'bulk_actions-upload', [ $this, 'register_media_bulk_actions' ] );
		add_filter( 'handle_bulk_actions-upload', [ $this, 'media_bulk_action_handler' ], 10, 3 );
	}

	/**
	 * Register Classifai bulk action.
	 *
	 * @param array $bulk_actions Current bulk actions.
	 *
	 * @return array
	 */
	public function register_bulk_actions( $bulk_actions ) {
		$bulk_actions['classify'] = __( 'Classify', 'classifai' );
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
		$settings = $this->computer_vision->get_settings();

		if (
			'no' !== $settings['enable_image_tagging'] ||
			empty( $this->computer_vision->get_alt_text_settings() )
		) {
			$bulk_actions['scan_image'] = __( 'Scan Image', 'classifai' );
		}

		if ( isset( $settings['enable_smart_cropping'] ) && '1' === $settings['enable_smart_cropping'] ) {
			$bulk_actions['smart_crop'] = __( 'Smart Crop', 'classifai' );
		}

		return $bulk_actions;
	}

	/**
	 * Handle bulk actions.
	 *
	 * @param string $redirect_to Redirect URL after bulk actions.
	 * @param string $doaction    Action ID.
	 * @param array  $post_ids    Post ids to apply bulk actions to.
	 *
	 * @return string
	 */
	public function bulk_action_handler( $redirect_to, $doaction, $post_ids ) {
		if ( 'classify' !== $doaction ) {
			return $redirect_to;
		}

		foreach ( $post_ids as $post_id ) {
			// Handle NLU classification.
			if ( is_a( $this->save_post_handler, '\Classifai\Admin\SavePostHandler' ) ) {
				$this->save_post_handler->classify( $post_id );
			}

			// Handle OpenAI Embeddings classification.
			if ( is_a( $this->embeddings, '\Classifai\Providers\OpenAI\Embeddings' ) ) {
				$this->embeddings->generate_embeddings_for_post( $post_id );
			}
		}

		$redirect_to = remove_query_arg( [ 'bulk_classified', 'bulk_scanned', 'bulk_cropped' ], $redirect_to );
		$redirect_to = add_query_arg( 'bulk_classified', count( $post_ids ), $redirect_to );

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
			! in_array( $doaction, [ 'scan_image', 'smart_crop' ], true )
		) {
			return $redirect_to;
		}

		foreach ( $attachment_ids as $attachment_id ) {
			$current_meta = wp_get_attachment_metadata( $attachment_id );

			if ( 'smart_crop' === $doaction ) {
				$this->computer_vision->smart_crop_image( $current_meta, $attachment_id );
			} else {
				$this->computer_vision->generate_image_alt_tags( $current_meta, $attachment_id );
			}
		}

		$action = 'scan_image' === $doaction ? 'scanned' : 'cropped';

		$redirect_to = remove_query_arg( [ 'bulk_classified', 'bulk_scanned', 'bulk_cropped' ], $redirect_to );
		$redirect_to = add_query_arg( rawurlencode( "bulk_{$action}" ), count( $attachment_ids ), $redirect_to );
		return esc_url_raw( $redirect_to );
	}

	/**
	 * Display an admin notice after bulk updates.
	 */
	public function bulk_action_admin_notice() {

		$classified = ! empty( $_GET['bulk_classified'] ) ? intval( wp_unslash( $_GET['bulk_classified'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$scanned    = ! empty( $_GET['bulk_scanned'] ) ? intval( wp_unslash( $_GET['bulk_scanned'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$cropped    = ! empty( $_GET['bulk_cropped'] ) ? intval( wp_unslash( $_GET['bulk_cropped'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( ! $classified && ! $scanned && ! $cropped ) {
			return;
		}

		if ( $classified ) {
			$classified_posts_count = $classified;
			$post_type              = 'post';
			$action                 = __( 'Classified', 'classifai' );
		} elseif ( $scanned ) {
			$classified_posts_count = $scanned;
			$post_type              = 'image';
			$action                 = __( 'Scanned', 'classifai' );
		} elseif ( $cropped ) {
			$classified_posts_count = $cropped;
			$post_type              = 'image';
			$action                 = __( 'Cropped', 'classifai' );
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
		if ( is_a( $this->save_post_handler, '\Classifai\Admin\SavePostHandler' ) ) {
			$post_types = get_supported_post_types();
		} elseif ( is_a( $this->embeddings, '\Classifai\Providers\OpenAI\Embeddings' ) ) {
			$post_types = $this->embeddings->supported_post_types();
		}

		if ( ! in_array( $post->post_type, $post_types, true ) ) {
			return $actions;
		}

		$actions['classify'] = sprintf(
			'<a href="%s">%s</a>',
			esc_url( wp_nonce_url( admin_url( sprintf( 'edit.php?action=classify&ids=%d&post_type=%s', $post->ID, $post->post_type ) ), 'bulk-posts' ) ),
			esc_html__( 'Classify', 'classifai' )
		);

		return $actions;
	}

}
