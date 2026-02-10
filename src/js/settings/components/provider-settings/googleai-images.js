/**
 * WordPress dependencies
 */
import { useSelect, useDispatch } from '@wordpress/data';
import { SelectControl, ToggleControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { SettingsRow } from '../settings-row';
import { STORE_NAME } from '../../data/store';
import { GoogleAISettings } from './googleai';

/**
 * Component for Google AI Images Provider settings.
 *
 * @param {Object}  props              Component props.
 * @param {boolean} props.isConfigured Whether the provider is configured.
 *
 * @return {React.ReactElement} GoogleAIImagesSettings component.
 */
export const GoogleAIImagesSettings = ( { isConfigured = false } ) => {
	const providerName = 'googleai_images';
	const providerSettings = useSelect(
		( select ) =>
			select( STORE_NAME ).getFeatureSettings( providerName ) || {}
	);
	const { setProviderSettings } = useDispatch( STORE_NAME );
	const onChange = ( data ) => setProviderSettings( providerName, data );

	return (
		<>
			{ ! isConfigured && (
				<GoogleAISettings
					providerSettings={ providerSettings }
					onChange={ onChange }
				/>
			) }
			<SettingsRow
				label={ __( 'Number of images', 'classifai' ) }
				description={ __(
					'Number of images that will be generated in one request. Note that each image will incur separate costs.',
					'classifai'
				) }
			>
				<SelectControl
					id={ `${ providerName }_number_of_images` }
					onChange={ ( value ) =>
						onChange( { number_of_images: value } )
					}
					value={ providerSettings.number_of_images || '1' }
					options={ Array.from( { length: 4 }, ( v, i ) => ( {
						label: i + 1,
						value: i + 1,
					} ) ) }
					__nextHasNoMarginBottom
					__next40pxDefaultSize
				/>
			</SettingsRow>
			<SettingsRow
				label={ __( 'Aspect ratio', 'classifai' ) }
				description={ __(
					'Aspect ratio of generated images.',
					'classifai'
				) }
			>
				<SelectControl
					id={ `${ providerName }_aspect_ratio` }
					onChange={ ( value ) =>
						onChange( { aspect_ratio: value } )
					}
					value={ providerSettings.aspect_ratio || '1:1' }
					options={ [
						{
							label: __( '1:1 (square)', 'classifai' ),
							value: '1:1',
						},
						{
							label: __( '3:4 (portrait)', 'classifai' ),
							value: '3:4',
						},
						{
							label: __( '4:3 (landscape)', 'classifai' ),
							value: '4:3',
						},
						{
							label: __( '9:16 (portrait)', 'classifai' ),
							value: '9:16',
						},
						{
							label: __( '16:9 (landscape)', 'classifai' ),
							value: '16:9',
						},
					] }
					__nextHasNoMarginBottom
					__next40pxDefaultSize
				/>
			</SettingsRow>
			<SettingsRow
				label={ __( 'Per-image settings', 'classifai' ) }
				description={ __(
					'If enabled, allows users to select the aspect ratio when generating an image.',
					'classifai'
				) }
			>
				<ToggleControl
					id={ `${ providerName }_per_image_settings` }
					onChange={ ( value ) =>
						onChange( { per_image_settings: value } )
					}
					checked={ providerSettings.per_image_settings || false }
					__nextHasNoMarginBottom
				/>
			</SettingsRow>
		</>
	);
};
