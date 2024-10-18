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
import { SettingsRow } from '../settings-row';
import { STORE_NAME } from '../../data/store';
import { useFeatureContext } from '../feature-settings/context';

/**
 * Component for Azure AI Vision Provider settings.
 *
 * This component is used within the ProviderSettings component to allow users to configure the Azure AI Vision Provider settings.
 *
 * @param {Object}  props              Component props.
 * @param {boolean} props.isConfigured Whether the provider is configured.
 *
 * @return {React.ReactElement} AzureAIVisionSettings component.
 */
export const AzureAIVisionSettings = ( { isConfigured = false } ) => {
	const providerName = 'ms_computer_vision';
	const { featureName } = useFeatureContext();
	const providerSettings = useSelect(
		( select ) =>
			select( STORE_NAME ).getFeatureSettings( providerName ) || {}
	);
	const { setProviderSettings } = useDispatch( STORE_NAME );
	const onChange = ( data ) => setProviderSettings( providerName, data );

	const Description = () => (
		<>
			{ __(
				'Supported protocol and hostname endpoints, e.g.,',
				'classifai'
			) }
			<code>
				{ __( 'https://EXAMPLE.openai.azure.com', 'classifai' ) }
			</code>
		</>
	);

	return (
		<>
			{ ! isConfigured && (
				<>
					<SettingsRow
						label={ __( 'Endpoint URL', 'classifai' ) }
						description={ <Description /> }
					>
						<InputControl
							id={ `${ providerName }_endpoint_url` }
							type="text"
							value={ providerSettings.endpoint_url || '' }
							onChange={ ( value ) =>
								onChange( { endpoint_url: value } )
							}
						/>
					</SettingsRow>
					<SettingsRow label={ __( 'API Key', 'classifai' ) }>
						<InputControl
							id={ `${ providerName }_api_key` }
							type="password"
							value={ providerSettings.api_key || '' }
							onChange={ ( value ) =>
								onChange( { api_key: value } )
							}
						/>
					</SettingsRow>
				</>
			) }
			{ 'feature_descriptive_text_generator' === featureName && (
				<SettingsRow
					label={ __( 'Confidence threshold', 'classifai' ) }
					description={ __(
						'Minimum confidence score for automatically added generated text, numeric value from 0-100. Recommended to be set to at least 55.',
						'classifai'
					) }
				>
					<InputControl
						id={ `${ providerName }_descriptive_confidence_threshold` }
						type="number"
						value={
							providerSettings.descriptive_confidence_threshold ||
							55
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
						'Minimum confidence score for automatically added image tags, numeric value from 0-100. Recommended to be set to at least 70.',
						'classifai'
					) }
				>
					<InputControl
						id={ `${ providerName }_tag_confidence_threshold` }
						type="number"
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
