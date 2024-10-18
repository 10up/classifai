/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { ToggleControl } from '@wordpress/components';
import { decodeEntities } from '@wordpress/html-entities';

/**
 * Internal dependencies
 */
import { getFeature } from '../../utils/utils';
import { SettingsRow } from '../settings-row';
import { useFeatureSettings } from '../../data/hooks';

/**
 * Enable Feature Toggle component.
 *
 * @param {Object} props          Component props.
 * @param {Object} props.children Component children.
 */
export const EnableToggleControl = ( { children } ) => {
	const { featureName, getFeatureSettings, setFeatureSettings } =
		useFeatureSettings();
	const status = getFeatureSettings( 'status' ) || '0';
	const feature = getFeature( featureName );

	if ( children && 'function' === typeof children ) {
		return children( { feature, status, setFeatureSettings } );
	}

	const enableDescription = decodeEntities(
		feature?.enable_description || __( 'Enable feature', 'classifai' )
	);

	return (
		<SettingsRow
			label={ __( 'Enable feature', 'classifai' ) }
			description={ enableDescription }
		>
			<ToggleControl
				className="classifai-enable-feature-toggle"
				checked={ status === '1' }
				onChange={ ( value ) =>
					setFeatureSettings( {
						status: value ? '1' : '0', // TODO: Use boolean, currently using string for backward compatibility.
					} )
				}
			/>
		</SettingsRow>
	);
};
