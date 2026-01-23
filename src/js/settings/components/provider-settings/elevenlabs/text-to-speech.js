/**
 * WordPress dependencies
 */
import { useSelect, useDispatch } from '@wordpress/data';
import { SelectControl } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { STORE_NAME } from '../../../data/store';
import { ElevenLabsSettings } from './base';
import { ModelSelector } from '../../model-selector';
import { __ } from '@wordpress/i18n';
import { SettingsRow } from '../../settings-row';

/**
 * Component for the ElevenLabs Text to Speech Provider settings.
 *
 * @param {Object}  props              Component props.
 * @param {boolean} props.isConfigured Whether the provider is configured.
 *
 * @return {React.ReactElement} ElevenLabsTextToSpeechSettings component.
 */
export const ElevenLabsTextToSpeechSettings = ( { isConfigured = false } ) => {
	const providerName = 'elevenlabs_text_to_speech';
	const providerSettings = useSelect(
		( select ) =>
			select( STORE_NAME ).getFeatureSettings( providerName ) || {}
	);
	const { setProviderSettings } = useDispatch( STORE_NAME );
	const onChange = ( data ) => setProviderSettings( providerName, data );
	const voices = providerSettings.voices.length
		? providerSettings.voices
		: [
				{
					name: __( '-- Choose Voice --', 'classifai' ),
					id: '',
				},
		  ];

	return (
		<>
			{ ! isConfigured && (
				<ElevenLabsSettings
					providerSettings={ providerSettings }
					onChange={ onChange }
				/>
			) }
			<ModelSelector
				providerName={ providerName }
				providerSettings={ providerSettings }
				onChange={ ( value ) => onChange( { model: value } ) }
				modelsDocUrl="https://elevenlabs.io/docs/models"
			/>
			<SettingsRow
				label={ __( 'Voice', 'classifai' ) }
				description={
					<>
						{ __(
							'Select the voice for the generated audio. You can find more details on voices',
							'classifai'
						) }{ ' ' }
						<a
							href="https://elevenlabs.io/app/voice-library"
							target="_blank"
							rel="noreferrer"
						>
							{ __( 'here', 'classifai' ) }
						</a>
						.
					</>
				}
			>
				<SelectControl
					id={ `${ providerName }_voice` }
					onChange={ ( value ) => onChange( { voice: value } ) }
					value={ providerSettings.voice || '' }
					options={ voices.map( ( ele ) => ( {
						label: ele.name,
						value: ele.id,
					} ) ) }
					disabled={ ! providerSettings.voices?.length }
					__nextHasNoMarginBottom
					__next40pxDefaultSize
				/>
			</SettingsRow>
		</>
	);
};
