/**
 * WordPress dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { Panel, PanelBody, Spinner, Notice, Slot } from '@wordpress/components';
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

/**
 * Component to display a notice when the Smart 404 feature is enabled but ElasticPress is not active.
 *
 * @return {React.ReactElement} The ElasticPressRequiredNotice component.
 */
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
 * This is the main component for the feature settings page. It renders the settings panel for the selected feature.
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
			<Notices feature={ featureName } />
			<Slot name="ClassifAIBeforeFeatureSettingsPanel">
				{ ( fills ) => <>{ fills }</> }
			</Slot>
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
			<Slot name="ClassifAIAfterFeatureSettingsPanel">
				{ ( fills ) => <>{ fills }</> }
			</Slot>
			<div className="classifai-settings-footer">
				<SaveButtonSlot>
					<SaveSettingsButton onSaveSuccess={ onSaveSuccess } />
				</SaveButtonSlot>
			</div>
		</>
	);
};
