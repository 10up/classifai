import { useSelect, useDispatch } from '@wordpress/data';
// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
import { __experimentalInputControl as InputControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { SettingsRow } from '../settings-row';
import { STORE_NAME } from '../../data/store';
import { useFeatureContext } from '../feature-settings/context';

export const AzureAIVisionSettings = () => {
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
			<SettingsRow
				label={ __( 'Endpoint URL', 'classifai' ) }
				description={ <Description /> }
			>
				<InputControl
					type="text"
					value={ providerSettings.endpoint_url || '' }
					onChange={ ( value ) =>
						onChange( { endpoint_url: value } )
					}
				/>
			</SettingsRow>
			<SettingsRow label={ __( 'API Key', 'classifai' ) }>
				<InputControl
					type="password"
					value={ providerSettings.api_key || '' }
					onChange={ ( value ) => onChange( { api_key: value } ) }
				/>
			</SettingsRow>
			{ 'feature_descriptive_text_generator' === featureName && (
				<SettingsRow
					label={ __( 'Confidence threshold', 'classifai' ) }
					description={ __(
						'Minimum confidence score for automatically added generated text, numeric value from 0-100. Recommended to be set to at least 55.',
						'classifai'
					) }
				>
					<InputControl
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
