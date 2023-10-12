<?php
/**
 * OpenAI Moderation integration
 */

namespace Classifai\Providers\OpenAI;

use Classifai\Providers\Provider;

use WP_Error;

class Moderation extends Provider {

	use OpenAI;

	/**
	 * OpenAI Moderation constructor.
	 *
	 * @param string $service The service this class belongs to.
	 */
	public function __construct( $service ) {
		parent::__construct(
			'OpenAI Moderation',
			'Moderation',
			'openai_moderation',
			$service
		);

		// Set the onboarding options.
		$this->onboarding_options = array(
			'title'    => __( 'OpenAI Moderation', 'classifai' ),
			'fields'   => array( 'api-key' ),
			'features' => array(
				'enable_moderation' => __( 'Enable comments moderation', 'classifai' ),
			),
		);
	}

	/**
	 * Register what we need for the plugin.
	 *
	 * This only fires if can_register returns true.
	 */
	public function register() {
		add_filter( 'comment_row_actions', [ $this, 'comment_row_actions' ], 10, 2 );
		add_action( 'admin_init', [ $this, 'maybe_moderate_comment' ] );
		add_filter( 'manage_edit-comments_columns', [ $this, 'add_comment_list_columns' ] );
		add_action( 'manage_comments_custom_column', [ $this, 'add_comment_list_column_content' ], 10, 2 );
		add_action( 'wp_insert_comment', [ $this, 'moderate_comment' ] );
	}

	/**
	 * Prints custom column content
	 *
	 * @param string $column_name Column name
	 * @param int    $comment_id  Column ID
	 * @return void
	 */
	public function add_comment_list_column_content( $column_name, $comment_id ) {
		if ( 'moderation_flagged' === $column_name ) {
			$flagged = get_comment_meta( $comment_id, 'classifai_moderation_flagged', true );
			if ( '0' === $flagged ) {
				$flagged = 'No';
			} elseif ( '1' === $flagged ) {
				$flagged = 'Yes';
			}
			echo '<div style="text-align: center">' . esc_html( $flagged ) . '</div>';
		}

		if ( 'moderation_flags' === $column_name ) {
			$flags = get_comment_meta( $comment_id, 'classifai_moderation_flags', true );
			$flags = $flags ? $flags : [];
			echo '<div style="text-align: center">' . esc_html( implode( ', ', $flags ) ) . '</div>';
		}
	}

	/**
	 * Prints custom column header
	 *
	 * @param array $columns Columns
	 * @return array
	 */
	public function add_comment_list_columns( $columns ) {
		$columns['moderation_flagged'] = __( 'Moderation Flagged', 'classifai' );
		$columns['moderation_flags']   = __( 'Moderation Flags', 'classifai' );

		return $columns;
	}

	/**
	 * Moderates comment when clicked on "Moderate" action button in comment list
	 *
	 * @return void
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
	 * Add action to comment row
	 *
	 * @param array       $actions Comment row action
	 * @param \WP_Comment $comment Comment object
	 * @return mixed
	 */
	public function comment_row_actions( $actions, $comment ) {
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
			esc_attr__( 'Moderate this comment' ),
			esc_html__( 'Moderate', 'classifai' )
		);

