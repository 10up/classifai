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

const ProviderFields = ( { provider, featureName } ) => {
	switch ( provider ) {
		case 'openai_chatgpt':
			return <OpenAIChatGPTSettings featureName={ featureName } />;

		case 'googleai_gemini_api':
			return <GoogleAIGeminiAPISettings featureName={ featureName } />;

		case 'azure_openai':
			return <AzureOpenAISettings featureName={ featureName } />;

		default:
			return null;
	}
};

/**
 * Feature Settings component.
 *
 * @param {Object} props             Component props.
 * @param {string} props.featureName Feature name.
 */
export const ProviderSettings = ( { featureName } ) => {
	const { setFeatureSettings } = useDispatch( STORE_NAME );
	const provider = useSelect(
		( select ) =>
			select( STORE_NAME ).getFeatureSettings( 'provider' ) ||
			Object.keys( feature?.providers || {} )[ 0 ]
	);

	const feature = getFeature( featureName );
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
			<ProviderFields provider={ provider } featureName={ featureName } />
			<Slot name="ClassifAIProviderSettings">
				{ ( fills ) => <> { fills }</> }
			</Slot>
			<PluginArea scope={ getScope( provider ) } />
		</>
	);
};
