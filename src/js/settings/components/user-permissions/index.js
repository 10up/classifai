/**
 * External dependencies
 */
import {
	CheckboxControl,
	PanelBody,
	PanelRow,
	ToggleControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { getFeature } from '../../utils/utils';
import { UserSelector } from '../../../components';

export const UserPermissions = ( {
	featureName,
	featureSettings,
	setSettings,
} ) => {
	const feature = getFeature( featureName );
	const roles = feature.roles || {};
	return (
		<PanelBody
			title={ __( 'User permissions', 'classifai' ) }
			initialOpen={ true }
		>
			<PanelRow>
				<div className="classifai-settings__roles">
					<div className="settings-label">
						{ __( 'Allowed roles', 'classifai' ) }
					</div>
					{ Object.keys( roles ).map( ( role ) => {
						return (
							<CheckboxControl
								key={ role }
								checked={
									featureSettings.roles?.[ role ] === role
								}
								label={ roles[ role ] }
								onChange={ ( value ) => {
									setSettings( {
										...featureSettings,
										roles: {
											...featureSettings.roles,
											[ role ]: value ? role : '0',
										},
									} );
								} }
							/>
						);
					} ) }
				</div>
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
