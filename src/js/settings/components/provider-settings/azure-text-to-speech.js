import { useSelect, useDispatch } from '@wordpress/data';
import {
	__experimentalInputControl as InputControl, // eslint-disable-line @wordpress/no-unsafe-wp-apis
	SelectControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { SettingsRow } from '../settings-row';
import { STORE_NAME } from '../../data/store';

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