		return $actions;
	}

	/**
	 * Check to see if the feature is enabled and a user has access.
	 *
	 * @return bool|WP_Error
	 */
	public function is_feature_enabled() {
		$settings = $this->get_settings();

		// Check if valid authentication is in place.
		if ( empty( $settings ) || ( isset( $settings['authenticated'] ) && false === $settings['authenticated'] ) ) {
			return new WP_Error( 'auth', esc_html__( 'Please set up valid authentication with OpenAI.', 'classifai' ) );
		}

		// Check if the current user has permission.
		$roles      = $settings['roles'] ?? [];
		$user_roles = wp_get_current_user()->roles ?? [];

		if ( empty( $roles ) || ! empty( array_diff( $user_roles, $roles ) ) ) {
			return new WP_Error( 'no_permission', esc_html__( 'User role does not have permission.', 'classifai' ) );
		}

		if ( ! current_user_can( 'moderate_comments' ) ) {
			return new WP_Error( 'no_permission', esc_html__( 'User does not have permission to moderate comments.', 'classifai' ) );
		}

		// Ensure feature is turned on.
		if ( ! isset( $settings['enable_moderation'] ) || 1 !== (int) $settings['enable_moderation'] ) {
			return new WP_Error( 'not_enabled', esc_html__( 'Comment moderation is not enabled.', 'classifai' ) );
		}

		return true;
	}

	/**
	 * Send comment to remote service for moderation.
	 *
	 * @param int $comment_id Attachment ID to process.
	 * @return void
	 */
	public function moderate_comment( $comment_id = 0 ) {
		$settings = $this->get_settings();
		$enabled  = $this->is_feature_enabled();

		if ( is_wp_error( $enabled ) ) {
			return;
		}

		$api_key = $settings['api_key'];
		$comment = get_comment( $comment_id );

		$api_response = wp_remote_post(
			'https://api.openai.com/v1/moderations',
			[
				'headers' => [
					'Content-Type'  => 'application/json',
					'Authorization' => 'Bearer ' . $api_key,
				],
				'body'    => wp_json_encode(
					[
						'input' => $comment->comment_content,
					]
				),
			]
		);

		if ( ! is_wp_error( $api_response ) && 200 === wp_remote_retrieve_response_code( $api_response ) ) {
			$body    = json_decode( wp_remote_retrieve_body( $api_response ), true );
			$flagged = $body['results'][0]['flagged'];

			if ( false === $flagged ) {
				update_comment_meta( $comment_id, 'classifai_moderation_flagged', '0' );
			} else {
				$flagged_categories = $body['results'][0]['categories'];
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
	}

	/**
	 * Setup fields
	 */
	public function setup_fields_sections() {
		$default_settings = $this->get_default_settings();

		$this->setup_api_fields( $default_settings['api_key'] );

		add_settings_field(
			'enable-moderation',
			esc_html__( 'Moderate post comments', 'classifai' ),
			[ $this, 'render_input' ],
			$this->get_option_name(),
			$this->get_option_name(),
			[
				'label_for'     => 'enable_moderation',
				'input_type'    => 'checkbox',
				'default_value' => $default_settings['enable_moderation'],
				'description'   => __( 'Automatically moderate incoming post comments', 'classifai' ),
			]
		);

		$roles = get_editable_roles() ?? [];
		$roles = array_combine( array_keys( $roles ), array_column( $roles, 'name' ) );

		add_settings_field(
			'roles',
			esc_html__( 'Allowed roles', 'classifai' ),
			[ $this, 'render_checkbox_group' ],
			$this->get_option_name(),
			$this->get_option_name(),
			[
				'label_for'      => 'roles',
				'options'        => $roles,
				'default_values' => $default_settings['roles'],
				'description'    => __( 'Choose which roles are allowed to moderate comments.', 'classifai' ),
			]
		);
	}

	/**
	 * Sanitization for the options being saved.
	 *
	 * @param array $settings Array of settings about to be saved.
	 *
	 * @return array The sanitized settings to be saved.
	 */
	public function sanitize_settings( $settings ) {
		$new_settings = $this->get_settings();
		$new_settings = array_merge(
			$new_settings,
			$this->sanitize_api_key_settings( $new_settings, $settings )
		);

		if ( empty( $settings['enable_moderation'] ) || 1 !== (int) $settings['enable_moderation'] ) {
			$new_settings['enable_moderation'] = 'no';
		} else {
			$new_settings['enable_moderation'] = '1';
		}

		if ( isset( $settings['roles'] ) && is_array( $settings['roles'] ) ) {
			$new_settings['roles'] = array_map( 'sanitize_text_field', $settings['roles'] );
		} else {
			$new_settings['roles'] = array_keys( get_editable_roles() ?? [] );
		}

		return $new_settings;
	}

	/**
	 * Resets settings for the provider.
	 */
	public function reset_settings() {
		update_option( $this->get_option_name(), $this->get_default_settings() );
	}

	/**
	 * Default settings for Whisper.
	 *
	 * @return array
	 */
	public function get_default_settings() {
		return [
			'authenticated'     => false,
			'api_key'           => '',
			'enable_moderation' => false,
			'roles'             => array_keys( get_editable_roles() ?? [] ),
		];
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
		$enable_moderation = 1 === intval( $settings['enable_moderation'] ?? 0 );

		return [
			__( 'Authenticated', 'classifai' )     => $authenticated ? __( 'yes', 'classifai' ) : __( 'no', 'classifai' ),
			__( 'Enable moderation', 'classifai' ) => $enable_moderation ? __( 'yes', 'classifai' ) : __( 'no', 'classifai' ),
			__( 'Allowed roles', 'classifai' )     => implode( ', ', $settings['roles'] ?? [] ),
			__( 'Latest response', 'classifai' )   => $this->get_formatted_latest_response( get_transient( 'classifai_openai_moderation_latest_response' ) ),
		];
	}

}
