/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { ToggleControl } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { decodeEntities } from '@wordpress/html-entities';

/**
 * Internal dependencies
 */
import { getFeature } from '../../utils/utils';
import { SettingsRow } from '../settings-row';
import { STORE_NAME } from '../../data/store';
import { useFeatureContext } from './context';

/**
 * Enable Feature Toggle component.
 *
 */
export const EnableToggleControl = ( { children } ) => {
	const { featureName } = useFeatureContext();
	const { setFeatureSettings } = useDispatch( STORE_NAME );
	const status = useSelect(
		( select ) => select( STORE_NAME ).getFeatureSettings( 'status' ) || '0'
	);

	const feature = getFeature( featureName );
	const enableDescription = decodeEntities(
		feature?.enable_description || __( 'Enable feature', 'classifai' )
	);

	if ( children && 'function' === typeof children ) {
		return children( { feature, status, setFeatureSettings } );
	}

	return (
		<SettingsRow
			label={ __( 'Enable feature', 'classifai' ) }
			description={ enableDescription }
		>
			<ToggleControl
				checked={ status === '1' }
				onChange={ ( value ) =>
					setFeatureSettings( {
						status: value ? '1' : '0', // TODO: Use boolean, currently using string for compatibility.
					} )
				}
			/>
		</SettingsRow>
	);
};
