/**
 * WordPress dependencies
 */
import { useSelect, useDispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { ModelSelector } from '../../model-selector';
import { STORE_NAME } from '../../../data/store';
import { ElevenLabsSettings } from './base';

/**
 * Component for the ElevenLabs Speech to Text Provider settings.
 *
 * @param {Object}  props              Component props.
 * @param {boolean} props.isConfigured Whether the provider is configured.
 *
 * @return {React.ReactElement} ElevenLabsSpeechToTextSettings component.
 */
export const ElevenLabsSpeechToTextSettings = ( { isConfigured = false } ) => {
	const providerName = 'elevenlabs_speech_to_text';
	const providerSettings = useSelect(
		( select ) =>
			select( STORE_NAME ).getFeatureSettings( providerName ) || {}
	);
	const { setProviderSettings } = useDispatch( STORE_NAME );
	const onChange = ( data ) => setProviderSettings( providerName, data );

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
		</>
	);
};
