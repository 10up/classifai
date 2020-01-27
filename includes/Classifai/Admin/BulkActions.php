<?php
namespace Classifai\Admin;

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
	 * @var $save_post_handler SavePostHandler Triggers a classification with Watson
	 */
	private $save_post_handler;

	/**
	 * Register the actions needed.
	 */
	public function register() {
		$post_types = get_supported_post_types();
		if ( empty( $post_types ) ) {
			return;
		}

		$this->save_post_handler = new SavePostHandler();

		foreach ( $post_types as $post_type ) {
			add_filter( "bulk_actions-edit-$post_type", [ $this, 'register_bulk_actions' ] );
			add_filter( "handle_bulk_actions-edit-$post_type", [ $this, 'bulk_action_handler' ], 10, 3 );
		}

		add_action( 'admin_notices', [ $this, 'bulk_action_admin_notice' ] );
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
			$this->save_post_handler->classify( $post_id );
		}
		$redirect_to = add_query_arg( 'bulk_classified', count( $post_ids ), $redirect_to );
		return $redirect_to;
	}

	/**
	 * Display an admin notice after classifying posts in bulk.
	 */
	public function bulk_action_admin_notice() {
		if ( empty( $_REQUEST['bulk_classified'] ) ) {
			return;
		}

		$classified_posts_count = intval( $_REQUEST['bulk_classified'] );

		$output  = '<div id="message" class="notice notice-success is-dismissible fade"><p>';
		$output .= sprintf(
			_n(
				'Classified %s post.',
				'Classified %s posts.',
				$classified_posts_count,
				'classifai'
			),
			$classified_posts_count
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
