/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	SelectControl,
	Slot,
	Icon,
	Tooltip,
	Notice,
	ToggleControl,
} from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { useState, useEffect, useRef, useMemo } from '@wordpress/element';
import { PluginArea } from '@wordpress/plugins';
import { Link } from 'react-router-dom';

/**
 * Internal dependencies
 */
import {
	getFeature,
	getScope,
	getProfileForProvider,
	hasFeatureLevelCredentials,
	isProviderConfigured,
} from '../../utils/utils';
import { SettingsRow } from '../settings-row';
import { STORE_NAME } from '../../data/store';
import { OpenAIChatGPTSettings } from './openai-chatgpt';
import { GoogleAIGeminiSettings } from './googleai-gemini';
import { GoogleAIImagesSettings } from './googleai-images';
import { AzureOpenAISettings } from './azure-openai';
import { useFeatureContext } from '../feature-settings/context';
import { IBMWatsonNLUSettings } from './ibm-watson-nlu';
import { OpenAIModerationSettings } from './openai-moderation';
import { OpenAIEmbeddingsSettings } from './openai-embeddings';
import { OpenAISpeechToTextSettings } from './openai-speech-to-text';
import { AzureAIVisionSettings } from './azure-ai-vision';
import { OpenAIImagesSettings } from './openai-images';
import { StableDiffusionSettings } from './stable-diffusion';
import { AmazonPollySettings } from './amazon-polly';
import { AzureTextToSpeechSettings } from './azure-text-to-speech';
import { OpenAITextToSpeechSettings } from './openai-text-to-speech';
import { ChromeAISettings } from './chrome-ai';
import { XAIGrokSettings } from './xai-grok';
import { OllamaSettings } from './ollama';
import { OllamaMultimodalSettings } from './ollama-multimodal';
import { OllamaEmbeddingsSettings } from './ollama-embeddings';
import { TogetherAIImagesSettings } from './together-ai-images';
import { ElevenLabsSpeechToTextSettings } from './elevenlabs/speech-to-text';
import { ElevenLabsTextToSpeechSettings } from './elevenlabs/text-to-speech';

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
			return <GoogleAIGeminiSettings isConfigured={ isConfigured } />;

		case 'googleai_images':
			return <GoogleAIImagesSettings isConfigured={ isConfigured } />;

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
			return <OpenAISpeechToTextSettings isConfigured={ isConfigured } />;

		case 'elevenlabs_speech_to_text':
			return (
				<ElevenLabsSpeechToTextSettings isConfigured={ isConfigured } />
			);

		case 'elevenlabs_text_to_speech':
			return (
				<ElevenLabsTextToSpeechSettings isConfigured={ isConfigured } />
			);

		case 'openai_moderation':
			return <OpenAIModerationSettings isConfigured={ isConfigured } />;

		case 'openai_dalle':
			return <OpenAIImagesSettings isConfigured={ isConfigured } />;

		case 'stable_diffusion':
			return <StableDiffusionSettings isConfigured={ isConfigured } />;

		case 'togetherai_image':
			return <TogetherAIImagesSettings isConfigured={ isConfigured } />;

		case 'ms_computer_vision':
			return <AzureAIVisionSettings isConfigured={ isConfigured } />;

		case 'aws_polly':
			return <AmazonPollySettings isConfigured={ isConfigured } />;

		case 'ms_azure_text_to_speech':
			return <AzureTextToSpeechSettings isConfigured={ isConfigured } />;

		case 'openai_text_to_speech':
			return <OpenAITextToSpeechSettings isConfigured={ isConfigured } />;

		case 'xai_grok':
			return <XAIGrokSettings isConfigured={ isConfigured } />;

		case 'chrome_ai':
			return <ChromeAISettings />;

		case 'ollama':
			return <OllamaSettings isConfigured={ isConfigured } />;

		case 'ollama_multimodal':
			return <OllamaMultimodalSettings isConfigured={ isConfigured } />;

		case 'ollama_embeddings':
			return <OllamaEmbeddingsSettings isConfigured={ isConfigured } />;

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
	const { setFeatureSettings, setProviderSettings } =
		useDispatch( STORE_NAME );
	const feature = getFeature( featureName );
	const provider = useSelect(
		( select ) =>
			select( STORE_NAME ).getFeatureSettings( 'provider' ) ||
			Object.keys( feature?.providers || {} )[ 0 ]
	);
	const featureSettings = useSelect( ( select ) =>
		select( STORE_NAME ).getFeatureSettings()
	);
	const { providerProfiles, providerConfigs } = useSelect(
		( select ) => ( {
			providerProfiles: select( STORE_NAME ).getProviderProfiles?.(),
			providerConfigs: select( STORE_NAME ).getProviderConfigs?.(),
		} ),
		[]
	);

	const profile = getProfileForProvider( provider, providerProfiles );
	const credentialFields = useMemo(
		() => profile?.credential_fields ?? [],
		[ profile?.credential_fields ]
	);
	const hasCredentialFields = credentialFields.length > 0;
	const globalConfig = profile
		? providerConfigs?.[ profile.profileId ]
		: null;
	const hasGlobal =
		globalConfig &&
		credentialFields.some(
			( f ) =>
				f !== 'authenticated' &&
				globalConfig[ f ] !== null &&
				globalConfig[ f ] !== undefined &&
				String( globalConfig[ f ] ).trim() !== ''
		);
	const hasFeatureCreds = hasFeatureLevelCredentials(
		featureSettings?.[ provider ],
		credentialFields
	);

	const ov = featureSettings?.[ provider ]?.override;
	const isOverridden = ov === true || ( ov !== false && hasFeatureCreds );

	// Seed override for existing users with Feature-level creds so it persists on save.
	useEffect( () => {
		if ( hasFeatureCreds && ov === undefined && provider ) {
			setProviderSettings( provider, { override: true } );
		}
	}, [ hasFeatureCreds, ov, provider, setProviderSettings ] );

	const hasClearedGlobalOnLoadRef = useRef( false );

	// When override is already on (e.g. on load), clear any credential fields that
	// match global so the form is not pre-populated with global data.
	useEffect( () => {
		if (
			! hasClearedGlobalOnLoadRef.current &&
			isOverridden &&
			hasCredentialFields &&
			provider &&
			globalConfig
		) {
			const credFields = credentialFields.filter(
				( f ) => f !== 'authenticated'
			);
			const updates = {};
			for ( const f of credFields ) {
				if (
					featureSettings?.[ provider ]?.[ f ] === globalConfig?.[ f ]
				) {
					updates[ f ] = '';
				}
			}
			if ( Object.keys( updates ).length > 0 ) {
				setProviderSettings( provider, updates );
				hasClearedGlobalOnLoadRef.current = true;
			}
		}
	}, [
		isOverridden,
		hasCredentialFields,
		provider,
		globalConfig,
		credentialFields,
		featureSettings,
		setProviderSettings,
	] );

	// Reset so we re-check when the user switches provider.
	useEffect( () => {
		hasClearedGlobalOnLoadRef.current = false;
	}, [ provider ] );

	// Remove the Chrome AI Provider from the list of providers if the browser AI is not available.
	if ( feature?.providers?.chrome_ai && ! window.LanguageModel ) {
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
		isProviderConfigured(
			featureSettings,
			providerProfiles,
			providerConfigs
		) &&
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
							{ isOverridden && hasFeatureCreds && (
								<Notice
									status="info"
									isDismissible={ false }
									className="classifai-provider-notice"
								>
									{ __(
										'Using Feature-level credentials. Configure this Provider in the',
										'classifai'
									) }{ ' ' }
									<Link to="/providers">
										{ __( 'Providers tab', 'classifai' ) }
									</Link>{ ' ' }
									{ __(
										'and turn off the Override Provider credentials setting to use the global configuration.',
										'classifai'
									) }
								</Notice>
							) }
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
						{ ! isOverridden && ! hasGlobal && (
							<Notice
								status="info"
								isDismissible={ false }
								className="classifai-provider-notice"
							>
								{ __(
									'Configure this Provider in the',
									'classifai'
								) }{ ' ' }
								<Link to="/providers">
									{ __( 'Providers tab', 'classifai' ) }
								</Link>{ ' ' }
								{ __(
									'to use it across Features.',
									'classifai'
								) }
							</Notice>
						) }
					</SettingsRow>
				) }
				{ hasCredentialFields ? (
					<>
						<SettingsRow
							label={ __(
								'Override Provider credentials',
								'classifai'
							) }
						>
							<ToggleControl
								checked={ isOverridden }
								onChange={ ( value ) =>
									setProviderSettings( provider, {
										override: !! value,
									} )
								}
							/>
						</SettingsRow>
						{ isOverridden && (
							<ProviderFields
								provider={ provider }
								isConfigured={ configured }
							/>
						) }
					</>
				) : (
					<ProviderFields
						provider={ provider }
						isConfigured={ configured }
					/>
				) }
				<Slot name="ClassifAIProviderSettings">
					{ ( fills ) => <> { fills }</> }
				</Slot>
				<PluginArea scope={ getScope( provider ) } />
			</>
		</>
	);
};
