import { useSelect, useDispatch } from '@wordpress/data';
import { STORE_NAME } from '../../data/store';
import { OpenAISettings } from './openai';

export const OpenAIModerationSettings = ( { isConfigured = false } ) => {
	const providerName = 'openai_moderation';
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
		<OpenAISettings
			providerSettings={ providerSettings }
			onChange={ onChange }
		/>
	);
};
