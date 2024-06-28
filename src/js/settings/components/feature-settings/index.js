/**
 * WordPress dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { Panel, PanelBody, Spinner } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { getFeature } from '../../utils/utils';
import { UserPermissions } from '../user-permissions';
import { STORE_NAME } from '../../data/store';
import { ProviderSettings } from '../provider-settings';
import { EnableToggleControl } from './enable-feature';
import { SaveSettingsButton } from './save-settings-button';
import { Notices } from './notices';
import { useFeatureContext } from './context';
import { FeatureAdditionalSettings } from '../feature-additional-settings';

/**
 * Feature Settings component.
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
					<FeatureAdditionalSettings />
				</PanelBody>
				<UserPermissions />
			</Panel>
			<div className="classifai-settings-footer">
				<SaveSettingsButton />
			</div>
		</>
	);
};
