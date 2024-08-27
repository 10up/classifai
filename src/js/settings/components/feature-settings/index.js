/**
 * WordPress dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { Panel, PanelBody, Spinner, Notice } from '@wordpress/components';
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
import { SaveSettingsButton, SaveButtonSlot } from './save-settings-button';
import { Notices } from './notices';
import { useFeatureContext } from './context';
import { FeatureAdditionalSettings } from '../feature-additional-settings';

const PersonalizerDeprecationNotice = () => (
	<Notice
		status="warning"
		isDismissible={ false }
		className="personalizer-deprecation-notice"
	>
		<p>
			<a
				href="https://learn.microsoft.com/en-us/azure/ai-services/personalizer/"
				target="_blank"
				rel="noreferrer"
			>
				{ __( 'As of September 2023', 'classifai' ) }
			</a>
			{ ', ' }
			{ __(
				'new Personalizer resources can no longer be created in Azure. This is currently the only provider available for the Recommended Content feature and as such, this feature will not work unless you had previously created a Personalizer resource. The Azure AI Personalizer provider is deprecated and will be removed in a future release. We hope to replace this provider with another one in a coming release to continue to support this feature',
				'classifai'
			) }
			{ __( '(see ', 'classifai' ) }
			<a
				href="https://github.com/10up/classifai/issues/392"
				target="_blank"
				rel="noreferrer"
			>
				{ __( 'issue#392', 'classifai' ) }
			</a>
			{ ').' }
		</p>
	</Notice>
);

/**
 * Feature Settings component.
 */
export const FeatureSettings = ( { onSaveSuccess = () => {} } ) => {
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
			{ 'feature_recommended_content' === featureName && (
				<PersonalizerDeprecationNotice />
			) }
			<Notices feature={ featureName } />
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
				<SaveButtonSlot>
					<SaveSettingsButton onSaveSuccess={ onSaveSuccess } />
				</SaveButtonSlot>
			</div>
		</>
	);
};
