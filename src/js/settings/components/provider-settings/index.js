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

const ProviderFields = ( { provider } ) => {
	switch ( provider ) {
		case 'openai_chatgpt':
			return <OpenAIChatGPTSettings />;

		case 'googleai_gemini_api':
			return <GoogleAIGeminiAPISettings />;

		case 'azure_openai':
		case 'azure_openai_embeddings':
			return <AzureOpenAISettings providerName={ provider } />;

		case 'ibm_watson_nlu':
			return <IBMWatsonNLUSettings />;

		case 'openai_embeddings':
			return <OpenAIEmbeddingsSettings />;

		case 'openai_whisper':
			return <OpenAIWhisperSettings />;

		case 'openai_moderation':
			return <OpenAIModerationSettings />;

		case 'openai_dalle':
			return <OpenAIDallESettings />;

		case 'ms_computer_vision':
			return <AzureAIVisionSettings />;

		case 'ms_azure_personalizer':
			return <AzurePersonalizerSettings />;

		case 'aws_polly':
			return <AmazonPollySettings />;

		case 'ms_azure_text_to_speech':
			return <AzureTextToSpeechSettings />;

		default:
			return null;
	}
};

/**
 * Feature Settings component.
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
		! editProvider &&
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
								<Icon
									icon="edit"
									className="classifai-settings-edit-provider"
									style={ {
										cursor: 'pointer',
									} }
									onClick={ () => setEditProvider( true ) }
								/>
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
							onChange={ ( value ) =>
								setFeatureSettings( { provider: value } )
							}
							value={ provider }
							options={ providers }
						/>
					</SettingsRow>
				) }
				<ProviderFields provider={ provider } />
				<Slot name="ClassifAIProviderSettings">
					{ ( fills ) => <> { fills }</> }
				</Slot>
				<PluginArea scope={ getScope( provider ) } />
			</>
		</>
	);
};
