/**
 * WordPress dependencies
 */
import { useSelect, useDispatch } from '@wordpress/data';
import { SelectControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { SettingsRow } from '../settings-row';
import { STORE_NAME } from '../../data/store';
import { OpenAISettings } from './openai';

/**
 * Component for OpenAI Speech to Text settings.
 *
 * @param {Object}  props              Component props.
 * @param {boolean} props.isConfigured Whether the provider is configured.
 *
 * @return {React.ReactElement} OpenAISpeechToTextSettings component.
 */
export const OpenAISpeechToTextSettings = ( { isConfigured = false } ) => {
	const providerName = 'openai_whisper';
	const providerSettings = useSelect(
		( select ) =>
			select( STORE_NAME ).getFeatureSettings( providerName ) || {}
	);
	const { setProviderSettings } = useDispatch( STORE_NAME );
	const onChange = ( data ) => setProviderSettings( providerName, data );

	return (
		<>
			{ ! isConfigured && (
				<OpenAISettings
					providerSettings={ providerSettings }
					onChange={ onChange }
				/>
			) }
			<SettingsRow
				label={ __( 'Model', 'classifai' ) }
				description={ __(
					'Choose which model you want to use. The default is Whisper which works well for most use cases. If you need more accurate results, you can use GPT-4o mini Transcribe or GPT-4o Transcribe, but note those are both significantly more expensive.',
					'classifai'
				) }
			>
				<SelectControl
					id={ `${ providerName }_model` }
					onChange={ ( value ) => onChange( { model: value } ) }
					value={ providerSettings.model || 'whisper-1' }
					options={ [
						{
							label: __( 'Whisper', 'classifai' ),
							value: 'whisper-1',
						},
						{
							label: __( 'GPT-4o mini Transcribe', 'classifai' ),
							value: 'gpt-4o-mini-transcribe',
						},
						{
							label: __( 'GPT-4o Transcribe', 'classifai' ),
							value: 'gpt-4o-transcribe',
						},
					] }
					__nextHasNoMarginBottom
					__next40pxDefaultSize
				/>
			</SettingsRow>
		</>
	);
};
