import { addFilter } from '@wordpress/hooks';
import { Fill, TextControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

function providerSettings() {
	return ( props ) => {
		const { featureSettings, setSettings } = props;
		const { provider } = featureSettings;
		if ( provider !== 'openai_whisper' ) {
			return null;
		}

		const apiKey = featureSettings.openai_whisper?.api_key || '';

		return (
			<>
				<Fill name="ProviderSettings">
					<TextControl
						label={ __( 'API Key', 'classifai' ) }
						value={ apiKey }
						onChange={ ( value ) =>
							setSettings( {
								openai_whisper: {
									...featureSettings.openai_whisper,
									api_key: value,
								},
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
	'classifai-provider-settings/openai-whisper',
	providerSettings
);
