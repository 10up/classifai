/**
 * WordPress dependencies
 */
// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
import { __experimentalInputControl as InputControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { SettingsRow } from '../../settings-row';

/**
 * Component for ElevenLabs Provider settings.
 *
 * This is the base component for ElevenLabs settings.
 *
 * @param {Object}   props                  Component props.
 * @param {Object}   props.providerSettings The provider settings.
 * @param {Function} props.onChange         Function to call when the provider settings change.
 *
 * @return {React.ReactElement} ElevenLabsSettings component.
 */
export const ElevenLabsSettings = ( { providerSettings, onChange } ) => {
	const Description = () => (
		<>
			{ __( "Don't have an ElevenLabs account yet?", 'classifai' ) }{ ' ' }
			<a
				title={ __( 'Sign up for an ElevenLabs account', 'classifai' ) }
				href="https://elevenlabs.io/app/sign-up"
				target="_blank"
				rel="noopener noreferrer"
			>
				{ __( 'Sign up for one', 'classifai' ) }
			</a>{ ' ' }
			{ __( 'in order to get your API key.', 'classifai' ) }
		</>
	);

	return (
		<>
			<SettingsRow
				label={ __( 'API Key', 'classifai' ) }
				description={ <Description /> }
			>
				<InputControl
					// eslint-disable-next-line no-restricted-syntax
					id="elevenlabs_api_key"
					type="password"
					value={ providerSettings.api_key || '' }
					onChange={ ( value ) => onChange( { api_key: value } ) }
					__next40pxDefaultSize
				/>
			</SettingsRow>
		</>
	);
};
