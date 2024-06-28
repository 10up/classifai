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

const ProviderFields = ( { provider } ) => {
	switch ( provider ) {
		case 'openai_chatgpt':
			return <OpenAIChatGPTSettings />;

		case 'googleai_gemini_api':
			return <GoogleAIGeminiAPISettings />;

		case 'azure_openai':
			return <AzureOpenAISettings />;

		case 'ibm_watson_nlu':
			return <IBMWatsonNLUSettings />;

		case 'openai_embeddings':
			return <OpenAIEmbeddingsSettings />;

		case 'openai_whisper':
			return <OpenAIWhisperSettings />;

		case 'openai_moderation':
			return <OpenAIModerationSettings />;

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

	const configured = isProviderConfigured( featureSettings );
	const providerLabel = feature.providers[ provider ] || '';

	const providers = Object.keys( feature?.providers || {} ).map(
		( value ) => {
			return {
				value,
				label: feature.providers[ value ] || '',
			};
		}
	);

	return (
		<>
			{ configured && ! editProvider && (
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
			{ ( ! configured || editProvider ) && (
				<>
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
					<ProviderFields provider={ provider } />
					<Slot name="ClassifAIProviderSettings">
						{ ( fills ) => <> { fills }</> }
					</Slot>
					<PluginArea scope={ getScope( provider ) } />
				</>
			) }
		</>
	);
};
