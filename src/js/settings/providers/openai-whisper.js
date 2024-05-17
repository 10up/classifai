import { addFilter } from '@wordpress/hooks';
import { Fill, TextControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

function openAIWhisperSettings( ProviderSettings ) {
	return ( props ) => {
		const { featureSettings = {}, setSettings = {} } = props;
		if ( featureSettings.provider !== 'openai_whisper' ) {
			return <ProviderSettings { ...props } />;
		}

		const apiKey = featureSettings.openai_whisper?.api_key || '';

		return (
			<>
				<ProviderSettings { ...props } />
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
	'classifai.ProviderSettings',
	'classifai-provider-settings/openai-whisper',
	openAIWhisperSettings
);
