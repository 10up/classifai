/**
 * WordPress dependencies
 */
import { useSelect, useDispatch } from '@wordpress/data';
import { SelectControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { SettingsRow } from '../../settings-row';
import { STORE_NAME } from '../../../data/store';
import { OllamaBaseSettings } from './base';
import { useFeatureContext } from '../../feature-settings/context';
import { PromptRepeater } from '../../feature-additional-settings/prompt-repeater';

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

	// Auto-select the first model if no model is selected and models are available.
	useEffect( () => {
		if (
			! providerSettings?.model &&
			models.length > 1 &&
			models[ 1 ]?.value
		) {
			onChange( { model: models[ 1 ].value } );
		}
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [ providerSettings?.models, providerSettings?.model ] );

	// Check if we have actual models (not just the placeholder).
	const hasModels = models.length > 1;

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
			<SettingsRow
				label={ __( 'Model', 'classifai' ) }
				description={ __(
					'Choose the model you want to use for requests. If no models are shown or you want to use a different model, please ensure this is installed in Ollama first.',
					'classifai'
				) }
			>
				<SelectControl
					id={ `${ providerName }_model` }
					onChange={ ( value ) => onChange( { model: value } ) }
					value={ providerSettings?.model || '' }
					options={ models }
					disabled={ ! hasModels }
					__nextHasNoMarginBottom
				/>
			</SettingsRow>
			{ ! isConfigured && (
				<OllamaBaseSettings
					providerSettings={ providerSettings }
					providerName={ providerName }
					onChange={ onChange }
				/>
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
