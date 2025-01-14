/**
 * WordPress dependencies
 */
import { useSelect, useDispatch } from '@wordpress/data';
// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
import { __experimentalInputControl as InputControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { SettingsRow } from '../settings-row';
import { STORE_NAME } from '../../data/store';
import { useFeatureContext } from '../feature-settings/context';
import { PromptRepeater } from '../feature-additional-settings/prompt-repeater';

/**
 * Component for xAI Grok Provider settings.
 *
 * This component is used within the ProviderSettings component to allow users to configure the xAI Grok Provider settings.
 *
 * @param {Object}  props              Component props.
 * @param {boolean} props.isConfigured Whether the provider is configured.
 *
 * @return {React.ReactElement} XAIGrokSettings component.
 */
export const XAIGrokSettings = ( { isConfigured = false } ) => {
	const { featureName } = useFeatureContext();
	const providerName = 'xai_grok';
	const providerSettings = useSelect(
		( select ) =>
			select( STORE_NAME ).getFeatureSettings( providerName ) || {}
	);
	const { setProviderSettings } = useDispatch( STORE_NAME );
	const onChange = ( data ) => setProviderSettings( providerName, data );
	const setPrompts = ( prompts ) => {
		setProviderSettings( providerName, {
			prompt: prompts,
		} );
	};

	const Description = () => (
		<>
			{ __( "Don't have an xAI account yet? ", 'classifai' ) }
			<a
				title={ __( 'Sign up for an xAI account', 'classifai' ) }
				href="https://accounts.x.ai/sign-up"
			>
				{ __( 'Sign up for one', 'classifai' ) }
			</a>{ ' ' }
			{ __( 'in order to get your API key.', 'classifai' ) }
		</>
	);
	const promptExamples = (
		<>
			{ __( 'Add a custom prompt, if desired. ', 'classifai' ) }
			{ __( 'See our ', 'classifai' ) }
			<a
				href="https://10up.github.io/classifai/tutorial-prompt-examples.html"
				target="_blank"
				rel="noopener noreferrer"
			>
				{ __( 'documentation', 'classifai' ) }
			</a>
			{ __(
				' for some example prompts you can try that have been tested for specific use cases.',
				'classifai'
			) }
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
			{ [
				'feature_content_resizing',
				'feature_title_generation',
			].includes( featureName ) && (
				<SettingsRow
					label={ __( 'Number of suggestions', 'classifai' ) }
					description={ __(
						'Number of suggestions that will be generated in one request.',
						'classifai'
					) }
				>
					<InputControl
						id={ `${ providerName }_number_of_suggestions` }
						type="number"
						value={ providerSettings.number_of_suggestions || 1 }
						onChange={ ( value ) =>
							onChange( { number_of_suggestions: value } )
						}
					/>
				</SettingsRow>
			) }
			{ [ 'feature_descriptive_text_generator' ].includes(
				featureName
			) && (
				<SettingsRow
					label={ __( 'Prompt', 'classifai' ) }
					description={ promptExamples }
				>
					<PromptRepeater
						prompts={ providerSettings.prompt }
						setPrompts={ setPrompts }
					/>
				</SettingsRow>
			) }
		</>
	);
};
