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
 * Component for Key Takeaways feature settings.
 *
 * This component is used within the FeatureSettings component to allow users
 * to configure the Key Takeaways feature.
 *
 * @return {React.ReactElement} KeyTakeawaysSettings component.
 */
export const KeyTakeawaysSettings = () => {
	const featureSettings = useSelect( ( select ) =>
		select( STORE_NAME ).getFeatureSettings()
	);
	const { setFeatureSettings } = useDispatch( STORE_NAME );
	const setPrompts = ( prompts ) => {
		setFeatureSettings( {
			key_takeaways_prompt: prompts,
		} );
	};

	return (
		<SettingsRow
			label={ __( 'Prompt', 'classifai' ) }
			description={ __(
				"Add a custom prompt. Note that the {{TITLE}} variable can be used in the prompt, and it will be replaced with the item's title.",
				'classifai'
			) }
		>
			<PromptRepeater
				prompts={ featureSettings.key_takeaways_prompt }
				setPrompts={ setPrompts }
			/>
		</SettingsRow>
	);
};
