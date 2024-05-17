/**
 * External dependencies
 */
import { TabPanel } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { FeatureSettings } from './feature-settings';
import { updateUrl } from '../utils/utils';

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
	const urlParams = new URLSearchParams( window.location.search );
	const requestedFeature = urlParams.get( 'feature' );
	const serviceFeatures = features[ tab ] || {};
	const initialFeature = Object.keys( serviceFeatures ).includes(
		requestedFeature
	)
		? requestedFeature
		: Object.keys( serviceFeatures )[ 0 ] || 'classification';

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
					return updateUrl( 'feature', featureName );
				} }
			>
				{ ( feature ) => {
					return (
						<>
							{ featureOptions.map( ( key ) => {
								if ( key.name !== feature.name ) {
									return null;
								}

								return (
									<FeatureSettings
										featureName={ feature.name }
										key={ feature.name }
									/>
								);
							} ) }
						</>
					);
				} }
			</TabPanel>
		</div>
	);
};
