/**
 * WordPress dependencies
 */
import { SelectControl, ToggleControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { SettingsRow } from '../settings-row';
import { useSelect, useDispatch } from '@wordpress/data';
import { STORE_NAME } from '../../data/store';
import { OpenAISettings } from './openai';

/**
 * Component for OpenAI Images Provider settings.
 *
 * @param {Object}  props              Component props.
 * @param {boolean} props.isConfigured Whether the provider is configured.
 *
 * @return {React.ReactElement} OpenAIImagesSettings component.
 */
export const OpenAIImagesSettings = ( { isConfigured = false } ) => {
	const providerName = 'openai_dalle';
	const providerSettings = useSelect(
		( select ) =>
			select( STORE_NAME ).getFeatureSettings( providerName ) || {}
	);
	const { setProviderSettings } = useDispatch( STORE_NAME );
	const onChange = ( data ) => setProviderSettings( providerName, data );

	return (
		<>
			{ ! isConfigured && (
				<OpenAISettings
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
					options={ Array.from( { length: 10 }, ( v, i ) => ( {
						label: i + 1,
						value: i + 1,
					} ) ) }
					__nextHasNoMarginBottom
					__next40pxDefaultSize
				/>
			</SettingsRow>
			<SettingsRow
				label={ __( 'Image quality', 'classifai' ) }
				description={ __(
					'The quality of the image that will be generated. Set to auto to allow OpenAI to choose the best quality for the model.',
					'classifai'
				) }
			>
				<SelectControl
					id={ `${ providerName }_quality` }
					onChange={ ( value ) => onChange( { quality: value } ) }
					value={ providerSettings.quality || 'auto' }
					options={ [
						{
							label: __( 'Auto', 'classifai' ),
							value: 'auto',
						},
						{
							label: __( 'Low', 'classifai' ),
							value: 'low',
						},
						{
							label: __( 'Medium', 'classifai' ),
							value: 'medium',
						},
						{
							label: __( 'High', 'classifai' ),
							value: 'high',
						},
					] }
					__nextHasNoMarginBottom
					__next40pxDefaultSize
				/>
			</SettingsRow>
			<SettingsRow
				label={ __( 'Image size', 'classifai' ) }
				description={ __(
					'Size of generated images. Larger sizes cost more.',
					'classifai'
				) }
			>
				<SelectControl
					id={ `${ providerName }_image_size` }
					onChange={ ( value ) => onChange( { image_size: value } ) }
					value={ providerSettings.image_size || '1024x1024' }
					options={ [
						{
							label: __( '1024x1024 (square)', 'classifai' ),
							value: '1024x1024',
						},
						{
							label: __( '1536x1024 (landscape)', 'classifai' ),
							value: '1536x1024',
						},
						{
							label: __( '1024x1536 (portrait)', 'classifai' ),
							value: '1024x1536',
						},
					] }
					__nextHasNoMarginBottom
					__next40pxDefaultSize
				/>
			</SettingsRow>
			<SettingsRow
				label={ __( 'Per-image settings', 'classifai' ) }
				description={ __(
					'If enabled, allows users to select the quality, size, and style when generating an image.',
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
