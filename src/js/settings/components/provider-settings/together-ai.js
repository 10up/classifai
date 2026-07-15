/**
 * WordPress dependencies
 */
// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
import { __experimentalInputControl as InputControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { SettingsRow } from '../settings-row';

/**
 * Component for Together AI Provider settings.
 *
 * This is the base component for Together AI settings.
 *
 * @param {Object}   props                  Component props.
 * @param {Object}   props.providerSettings The provider settings.
 * @param {Function} props.onChange         Function to call when the provider settings change.
 *
 * @return {React.ReactElement} TogetherAISettings component.
 */
export const TogetherAISettings = ( { providerSettings, onChange } ) => {
	const Description = () => (
		<>
			{ __( "Don't have a Together AI account yet?", 'classifai' ) }{ ' ' }
			<a
				title={ __( 'Sign up for a Together AI account', 'classifai' ) }
				href="https://api.together.ai/"
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
					id="togetherai_api_key"
					type="password"
					value={ providerSettings.api_key || '' }
					onChange={ ( value ) => onChange( { api_key: value } ) }
					__next40pxDefaultSize
				/>
			</SettingsRow>
		</>
	);
};
