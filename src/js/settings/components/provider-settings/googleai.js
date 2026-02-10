// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
import { __experimentalInputControl as InputControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { SettingsRow } from '../settings-row';

/**
 * Component for Google AI Provider settings.
 *
 * This is the base component for Google AI settings.
 *
 * @param {Object}   props                  Component props.
 * @param {Object}   props.providerSettings The provider settings.
 * @param {Function} props.onChange         Function to call when the provider settings change.
 *
 * @return {React.ReactElement} GoogleAISettings component.
 */
export const GoogleAISettings = ( { providerSettings, onChange } ) => {
	const Description = () => (
		<>
			{ __( "Don't have an Google AI (Gemini API) key?", 'classifai' ) }{ ' ' }
			<a
				title={ __( 'Get an API key', 'classifai' ) }
				href="https://makersuite.google.com/app/apikey"
				target="_blank"
				rel="noopener noreferrer"
			>
				{ __( 'Get an API key', 'classifai' ) }
			</a>{ ' ' }
			{ __( 'now.', 'classifai' ) }
		</>
	);

	return (
		<>
			<SettingsRow
				label={ __( 'API key', 'classifai' ) }
				description={ <Description /> }
			>
				<InputControl
					id="googleai_gemini_api_key"
					type="password"
					value={ providerSettings.api_key || '' }
					onChange={ ( value ) => onChange( { api_key: value } ) }
					__next40pxDefaultSize
				/>
			</SettingsRow>
		</>
	);
};
