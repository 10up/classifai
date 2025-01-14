/**
 * WordPress dependencies
 */
import {
	__experimentalInputControl as InputControl, // eslint-disable-line @wordpress/no-unsafe-wp-apis
	SelectControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { SettingsRow } from '../settings-row';
import { useSelect, useDispatch } from '@wordpress/data';
import { STORE_NAME } from '../../data/store';

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
export const OpenAIDallESettings = ( { isConfigured = false } ) => {
	const providerName = 'openai_dalle';
	const providerSettings = useSelect(
		( select ) =>
			select( STORE_NAME ).getFeatureSettings( providerName ) || {}
	);
	const { setProviderSettings } = useDispatch( STORE_NAME );
	const onChange = ( data ) => setProviderSettings( providerName, data );

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
				/>
			</SettingsRow>
			<SettingsRow
				label={ __( 'Image quality', 'classifai' ) }
				description={ __(
					'The quality of the image that will be generated. High Definition creates images with finer details and greater consistency across the image but costs more.',
					'classifai'
				) }
			>
				<SelectControl
					id={ `${ providerName }_quality` }
					onChange={ ( value ) => onChange( { quality: value } ) }
					value={ providerSettings.quality || 'standard' }
					options={ [
						{
							label: __( 'Standard', 'classifai' ),
							value: 'standard',
						},
						{
							label: __( 'High Definition', 'classifai' ),
							value: 'hd',
						},
					] }
					__nextHasNoMarginBottom
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
							label: __( '1792x1024 (landscape)', 'classifai' ),
							value: '1792x1024',
						},
						{
							label: __( '1024x1792 (portrait)', 'classifai' ),
							value: '1024x1792',
						},
					] }
					__nextHasNoMarginBottom
				/>
			</SettingsRow>
			<SettingsRow
				label={ __( 'Image style', 'classifai' ) }
				description={ __(
					'The style of the generated images. Vivid causes more hyper-real and dramatic images. Natural causes more natural, less hyper-real looking images.',
					'classifai'
				) }
			>
				<SelectControl
					id={ `${ providerName }_style` }
					onChange={ ( value ) => onChange( { style: value } ) }
					value={ providerSettings.style || 'vivid' }
					options={ [
						{
							label: __( 'Vivid', 'classifai' ),
							value: 'vivid',
						},
						{
							label: __( 'Natural', 'classifai' ),
							value: 'natural',
						},
					] }
					__nextHasNoMarginBottom
				/>
			</SettingsRow>
		</>
	);
};
