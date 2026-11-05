/**
 * WordPress dependencies
 */
import { useSelect, useDispatch } from '@wordpress/data';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { SettingsRow } from '../settings-row';
import { STORE_NAME } from '../../data/store';
import { PromptRepeater } from './prompt-repeater';

/**
 * Component for the Writing Tools feature settings.
 *
 * This component is used within the FeatureSettings component to allow users to configure the Writing Tools feature.
 *
 * @return {React.ReactElement} WritingToolsSettings component.
 */
export const WritingToolsSettings = () => {
	const featureSettings = useSelect( ( select ) =>
		select( STORE_NAME ).getFeatureSettings()
	);
	const { setFeatureSettings } = useDispatch( STORE_NAME );

	return (
		<>
			<SettingsRow
				label={ __( 'Condense text prompt', 'classifai' ) }
				description={ __( 'Enter your custom prompt.', 'classifai' ) }
				className="settings-condense-text-prompt"
			>
				<PromptRepeater
					prompts={ featureSettings.condense_text_prompt }
					setPrompts={ ( prompts ) => {
						setFeatureSettings( {
							condense_text_prompt: prompts,
						} );
					} }
				/>
			</SettingsRow>
			<SettingsRow
				label={ __( 'Expand text prompt', 'classifai' ) }
				description={ __( 'Enter your custom prompt.', 'classifai' ) }
				className="settings-expand-text-prompt"
			>
				<PromptRepeater
					prompts={ featureSettings.expand_text_prompt }
					setPrompts={ ( prompts ) => {
						setFeatureSettings( {
							expand_text_prompt: prompts,
						} );
					} }
				/>
			</SettingsRow>
			<SettingsRow
				label={ __( 'Fix grammar and spelling', 'classifai' ) }
				description={ __( 'Enter your custom prompt.', 'classifai' ) }
				className="settings-fix-grammar-text-prompt"
			>
				<PromptRepeater
					prompts={ featureSettings.fix_grammar_text_prompt }
					setPrompts={ ( prompts ) => {
						setFeatureSettings( {
							fix_grammar_text_prompt: prompts,
						} );
					} }
				/>
			</SettingsRow>
		</>
	);
};
