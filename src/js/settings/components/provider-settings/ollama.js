/**
 * WordPress dependencies
 */
import { useSelect, useDispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_NAME } from '../../data/store';
import { OllamaBaseSettings } from './ollama-base';
import { SetupInstruction } from './setup-instruction';

/**
 * Component for Ollama Provider settings.
 *
 * This component is used within the ProviderSettings component to
 * allow users to configure the Ollama Provider settings.
 *
 * @param {Object}  props              Component props.
 * @param {boolean} props.isConfigured Whether the provider is configured.
 *
 * @return {React.ReactElement} OllamaSettings component.
 */
export const OllamaSettings = ( { isConfigured = false } ) => {
	const providerName = 'ollama';
	const providerSettings = useSelect(
		( select ) =>
			select( STORE_NAME ).getFeatureSettings( providerName ) || {}
	);
	const { setProviderSettings } = useDispatch( STORE_NAME );
	const onChange = ( data ) => setProviderSettings( providerName, data );

	if ( isConfigured ) {
		return null;
	}

	return (
		<>
			<SetupInstruction provider={ providerName } />
			<OllamaBaseSettings
				providerSettings={ providerSettings }
				providerName
				onChange={ onChange }
			/>
		</>
	);
};
