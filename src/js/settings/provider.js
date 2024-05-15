import { addFilter } from '@wordpress/hooks';
import { Fill, TextareaControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

function providerSettings() {
	return ( { featureSettings, setSettings } ) => {
		const { provider, message } = featureSettings;
		if ( provider !== 'a' ) {
			return null;
		}

		return (
			<>
				<Fill name="ProviderSettings">
					<TextareaControl
						label={ __( 'Message', 'classifai' ) }
						value={ message }
						onChange={ ( value ) =>
							setSettings( {
								message: value,
							} )
						}
					/>
				</Fill>
			</>
		);
	};
}

addFilter(
	'classifai.PluginSettings',
	'classifai/provider-settings',
	providerSettings
);
