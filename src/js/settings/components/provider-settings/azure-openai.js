import { useSelect, useDispatch } from '@wordpress/data';
// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
import { __experimentalInputControl as InputControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { SettingsRow } from '../settings-row';
import { STORE_NAME } from '../../data/store';
import { useFeatureContext } from '../feature-settings/context';

export const AzureOpenAISettings = ( {
	providerName = 'azure_openai',
	isConfigured = false,
} ) => {
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
					<SettingsRow
						label={ __( 'Deployment name', 'classifai' ) }
						description={ __(
							'Custom name you chose for your deployment when you deployed a model.',
							'classifai'
						) }
					>
						<InputControl
							id={ `${ providerName }_deployment` }
							type="text"
							value={ providerSettings.deployment || '' }
							onChange={ ( value ) =>
								onChange( { deployment: value } )
							}
						/>
					</SettingsRow>
				</>
			) }
			{ [
				'feature_content_resizing',
				'feature_title_generation',
			].includes( featureName ) && (
				<SettingsRow
					label={ __( 'Number of suggestions', 'classifai' ) }
					description={ __(
						'Number of suggestions that will be generated in one request.',
						'classifai'
					) }
				>
					<InputControl
						id={ `${ providerName }_number_of_suggestions` }
						type="number"
						value={ providerSettings.number_of_suggestions || 1 }
						onChange={ ( value ) =>
							onChange( { number_of_suggestions: value } )
						}
					/>
				</SettingsRow>
			) }
		</>
	);
};
