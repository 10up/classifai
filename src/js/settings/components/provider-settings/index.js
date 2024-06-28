/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { SelectControl, Slot } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { PluginArea } from '@wordpress/plugins';

/**
 * Internal dependencies
 */
import { getFeature, getScope } from '../../utils/utils';
import { SettingsRow } from '../settings-row';
import { STORE_NAME } from '../../data/store';
import { OpenAIChatGPTSettings } from './openai-chatgpt';
import { GoogleAIGeminiAPISettings } from './google-gemini-api';
import { AzureOpenAISettings } from './azure-openai';
import { useFeatureContext } from '../feature-settings/context';

const ProviderFields = ( { provider } ) => {
	switch ( provider ) {
		case 'openai_chatgpt':
			return <OpenAIChatGPTSettings />;

		case 'googleai_gemini_api':
			return <GoogleAIGeminiAPISettings />;

		case 'azure_openai':
			return <AzureOpenAISettings />;

		default:
			return null;
	}
};

/**
 * Feature Settings component.
 */
export const ProviderSettings = () => {
	const { featureName } = useFeatureContext();
	const { setFeatureSettings } = useDispatch( STORE_NAME );
	const feature = getFeature( featureName );
	const provider = useSelect(
		( select ) =>
			select( STORE_NAME ).getFeatureSettings( 'provider' ) ||
			Object.keys( feature?.providers || {} )[ 0 ]
	);

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
			<SettingsRow label={ __( 'Select a provider', 'classifai' ) }>
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
	);
};
