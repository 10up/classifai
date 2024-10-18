/**
 * WordPress dependencies
 */
import { PanelBody, ToggleControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useDispatch, useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { UserSelector } from '../../../components';
import { AllowedRoles } from '../allowed-roles';
import { SettingsRow } from '../settings-row';
import { STORE_NAME } from '../../data/store';
import { useUserPermissionsPreferences } from '../../utils/utils';

/**
 * Component for User Permissions settings.
 *
 * This component is used within the FeatureSettings component to allow users to configure the User Permissions settings.
 *
 * @return {React.ReactElement} UserPermissions component.
 */
export const UserPermissions = () => {
	const { isOpen, setIsOpen } = useUserPermissionsPreferences();
	const { setFeatureSettings } = useDispatch( STORE_NAME );
	// eslint-disable-next-line camelcase
	const { users, user_based_opt_out } = useSelect( ( select ) => {
		return {
			users: select( STORE_NAME ).getFeatureSettings( 'users' ),
			user_based_opt_out:
				select( STORE_NAME ).getFeatureSettings( 'user_based_opt_out' ),
		};
	} );
	return (
		<PanelBody
			title={ __( 'User permissions', 'classifai' ) }
			initialOpen={ isOpen }
			onToggle={ ( opened ) => {
				setIsOpen( opened );
			} }
			className="classifai-settings__user-permissions"
		>
			<AllowedRoles />

			<SettingsRow
				label={ __( 'Allowed users', 'classifai' ) }
				className="classifai-settings__users"
				description={ __(
					'Select users who can access this feature.',
					'classifai'
				) }
			>
				<UserSelector
					value={ users || [] }
					onChange={ ( value ) => {
						setFeatureSettings( {
							users: value,
						} );
					} }
				/>
			</SettingsRow>

			<SettingsRow
				label={ __( 'Enable user-based opt-out', 'classifai' ) }
				description={ __(
					'Enables ability for users to opt-out from their user profile page.',
					'classifai'
				) }
			>
				<ToggleControl
					// eslint-disable-next-line camelcase
					checked={ user_based_opt_out === '1' }
					onChange={ ( value ) => {
						setFeatureSettings( {
							user_based_opt_out: value ? '1' : 'no',
						} );
					} }
					className="classifai-settings__user-based-opt-out"
				/>
			</SettingsRow>
		</PanelBody>
	);
};
