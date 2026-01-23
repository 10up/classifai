/**
 * WordPress dependencies
 */
import { useState } from '@wordpress/element';
import {
	__experimentalInputControl as InputControl, // eslint-disable-line @wordpress/no-unsafe-wp-apis
	Button,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { SettingsRow } from '../../settings-row';

/**
 * Component for IBM Watson NLU Provider settings.
 *
 * This is the base component for IBM Watson NLU settings.
 *
 * @param {Object}   props                  Component props.
 * @param {Object}   props.providerSettings The provider settings.
 * @param {Function} props.onChange         Function to call when the provider settings change.
 *
 * @return {React.ReactElement} IBMWatsonBaseSettings component.
 */
export const IBMWatsonBaseSettings = ( { providerSettings, onChange } ) => {
	const [ useAPIkey, setUseAPIkey ] = useState(
		! providerSettings.username || 'apikey' === providerSettings.username
	);

	const Description = () => (
		<>
			{ __( "Don't have an IBM Cloud account yet?", 'classifai' ) }{ ' ' }
			<a
				title={ __( 'Register for an IBM Cloud account', 'classifai' ) }
				href="https://cloud.ibm.com/registration"
				target="_blank"
				rel="noopener noreferrer"
			>
				{ __( 'Register for one', 'classifai' ) }
			</a>{ ' ' }
			{ __( 'and set up a', 'classifai' ) }{ ' ' }
			<a
				href="https://cloud.ibm.com/catalog/services/natural-language-understanding"
				target="_blank"
				rel="noopener noreferrer"
			>
				{ __( 'Natural Language Understanding', 'classifai' ) }
			</a>{ ' ' }
			{ __( 'Resource to get your API key.', 'classifai' ) }
		</>
	);

	return (
		<>
			<SettingsRow label={ __( 'API URL', 'classifai' ) }>
				<InputControl
					id="ibm_watson_nlu_endpoint_url"
					type="url"
					value={ providerSettings.endpoint_url || '' }
					onChange={ ( value ) =>
						onChange( { endpoint_url: value } )
					}
					__next40pxDefaultSize
				/>
			</SettingsRow>
			{ ! useAPIkey && (
				<SettingsRow label={ __( 'API Username', 'classifai' ) }>
					<InputControl
						id="ibm_watson_nlu_username"
						type="text"
						value={ providerSettings.username || '' }
						onChange={ ( value ) =>
							onChange( { username: value } )
						}
						__next40pxDefaultSize
					/>
				</SettingsRow>
			) }
			<SettingsRow
				label={
					useAPIkey
						? __( 'API Key', 'classifai' )
						: __( 'API Password', 'classifai' )
				}
				description={ <Description /> }
			>
				<InputControl
					id="ibm_watson_nlu_password"
					type="password"
					value={ providerSettings.password || '' }
					onChange={ ( value ) => {
						const data = { password: value };
						if ( useAPIkey ) {
							data.username = 'apikey';
						}
						onChange( data );
					} }
					__next40pxDefaultSize
				/>
			</SettingsRow>
			<SettingsRow>
				<Button
					className="classifai-ibm-watson-toggle-api-key"
					variant="link"
					onClick={ () => {
						setUseAPIkey( ! useAPIkey );
					} }
				>
					{ useAPIkey
						? __( 'Use a username/password instead?', 'classifai' )
						: __( 'Use an API Key instead?', 'classifai' ) }
				</Button>
			</SettingsRow>
		</>
	);
};
