/**
 * WordPress dependencies
 */
import {
	__experimentalInputControl as InputControl, // eslint-disable-line @wordpress/no-unsafe-wp-apis
	SelectControl,
	ToggleControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useSelect, useDispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { SettingsRow } from '../../settings-row';
import { STORE_NAME } from '../../../data/store';
import { config } from './config';

/**
 * Component for OpenAI DALL-E Provider settings.
 *
 * This component is used within the ProviderSettings component to allow users to configure the OpenAI DALL-E Provider settings.
 *
 * @param {Object}  props              Component props.
 * @param {boolean} props.isConfigured Whether the provider is configured.
 *
 * @return {React.ReactElement} OpenAIDallESettings component.
 */
export const LeonardoSettings = ( { isConfigured = false } ) => {
	const providerName = 'leonardo';
	const providerSettings = useSelect(
		( select ) =>
			select( STORE_NAME ).getFeatureSettings( providerName ) || {}
	);
	const { setProviderSettings } = useDispatch( STORE_NAME );
	const onChange = ( data ) => setProviderSettings( providerName, data );

	const Description = () => (
		<>
			{ __( "Don't have an Leonardo.Ai account yet?", 'classifai' ) }{ ' ' }
			<a
				title={ __( 'Sign up for an Leonardo.Ai account', 'classifai' ) }
				href="https://app.leonardo.ai/auth/login"
			>
				{ __( 'Sign up for one', 'classifai' ) }
			</a>{ ' ' }
			{ __( 'in order to get your API key.', 'classifai' ) }
		</>
	);

	return (
		<>
			{ ! isConfigured && (
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
			) }
			<SettingsRow label={ __( 'Model', 'classifai' ) }>
				<SelectControl
					id={ `${ providerName }_model` }
					options={ config.models }
					value={ providerSettings.model }
					onChange={ ( value ) => onChange( { model: value } ) }
				/>
			</SettingsRow>
			<SettingsRow label={ __( 'Preset', 'classifai' ) }>
				<SelectControl
					id={ `${ providerName }_preset` }
					options={ config[ providerSettings.model ].presets }
					value={ providerSettings.preset }
					onChange={ ( value ) => onChange( { preset: value } ) }
				/>
			</SettingsRow>
			<SettingsRow label={ __( 'Number of images', 'classifai' ) }>
				<SelectControl
					id={ `${ providerName }_num_images` }
					onChange={ ( value ) =>
						onChange( { num_images: value } )
					}
					value={ providerSettings.num_images || '1' }
					options={ Array.from( { length: 10 }, ( v, i ) => ( {
						label: i + 1,
						value: i + 1,
					} ) ) }
					__nextHasNoMarginBottom
				/>
			</SettingsRow>
		</>
	);
}
