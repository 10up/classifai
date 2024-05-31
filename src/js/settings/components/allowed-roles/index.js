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

export const AllowedRoles = ( { featureName } ) => {
	const { setFeatureSettings } = useDispatch( STORE_NAME );
	const featureSettings = useSelect( ( select ) =>
		select( STORE_NAME ).getFeatureSettings()
	);
	const feature = getFeature( featureName );
	const roles = feature.roles || {};
	return (
		<div className="classifai-settings__roles">
			<div className="settings-label">
				{ __( 'Allowed roles', 'classifai' ) }
			</div>
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
		</div>
	);
};
