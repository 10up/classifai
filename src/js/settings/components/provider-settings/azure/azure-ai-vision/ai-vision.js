/**
 * WordPress dependencies
 */
import { useSelect, useDispatch } from '@wordpress/data';
// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
import { __experimentalInputControl as InputControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { SettingsRow } from '../../../settings-row';
import { STORE_NAME } from '../../../../data/store';
import { useFeatureContext } from '../../../feature-settings/context';
import { AzureAIVisionBaseSettings } from './base';

/**
 * Component for Azure AI Vision Provider settings.
 *
 * @param {Object}  props              Component props.
 * @param {string}  props.providerName The provider name.
 * @param {boolean} props.isConfigured Whether the provider is configured.
 *
 * @return {React.ReactElement} AzureAIVisionSettings component.
 */
export const AzureAIVisionSettings = ( {
	providerName,
	isConfigured = false,
} ) => {
	const { featureName } = useFeatureContext();
	const providerSettings = useSelect(
		( select ) =>
			select( STORE_NAME ).getFeatureSettings( providerName ) || {}
	);
	const { setProviderSettings } = useDispatch( STORE_NAME );
	const onChange = ( data ) => setProviderSettings( providerName, data );

	return (
		<>
			{ ! isConfigured && (
				<>
					<AzureAIVisionBaseSettings
						providerSettings={ providerSettings }
						onChange={ onChange }
					/>
				</>
			) }

			{ 'feature_descriptive_text_generator' === featureName && (
				<SettingsRow
					label={ __( 'Confidence threshold', 'classifai' ) }
					description={ __(
						'Minimum confidence score for automatically added generated text, numeric value from 0–100. Recommended to be set to at least 70.',
						'classifai'
					) }
				>
					<InputControl
						id={ `${ providerName }_descriptive_confidence_threshold` }
						type="number"
						min={ 0 }
						max={ 100 }
						step={ 0.01 }
						value={
							providerSettings.descriptive_confidence_threshold ||
							70
						}
						onChange={ ( value ) =>
							onChange( {
								descriptive_confidence_threshold: value,
							} )
						}
					/>
				</SettingsRow>
			) }

			{ 'feature_image_tags_generator' === featureName && (
				<SettingsRow
					label={ __( 'Confidence threshold', 'classifai' ) }
					description={ __(
						'Minimum confidence score for automatically added image tags, numeric value from 0–100. Recommended to be set to at least 70.',
						'classifai'
					) }
				>
					<InputControl
						id={ `${ providerName }_tag_confidence_threshold` }
						type="number"
						min={ 0 }
						max={ 100 }
						step={ 0.01 }
						value={
							providerSettings.tag_confidence_threshold || 70
						}
						onChange={ ( value ) =>
							onChange( {
								tag_confidence_threshold: value,
							} )
						}
					/>
				</SettingsRow>
			) }
		</>
	);
};
