/**
 * WordPress dependencies
 */
import {
	__experimentalInputControl as InputControl, // eslint-disable-line @wordpress/no-unsafe-wp-apis
	SelectControl,
	ToggleControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { SettingsRow } from '../settings-row';
import { useSelect, useDispatch } from '@wordpress/data';
import { STORE_NAME } from '../../data/store';

/**
 * Component for the Stable Diffusion Provider settings.
 *
 * @param {Object}  props              Component props.
 * @param {boolean} props.isConfigured Whether the provider is configured.
 *
 * @return {React.ReactElement} StableDiffusionSettings component.
 */
export const StableDiffusionSettings = ( { isConfigured = false } ) => {
	const providerName = 'stable_diffusion';
	const providerSettings = useSelect(
		( select ) =>
			select( STORE_NAME ).getFeatureSettings( providerName ) || {}
	);
	const { setProviderSettings } = useDispatch( STORE_NAME );
	const onChange = ( data ) => setProviderSettings( providerName, data );

	const Description = () => (
		<>
			{ __(
				"URL of the locally hosted Stable Diffusion instance. Defaults to http://127.0.0.1:7860/. Don't have Stable Diffusion installed yet?",
				'classifai'
			) }
			<a
				title={ __( 'Install Stable Diffusion', 'classifai' ) }
				href="https://github.com/AUTOMATIC1111/stable-diffusion-webui/#installation-and-running"
				target="_blank"
				rel="noopener noreferrer"
			>
				{ ' ' }
				{ __( 'Download Stable Diffusion', 'classifai' ) }
			</a>
		</>
	);

	const models = [
		{ label: __( '-- Choose Model --', 'classifai' ), value: '' },
	];

	// Convert providerSettings.models to an array from an object.
	if (
		providerSettings?.models &&
		! Array.isArray( providerSettings.models )
	) {
		for ( const [ key, value ] of Object.entries(
			providerSettings.models
		) ) {
			models.push( { label: value, value: key } );
		}
	}

	return (
		<>
			{ ! isConfigured && (
				<SettingsRow
					label={ __( 'Endpoint URL', 'classifai' ) }
					description={ <Description /> }
				>
					<InputControl
						id={ `${ providerName }_endpoint_url` }
						type="text"
						value={ providerSettings?.endpoint_url || '' }
						onChange={ ( value ) =>
							onChange( { endpoint_url: value } )
						}
						__next40pxDefaultSize
					/>
				</SettingsRow>
			) }
			<SettingsRow
				label={ __( 'Model', 'classifai' ) }
				description={ __(
					'Choose the model you want to use. If no models are shown or you want to use a different model, please ensure this is installed in Stable Diffusion first.',
					'classifai'
				) }
			>
				<SelectControl
					id={ `${ providerName }_model` }
					onChange={ ( value ) => onChange( { model: value } ) }
					value={ providerSettings?.model || '' }
					options={ models }
					disabled={ ! isConfigured }
					__nextHasNoMarginBottom
					__next40pxDefaultSize
				/>
			</SettingsRow>
			<SettingsRow
				label={ __( 'Number of images', 'classifai' ) }
				description={ __(
					'Number of images that will be generated in one request. Note that more images will take longer to generate.',
					'classifai'
				) }
			>
				<SelectControl
					id={ `${ providerName }_number_of_images` }
					onChange={ ( value ) =>
						onChange( { number_of_images: value } )
					}
					value={ providerSettings.number_of_images || '1' }
					options={ Array.from( { length: 5 }, ( v, i ) => ( {
						label: i + 1,
						value: i + 1,
					} ) ) }
					__nextHasNoMarginBottom
					__next40pxDefaultSize
				/>
			</SettingsRow>
			<SettingsRow
				label={ __( 'Image size', 'classifai' ) }
				description={ __(
					'Size of generated images. Larger sizes will take longer to generate.',
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
					'If enabled, allows users to select the size when generating an image.',
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
