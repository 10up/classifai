/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { SelectControl, Slot, Icon, Tooltip } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { useState } from '@wordpress/element';
import { PluginArea } from '@wordpress/plugins';

/**
 * Internal dependencies
 */
import { getFeature, getScope, isProviderConfigured } from '../../utils/utils';
import { SettingsRow } from '../settings-row';
import { STORE_NAME } from '../../data/store';
import { OpenAIChatGPTSettings } from './openai-chatgpt';
import { GoogleAIGeminiAPISettings } from './google-gemini-api';
import { AzureOpenAISettings } from './azure-openai';
import { useFeatureContext } from '../feature-settings/context';
import { IBMWatsonNLUSettings } from './ibm-watson-nlu';
import { OpenAIModerationSettings } from './openai-moderation';
import { OpenAIEmbeddingsSettings } from './openai-embeddings';
import { OpenAIWhisperSettings } from './openai-whisper';
import { AzureAIVisionSettings } from './azure-ai-vision';
import { AzurePersonalizerSettings } from './azure-personlizer';
import { OpenAIDallESettings } from './openai-dalle';
import { AmazonPollySettings } from './amazon-polly';
import { AzureTextToSpeechSettings } from './azure-text-to-speech';
import { OpenAITextToSpeachSettings } from './openai-text-to-speech';
import { ChromeAISettings } from './chrome-ai';

/**
 * Component for rendering provider setting fields based on the selected provider.
 *
 * @param {Object}  props              Component props.
 * @param {string}  props.provider     The selected provider.
 * @param {boolean} props.isConfigured Whether the provider is configured.
 *
 * @return {React.ReactElement} ProviderFields component.
 */
const ProviderFields = ( { provider, isConfigured } ) => {
	switch ( provider ) {
		case 'openai_chatgpt':
			return <OpenAIChatGPTSettings isConfigured={ isConfigured } />;

		case 'googleai_gemini_api':
			return <GoogleAIGeminiAPISettings isConfigured={ isConfigured } />;

		case 'azure_openai':
		case 'azure_openai_embeddings':
			return (
				<AzureOpenAISettings
					providerName={ provider }
					isConfigured={ isConfigured }
				/>
			);

		case 'ibm_watson_nlu':
			return <IBMWatsonNLUSettings isConfigured={ isConfigured } />;

		case 'openai_embeddings':
			return <OpenAIEmbeddingsSettings isConfigured={ isConfigured } />;

		case 'openai_whisper':
			return <OpenAIWhisperSettings isConfigured={ isConfigured } />;

		case 'openai_moderation':
			return <OpenAIModerationSettings isConfigured={ isConfigured } />;

		case 'openai_dalle':
			return <OpenAIDallESettings isConfigured={ isConfigured } />;

		case 'ms_computer_vision':
			return <AzureAIVisionSettings isConfigured={ isConfigured } />;

		case 'ms_azure_personalizer':
			return <AzurePersonalizerSettings isConfigured={ isConfigured } />;

		case 'aws_polly':
			return <AmazonPollySettings isConfigured={ isConfigured } />;

		case 'ms_azure_text_to_speech':
			return <AzureTextToSpeechSettings isConfigured={ isConfigured } />;

		case 'openai_text_to_speech':
			return <OpenAITextToSpeachSettings isConfigured={ isConfigured } />;

		case 'chrome_ai':
			return <ChromeAISettings />;

		default:
			return null;
	}
};

/**
 * Provider Settings component.
 *
 * This component is used within the FeatureSettings component to allow users to configure the provider settings.
 *
 * @return {React.ReactElement} ProviderSettings component.
 */
export const ProviderSettings = () => {
	const [ editProvider, setEditProvider ] = useState( false );
	const { featureName } = useFeatureContext();
	const { setFeatureSettings } = useDispatch( STORE_NAME );
	const feature = getFeature( featureName );
	const provider = useSelect(
		( select ) =>
			select( STORE_NAME ).getFeatureSettings( 'provider' ) ||
			Object.keys( feature?.providers || {} )[ 0 ]
	);
	const featureSettings = useSelect( ( select ) =>
		select( STORE_NAME ).getFeatureSettings()
	);

	// Remove the Chrome AI Provider from the list of providers if the browser AI is not available.
	if ( feature?.providers?.chrome_ai && ! window.ai ) {
		delete feature.providers.chrome_ai;
	}

	const providerLabel = feature.providers[ provider ] || '';
	const providers = Object.keys( feature?.providers || {} ).map(
		( value ) => {
			return {
				value,
				label: feature.providers[ value ] || '',
			};
		}
	);

	const configured =
		isProviderConfigured( featureSettings ) &&
		featureName !== editProvider &&
		providerLabel;

	return (
		<>
			{ configured && (
				<>
					<SettingsRow label={ __( 'Provider', 'classifai' ) }>
						<>
							<Tooltip text={ __( 'Configured', 'classifai' ) }>
								<>
									<Icon icon="yes-alt" /> { providerLabel }
								</>
							</Tooltip>{ ' ' }
							<Tooltip text={ __( 'Edit', 'classifai' ) }>
								{ /* The fragment is necessary here. `Tooltip` tries to pass `refs` to `Icon` which isn't
								wrapped inside forwardRef(), without which it throws an error. DO NOT REMOVE THE FRAGMENTS. */ }
								<>
									<Icon
										icon="edit"
										className="classifai-settings-edit-provider"
										style={ {
											cursor: 'pointer',
										} }
										onClick={ () =>
											setEditProvider( featureName )
										}
									/>
								</>
							</Tooltip>
						</>
					</SettingsRow>
				</>
			) }
			<>
				{ ! configured && (
					<SettingsRow
						label={ __( 'Select a provider', 'classifai' ) }
					>
						<SelectControl
							className="classifai-provider-select"
							onChange={ ( value ) =>
								setFeatureSettings( { provider: value } )
							}
							value={ provider }
							options={ providers }
							__nextHasNoMarginBottom
						/>
					</SettingsRow>
				) }
				<ProviderFields
					provider={ provider }
					isConfigured={ configured }
				/>
				<Slot name="ClassifAIProviderSettings">
					{ ( fills ) => <> { fills }</> }
				</Slot>
				<PluginArea scope={ getScope( provider ) } />
			</>
		</>
	);
};
