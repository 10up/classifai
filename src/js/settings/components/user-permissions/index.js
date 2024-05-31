/**
 * External dependencies
 */
import { PanelBody, PanelRow, ToggleControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { UserSelector } from '../../../components';
import { AllowedRoles } from '../allowed-roles';

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
			<PanelRow>
				<AllowedRoles featureName={ featureName } />
			</PanelRow>
			<PanelRow>
				<div className="classifai-settings__users">
					<UserSelector
						value={ featureSettings.users || [] }
						onChange={ ( users ) => {
							setSettings( {
								...featureSettings,
								users,
							} );
						} }
						label={ __( 'Allowed users', 'classifai' ) }
					/>
				</div>
			</PanelRow>
			<PanelRow>
				<div className="classifai-settings__user_based_opt_out">
					<ToggleControl
						checked={ featureSettings?.user_based_opt_out === '1' }
						label={ __( 'Enable user-based opt-out', 'classifai' ) }
						onChange={ ( value ) => {
							setSettings( {
								...featureSettings,
								user_based_opt_out: value ? '1' : 'no',
							} );
						} }
					/>
				</div>
			</PanelRow>
		</PanelBody>
	);
};
