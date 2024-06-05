/**
 * External dependencies
 */
import { PanelBody, ToggleControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { UserSelector } from '../../../components';
import { AllowedRoles } from '../allowed-roles';
import { SettingsRow } from '../settings-row';

export const UserPermissions = ( {
	featureName,
	featureSettings,
	setSettings,
} ) => {
	return (
		<PanelBody
			title={ __( 'User permissions', 'classifai' ) }
			initialOpen={ true }
		>
			<AllowedRoles featureName={ featureName } />

			<SettingsRow
				label={ __( 'Allowed users', 'classifai' ) }
				className="classifai-settings__users"
				description={ __(
					'Select users who can access this feature.',
					'classifai'
				) }
			>
				<UserSelector
					value={ featureSettings.users || [] }
					onChange={ ( users ) => {
						setSettings( {
							...featureSettings,
							users,
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
					checked={ featureSettings?.user_based_opt_out === '1' }
					onChange={ ( value ) => {
						setSettings( {
							...featureSettings,
							user_based_opt_out: value ? '1' : 'no',
						} );
					} }
				/>
			</SettingsRow>
		</PanelBody>
	);
};
