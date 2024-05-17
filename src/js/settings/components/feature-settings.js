/**
 * WordPress dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import {
	withFilters,
	Slot,
	ToggleControl,
	SelectControl,
	Button,
	Panel,
	PanelBody,
	Spinner,
} from '@wordpress/components';
import { useState } from '@wordpress/element';
import { UserPermissions } from './user-access';

/**
 * Internal dependencies
 */
import { getFeature } from '../utils/utils';
import { useSettings } from '../hooks/use-settings';

// Provides an entry point to slot in additional settings.
const ProviderSettingsComponent = () => <></>;
const FeatureSettingsComponent = () => <></>;
const ProviderSettings = withFilters( 'classifai.ProviderSettings' )(
	ProviderSettingsComponent
);
const AdditionalFeatureSettings = withFilters( 'classifai.FeatureSettings' )(
	FeatureSettingsComponent
);

/**
 * Feature Settings component.
 *
 * @param {Object} props All the props passed to this function
 */
export const FeatureSettings = ( props ) => {
	const { featureName } = props;
	const { setFeatureSettings, saveSettings, settings, isLoaded } =
		useSettings();
	const feature = getFeature( featureName );
	const featureSettings = settings[ featureName ] || {};
	const featureTitle = feature?.label || __( 'Feature', 'classifai' );
	const [ hasUpdates, setHasUpdates ] = useState( false );

	const providers = Object.keys( feature?.providers || {} ).map(
		( value ) => {
			return {
				value,
				label: feature.providers[ value ] || '',
			};
		}
	);

	function setSettings( newSettings ) {
		setFeatureSettings( featureName, {
			...featureSettings,
			...newSettings,
		} );
		setHasUpdates( true );
	}

	function saveFeatureSettings() {
		saveSettings( featureName );
		setHasUpdates( false );
	}

	if ( ! isLoaded ) {
		return <Spinner />;
	}

	return (
		<>
			<Panel
				header={
					// translators: %s: Feature title
					sprintf( __( '%s Settings', 'classifai' ), featureTitle )
				}
			>
				<PanelBody>
					<ToggleControl
						label={ __( 'Enable feature', 'classifai' ) }
						checked={ featureSettings.status === '1' }
						onChange={ ( status ) =>
							setSettings( {
								status: status ? '1' : '0', // TODO: Use boolean, currently using string for compatibility.
							} )
						}
					/>
					<SelectControl
						label={ __( 'Select a provider', 'classifai' ) }
						onChange={ ( provider ) => setSettings( { provider } ) }
						value={ featureSettings.provider }
						options={ providers }
					/>

					<Slot name="ProviderSettings" />
					<Slot name="FeatureSettings" />
				</PanelBody>
				<UserPermissions
					featureName={ featureName }
					featureSettings={ featureSettings }
					setSettings={ setSettings }
				/>
			</Panel>
			<div className="settings-tab__plugin-settings inner-container">
				<div className="setting-tabs__setting-panels">
					<Button
						className="save-settings-button"
						variant="primary"
						disabled={ ! hasUpdates }
						onClick={ saveFeatureSettings }
					>
						{ __( 'Save Settings', 'classifai' ) }
					</Button>
				</div>
			</div>
			<ProviderSettings
				featureSettings={ featureSettings }
				setSettings={ setSettings }
				{ ...props }
			/>
			<AdditionalFeatureSettings
				featureSettings={ featureSettings }
				setSettings={ setSettings }
				{ ...props }
			/>
		</>
	);
};
