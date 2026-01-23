/**
 * WordPress dependencies
 */
import { useSelect, useDispatch } from '@wordpress/data';
import { SelectControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { SettingsRow } from '../../../settings-row';
import { STORE_NAME } from '../../../../data/store';
import { AzureTextToSpeechBaseSettings } from './base';

/**
 * Component for Azure Text to Speech Provider settings.
 *
 * @param {Object}  props              Component props.
 * @param {string}  props.providerName The provider name.
 * @param {boolean} props.isConfigured Whether the provider is configured.
 *
 * @return {React.ReactElement} AzureTextToSpeechSettings component.
 */
export const AzureTextToSpeechSettings = ( {
	providerName,
	isConfigured = false,
} ) => {
	const providerSettings = useSelect(
		( select ) =>
			select( STORE_NAME ).getFeatureSettings( providerName ) || {}
	);
	const { setProviderSettings } = useDispatch( STORE_NAME );
	const onChange = ( data ) => setProviderSettings( providerName, data );

	return (
		<>
			{ ! isConfigured && (
				<AzureTextToSpeechBaseSettings
					providerSettings={ providerSettings }
					onChange={ onChange }
				/>
			) }

			{ !! providerSettings.voices?.length && (
				<SettingsRow label={ __( 'Voice', 'classifai' ) }>
					<SelectControl
						id={ `${ providerName }_voice` }
						onChange={ ( value ) => onChange( { voice: value } ) }
						value={ providerSettings.voice || '' }
						options={ ( providerSettings.voices || [] ).map(
							( ele ) => ( {
								label: `${ ele.LocaleName } (${ ele.DisplayName }/${ ele.Gender })`,
								value: `${ ele.ShortName }|${ ele.Gender }`,
							} )
						) }
						__nextHasNoMarginBottom
						__next40pxDefaultSize
					/>
				</SettingsRow>
			) }
		</>
	);
};
