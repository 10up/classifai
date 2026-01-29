/**
 * WordPress dependencies
 */
import {
	__experimentalInputControl as InputControl, // eslint-disable-line @wordpress/no-unsafe-wp-apis
	SelectControl,
	TextareaControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { SettingsRow } from '../settings-row';
import { useSelect, useDispatch } from '@wordpress/data';
import { STORE_NAME } from '../../data/store';
import { OpenAISettings } from './openai';

/**
 * Component for OpenAI Text to Speech Provider settings.
 *
 * This component is used within the ProviderSettings component to allow users to configure the OpenAI Text to Speech Provider settings.
 *
 * @param {Object}  props              Component props.
 * @param {boolean} props.isConfigured Whether the provider is configured.
 *
 * @return {React.ReactElement} OpenAITextToSpeechSettings component.
 */
export const OpenAITextToSpeechSettings = ( { isConfigured = false } ) => {
	const providerName = 'openai_text_to_speech';
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
				label={ __( 'TTS model', 'classifai' ) }
				description={
					<>
						{ __( 'Select a', 'classifai' ) }{ ' ' }
						<a
							href="https://platform.openai.com/docs/models#tts"
							title={ __(
								'OpenAI Text to Speech models',
								'classifai'
							) }
							target="_blank"
							rel="noreferrer"
						>
							{ __( 'model', 'classifai' ) }
						</a>{ ' ' }
						{ __(
							'depending on your requirements. GPT-4o mini TTS is the recommendation, as it is the newest model and typically provides the best results and performance at the best cost.',
							'classifai'
						) }
					</>
				}
			>
				<SelectControl
					id={ `${ providerName }_tts_model` }
					onChange={ ( value ) => onChange( { tts_model: value } ) }
					value={ providerSettings.tts_model || 'gpt-4o-mini-tts' }
					options={ [
						{
							label: __( 'GPT-4o mini TTS', 'classifai' ),
							value: 'gpt-4o-mini-tts',
						},
						{
							label: __( 'Text-to-speech 1', 'classifai' ),
							value: 'tts-1',
						},
						{
							label: __( 'Text-to-speech 1 HD', 'classifai' ),
							value: 'tts-1-hd',
						},
					] }
					__nextHasNoMarginBottom
					__next40pxDefaultSize
				/>
			</SettingsRow>
			<SettingsRow
				label={ __( 'Voice', 'classifai' ) }
				description={
					<>
						{ __( 'Select the speech', 'classifai' ) }{ ' ' }
						<a
							href="https://platform.openai.com/docs/guides/text-to-speech#voice-options"
							target="_blank"
							rel="noreferrer"
						>
							{ __( 'voice', 'classifai' ) }
						</a>
						.
					</>
				}
			>
				<SelectControl
					id={ `${ providerName }_voice` }
					onChange={ ( value ) => onChange( { voice: value } ) }
					value={ providerSettings.voice || 'alloy' }
					options={ [
						{
							label: __( 'Alloy', 'classifai' ),
							value: 'alloy',
						},
						{
							label: __( 'Ash', 'classifai' ),
							value: 'ash',
						},
						{
							label: __( 'Coral', 'classifai' ),
							value: 'coral',
						},
						{
							label: __( 'Echo', 'classifai' ),
							value: 'echo',
						},
						{
							label: __( 'Fable', 'classifai' ),
							value: 'fable',
						},
						{
							label: __( 'Onyx', 'classifai' ),
							value: 'onyx',
						},
						{
							label: __( 'Nova', 'classifai' ),
							value: 'nova',
						},
						{
							label: __( 'Sage', 'classifai' ),
							value: 'sage',
						},
						{
							label: __( 'Shimmer', 'classifai' ),
							value: 'shimmer',
						},
					] }
					__nextHasNoMarginBottom
					__next40pxDefaultSize
				/>
			</SettingsRow>
			<SettingsRow
				label={ __( 'Voice instructions', 'classifai' ) }
				description={
					<>
						{ __(
							'Optional instructions to control the voice characteristics of the generated audio.',
							'classifai'
						) }{ ' ' }
						{ __(
							'For example: "Speak in a calm, professional tone" or "Use a more energetic delivery".',
							'classifai'
						) }
					</>
				}
			>
				<TextareaControl
					id={ `${ providerName }_instructions` }
					onChange={ ( value ) =>
						onChange( { instructions: value } )
					}
					value={ providerSettings.instructions || '' }
					rows={ 3 }
					placeholder={ __(
						'Enter instructions to control voice characteristics…',
						'classifai'
					) }
					__nextHasNoMarginBottom
				/>
			</SettingsRow>
			<SettingsRow
				label={ __( 'Audio format', 'classifai' ) }
				description={ __(
					'Select the desired audio format.',
					'classifai'
				) }
			>
				<SelectControl
					id={ `${ providerName }_format` }
					onChange={ ( value ) => onChange( { format: value } ) }
					value={ providerSettings.format || '.mp3' }
					options={ [
						{
							label: __( '.mp3', 'classifai' ),
							value: 'mp3',
						},
						{
							label: __( '.wav', 'classifai' ),
							value: 'wav',
						},
					] }
					__nextHasNoMarginBottom
					__next40pxDefaultSize
				/>
			</SettingsRow>
			<SettingsRow
				label={ __( 'Audio speed', 'classifai' ) }
				description={ __(
					'Select the desired speed of the generated audio.',
					'classifai'
				) }
			>
				<InputControl
					id={ `${ providerName }_speed` }
					onChange={ ( value ) => onChange( { speed: value } ) }
					value={ providerSettings.speed || 1 }
					type="number"
					step="0.25"
					min="0.25"
					max="4"
					__next40pxDefaultSize
				/>
			</SettingsRow>
		</>
	);
};
