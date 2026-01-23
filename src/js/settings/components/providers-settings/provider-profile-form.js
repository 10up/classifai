/**
 * WordPress dependencies
 */
import { useState, useEffect, useMemo } from '@wordpress/element';
import { useDispatch } from '@wordpress/data';
import { Button, Flex, FlexItem } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { store as noticesStore } from '@wordpress/notices';

/**
 * Internal dependencies
 */
import { STORE_NAME } from '../../data/store';
import { OpenAIBaseSettings } from '../provider-settings/openai/base';
import { GoogleAIBaseSettings } from '../provider-settings/google/base';
import { ElevenLabsSettings } from '../provider-settings/elevenlabs/base';
import { TogetherAIBaseSettings } from '../provider-settings/together-ai/base';
import { OllamaBaseSettings } from '../provider-settings/ollama/base';
import { AzureOpenAIBaseSettings } from '../provider-settings/azure/azure-openai/base';
import { AzureAIVisionBaseSettings } from '../provider-settings/azure/azure-ai-vision/base';
import { AzureTextToSpeechBaseSettings } from '../provider-settings/azure/azure-text-to-speech/base';
import { AmazonPollyBaseSettings } from '../provider-settings/amazon-polly/base';
import { IBMWatsonBaseSettings } from '../provider-settings/watson/base';
import { XAIBaseSettings } from '../provider-settings/xai/base';
import { StableDiffusionBaseSettings } from '../provider-settings/stable-diffusion/base';

/**
 * Build form config object from credential_fields (excl. authenticated).
 *
 * @param {string[]} credentialFields Field names.
 * @param {Object}   savedConfig      Saved config (from Redux).
 * @return {Object} Key-value of field to string.
 */
function getInitialFormValues( credentialFields, savedConfig ) {
	const inputFields = credentialFields.filter(
		( f ) => f !== 'authenticated'
	);
	const out = {};
	for ( const k of inputFields ) {
		const v = savedConfig?.[ k ];
		out[ k ] =
			v !== null && v !== undefined && typeof v === 'string' ? v : '';
	}
	return out;
}

/**
 * Render provider-specific settings component.
 *
 * @param {string}   profileId        Profile ID.
 * @param {Object}   providerSettings Provider settings values.
 * @param {Function} onChange         Change handler.
 * @return {React.ReactElement|null} Provider settings component or null.
 */
function renderProviderSettings( profileId, providerSettings, onChange ) {
	switch ( profileId ) {
		case 'openai':
			return (
				<OpenAIBaseSettings
					providerSettings={ providerSettings }
					onChange={ onChange }
				/>
			);

		case 'googleai':
			return (
				<GoogleAIBaseSettings
					providerSettings={ providerSettings }
					onChange={ onChange }
				/>
			);

		case 'elevenlabs':
			return (
				<ElevenLabsSettings
					providerSettings={ providerSettings }
					onChange={ onChange }
				/>
			);

		case 'togetherai_image':
			return (
				<TogetherAIBaseSettings
					providerSettings={ providerSettings }
					onChange={ onChange }
				/>
			);

		case 'ollama':
			return (
				<OllamaBaseSettings
					providerSettings={ providerSettings }
					onChange={ onChange }
					providerName="ollama"
					showModels={ false }
				/>
			);

		case 'azure_openai':
		case 'azure_openai_embeddings': {
			return (
				<AzureOpenAIBaseSettings
					providerSettings={ providerSettings }
					onChange={ onChange }
				/>
			);
		}

		case 'ms_computer_vision': {
			return (
				<AzureAIVisionBaseSettings
					providerSettings={ providerSettings }
					onChange={ onChange }
				/>
			);
		}

		case 'ms_azure_text_to_speech': {
			return (
				<AzureTextToSpeechBaseSettings
					providerSettings={ providerSettings }
					onChange={ onChange }
				/>
			);
		}

		case 'aws_polly': {
			return (
				<AmazonPollyBaseSettings
					providerSettings={ providerSettings }
					onChange={ onChange }
				/>
			);
		}

		case 'ibm_watson_nlu': {
			return (
				<IBMWatsonBaseSettings
					providerSettings={ providerSettings }
					onChange={ onChange }
				/>
			);
		}

		case 'xai_grok': {
			return (
				<XAIBaseSettings
					providerSettings={ providerSettings }
					onChange={ onChange }
				/>
			);
		}

		case 'stable_diffusion': {
			return (
				<StableDiffusionBaseSettings
					providerSettings={ providerSettings }
					onChange={ onChange }
				/>
			);
		}

		default:
			return null;
	}
}

