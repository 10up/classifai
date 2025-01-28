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
import { useFeatureContext } from '../feature-settings/context';
import { PromptRepeater } from '../feature-additional-settings/prompt-repeater';

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

	const Description = () => (
		<>
			{ __(
				'URL of the locally hosted Ollama instance. Defaults to http://localhost:11434/. ',
				'classifai'
			) }
			{ __( "Don't have Ollama installed yet? ", 'classifai' ) }
			<a
				title={ __( 'Install Ollama', 'classifai' ) }
				href="https://ollama.com/"
				target="_blank"
				rel="noopener noreferrer"
			>
				{ __( 'Download Ollama', 'classifai' ) }
			</a>
		</>
	);
	const promptExamples = (
		<>
			{ __( 'Add a custom prompt, if desired. ', 'classifai' ) }
			{ __( 'See our ', 'classifai' ) }
			<a
				href="https://10up.github.io/classifai/tutorial-prompt-examples.html"
				target="_blank"
				rel="noopener noreferrer"
			>
				{ __( 'documentation', 'classifai' ) }
			</a>
			{ __(
				' for some example prompts you can try that have been tested for specific use cases.',
				'classifai'
			) }
		</>
	);

	const models = [
		{ label: __( '-- Choose Model --', 'classifai' ), value: '' },
	];

	// Convert providerSettings.models to an array from an object.
	if (
		providerSettings?.models &&
		! Array.isArray( providerSettings.models )
	) {
		for ( const [ key, value ] of Object.entries(
			providerSettings.models
		) ) {
			models.push( { label: value, value: key } );
		}
	}

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
							value={ providerSettings?.endpoint_url || '' }
							onChange={ ( value ) =>
								onChange( { endpoint_url: value } )
							}
						/>
					</SettingsRow>
					<SettingsRow
						label={ __( 'Model', 'classifai' ) }
						description={ __(
							'Choose the model you want to use for requests. If no models are shown or you want to use a different model, please ensure this is installed in Ollama first.',
							'classifai'
						) }
					>
						<SelectControl
							id={ `${ providerName }_model` }
							onChange={ ( value ) =>
								onChange( { model: value } )
							}
							value={ providerSettings?.model || '' }
							options={ models }
							__nextHasNoMarginBottom
						/>
					</SettingsRow>
				</>
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
