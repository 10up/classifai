<?php

namespace Classifai\Features;

use Classifai\Services\LanguageProcessing;
use Classifai\Providers\OpenAI\Moderation as ModerationProvider;
use WP_REST_Server;
use WP_REST_Request;
use WP_Error;

/**
 * Class Moderation
 */
class Moderation extends Feature {

	/**
	 * ID of the current feature.
	 *
	 * @var string
	 */
	const ID = 'feature_moderation';

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->label = __( 'Moderation', 'classifai' );

		// Contains all providers that are registered to the service.
		$this->provider_instances = $this->get_provider_instances( LanguageProcessing::get_service_providers() );

		// Contains just the providers this feature supports.
		$this->supported_providers = [
			ModerationProvider::ID => __( 'OpenAI Moderation', 'classifai' ),
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
	 *
	 * This only runs if is_feature_enabled() returns true.
	 */
	public function feature_setup() {
		if ( in_array( 'comments', $this->get_moderation_content_settings(), true ) ) {
			add_action( 'wp_insert_comment', [ $this, 'moderate_comment' ] );
			add_action( 'admin_init', [ $this, 'maybe_moderate_comment' ] );
			add_action( 'manage_comments_custom_column', [ $this, 'add_comment_list_column_content' ], 10, 2 );

			add_filter( 'comment_row_actions', [ $this, 'comment_row_actions' ], 10, 2 );
			add_filter( 'manage_edit-comments_columns', [ $this, 'add_comment_list_columns' ] );
		}
	}

	/**
	 * Register any needed endpoints.
	 */
	public function register_endpoints() {
		register_rest_route(
			'classifai/v1',
			'moderate(?:/(?P<id>\d+))?',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'rest_endpoint_callback' ],
					'args'                => [
						'id'   => [
							'required'          => true,
							'type'              => 'integer',
							'sanitize_callback' => 'absint',
							'description'       => esc_html__( 'Item ID to moderate.', 'classifai' ),
						],
						'type' => [
							'required'          => true,
							'type'              => 'string',
							'enum'              => [
								'comment',
								'post',
							],
							'sanitize_callback' => 'sanitize_text_field',
							'description'       => esc_html__( 'Item type to moderate.', 'classifai' ),
						],
					],
					'permission_callback' => [ $this, 'comment_moderation_permissions_check' ],
				],
			]
		);
	}

	/**
	 * Check if a given request has access to moderate content.
	 *
	 * This check ensures we have a proper ID, the current user
	 * making the request has access to that item, that we are
	 * properly authenticated and that the feature is turned on.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|bool
	 */
	public function comment_moderation_permissions_check( WP_REST_Request $request ) {
		$item_id   = $request->get_param( 'id' );
		$item_type = $request->get_param( 'type' );
		$cap       = 'post' === $item_type ? 'edit_post' : 'edit_comment';

		// Ensure we have a logged in user that can edit the item.
		if ( empty( $item_id ) || ! current_user_can( $cap, $item_id ) ) {
			return false;
		}

		// Handle checks for the post item_type (which isn't actually implemented yet)
		if ( 'post' === $item_type ) {
			if ( ! in_array( 'post', $this->get_moderation_content_settings(), true ) ) {
				return new WP_Error( 'not_enabled', esc_html__( 'Post moderation not currently enabled.', 'classifai' ) );
			}

			$post_type     = get_post_type( $item_id );
			$post_type_obj = get_post_type_object( $item_id );

			// Ensure the post type is allowed in REST endpoints.
			if ( ! $post_type || empty( $post_type_obj ) || empty( $post_type_obj->show_in_rest ) ) {
				return false;
			}
		}

		// Handle checks for the comment item_type.
		if ( 'comment' === $item_type ) {
			if ( ! in_array( 'comments', $this->get_moderation_content_settings(), true ) ) {
				return new WP_Error( 'not_enabled', esc_html__( 'Comment moderation not currently enabled.', 'classifai' ) );
			}
		}

		// Ensure the feature is enabled. Also runs a user check.
		if ( ! $this->is_feature_enabled() ) {
			return new WP_Error( 'not_enabled', esc_html__( 'Moderation not currently enabled.', 'classifai' ) );
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

		if ( strpos( $route, '/classifai/v1/moderate' ) === 0 ) {
			$result = $this->run( $request->get_param( 'id' ), $request->get_param( 'type' ) );

			if (
				! empty( $result ) &&
				! is_wp_error( $result ) &&
				'comment' === $request->get_param( 'type' )
			) {
				$this->save_comment( $result, $request->get_param( 'id' ) );
			}

			return rest_ensure_response( $result );
		}

		return parent::rest_endpoint_callback( $request );
	}

	/**
	 * Save comment moderation data.
	 *
	 * @param array $result Moderation result.
	 * @param int   $comment_id Comment ID
	 */
	public function save_comment( array $result, int $comment_id ) {
		$flagged = $result['flagged'];

		if ( false === $flagged ) {
			update_comment_meta( $comment_id, 'classifai_moderation_flagged', '0' );
		} else {
			$flagged_categories = $result['categories'];
			$moderation_flags   = array_keys(
				array_filter(
					$flagged_categories,
					function ( $value ) {
						return $value;
					}
				)
			);

			wp_update_comment(
				[
					'comment_ID'       => $comment_id,
					'comment_approved' => '0',
				]
			);
			update_comment_meta( $comment_id, 'classifai_moderation_flagged', '1' );
			update_comment_meta( $comment_id, 'classifai_moderation_flags', $moderation_flags );
		}
	}

	/**
	 * Moderate newly added comments.
	 *
	 * @param int $comment_id Comment ID.
	 */
	public function moderate_comment( int $comment_id ) {
		$result = $this->run( $comment_id, 'comment' );

		if ( ! empty( $result ) && ! is_wp_error( $result ) ) {
			$this->save_comment( $result, $comment_id );
		}
	}

	/**
	 * Maybe moderate a comment.
	 *
	 * This fires when the "Moderate" action link is clicked
	 * in the comment list.
	 */
	public function maybe_moderate_comment() {
		$action     = sanitize_text_field( wp_unslash( $_GET['a'] ?? null ) );
		$comment_id = sanitize_text_field( wp_unslash( $_GET['c'] ?? null ) );
		$nonce      = sanitize_text_field( wp_unslash( $_GET['nonce'] ?? null ) );

		if (
			'moderate' === $action &&
			$comment_id &&
			wp_verify_nonce( $nonce, 'moderate_comment' )
		) {
			$this->moderate_comment( $comment_id );
			wp_safe_redirect( '/wp-admin/edit-comments.php' );
			exit;
		}
	}

	/**
	 * Add action to comment row.
	 *
	 * @param array       $actions Comment row action
	 * @param \WP_Comment $comment Comment object
	 * @return array
	 */
	public function comment_row_actions( array $actions, \WP_Comment $comment ): array {
		$nonce = wp_create_nonce( 'moderate_comment' );

		$actions['moderate'] = sprintf(
			'<a href="%s" aria-label="%s">%s</a>',
			add_query_arg(
				[
					'a'     => 'moderate',
					'c'     => $comment->comment_ID,
					'nonce' => $nonce,
				],
				admin_url( 'edit-comments.php' ),
			),
			esc_attr__( 'Moderate this comment', 'classifai' ),
			esc_html__( 'Moderate', 'classifai' )
		);

		return $actions;
	}

	/**
	 * Prints custom column header
	 *
	 * @param array $columns Columns
	 * @return array
	 */
	public function add_comment_list_columns( array $columns ): array {
		$columns['moderation_flagged'] = __( 'Moderation flagged', 'classifai' );
		$columns['moderation_flags']   = __( 'Moderation flags', 'classifai' );

		return $columns;
	}

	/**
	 * Prints custom column content.
	 *
	 * @param string $column_name Column name
	 * @param int    $comment_id  Column ID
	 */
	public function add_comment_list_column_content( string $column_name, int $comment_id ) {
		if ( 'moderation_flagged' === $column_name ) {
			$flagged = get_comment_meta( $comment_id, 'classifai_moderation_flagged', true );

			if ( '0' === $flagged ) {
				$flagged = __( 'No', 'classifai' );
			} elseif ( '1' === $flagged ) {
				$flagged = __( 'Yes', 'classifai' );
			}

			echo '<div>' . esc_html( $flagged ) . '</div>';
		}

		if ( 'moderation_flags' === $column_name ) {
			$flags = get_comment_meta( $comment_id, 'classifai_moderation_flags', true );
			$flags = $flags ? $flags : [];

			echo '<div>' . esc_html( implode( ', ', $flags ) ) . '</div>';
		}
	}

	/**
	 * Get the description for the enable field.
	 *
	 * @return string
	 */
	public function get_enable_description(): string {
		return esc_html__( 'Automatically moderate content based on settings.', 'classifai' );
	}

	/**
	 * Add any needed custom fields.
	 */
	public function add_custom_settings_fields() {
		$settings      = $this->get_settings();
		$content_types = [
			'comments' => esc_html__( 'Comments', 'classifai' ),
		];

		add_settings_field(
			'content_types',
			esc_html__( 'Content to moderate', 'classifai' ),
			[ $this, 'render_checkbox_group' ],
			$this->get_option_name(),
			$this->get_option_name() . '_section',
			[
				'label_for'      => 'content_types',
				'options'        => $content_types,
				'default_values' => $settings['content_types'],
				'description'    => __( 'Choose what type of content to moderate.', 'classifai' ),
			]
		);
	}

	/**
	 * Returns the default settings for the feature.
	 *
	 * @return array
	 */
	public function get_feature_default_settings(): array {
		return [
			'content_types' => [
				'comments' => 'comments',
			],
			'provider'      => ModerationProvider::ID,
		];
	}

	/**
	 * Sanitizes the default feature settings.
	 *
	 * @param array $new_settings Settings being saved.
	 * @return array
	 */
	public function sanitize_default_feature_settings( array $new_settings ): array {
		$settings      = $this->get_settings();
		$content_types = [
			'comments',
		];

		foreach ( $content_types as $type ) {
			if ( ! isset( $new_settings['content_types'][ $type ] ) ) {
				$new_settings['content_types'][ $type ] = $settings['content_types'];
			} else {
				$new_settings['content_types'][ $type ] = sanitize_text_field( $new_settings['content_types'][ $type ] );
			}
		}

		return $new_settings;
	}

	/**
	 * Returns an array of fields enabled to be moderated.
	 *
	 * @return array
	 */
	public function get_moderation_content_settings(): array {
		$settings       = $this->get_settings();
		$enabled_fields = array();

		if ( ! isset( $settings['content_types'] ) || ! is_array( $settings['content_types'] ) ) {
			return $enabled_fields;
		}

		foreach ( $settings['content_types'] as $key => $value ) {
			if ( 0 !== $value && '0' !== $value ) {
				$enabled_fields[] = $key;
			}
		}

		return $enabled_fields;
	}
}
