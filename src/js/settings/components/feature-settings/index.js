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
import { useDispatch, useSelect } from '@wordpress/data';
import { useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { getFeature, getScope } from '../../utils/utils';
import { useSettings } from '../../hooks';
import { UserPermissions } from '../user-permissions';
import { SettingsRow } from '../settings-row';
import { STORE_NAME } from '../../data/store';
import { Navigate, useParams } from 'react-router-dom';

const { features } = window.classifAISettings;
/**
 * Feature Settings component.
 *
 */
export const FeatureSettings = () => {
	const { feature: featureName, service } = useParams();
	const serviceFeatures = Object.keys( features[ service ] || {} );
	const { setCurrentFeature } = useDispatch( STORE_NAME );
	const { setFeatureSettings, saveSettings } = useSettings();

	useEffect( () => {
		setCurrentFeature( featureName );
	}, [ featureName, setCurrentFeature ] );

	const { settings, isLoaded } = useSelect( ( select ) => {
		return {
			settings: select( STORE_NAME ).getSettings(),
			isLoaded: select( STORE_NAME ).getIsLoaded(),
		};
	} );

	// If the feature is not available, redirect to the first feature.
	if ( ! serviceFeatures.includes( featureName ) ) {
		return <Navigate to={ serviceFeatures[ 0 ] } replace />;
	}

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
				className="settings-panel"
			>
				<PanelBody>
					<SettingsRow label={ __( 'Enable feature', 'classifai' ) }>
						<ToggleControl
							label={ __( 'Enable feature', 'classifai' ) }
							checked={ featureSettings.status === '1' }
							onChange={ ( status ) =>
								setSettings( {
									status: status ? '1' : '0', // TODO: Use boolean, currently using string for compatibility.
								} )
							}
						/>
					</SettingsRow>
					<SettingsRow
						label={ __( 'Select a provider', 'classifai' ) }
					>
						<SelectControl
							onChange={ ( provider ) =>
								setSettings( { provider } )
							}
							value={ featureSettings.provider }
							options={ providers }
						/>
					</SettingsRow>

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
			<div className="classifai-settings-footer">
				<Button
					className="save-settings-button"
					variant="primary"
					onClick={ saveFeatureSettings }
				>
					{ __( 'Save Settings', 'classifai' ) }
				</Button>
			</div>
			<PluginArea scope={ getScope( featureName ) } />
			<PluginArea scope={ getScope( featureSettings.provider ) } />
		</>
	);
};
