/**
 * WordPress dependencies
 */
import { useSelect, useDispatch } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import {
	__experimentalInputControl as InputControl, // eslint-disable-line @wordpress/no-unsafe-wp-apis
	CheckboxControl,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import { SettingsRow } from '../settings-row';
import { STORE_NAME } from '../../data/store';
import { PromptRepeater } from './prompt-repeater';

/**
 * Component for Excerpt Generation feature settings.
 *
 * This component is used within the FeatureSettings component to allow users to configure the Excerpt Generation feature.
 *
 * @return {React.ReactElement} ExcerptGenerationSettings component.
 */
export const ExcerptGenerationSettings = () => {
	const featureSettings = useSelect( ( select ) =>
		select( STORE_NAME ).getFeatureSettings()
	);
	const { excerptPostTypes } = window.classifAISettings;
	const { setFeatureSettings } = useDispatch( STORE_NAME );
	const setPrompts = ( prompts ) => {
		setFeatureSettings( {
			generate_excerpt_prompt: prompts,
		} );
	};

	return (
		<>
			<SettingsRow
				label={ __( 'Prompt', 'classifai' ) }
				description={ __(
					"Add a custom prompt. Note the following variables that can be used in the prompt and will be replaced with content: {{WORDS}} will be replaced with the desired excerpt length setting. {{TITLE}} will be replaced with the item's title.",
					'classifai'
				) }
			>
				<PromptRepeater
					prompts={ featureSettings.generate_excerpt_prompt }
					setPrompts={ setPrompts }
				/>
			</SettingsRow>
			<SettingsRow
				label={ __( 'Allowed post types', 'classifai' ) }
				description={ __(
					'Choose which post types support this feature.',
					'classifai'
				) }
				className="settings-allowed-post-types"
			>
				{ Object.keys( excerptPostTypes || {} ).map( ( key ) => {
					return (
						<CheckboxControl
							id={ key }
							key={ key }
							checked={
								featureSettings.post_types?.[ key ] === key
							}
							label={ excerptPostTypes?.[ key ] }
							onChange={ ( value ) => {
								setFeatureSettings( {
									post_types: {
										...featureSettings.post_types,
										[ key ]: value ? key : '0',
									},
								} );
							} }
							__nextHasNoMarginBottom
						/>
					);
				} ) }
			</SettingsRow>
			<SettingsRow
				label={ __( 'Excerpt length', 'classifai' ) }
				description={ __(
					'How many words should the excerpt be? Note that the final result may not exactly match this, it often tends to exceed this number by 10-15 words.',
					'classifai'
				) }
			>
				<InputControl
					id="excerpt_length"
					type="number"
					value={ featureSettings.length || 55 }
					onChange={ ( value ) =>
						setFeatureSettings( { length: value } )
					}
				/>
			</SettingsRow>
		</>
	);
};
