import { registerPlugin } from '@wordpress/plugins';
import { useSelect, useDispatch } from '@wordpress/data';
import { Fill, TextControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

const OpenAIModerationSettings = () => {
	const providerName = 'openai_moderation';
	const featureSettings = useSelect( ( select ) =>
		select( 'classifai-settings' ).getFeatureSettings()
	);
	const { setFeatureSettings } = useDispatch( 'classifai-settings' );
	const providerSettings = featureSettings[ providerName ] || {};
	const setProviderSettings = ( data ) =>
		setFeatureSettings( {
			[ providerName ]: {
				...providerSettings,
				...data,
			},
		} );

	return (
		<Fill name="ClassifAIProviderSettings">
			<TextControl
				label={ __( 'API Key', 'classifai' ) }
				value={ providerSettings.api_key || '' }
				onChange={ ( value ) =>
					setProviderSettings( { api_key: value } )
				}
			/>
			<span className="description classifai-input-description">
				{ __( "Don't have an OpenAI account yet? ", 'classifai' ) }
				<a
					title={ __( 'Sign up for an OpenAI account', 'classifai' ) }
					href="https://platform.openai.com/signup"
				>
					{ __( 'Sign up for one', 'classifai' ) }
				</a>{ ' ' }
				{ __( 'in order to get your API key.', 'classifai' ) }
			</span>
		</Fill>
	);
};

registerPlugin( 'classifai-provider-moderation-chatgpt', {
	scope: 'openai-moderation',
	render: OpenAIModerationSettings,
} );
