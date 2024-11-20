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
 * Component for the Content Resizing feature settings.
 *
 * This component is used within the FeatureSettings component to allow users to configure the Content Resizing feature.
 *
 * @return {React.ReactElement} ContentResizingSettings component.
 */
export const ContentResizingSettings = () => {
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
					setPromts={ ( prompts ) => {
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
					setPromts={ ( prompts ) => {
						setFeatureSettings( {
							expand_text_prompt: prompts,
						} );
					} }
				/>
			</SettingsRow>
		</>
	);
};
