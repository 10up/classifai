<?php
/**
 * Class for handling the access control for ClassifAI features.
 *
 * @package Classifai
 * @since 2.4.0
 */

namespace Classifai\Providers;

class AccessControl {

	/**
	 * The provider name.
	 *
	 * @var Provider $provider
	 */
	protected $provider;

	/**
	 * The feature name.
	 *
	 * @var string $feature
	 */
	protected $feature;

	/**
	 * The provider settings.
	 *
	 * @var array $settings
	 */
	protected $settings;

	/**
	 * The role based access key.
	 *
	 * @var string $role_based_access_key
	 */
	protected $role_based_access_key;

	/**
	 * The roles key.
	 *
	 * @var string $roles_key
	 */
	protected $roles_key;

	/**
	 * The user based access key.
	 *
	 * @var string $user_based_access_key
	 */
	protected $user_based_access_key;

	/**
	 * The user based opt out key.
	 *
	 * @var string $user_based_opt_out_key
	 */
	protected $user_based_opt_out_key;

	/**
	 * The users key.
	 *
	 * @var string $users_key
	 */
	protected $users_key;

	/**
	 * Constructor.
	 *
	 * @param Provider $provider The provider class instance.
	 * @param string   $feature  The feature name.
	 */
	public function __construct( Provider $provider, string $feature ) {
		$this->provider = $provider;
		$this->feature  = $feature;

		$this->role_based_access_key  = $feature . '_role_based_access';
		$this->roles_key              = $feature . '_roles';
		$this->user_based_access_key  = $feature . '_user_based_access';
		$this->user_based_opt_out_key = $feature . '_user_based_opt_out';
		$this->users_key              = $feature . '_users';
	}

	/**
	 * Get settings for the current feature.
	 *
	 * @return array
	 */
	public function get_settings(): array {
		if ( is_null( $this->settings ) ) {
			$this->settings = $this->provider->get_settings();
		}

		return $this->settings;
	}

	/**
	 * Determines whether user-based access control is enabled for the current feature.
	 *
	 * @return bool
	 */
	public function is_user_based_access_enabled(): bool {
		$settings = $this->get_settings();
		return isset( $settings[ $this->user_based_access_key ] ) && 1 === (int) $settings[ $this->user_based_access_key ];
	}

	/**
	 * Determines whether user-based opt-out is enabled for the current feature.
	 *
	 * @return bool
	 */
	public function is_user_based_opt_out_enabled(): bool {
		$settings = $this->get_settings();
		return isset( $settings[ $this->user_based_opt_out_key ] ) && 1 === (int) $settings[ $this->user_based_opt_out_key ];
	}

	/**
	 * Determines whether role-based access control is enabled for the current feature.
	 *
	 * @return bool
	 */
	public function is_role_based_access_enabled(): bool {
		$settings = $this->get_settings();
		return isset( $settings[ $this->role_based_access_key ] ) && 1 === (int) $settings[ $this->role_based_access_key ];
	}

	/**
	 * Get the list of allowed roles for the current feature.
	 *
	 * @return array
	 */
	public function get_allowed_roles(): array {
		$settings  = $this->get_settings();
		$roles_key = $this->roles_key;

		// Backward compatibility for old roles keys.
		switch ( $this->feature ) {
			case 'title_generation':
				if ( ! isset( $settings[ $this->roles_key ] ) && isset( $settings['title_roles'] ) ) {
					$roles_key = 'title_roles';
				}
				break;

			case 'excerpt_generation':
			case 'speech_to_text':
			case 'image_generation':
				if ( ! isset( $settings[ $this->roles_key ] ) && isset( $settings['roles'] ) ) {
					$roles_key = 'roles';
				}
				break;

			default:
				break;
		}

		return $settings[ $roles_key ] ?? [];
	}

	/**
	 * Get the list of allowed users for the current feature.
	 *
	 * @return array
	 */
	public function get_allowed_users(): array {
		$settings = $this->get_settings();
		$users    = $settings[ $this->users_key ] ?? [];

		return array_map( 'absint', $users );
	}

	/**
	 * Determine if the current user has access of the feature
	 *
	 * @param int $user_id User ID.
	 * @return bool
	 */
	public function has_access( $user_id = null ): bool {
		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}

		$user_id    = (int) $user_id;
		$user       = get_user_by( 'id', $user_id );
		$user_roles = $user->roles ?? [];
		$access     = false;
		$settings   = $this->get_settings();

		$feature_roles = $this->get_allowed_roles();
		$feature_users = $this->get_allowed_users();

		/*
		 * Checks if Role-based access is enabled and user role has access to the feature.
		 */
		if ( $this->is_role_based_access_enabled() ) {
			$access = ( ! empty( $feature_roles ) && ! empty( array_intersect( $user_roles, $feature_roles ) ) );
		}

		/*
		 * Checks if User-based access is enabled and user has access to the feature.
		 */
		if ( ! $access && $this->is_user_based_access_enabled() ) {
			$access = ( ! empty( $feature_users ) && ! empty( in_array( $user_id, $feature_users, true ) ) );
		}

		/*
		 * Checks if User-based opt-out is enabled and user has opted out from the feature.
		 */
		if ( $access && $this->is_user_based_opt_out_enabled() ) {
			$opted_out_features = (array) get_user_meta( $user_id, 'classifai_opted_out_features', true );
			$access             = ( ! in_array( $this->feature, $opted_out_features, true ) );
		}

		/**
		 * Filter to override user access to a ClassifAI feature.
		 *
		 * @since 2.4.0
		 * @hook classifai_has_access
		 *
		 * @param {bool}   $access   Current access value.
		 * @param {string} $feature  Feature name.
		 * @param {int}    $user_id  User ID.
		 * @param {array}  $settings Feature settings.
		 *
		 * @return {bool} Should the user have access?
		 */
		return apply_filters( 'classifai_has_access', $access, $this->feature, $user_id, $settings );
	}
}
