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
} from '@wordpress/components';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */

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
	const { feature, featureSettings, setFeatureSettings, saveSettings } =
		props;
	const featureName = feature?.name;
	const featureTitle = feature?.title || __( 'Feature', 'classifai' );
	const [ hasUpdates, setHasUpdates ] = useState( false );

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
			<h2>
				{
					// translators: %s: Feature title
					sprintf( __( '%s Settings', 'classifai' ), featureTitle )
				}
			</h2>
			<div className="settings-tab__plugin-settings inner-container">
				<div className="setting-tabs__setting-panels">
					<div className="settings-panel">
						<div className="settings-label">
							<span>{ __( 'Enable feature', 'classifai' ) }</span>
						</div>
						<div className="settings-control">
							<ToggleControl
								label={ __( 'Enable feature', 'classifai' ) }
								checked={ featureSettings.status === '1' }
								onChange={ ( status ) =>
									setSettings( {
										status: status ? '1' : '0',
									} )
								}
							/>
						</div>

						<div className="settings-label">
							<span>
								{ __( 'Select a provider', 'classifai' ) }
							</span>
						</div>
						<div className="settings-control">
							<SelectControl // TODO: Remove this temporary code
								onChange={ ( provider ) =>
									setSettings( { provider } )
								}
								value={ featureSettings.provider }
								options={ [
									{
										label: 'Option A',
										value: 'a',
									},
									{
										label: 'Option B',
										value: 'b',
									},
									{
										label: 'Option C',
										value: 'c',
									},
								] }
							/>
						</div>
						<Slot name="ProviderSettings" />
						<Slot name="FeatureSettings" />
					</div>

					<Button
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
