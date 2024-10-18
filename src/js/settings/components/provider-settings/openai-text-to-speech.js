/**
 * WordPress dependencies
 */
import {
	__experimentalInputControl as InputControl, // eslint-disable-line @wordpress/no-unsafe-wp-apis
	SelectControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { SettingsRow } from '../settings-row';
import { useSelect, useDispatch } from '@wordpress/data';
import { STORE_NAME } from '../../data/store';

/**
 * Component for OpenAI Text to Speech Provider settings.
 *
 * This component is used within the ProviderSettings component to allow users to configure the OpenAI Text to Speech Provider settings.
 *
 * @param {Object}  props              Component props.
 * @param {boolean} props.isConfigured Whether the provider is configured.
 *
 * @return {React.ReactElement} OpenAITextToSpeachSettings component.
 */
export const OpenAITextToSpeachSettings = ( { isConfigured = false } ) => {
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
			{ ! isConfigured && (
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
			) }
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
					id={ `${ providerName }_tts_model` }
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
					id={ `${ providerName }_voice` }
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
				/>
			</SettingsRow>
		</>
	);
};
