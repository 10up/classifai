<?php

namespace Classifai;

use Classifai\Providers\Provider;
use Classifai\Services\Service;

/**
 * The UserAccess class provides role and user based access control for ClassifAI feature.
 *
 * This class contains methods for managing access settings, such as adding and removing roles and users, and checking whether a given role or user has access to ClassifAI.
 */
class UserAccess {

	/**
	 * The user meta key for storing the list of opted out features.
	 *
	 * @var string $opt_out_key
	 */
	private $opt_out_key = 'classifai_opted_out_features';

	/**
	 * Initialize the class.
	 */
	public function init() {
		add_action( 'wp_ajax_classifai_search_users', array( $this, 'classifai_search_users' ) );
		add_action( 'show_user_profile', array( $this, 'user_settings' ) );
		add_action( 'edit_user_profile', array( $this, 'user_settings' ) );

		add_action( 'personal_options_update', array( $this, 'save_user_settings' ) );
		add_action( 'edit_user_profile_update', array( $this, 'save_user_settings' ) );
	}

	/**
	 * Ajax callback for searching users.
	 *
	 * @return void
	 */
	public function classifai_search_users() {
		check_ajax_referer( 'classifai-user-search', 'security' );

		$search = isset( $_GET['search'] ) ? sanitize_text_field( wp_unslash( $_GET['search'] ) ) : '';
		$users  = get_users(
			array(
				'search'         => '*' . $search . '*',
				'number'         => 10,
				'search_columns' => array( 'user_login', 'user_nicename', 'user_email', 'ID', 'display_name' ),
				'fields'         => array( 'ID', 'display_name' ),
			)
		);

		// bail if we don't have any results
		if ( empty( $users ) ) {
			wp_send_json_success( array() );
		}

		// build our results
		$results = array_map(
			function( $user ) {
				return array(
					'id'   => $user->ID,
					'text' => $user->display_name,
				);
			},
			$users
		);

		wp_send_json_success( $results );
	}

	/**
	 * Add ClassifAI features opt-out checkboxes to user profile and edit user.
	 *
	 * @param WP_User $user User object.
	 * @return void
	 */
	public function user_settings( $user ) {
		$user_id = $user->ID;

		// Bail if user is not current user or current user cannot edit the user.
		if ( get_current_user_id() !== $user_id && ! current_user_can( 'edit_user', $user_id ) ) {
			return;
		}

		// Bail if user is not allowed to access ClassifAI features.
		$features = $this->get_allowed_features( $user->ID );
		if ( empty( $features ) ) {
			return;
		}
		?>
		<div id="classifai-profile-features-section">
			<h3><?php esc_html_e( 'ClassifAI features', 'classifai' ); ?></h3>

			<table class="form-table" role="presentation">
				<?php
				$opted_out_features = (array) get_user_meta( $user->ID, $this->opt_out_key, true );
				foreach ( $features as $feature => $feature_name ) {
					?>
					<tr class="classifai-features-row">
						<th scope="row"><?php echo esc_html( $feature_name ); ?></th>
						<td >
							<label for="<?php echo esc_attr( $this->opt_out_key . '_' . $feature ); ?>">
								<input
									name="<?php echo esc_attr( $this->opt_out_key . '[]' ); ?>"
									type="checkbox"
									id="<?php echo esc_attr( $this->opt_out_key . '_' . $feature ); ?>"
									value="<?php echo esc_attr( $feature ); ?>"
									<?php checked( true, in_array( $feature, $opted_out_features, true ), true ); ?>
									/>
								<?php
								/* translators: %s: Feature name. */
								echo esc_html( sprintf( __( 'Opt out of using the %s feature', 'classifai' ), $feature_name ) );
								?>
							</label>
						</td>
					</tr>
					<?php
				}
				wp_nonce_field( 'classifai_out_out_features', 'classifai_out_out_features_nonce' );
				?>
			</table>
		</div>
		<?php
	}

	/**
	 * Save ClassifAI features opt-out settings.
	 *
	 * @param int $user_id User ID.
	 * @return void
	 */
	public function save_user_settings( $user_id ) {
		if (
			! isset( $_POST['classifai_out_out_features_nonce'] ) ||
			! isset( $_POST[ $this->opt_out_key ] ) ||
			! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['classifai_out_out_features_nonce'] ) ), 'classifai_out_out_features' )
		) {
			return;
		}

		$user_id = (int) $user_id;

		if ( get_current_user_id() !== $user_id && ! current_user_can( 'edit_user', $user_id ) ) {
			return;
		}

		$opted_out_feautures = isset( $_POST['classifai_opted_out_features'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['classifai_opted_out_features'] ) ) : array();

		update_user_meta( $user_id, $this->opt_out_key, $opted_out_feautures );
	}

	/**
	 * Get the list of features user has access to and user opt-out is enabled.
	 *
	 * @param int $user_id User ID.
	 * @return array List of features.
	 */
	public function get_allowed_features( $user_id ) {
		$user = get_user_by( 'id', $user_id );
		if ( ! $user ) {
			return array();
		}

		$user_roles       = $user->roles ?? [];
		$allowed_features = array();

		$services = get_plugin()->services;
		if ( ! isset( $services['service_manager'] ) || ! $services['service_manager']->service_classes ) {
			return $allowed_features;
		}

		$service_classes = $services['service_manager']->service_classes;
		foreach ( $service_classes as $service_class ) {
			if ( ! $service_class instanceof Service || empty( $service_class->provider_classes ) ) {
				continue;
			}

			foreach ( $service_class->provider_classes as $provider_class ) {
				if ( ! $provider_class instanceof Provider ) {
					continue;
				}
				$provider_features = $provider_class->get_features();
				if ( empty( $provider_features ) ) {
					continue;
				}
				$settings = $provider_class->get_settings();
				foreach ( $provider_features as $feature => $feature_name ) {
					$user_based_access_key  = $feature . '_user_based_access';
					$user_based_opt_out_key = $feature . '_user_based_opt_out';
					$users_key              = $feature . '_allowed_users';
					$role_based_access_key  = $feature . '_role_based_access';
					$roles_key              = $feature . '_roles';

					// Check if feature has user based opt-out enabled.
					if ( isset( $settings[ $user_based_opt_out_key ] ) && 1 === (int) $settings[ $user_based_opt_out_key ] ) {
						$feature_roles = $settings[ $roles_key ] ?? [];
						if (
							isset( $settings[ $role_based_access_key ] ) &&
							1 === (int) $settings[ $role_based_access_key ] &&
							! empty( $feature_roles ) &&
							! empty( array_intersect( $user_roles, $feature_roles ) )
						) {
							$allowed_features[ $feature ] = $feature_name;
							continue;
						}

						if (
							isset( $settings[ $user_based_access_key ] ) &&
							1 === (int) $settings[ $user_based_access_key ] &&
							! empty( $users_key ) &&
							in_array( $user_id, $settings[ $users_key ], true )
						) {
							$allowed_features[ $feature ] = $feature_name;
						}
					}
				}
			}
		}

		return $allowed_features;
	}
}
