<?php

namespace Classifai\Admin;

use Classifai\Features\Feature;
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
	 * Add features opt-out checkboxes to user profile and edit user.
	 *
	 * @param \WP_User $user User object.
	 */
	public function user_settings( \WP_User $user ) {
		$user_id = $user->ID;

		// Bail if user is not current user or current user cannot edit the user.
		if ( get_current_user_id() !== $user_id && ! current_user_can( 'edit_user', $user_id ) ) {
			return;
		}

		// Bail if user is not allowed to access features.
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
	 * Save features opt-out settings.
	 *
	 * @param int $user_id User ID.
	 */
	public function save_user_settings( int $user_id ) {
		if (
			! isset( $_POST['classifai_out_out_features_nonce'] ) ||
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
	public function get_allowed_features( int $user_id ): array {
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
			if ( ! $service_class instanceof Service || empty( $service_class->feature_classes ) ) {
				continue;
			}

			foreach ( $service_class->feature_classes as $feature_class ) {
				if ( ! $feature_class instanceof Feature || ! $feature_class->is_enabled() ) {
					continue;
				}

				$settings = $feature_class->get_settings();
				// Bail if feature settings are empty.
				if ( empty( $settings ) ) {
					continue;
				}

				$user_based_opt_out_enabled = isset( $settings['user_based_opt_out'] ) && 1 === (int) $settings['user_based_opt_out'];

				// Bail if user opt-out is not enabled.
				if ( ! $user_based_opt_out_enabled ) {
					continue;
				}

				// Check if user has access to the feature by role.
				$allowed_roles = $settings['roles'] ?? [];
				// For super admins that don't have a specific role on a site, treat them as admins.
				if ( is_multisite() && is_super_admin( $user_id ) && empty( $user_roles ) ) {
					$user_roles = [ 'administrator' ];
				}

				if (
					! empty( $allowed_roles ) &&
					! empty( array_intersect( $user_roles, $allowed_roles ) )
				) {
					$allowed_features[ $feature_class::ID ] = $feature_class->get_label();
					continue;
				}

				// Check if user has access to the feature.
				$allowed_users = $settings['users'] ?? [];
				if (
					! empty( $allowed_users ) &&
					in_array( $user_id, $allowed_users, true )
				) {
					$allowed_features[ $feature_class::ID ] = $feature_class->get_label();
				}
			}
		}

		return $allowed_features;
	}
}
