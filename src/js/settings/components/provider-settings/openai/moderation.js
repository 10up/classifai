/**
 * WordPress dependencies
 */
import { useSelect, useDispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_NAME } from '../../../data/store';
import { OpenAIBaseSettings } from './base';

/**
 * Component for OpenAI Moderation settings.
 *
 * @param {Object}  props              Component props.
 * @param {boolean} props.isConfigured Whether the provider is configured.
 *
 * @return {React.ReactElement} OpenAIModerationSettings component.
 */
export const OpenAIModerationSettings = ( { isConfigured = false } ) => {
	const providerName = 'openai_moderation';
	const providerSettings = useSelect(
		( select ) =>
			select( STORE_NAME ).getFeatureSettings( providerName ) || {}
	);
	const { setProviderSettings } = useDispatch( STORE_NAME );
	const onChange = ( data ) => setProviderSettings( providerName, data );

	return (
		<>
			{ ! isConfigured && (
				<OpenAIBaseSettings
					providerSettings={ providerSettings }
					onChange={ onChange }
				/>
			) }
		</>
	);
};
