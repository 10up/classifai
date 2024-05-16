/**
 * External dependencies
 */
import { TabPanel } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { FeatureSettings } from './feature-settings';
import { useSettings } from '../hooks/use-settings';
import { updateUrl } from '../utils/utils';

/**
 * Internal dependencies
 */
const { features } = window.classifAISettings;

export const SettingsWrapper = ( props ) => {
	const { tab } = props;
	const servicefeatures = features[ tab ];
	const { settings, setSettings, saveSettings } = useSettings();

	const urlParams = new URLSearchParams( window.location.search );
	const requestedFeature = urlParams.get( 'feature' );
	const initialFeature = Object.keys( servicefeatures ).includes(
		requestedFeature
	)
		? requestedFeature
		: Object.keys( servicefeatures )[ 0 ];

	const featureOptions = Object.keys( servicefeatures ).map( ( feature ) => {
		return {
			name: feature,
			title:
				servicefeatures[ feature ]?.label ||
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
					console.log( 'featureName', featureName );
					updateUrl( 'feature', featureName );
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
										featureSettings={
											settings[ feature.name ] ?? {}
										}
										setFeatureSettings={ ( newSettings ) =>
											setSettings( {
												...settings,
												[ feature.name ]: newSettings,
											} )
										}
										saveSettings={ saveSettings }
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
