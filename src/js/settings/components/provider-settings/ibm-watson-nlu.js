/**
 * WordPress dependencies
 */
import { useSelect, useDispatch } from '@wordpress/data';
import { useState } from '@wordpress/element';
import {
	__experimentalInputControl as InputControl, // eslint-disable-line @wordpress/no-unsafe-wp-apis
	Button,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { SettingsRow } from '../settings-row';
import { STORE_NAME } from '../../data/store';

/**
 * Component for IBM Watson NLU Provider settings.
 *
 * This component is used within the ProviderSettings component to allow users to configure the IBM Watson NLU Provider settings.
 *
 * @param {Object}  props              Component props.
 * @param {boolean} props.isConfigured Whether the provider is configured.
 *
 * @return {React.ReactElement} IBMWatsonNLUSettings component.
 */
export const IBMWatsonNLUSettings = ( { isConfigured = false } ) => {
	const providerName = 'ibm_watson_nlu';
	const providerSettings = useSelect(
		( select ) =>
			select( STORE_NAME ).getFeatureSettings( providerName ) || {}
	);
	const [ useAPIkey, setUseAPIkey ] = useState(
		! providerSettings.username || 'apikey' === providerSettings.username
	);
	const { setProviderSettings } = useDispatch( STORE_NAME );
	const onChange = ( data ) => setProviderSettings( providerName, data );

	if ( isConfigured ) {
		return null;
	}

	const Description = () => (
		<>
			{ __( "Don't have an IBM Cloud account yet?", 'classifai' ) }{ ' ' }
			<a
				title={ __( 'Register for an IBM Cloud account', 'classifai' ) }
				href="https://cloud.ibm.com/registration"
			>
				{ __( 'Register for one', 'classifai' ) }
			</a>
			{ __( ' and set up a ', 'classifai' ) }
			<a href="https://cloud.ibm.com/catalog/services/natural-language-understanding">
				{ __( 'Natural Language Understanding', 'classifai' ) }
			</a>
			{ __( ' Resource to get your API key.', 'classifai' ) }
		</>
	);

	return (
		<>
			<SettingsRow label={ __( 'API URL', 'classifai' ) }>
				<InputControl
					id={ `${ providerName }_endpoint_url` }
					type="url"
					value={ providerSettings.endpoint_url || '' }
					onChange={ ( value ) =>
						onChange( { endpoint_url: value } )
					}
				/>
			</SettingsRow>
			{ ! useAPIkey && (
				<SettingsRow label={ __( 'API Username', 'classifai' ) }>
					<InputControl
						id={ `${ providerName }_username` }
						type="text"
						value={ providerSettings.username || '' }
						onChange={ ( value ) =>
							onChange( { username: value } )
						}
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
					id={ `${ providerName }_password` }
					type="password"
					value={ providerSettings.password || '' }
					onChange={ ( value ) => {
						const data = { password: value };
						if ( useAPIkey ) {
							data.username = 'apikey';
						}
						onChange( data );
					} }
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
