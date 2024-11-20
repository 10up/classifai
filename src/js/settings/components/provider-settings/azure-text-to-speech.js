/**
 * WordPress dependencies
 */
import { useSelect, useDispatch } from '@wordpress/data';
import {
	__experimentalInputControl as InputControl, // eslint-disable-line @wordpress/no-unsafe-wp-apis
	SelectControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { SettingsRow } from '../settings-row';
import { STORE_NAME } from '../../data/store';

/**
 * Component for Azure Text to Speech Provider settings.
 *
 * This component is used within the ProviderSettings component to allow users to configure the Azure Text to Speech Provider settings.
 *
 * @param {Object}  props              Component props.
 * @param {boolean} props.isConfigured Whether the provider is configured.
 *
 * @return {React.ReactElement} AzureTextToSpeechSettings component.
 */
export const AzureTextToSpeechSettings = ( { isConfigured = false } ) => {
	const providerName = 'ms_azure_text_to_speech';
	const providerSettings = useSelect(
		( select ) =>
			select( STORE_NAME ).getFeatureSettings( providerName ) || {}
	);
	const { setProviderSettings } = useDispatch( STORE_NAME );
	const onChange = ( data ) => setProviderSettings( providerName, data );

	const Description = () => (
		<>
			{ __( 'Text to Speech region endpoint, e.g. ', 'classifai' ) }
			<code>
				{ __(
					'https://LOCATION.tts.speech.microsoft.com/',
					'classifai'
				) }
			</code>
			{ '. ' }
			{ __( ' Replace ', 'classifai' ) }
			<code>{ __( 'LOCATION', 'classifai' ) }</code>
			{ __(
				' with the Location/Region you selected for the resource in Azure.',
				'classifai'
			) }
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
			{ !! providerSettings.voices?.length && (
				<SettingsRow label={ __( 'Voice', 'classifai' ) }>
					<SelectControl
						id={ `${ providerName }_voice` }
						onChange={ ( value ) => onChange( { voice: value } ) }
						value={ providerSettings.voice || '' }
						options={ ( providerSettings.voices || [] ).map(
							( ele ) => ( {
								label: `${ ele.LocaleName } (${ ele.DisplayName }/${ ele.Gender })`,
								value: `${ ele.ShortName }|${ ele.Gender }`,
							} )
						) }
					/>
				</SettingsRow>
			) }
		</>
	);
};
