/**
 * WordPress dependencies
 */
import { useSelect, useDispatch } from '@wordpress/data';
// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
import { __experimentalInputControl as InputControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { SettingsRow } from '../settings-row';
import { STORE_NAME } from '../../data/store';

/**
 * Component for Google AI (Gemini API) Provider settings.
 *
 * This component is used within the ProviderSettings component to allow users to configure the Google AI (Gemini API) Provider settings.
 *
 * @param {Object}  props              Component props.
 * @param {boolean} props.isConfigured Whether the provider is configured.
 *
 * @return {React.ReactElement} GoogleAIGeminiAPISettings component.
 */
export const GoogleAIGeminiAPISettings = ( { isConfigured = false } ) => {
	const providerName = 'googleai_gemini_api';
	const providerSettings = useSelect(
		( select ) =>
			select( STORE_NAME ).getFeatureSettings( providerName ) || {}
	);
	const { setProviderSettings } = useDispatch( STORE_NAME );
	const onChange = ( data ) => setProviderSettings( providerName, data );

	if ( isConfigured ) {
		return null;
	}

	const Description = () => (
		<>
			{ __( "Don't have an Google AI (Gemini API) key?", 'classifai' ) }{ ' ' }
			<a
				title={ __( 'Get an API key', 'classifai' ) }
				href="https://makersuite.google.com/app/apikey"
			>
				{ __( 'Get an API key', 'classifai' ) }
			</a>{ ' ' }
			{ __( 'now.', 'classifai' ) }
		</>
	);

	return (
		<>
			<SettingsRow
				label={ __( 'API Key', 'classifai' ) }
				description={ <Description /> }
			>
				<InputControl
					id={ `${ providerName }_api_key` }
					type="password"
					value={ providerSettings.api_key || '' }
					onChange={ ( value ) => onChange( { api_key: value } ) }
				/>
			</SettingsRow>
		</>
	);
};
