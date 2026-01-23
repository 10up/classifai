/**
 * WordPress dependencies
 */
import {
	__experimentalInputControl as InputControl, // eslint-disable-line @wordpress/no-unsafe-wp-apis
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { SettingsRow } from '../../settings-row';

/**
 * Component for the Stable Diffusion Provider settings.
 *
 * This is the base component for Stable Diffusion settings.
 *
 * @param {Object}   props                  Component props.
 * @param {Object}   props.providerSettings The provider settings.
 * @param {Function} props.onChange         Function to call when the provider settings change.
 *
 * @return {React.ReactElement} StableDiffusionBaseSettings component.
 */
export const StableDiffusionBaseSettings = ( {
	providerSettings,
	onChange,
} ) => {
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

	return (
		<>
			<SettingsRow
				label={ __( 'Endpoint URL', 'classifai' ) }
				description={ <Description /> }
			>
				<InputControl
					id="stable_diffusion_endpoint_url"
					type="text"
					value={ providerSettings?.endpoint_url || '' }
					onChange={ ( value ) =>
						onChange( { endpoint_url: value } )
					}
					__next40pxDefaultSize
				/>
			</SettingsRow>
		</>
	);
};
