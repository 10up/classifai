/**
 * WordPress dependencies
 */
import { useSelect, useDispatch } from '@wordpress/data';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { SettingsRow } from '../../settings-row';
import { STORE_NAME } from '../../../data/store';
import { OllamaBaseSettings } from './base';
import { useFeatureContext } from '../../feature-settings/context';
import { PromptRepeater } from '../../feature-additional-settings/prompt-repeater';
import { ModelsSelector } from './models';

/**
 * Component for Ollama Multimodal Provider settings.
 *
 * This component is used within the ProviderSettings component
 * to allow users to configure the Ollama Multimodal Provider settings.
 *
 * @param {Object}  props              Component props.
 * @param {boolean} props.isConfigured Whether the provider is configured.
 *
 * @return {React.ReactElement} OpenAIChatGPTSettings component.
 */
export const OllamaMultimodalSettings = ( { isConfigured = false } ) => {
	const { featureName } = useFeatureContext();
	const providerName = 'ollama_multimodal';
	const providerSettings = useSelect(
		( select ) =>
			select( STORE_NAME ).getFeatureSettings( providerName ) || {}
	);
	const { setProviderSettings } = useDispatch( STORE_NAME );
	const onChange = ( data ) => setProviderSettings( providerName, data );
	const setPrompts = ( prompts ) => {
		setProviderSettings( providerName, {
			prompt: prompts,
		} );
	};

	const promptExamples = (
		<>
			{ __( 'Add a custom prompt, if desired. See our', 'classifai' ) }
			<a
				href="https://10up.github.io/classifai/advanced-docs/prompt-examples"
				target="_blank"
				rel="noopener noreferrer"
			>
				{ ' ' }
				{ __( 'documentation', 'classifai' ) }
			</a>{ ' ' }
			{ __(
				'for some example prompts you can try that have been tested for specific use cases.',
				'classifai'
			) }
		</>
	);

	return (
		<>
			{ ! isConfigured && (
				<OllamaBaseSettings
					providerSettings={ providerSettings }
					providerName={ providerName }
					onChange={ onChange }
				/>
			) }

			<ModelsSelector
				providerSettings={ providerSettings }
				providerName={ providerName }
				onChange={ onChange }
			/>

			{ [
				'feature_descriptive_text_generator',
				'feature_image_to_text_generator',
				'feature_image_tags_generator',
			].includes( featureName ) && (
				<SettingsRow
					label={ __( 'Prompt', 'classifai' ) }
					description={ promptExamples }
				>
					<PromptRepeater
						prompts={ providerSettings.prompt }
						setPrompts={ setPrompts }
					/>
				</SettingsRow>
			) }
		</>
	);
};
