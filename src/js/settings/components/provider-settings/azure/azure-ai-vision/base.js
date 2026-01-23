/**
 * WordPress dependencies
 */
// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
import { __experimentalInputControl as InputControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { SettingsRow } from '../../../settings-row';

/**
 * Component for Azure AI Vision Provider settings.
 *
 * This is the base component for Azure AI Vision settings.
 *
 * @param {Object}   props                  Component props.
 * @param {Object}   props.providerSettings The provider settings.
 * @param {Function} props.onChange         Function to call when the provider settings change.
 *
 * @return {React.ReactElement} AzureAIVisionSettings component.
 */
export const AzureAIVisionBaseSettings = ( { providerSettings, onChange } ) => {
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
					id="ms_computer_vision_endpoint_url"
					type="text"
					value={ providerSettings.endpoint_url || '' }
					onChange={ ( value ) =>
						onChange( { endpoint_url: value } )
					}
					__next40pxDefaultSize
				/>
			</SettingsRow>
			<SettingsRow label={ __( 'API Key', 'classifai' ) }>
				<InputControl
					id="ms_computer_vision_api_key"
					type="password"
					value={ providerSettings.api_key || '' }
					onChange={ ( value ) => onChange( { api_key: value } ) }
					__next40pxDefaultSize
				/>
			</SettingsRow>
		</>
	);
};
