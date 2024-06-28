/**
 * WordPress dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { Panel, PanelBody, Spinner, Slot } from '@wordpress/components';
import { PluginArea } from '@wordpress/plugins';
import { useDispatch, useSelect } from '@wordpress/data';
import { useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { getFeature, getScope } from '../../utils/utils';
import { UserPermissions } from '../user-permissions';
import { STORE_NAME } from '../../data/store';
import { ProviderSettings } from '../provider-settings';
import { EnableToggleControl } from './enable-feature';
import { SaveSettingsButton } from './save-settings-button';
import { Notices } from './notices';
import { useFeatureContext } from './context';

/**
 * Feature Settings component.
 *
 * @param {Object} props             Component props.
 * @param {string} props.featureName Feature name.
 */
export const FeatureSettings = () => {
	const { featureName } = useFeatureContext();
	const { setCurrentFeature } = useDispatch( STORE_NAME );

	useEffect( () => {
		setCurrentFeature( featureName );
	}, [ featureName, setCurrentFeature ] );

	const isLoaded = useSelect( ( select ) =>
		select( STORE_NAME ).getIsLoaded()
	);

	const feature = getFeature( featureName );
	const featureTitle = feature?.label || __( 'Feature', 'classifai' );

	if ( ! isLoaded ) {
		return <Spinner />; // TODO: Add proper styling for the spinner.
	}

	return (
		<>
			<Notices />
			<Panel
				header={
					// translators: %s: Feature title
					sprintf( __( '%s Settings', 'classifai' ), featureTitle )
				}
				className="settings-panel"
			>
				<PanelBody>
					<EnableToggleControl />
					<ProviderSettings />
					<Slot name="ClassifAIFeatureSettings">
						{ ( fills ) => <> { fills }</> }
					</Slot>
				</PanelBody>
				<UserPermissions />
			</Panel>
			<div className="classifai-settings-footer">
				<SaveSettingsButton />
			</div>
			<PluginArea scope={ getScope( featureName ) } />
		</>
	);
};