/**
 * Form to edit and save global Provider credentials for one profile.
 *
 * @param {Object}   props           Component props.
 * @param {string}   props.profileId Profile ID (e.g. openai, ollama).
 * @param {Object}   props.profile   Profile { label, credential_fields }.
 * @param {Object}   props.config    Current saved config from Redux.
 * @param {Function} props.onSaved   Callback after successful save; receives new configs.
 */
export function ProviderProfileForm( { profileId, profile, config, onSaved } ) {
	const { setProviderConfigs } = useDispatch( STORE_NAME );
	const { createSuccessNotice, createErrorNotice } =
		useDispatch( noticesStore );

	const credFields = useMemo(
		() => profile?.credential_fields ?? [],
		[ profile?.credential_fields ]
	);
	const inputFields = credFields.filter( ( f ) => f !== 'authenticated' );

	const [ formValues, setFormValues ] = useState( () =>
		getInitialFormValues( credFields, config )
	);
	const [ isSaving, setIsSaving ] = useState( false );

	// Sync form from Redux when config changes (e.g. after save or load).
	useEffect( () => {
		setFormValues( getInitialFormValues( credFields, config ) );
	}, [ profileId, config, credFields ] );

	const updateField = ( updates ) => {
		setFormValues( ( prev ) => ( { ...prev, ...updates } ) );
	};

	const handleSave = async () => {
		setIsSaving( true );
		try {
			const res = await apiFetch( {
				path: '/classifai/v1/provider-configs',
				method: 'POST',
				data: {
					profile_id: profileId,
					config: formValues,
				},
			} );

			if ( res.success && res.configs ) {
				setProviderConfigs( res.configs );
				onSaved?.( res.configs );

				if ( res.verification_error ) {
					createSuccessNotice(
						__(
							'Credentials saved but verification failed:',
							'classifai'
						) +
							' ' +
							res.verification_error,
						{ type: 'snackbar' }
					);
				} else if ( res.configs[ profileId ]?.authenticated ) {
					createSuccessNotice(
						__( 'Credentials saved and verified.', 'classifai' ),
						{ type: 'snackbar' }
					);
				} else {
					createSuccessNotice(
						__( 'Credentials saved.', 'classifai' ),
						{ type: 'snackbar' }
					);
				}
			}
		} catch ( err ) {
			createErrorNotice(
				err?.message ||
					__(
						'An error occurred while saving. Please try again.',
						'classifai'
					),
				{ type: 'snackbar' }
			);
		} finally {
			setIsSaving( false );
		}
	};

	if ( inputFields.length === 0 ) {
		return null;
	}

	return (
		<div className="classifai-provider-profile-form">
			{ renderProviderSettings( profileId, formValues, updateField ) }

			<div className="classifai-provider-profile-form__actions">
				<Flex justify="end" expanded={ false }>
					<FlexItem>
						<Button
							variant="primary"
							onClick={ handleSave }
							isBusy={ isSaving }
						>
							{ isSaving
								? __( 'Saving…', 'classifai' )
								: __( 'Save', 'classifai' ) }
						</Button>
					</FlexItem>
				</Flex>
			</div>
		</div>
	);
}
