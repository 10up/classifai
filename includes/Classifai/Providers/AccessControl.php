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
	public function get_settings() {
		if ( is_null( $this->settings ) ) {
			$this->settings = $this->provider->get_settings();
		}
		return $this->settings;
	}

	/**
	 * Determines whether user-based access control is enabled for the current feature.
	 *
	 * @return boolean
	 */
	public function is_user_based_access_enabled() {
		$settings = $this->get_settings();
		return isset( $settings[ $this->user_based_access_key ] ) && 1 === (int) $settings[ $this->user_based_access_key ];
	}

	/**
	 * Determines whether user-based opt-out is enabled for the current feature.
	 *
	 * @return boolean
	 */
	public function is_user_based_opt_out_enabled() {
		$settings = $this->get_settings();
		return isset( $settings[ $this->user_based_opt_out_key ] ) && 1 === (int) $settings[ $this->user_based_opt_out_key ];
	}

	/**
	 * Determines whether role-based access control is enabled for the current feature.
	 *
	 * @return boolean
	 */
	public function is_role_based_access_enabled() {
		$settings = $this->get_settings();
		return isset( $settings[ $this->role_based_access_key ] ) && 1 === (int) $settings[ $this->role_based_access_key ];
	}

	/**
	 * Get the list of allowed roles for the current feature.
	 *
	 * @return array
	 */
	public function get_allowed_roles() {
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
	public function get_allowed_users() {
		$settings = $this->get_settings();
		$users    = $settings[ $this->users_key ] ?? [];
		return array_map( 'absint', $users );
	}

	/**
	 * Add settings fields for Role/User based access.
	 *
	 * @param string $section Settings section.
	 * @return void
	 */
	public function add_settings( string $section = '' ) {
		$default_settings = $this->provider->get_default_settings();
		$settings         = $this->get_settings();

		$option_name = $this->provider->get_option_name();
		if ( empty( $section ) ) {
			$section = $this->provider->get_option_name();
		}

		// Backward compatibility for old roles keys.
		$backward_compatible_roles_key = '';
		switch ( $this->feature ) {
			case 'title_generation':
				$backward_compatible_roles_key = 'title_roles';
				break;

			case 'excerpt_generation':
			case 'speech_to_text':
			case 'image_generation':
				$backward_compatible_roles_key = 'roles';
				break;

			default:
				break;
		}

		$default_settings = array_merge(
			$this->provider->get_default_settings(),
			$default_settings,
		);

		add_settings_field(
			$this->role_based_access_key,
			esc_html__( 'Enable role-based access', 'classifai' ),
			[ $this->provider, 'render_input' ],
			$option_name,
			$section,
			[
				'label_for'     => $this->role_based_access_key,
				'input_type'    => 'checkbox',
				'default_value' => $default_settings[ $this->role_based_access_key ],
				'description'   => __( 'Enables ability to select which roles can access this feature.', 'classifai' ),
				'class'         => 'classifai-role-based-access',
			]
		);

		// Add hidden class if role-based access is disabled.
		$class = 'allowed_roles_row';
		if ( ! isset( $settings[ $this->role_based_access_key ] ) || '1' !== $settings[ $this->role_based_access_key ] ) {
			$class .= ' hidden';
		}

		add_settings_field(
			$this->roles_key,
			esc_html__( 'Allowed roles', 'classifai' ),
			[ $this->provider, 'render_checkbox_group' ],
			$option_name,
			$section,
			[
				'label_for'               => $this->roles_key,
				'options'                 => $this->provider->get_roles(),
				'default_values'          => $default_settings[ $this->roles_key ],
				'description'             => __( 'Choose which roles are allowed to access this feature.', 'classifai' ),
				'class'                   => $class,
				'backward_compatible_key' => $backward_compatible_roles_key,
			]
		);

		add_settings_field(
			$this->user_based_access_key,
			esc_html__( 'Enable user-based access', 'classifai' ),
			[ $this->provider, 'render_input' ],
			$option_name,
			$section,
			[
				'label_for'     => $this->user_based_access_key,
				'input_type'    => 'checkbox',
				'default_value' => $default_settings[ $this->user_based_access_key ],
				'description'   => __( 'Enables ability to select which users can access this feature.', 'classifai' ),
				'class'         => 'classifai-user-based-access',
			]
		);

		// Add hidden class if user-based access is disabled.
		$users_class = 'allowed_users_row';
		if ( ! isset( $settings[ $this->user_based_access_key ] ) || '1' !== $settings[ $this->user_based_access_key ] ) {
			$users_class .= ' hidden';
		}

		add_settings_field(
			$this->users_key,
			esc_html__( 'Allowed users', 'classifai' ),
			[ $this->provider, 'render_allowed_users' ],
			$option_name,
			$section,
			[
				'label_for'     => $this->users_key,
				'default_value' => $default_settings[ $this->users_key ],
				'description'   => __( 'Users who have access to this feature.', 'classifai' ),
				'class'         => $users_class,
			]
		);

		add_settings_field(
			$this->user_based_opt_out_key,
			esc_html__( 'Enable user-based opt-out', 'classifai' ),
			[ $this->provider, 'render_input' ],
			$option_name,
			$section,
			[
				'label_for'     => $this->user_based_opt_out_key,
				'input_type'    => 'checkbox',
				'default_value' => $default_settings[ $this->user_based_opt_out_key ],
				'description'   => __( 'Enables ability for users to opt-out from their user profile page.', 'classifai' ),
				'class'         => 'classifai-user-based-opt-out',
			]
		);
	}

	/**
	 * Sanitization for the roles/users access options being saved.
	 *
	 * @param array $settings Array of settings about to be saved.
	 *
	 * @return array The sanitized settings to be saved.
	 */
	public function sanitize_settings( array $settings ) {
		$new_settings = [];

		if ( empty( $settings[ $this->role_based_access_key ] ) || 1 !== (int) $settings[ $this->role_based_access_key ] ) {
			$new_settings[ $this->role_based_access_key ] = 'no';
		} else {
			$new_settings[ $this->role_based_access_key ] = '1';
		}

		// Allowed roles.
		if ( isset( $settings[ $this->roles_key ] ) && is_array( $settings[ $this->roles_key ] ) ) {
			$new_settings[ $this->roles_key ] = array_map( 'sanitize_text_field', $settings[ $this->roles_key ] );
		} else {
			$new_settings[ $this->roles_key ] = array_keys( get_editable_roles() ?? [] );
		}

		if ( empty( $settings[ $this->user_based_access_key ] ) || 1 !== (int) $settings[ $this->user_based_access_key ] ) {
			$new_settings[ $this->user_based_access_key ] = 'no';
		} else {
			$new_settings[ $this->user_based_access_key ] = '1';
		}

		// Allowed users.
		if ( isset( $settings[ $this->users_key ] ) && ! empty( $settings[ $this->users_key ] ) ) {
			if ( is_array( $settings[ $this->users_key ] ) ) {
				$new_settings[ $this->users_key ] = array_map( 'absint', $settings[ $this->users_key ] );
			} else {
				$new_settings[ $this->users_key ] = array_map( 'absint', explode( ',', $settings[ $this->users_key ] ) );
			}
		} else {
			$new_settings[ $this->users_key ] = array();
		}

		// User-based opt-out.
		if ( empty( $settings[ $this->user_based_opt_out_key ] ) || 1 !== (int) $settings[ $this->user_based_opt_out_key ] ) {
			$new_settings[ $this->user_based_opt_out_key ] = 'no';
		} else {
			$new_settings[ $this->user_based_opt_out_key ] = '1';
		}
		return $new_settings;
	}

	/**
	 * Determine if the current user has access of the feature
	 *
	 * @param int $user_id User ID.
	 * @return bool
	 */
	public function has_access( $user_id = null ) {
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
