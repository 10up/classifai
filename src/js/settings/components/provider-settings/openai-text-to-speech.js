import {
	__experimentalInputControl as InputControl, // eslint-disable-line @wordpress/no-unsafe-wp-apis
	SelectControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { SettingsRow } from '../settings-row';
import { useSelect, useDispatch } from '@wordpress/data';
import { STORE_NAME } from '../../data/store';

export const OpenAITextToSpeachSettings = () => {
	const providerName = 'openai_text_to_speech';
	const providerSettings = useSelect(
		( select ) =>
			select( STORE_NAME ).getFeatureSettings( providerName ) || {}
	);
	const { setProviderSettings } = useDispatch( STORE_NAME );
	const onChange = ( data ) => setProviderSettings( providerName, data );

	const Description = () => (
		<>
			{ __( "Don't have an OpenAI account yet? ", 'classifai' ) }
			<a
				title={ __( 'Sign up for an OpenAI account', 'classifai' ) }
				href="https://platform.openai.com/signup"
			>
				{ __( 'Sign up for one', 'classifai' ) }
			</a>{ ' ' }
			{ __( 'in order to get your API key.', 'classifai' ) }
		</>
	);

	return (
		<>
			<SettingsRow
				label={ __( 'API Key', 'classifai' ) }
				description={ <Description /> }
			>
				<InputControl
					type="password"
					value={ providerSettings.api_key || '' }
					onChange={ ( value ) => onChange( { api_key: value } ) }
				/>
			</SettingsRow>
			<SettingsRow
				label={ __( 'TTS model', 'classifai' ) }
				description={
					<>
						{ __( 'Select a ', 'classifai' ) }
						<a
							href="https://platform.openai.com/docs/models/tts"
							title={ __(
								'OpenAI Text to Speech models',
								'classifai'
							) }
							target="_blank"
							rel="noreferrer"
						>
							{ __( 'model', 'classifai' ) }
						</a>
						{ __( ' depending on your requirement.', 'classifai' ) }
					</>
				}
			>
				<SelectControl
					onChange={ ( value ) => onChange( { tts_model: value } ) }
					value={ providerSettings.tts_model || 'tts-1' }
					options={ [
						{
							label: __(
								'Text-to-speech 1 (Optimized for speed)',
								'classifai'
							),
							value: 'tts-1',
						},
						{
							label: __(
								'Text-to-speech 1 HD (Optimized for quality)',
								'classifai'
							),
							value: 'tts-1-hd',
						},
					] }
				/>
			</SettingsRow>
			<SettingsRow
				label={ __( 'Voice', 'classifai' ) }
				description={
					<>
						{ __( 'Select the speech ', 'classifai' ) }
						<a
							href="https://platform.openai.com/docs/models/tts"
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
					onChange={ ( value ) => onChange( { voice: value } ) }
					value={ providerSettings.voice || 'alloy' }
					options={ [
						{
							label: __( 'Alloy (male)', 'classifai' ),
							value: 'alloy',
						},
						{
							label: __( 'Echo (male)', 'classifai' ),
							value: 'echo',
						},
						{
							label: __( 'Fable (male)', 'classifai' ),
							value: 'fable',
						},
						{
							label: __( 'Onyx (male)', 'classifai' ),
							value: 'onyx',
						},
						{
							label: __( 'Nova (female)', 'classifai' ),
							value: 'nova',
						},
						{
							label: __( 'Shimmer (female)', 'classifai' ),
							value: 'shimmer',
						},
					] }
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
					onChange={ ( value ) => onChange( { speed: value } ) }
					value={ providerSettings.speed || 1 }
					type="number"
					step="0.25"
					min="0.25"
					max="4"
				/>
			</SettingsRow>
		</>
	);
};
