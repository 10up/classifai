/**
 * WordPress dependencies
 */
import {
	__experimentalInputControl as InputControl, // eslint-disable-line @wordpress/no-unsafe-wp-apis
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { SettingsRow } from '../../../settings-row';

/**
 * Component for Azure Text to Speech Provider settings.
 *
 * This is the base component for Azure Text to Speech settings.
 *
 * @param {Object}   props                  Component props.
 * @param {Object}   props.providerSettings The provider settings.
 * @param {Function} props.onChange         Function to call when the provider settings change.
 *
 * @return {React.ReactElement} AzureTextToSpeechBaseSettings component.
 */
export const AzureTextToSpeechBaseSettings = ( {
	providerSettings,
	onChange,
} ) => {
	const Description = () => (
		<>
			{ __( 'Text to Speech region endpoint, e.g.', 'classifai' ) }{ ' ' }
			<code>
				{ __(
					'https://LOCATION.tts.speech.microsoft.com/',
					'classifai'
				) }
			</code>
			{ '. ' }
			{ __( 'Replace', 'classifai' ) }{ ' ' }
			<code>{ __( 'LOCATION', 'classifai' ) }</code>{ ' ' }
			{ __(
				'with the Location/Region you selected for the resource in Azure.',
				'classifai'
			) }
		</>
	);

	return (
		<>
			<SettingsRow
				label={ __( 'Endpoint URL', 'classifai' ) }
				description={ <Description /> }
			>
				<InputControl
					id="ms_azure_text_to_speech_endpoint_url"
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
					id="ms_azure_text_to_speech_api_key"
					type="password"
					value={ providerSettings.api_key || '' }
					onChange={ ( value ) => onChange( { api_key: value } ) }
					__next40pxDefaultSize
				/>
			</SettingsRow>
		</>
	);
};
