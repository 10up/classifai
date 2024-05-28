/**
 * WordPress dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import {
	ToggleControl,
	SelectControl,
	Button,
	Panel,
	PanelBody,
	Spinner,
	Slot,
} from '@wordpress/components';
import { PluginArea } from '@wordpress/plugins';

/**
 * Internal dependencies
 */
import { getFeature, getScope } from '../../utils/utils';
import { useSettings } from '../../hooks';
import { UserPermissions } from '../user-permissions';

/**
 * Feature Settings component.
 *
 * @param {Object} props             All the props passed to this function.
 * @param {string} props.featureName The name of the feature.
 */
export const FeatureSettings = ( { featureName } ) => {
	const { setFeatureSettings, saveSettings, settings, isLoaded } =
		useSettings();
	const feature = getFeature( featureName );
	const featureSettings = settings[ featureName ] || {};
	const featureTitle = feature?.label || __( 'Feature', 'classifai' );

	const providers = Object.keys( feature?.providers || {} ).map(
		( value ) => {
			return {
				value,
				label: feature.providers[ value ] || '',
			};
		}
	);

	function setSettings( newSettings ) {
		setFeatureSettings( newSettings );
	}

	function saveFeatureSettings() {
		saveSettings( featureName );
	}

	if ( ! isLoaded ) {
		return <Spinner />; // TODO: Add proper styling for the spinner.
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

					<Slot name="ClassifAIProviderSettings">
						{ ( fills ) => <> { fills }</> }
					</Slot>
					<Slot name="ClassifAIFeatureSettings">
						{ ( fills ) => <> { fills }</> }
					</Slot>
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
						onClick={ saveFeatureSettings }
					>
						{ __( 'Save Settings', 'classifai' ) }
					</Button>
				</div>
			</div>
			<PluginArea scope={ getScope( featureName ) } />
			<PluginArea scope={ getScope( featureSettings.provider ) } />
		</>
	);
};
