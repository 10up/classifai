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

/**
 * Component for Azure Language Provider settings.
 *
 * This component is used within the ProviderSettings component to allow users to configure the Azure Language Provider settings.
 *
 * @param {Object}  props              Component props.
 * @param {boolean} props.isConfigured Whether the provider is configured.
 *
 * @return {React.ReactElement} AzureLanguageSettings component.
 */
export const AzureLanguageSettings = ( { isConfigured = false } ) => {
	const providerName = 'azure_language';
	const providerSettings = useSelect(
		( select ) =>
			select( STORE_NAME ).getFeatureSettings( providerName ) || {}
	);
	const { setProviderSettings } = useDispatch( STORE_NAME );
	const onChange = ( data ) => {
		console.log( 'data', data );
		setProviderSettings( providerName, data );
	};

	const Description = () => (
		<>
			{ __( "Don't have an Azure account yet?", 'classifai' ) }{ ' ' }
			<a
				title={ __( 'Create a Language resource in Azure', 'classifai' ) }
				href="https://portal.azure.com/#home"
				target="_blank"
				rel="noopener noreferrer"
			>
				{ __( 'Create a Language resource', 'classifai' ) }
			</a>{ ' ' }
			{ __( 'in the Azure portal to get your key and endpoint.', 'classifai' ) }
		</>
	);

	return (
		<>
			{ ! isConfigured && (
				<>
					<SettingsRow
						label={ __( 'API Key', 'classifai' ) }
						description={ <Description /> }
					>
						<InputControl
							id={ `${ providerName }_api_key` }
							type="password"
							value={ providerSettings.api_key || '' }
							onChange={ ( value ) => onChange( { api_key: value } ) }
						/>
					</SettingsRow>
					<SettingsRow
						label={ __( 'Endpoint URL', 'classifai' ) }
						description={ __(
							'Azure Cognitive Service Language Endpoint. You can find this in the Azure portal under your Language resource.',
							'classifai'
						) }
					>
						<InputControl
							id={ `${ providerName }_endpoint_url` }
							type="url"
							value={ providerSettings.endpoint_url || '' }
							onChange={ ( value ) => onChange( { endpoint_url: value } ) }
						/>
					</SettingsRow>
				</>
			) }
		</>
	);
};
