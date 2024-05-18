/**
 * External dependencies
 */
import { TabPanel } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { FeatureSettings } from './feature-settings';
import { updateUrl, getInitialFeature } from '../utils/utils';
import { useSettings } from '../hooks';

/**
 * Internal dependencies
 */
const { features } = window.classifAISettings;

/**
 * SettingsWrapper component to render the feature navigation tabs and the feature settings.
 *
 * @param {Object} props     All the props passed to this function
 * @param {string} props.tab The name of the tab.
 * @return {Object} The SettingsWrapper component.
 */
export const SettingsWrapper = ( { tab } ) => {
	// Switch the default feature tab based on the URL feature query
	const initialFeature = getInitialFeature( tab );
	const serviceFeatures = features[ tab ] || {};
	const { setCurrentFeature } = useSettings();

	// Get the features for the selected service.
	const featureOptions = Object.keys( serviceFeatures ).map( ( feature ) => {
		return {
			name: feature,
			title:
				serviceFeatures[ feature ]?.label ||
				__( 'Feature', 'classifai' ),
			className: feature,
		};
	} );

	return (
		<div className="classifai-settings-wrapper">
			<TabPanel
				className={ 'feature-tabs' }
				activeClass="active-tab"
				initialTabName={ initialFeature }
				tabs={ featureOptions }
				onSelect={ ( featureName ) => {
					setCurrentFeature( featureName );
					return updateUrl( 'feature', featureName );
				} }
			>
				{ ( feature ) => {
					return (
						<FeatureSettings
							featureName={ feature.name }
							key={ feature.name }
						/>
					);
				} }
			</TabPanel>
		</div>
	);
};
