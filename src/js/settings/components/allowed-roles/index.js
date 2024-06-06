/**
 * External dependencies
 */
import { useSelect, useDispatch } from '@wordpress/data';
import { CheckboxControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { getFeature } from '../../utils/utils';
import { STORE_NAME } from '../../data/store';
import { SettingsRow } from '../settings-row';

export const AllowedRoles = ( { featureName } ) => {
	const { setFeatureSettings } = useDispatch( STORE_NAME );
	const featureSettings = useSelect(
		( select ) => select( STORE_NAME ).getSettings( featureName ) || {}
	);
	const feature = getFeature( featureName );
	const roles = feature.roles || {};
	return (
		<SettingsRow
			label={ __( 'Allowed roles', 'classifai' ) }
			className="settings-allowed-roles"
			description={ __(
				'Choose which roles are allowed to access this feature.',
				'classifai'
			) }
		>
			{ Object.keys( roles ).map( ( role ) => {
				return (
					<CheckboxControl
						key={ role }
						checked={ featureSettings.roles?.[ role ] === role }
						label={ roles[ role ] }
						onChange={ ( value ) => {
							setFeatureSettings( {
								roles: {
									...featureSettings.roles,
									[ role ]: value ? role : '0',
								},
							} );
						} }
					/>
				);
			} ) }
		</SettingsRow>
	);
};
