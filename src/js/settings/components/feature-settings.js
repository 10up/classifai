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
} from '@wordpress/components';
import { useState } from '@wordpress/element';
import { UserPermissions } from './user-access';

/**
 * Internal dependencies
 */
import { getFeature } from '../utils/utils';

// Provides an entry point to slot in additional settings. Must be placed
// outside of function to avoid unnecessary rerenders.
const AdditionalSettings = withFilters( 'classifai.PluginSettings' )(
	// eslint-disable-next-line no-unused-vars
	( props ) => <></>
);

/**
 * Renders the plugin Settings tab of the Block Visibility settings page
 *
 * @since 1.0.0
 * @param {Object} props All the props passed to this function
 */
export const FeatureSettings = ( props ) => {
	const { featureName, featureSettings, setFeatureSettings, saveSettings } =
		props;
	const feature = getFeature( featureName );
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
		setFeatureSettings( {
			...featureSettings,
			...newSettings,
		} );
		setHasUpdates( true );
	}

	function saveFeatureSettings() {
		saveSettings( { [ featureName ]: featureSettings } );
		setHasUpdates( false );
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
								status: status ? '1' : '0',
							} )
						}
					/>

					<SelectControl // TODO: Remove this temporary code
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
			<AdditionalSettings
				featureSettings={ featureSettings }
				setSettings={ setSettings }
				{ ...props }
			/>
		</>
	);
};
