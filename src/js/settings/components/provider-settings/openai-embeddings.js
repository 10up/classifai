/**
 * WordPress dependencies
 */
import {
	__experimentalInputControl as InputControl, // eslint-disable-line @wordpress/no-unsafe-wp-apis
} from '@wordpress/components';
import { useSelect, useDispatch } from '@wordpress/data';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { SettingsRow } from '../settings-row';
import { STORE_NAME } from '../../data/store';
import { OpenAISettings } from './openai';
import { useFeatureContext } from '../feature-settings/context';

/**
 * React Component for OpenAI Embeddings settings.
 *
 * This component is used within the ProviderSettings component to allow users to configure the OpenAI Embeddings settings.
 *
 * @param {Object}  props              Component props.
 * @param {boolean} props.isConfigured Whether the provider is configured.
 *
 * @return {React.ReactElement} OpenAIEmbeddingsSettings component.
 */
export const OpenAIEmbeddingsSettings = ( { isConfigured = false } ) => {
	const { featureName } = useFeatureContext();
	const providerName = 'openai_embeddings';
	const providerSettings = useSelect(
		( select ) =>
			select( STORE_NAME ).getFeatureSettings( providerName ) || {}
	);
	const { setProviderSettings } = useDispatch( STORE_NAME );
	const onChange = ( data ) => setProviderSettings( providerName, data );

	return (
		<>
			{ ! isConfigured && (
				<OpenAISettings
					providerSettings={ providerSettings }
					onChange={ onChange }
				/>
			) }
			{ [ 'feature_recommended_content' ].includes( featureName ) && (
				<SettingsRow label={ __( 'Threshold %', 'classifai' ) }>
					<InputControl
						// eslint-disable-next-line no-restricted-syntax
						id="embedding-threshold"
						type="number"
						style={ { width: '10%' } }
						value={ providerSettings.embedding_threshold }
						onChange={ ( value ) =>
							onChange( { embedding_threshold: value } )
						}
						min="1"
						max="100"
						step="0.01"
						__next40pxDefaultSize
					/>
				</SettingsRow>
			) }
		</>
	);
};
