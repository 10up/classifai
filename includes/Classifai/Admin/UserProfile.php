<?php

namespace Classifai\Admin;

use Classifai\Providers\AccessControl;
use Classifai\Providers\Provider;
use Classifai\Services\Service;

use function Classifai\get_plugin;

/**
 * The UserProfile class provides opt-out settings for ClassifAI feature on user profile page.
 *
 * @since 2.4.0
 */
class UserProfile {

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
		add_action( 'show_user_profile', array( $this, 'user_settings' ) );
		add_action( 'edit_user_profile', array( $this, 'user_settings' ) );

		add_action( 'personal_options_update', array( $this, 'save_user_settings' ) );
		add_action( 'edit_user_profile_update', array( $this, 'save_user_settings' ) );
	}

	/**
	 * Add ClassifAI features opt-out checkboxes to user profile and edit user.
	 *
	 * @param \WP_User $user User object.
	 * @return void
	 */
	public function user_settings( \WP_User $user ) {
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
								echo esc_html( sprintf( __( 'Opt out of using the %s feature.', 'classifai' ), strtolower( $feature_name ) ) );
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
				foreach ( $provider_features as $feature => $feature_name ) {
					$access_control = new AccessControl( $provider_class, $feature );

					// Check if feature has user based opt-out enabled.
					if ( $access_control->is_user_based_opt_out_enabled() ) {
						// Check if user has access to the feature by role.
						$allowed_roles = $access_control->get_allowed_roles();
						if (
							$access_control->is_role_based_access_enabled() &&
							! empty( $allowed_roles ) &&
							! empty( array_intersect( $user_roles, $allowed_roles ) )
						) {
							$allowed_features[ $feature ] = $feature_name;
							continue;
						}

						// Check if user has access to the feature.
						$allowed_users = $access_control->get_allowed_users();
						if (
							$access_control->is_user_based_access_enabled() &&
							! empty( $allowed_users ) &&
							in_array( $user_id, $allowed_users, true )
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
