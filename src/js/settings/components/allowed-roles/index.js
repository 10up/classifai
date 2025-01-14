/**
 * WordPress dependencies
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
import { useFeatureContext } from '../feature-settings/context';

/**
 * React Component for selecting user roles to provide access to a specific feature.
 *
 * This component is utilized in the feature settings page under the "User Permissions" section.
 * It allows administrators to specify which user roles are permitted to access the feature.
 *
 * @return {React.ReactElement} AllowedRoles component.
 */
export const AllowedRoles = () => {
	const { featureName } = useFeatureContext();
	const { setFeatureSettings } = useDispatch( STORE_NAME );
	const roles = useSelect(
		( select ) => select( STORE_NAME ).getFeatureSettings( 'roles' ) || {}
	);
	const feature = getFeature( featureName );
	const featureRoles = feature.roles || {};
	return (
		<SettingsRow
			label={ __( 'Allowed roles', 'classifai' ) }
			className="settings-allowed-roles"
			description={ __(
				'Choose which roles are allowed to access this feature.',
				'classifai'
			) }
		>
			{ Object.keys( featureRoles ).map( ( role ) => {
				return (
					<CheckboxControl
						id={ role }
						key={ role }
						checked={ roles?.[ role ] === role }
						label={ featureRoles[ role ] }
						onChange={ ( value ) => {
							setFeatureSettings( {
								roles: {
									...roles,
									[ role ]: value ? role : '0',
								},
							} );
						} }
						__nextHasNoMarginBottom
					/>
				);
			} ) }
		</SettingsRow>
	);
};
