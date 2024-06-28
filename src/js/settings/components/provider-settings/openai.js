// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
import { __experimentalInputControl as InputControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { SettingsRow } from '../settings-row';

export const OpenAISettings = ( { providerSettings, onChange } ) => {
	const Description = () => (
		<>
			{ __( "Don't have an OpenAI account yet? ", 'classifai' ) }
			<a
				title={ __( 'Sign up for an OpenAI account', 'classifai' ) }
				href="https://platform.openai.com/signup"
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
					type="password"
					value={ providerSettings.api_key || '' }
					onChange={ ( value ) => onChange( { api_key: value } ) }
				/>
			</SettingsRow>
		</>
	);
};
