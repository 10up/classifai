/**
 * WordPress dependencies
 */
import { useSelect, useDispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_NAME } from '../../data/store';
import { GoogleAISettings } from './googleai';

/**
 * Component for Google AI (Gemini) Provider settings.
 *
 * @param {Object}  props              Component props.
 * @param {boolean} props.isConfigured Whether the provider is configured.
 *
 * @return {React.ReactElement} GoogleAIGeminiSettings component.
 */
export const GoogleAIGeminiSettings = ( { isConfigured = false } ) => {
	const providerName = 'googleai_gemini_api';
	const providerSettings = useSelect(
		( select ) =>
			select( STORE_NAME ).getFeatureSettings( providerName ) || {}
	);
	const { setProviderSettings } = useDispatch( STORE_NAME );
	const onChange = ( data ) => setProviderSettings( providerName, data );

	return (
		<>
			{ ! isConfigured && (
				<GoogleAISettings
					providerSettings={ providerSettings }
					onChange={ onChange }
				/>
			) }
		</>
	);
};
