/**
 * WordPress dependencies
 */
import { useSelect, useDispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_NAME } from '../../../data/store';
import { OllamaBaseSettings } from './base';
import { ModelsSelector } from './models';

/**
 * React Component for Ollama Embeddings settings.
 *
 * This component is used within the ProviderSettings component to
 * allow users to configure the Ollama Embeddings settings.
 *
 * @param {Object}  props              Component props.
 * @param {boolean} props.isConfigured Whether the provider is configured.
 *
 * @return {React.ReactElement} OllamaEmbeddingsSettings component.
 */
export const OllamaEmbeddingsSettings = ( { isConfigured = false } ) => {
	const providerName = 'ollama_embeddings';
	const providerSettings = useSelect(
		( select ) =>
			select( STORE_NAME ).getFeatureSettings( providerName ) || {}
	);
	const { setProviderSettings } = useDispatch( STORE_NAME );
	const onChange = ( data ) => setProviderSettings( providerName, data );

	return (
		<>
			{ ! isConfigured && (
				<OllamaBaseSettings
					providerSettings={ providerSettings }
					providerName={ providerName }
					onChange={ onChange }
				/>
			) }
			<ModelsSelector
				providerSettings={ providerSettings }
				providerName={ providerName }
				onChange={ onChange }
			/>
		</>
	);
};
