/**
 * WordPress Dependencies
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
 * Component for Title Generation feature settings.
 *
 * This component is used within the FeatureSettings component to allow users to configure the Title Generation feature.
 *
 * @return {React.ReactElement} TitleGenerationSettings component.
 */
export const TitleGenerationSettings = () => {
	const featureSettings = useSelect( ( select ) =>
		select( STORE_NAME ).getFeatureSettings()
	);
	const { setFeatureSettings } = useDispatch( STORE_NAME );
	const setPromts = ( prompts ) => {
		setFeatureSettings( {
			generate_title_prompt: prompts,
		} );
	};

	return (
		<SettingsRow
			label={ __( 'Prompt', 'classifai' ) }
			description={ __(
				'Add a custom prompt, if desired.',
				'classifai'
			) }
		>
			<PromptRepeater
				prompts={ featureSettings.generate_title_prompt }
				setPromts={ setPromts }
			/>
		</SettingsRow>
	);
};
