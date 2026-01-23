/**
 * WordPress dependencies
 */
import { useSelect, useDispatch } from '@wordpress/data';
// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
import { __experimentalInputControl as InputControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { SettingsRow } from '../../settings-row';
import { STORE_NAME } from '../../../data/store';
import { useFeatureContext } from '../../feature-settings/context';
import { PromptRepeater } from '../../feature-additional-settings/prompt-repeater';
import { OpenAIBaseSettings } from './base';

/**
 * Component for OpenAI ChatGPT Provider settings.
 *
 * @param {Object}  props              Component props.
 * @param {boolean} props.isConfigured Whether the provider is configured.
 *
 * @return {React.ReactElement} OpenAIChatGPTSettings component.
 */
export const OpenAIChatGPTSettings = ( { isConfigured = false } ) => {
	const { featureName } = useFeatureContext();
	const providerName = 'openai_chatgpt';
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
			{ __( 'Add a custom prompt, if desired. See our', 'classifai' ) }{ ' ' }
			<a
				href="https://10up.github.io/classifai/advanced-docs/prompt-examples"
				target="_blank"
				rel="noopener noreferrer"
			>
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
				<OpenAIBaseSettings
					providerSettings={ providerSettings }
					onChange={ onChange }
				/>
			) }

			{ [
				'feature_content_resizing',
				'feature_title_generation',
			].includes( featureName ) && (
				<SettingsRow
					label={ __( 'Number of suggestions', 'classifai' ) }
					description={ __(
						'Number of suggestions that will be generated in one request.',
						'classifai'
					) }
				>
					<InputControl
						id={ `${ providerName }_number_of_suggestions` }
						type="number"
						min={ 1 }
						max={ 10 }
						value={ providerSettings.number_of_suggestions || 1 }
						onChange={ ( value ) =>
							onChange( { number_of_suggestions: value } )
						}
						__next40pxDefaultSize
					/>
				</SettingsRow>
			) }
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
