import { useSelect, useDispatch } from '@wordpress/data';
// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
import { __experimentalInputControl as InputControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { SettingsRow } from '../settings-row';
import { STORE_NAME } from '../../data/store';

export const AzurePersonalizerSettings = ( { isConfigured = false } ) => {
	const providerName = 'ms_azure_personalizer';
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
		<>
			<SettingsRow label={ __( 'Endpoint URL', 'classifai' ) }>
				<InputControl
					type="text"
					value={ providerSettings.endpoint_url || '' }
					onChange={ ( value ) =>
						onChange( { endpoint_url: value } )
					}
				/>
			</SettingsRow>
			<SettingsRow label={ __( 'API Key', 'classifai' ) }>
				<InputControl
					type="password"
					value={ providerSettings.api_key || '' }
					onChange={ ( value ) => onChange( { api_key: value } ) }
				/>
			</SettingsRow>
		</>
	);
};
