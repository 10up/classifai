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

const ElasticPressRequiredNotice = () => (
	<Notice
		status="error"
		isDismissible={ false }
		className="elasticpress-required-notice"
	>
		<p>
			{ __(
				'The Smart 404 Feature requires the ElasticPress plugin to be installed and active prior to use.',
				'classifai'
			) }
		</p>
	</Notice>
);

/**
 * Feature Settings component.
 *
 * @param {Object}   props               Component props.
 * @param {Function} props.onSaveSuccess Callback function to be executed after saving settings.
 */
export const FeatureSettings = ( { onSaveSuccess = () => {} } ) => {
	const { featureName } = useFeatureContext();
	const { setCurrentFeature } = useDispatch( STORE_NAME );

	useEffect( () => {
		setCurrentFeature( featureName );
	}, [ featureName, setCurrentFeature ] );

	const { isLoaded, error } = useSelect( ( select ) => {
		return {
			isLoaded: select( STORE_NAME ).getIsLoaded(),
			error: select( STORE_NAME ).getError(),
		};
	} );

	const feature = getFeature( featureName );
	const featureTitle = feature?.label || __( 'Feature', 'classifai' );

	// Show loading spinner if settings are not loaded yet.
	if ( ! isLoaded ) {
		return (
			<div className="classifai-loading-settings">
				<Spinner />
				<span className="description">
					{ __( 'Loading settings…', 'classifai' ) }
				</span>
			</div>
		);
	}

	// Show error notice if settings failed to load.
	if ( error ) {
		return (
			<Notice status="error" isDismissible={ false }>
				{ error }
			</Notice>
		);
	}

	// Show ElasticPress required notice if the feature is Smart 404 and ElasticPress is not active.
	if (
		'feature_smart_404' === featureName &&
		! window.classifAISettings?.isEPinstalled
	) {
		return <ElasticPressRequiredNotice />;
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
