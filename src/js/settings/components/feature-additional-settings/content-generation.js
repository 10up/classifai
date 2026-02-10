/**
 * WordPress dependencies
 */
import { useSelect, useDispatch } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { CheckboxControl, ToggleControl } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { SettingsRow } from '../settings-row';
import { STORE_NAME } from '../../data/store';
import { PromptRepeater } from './prompt-repeater';

/**
 * Component for the Content Generation Feature settings.
 *
 * @return {React.ReactElement} ContentGenerationSettings component.
 */
export const ContentGenerationSettings = () => {
	const featureSettings = useSelect( ( select ) =>
		select( STORE_NAME ).getFeatureSettings()
	);
	const { postTypes } = window.classifAISettings;
	const { setFeatureSettings } = useDispatch( STORE_NAME );
	const setPrompts = ( prompts ) => {
		setFeatureSettings( {
			prompt: prompts,
		} );
	};

	return (
		<>
			<SettingsRow
				label={ __( 'Prompt', 'classifai' ) }
				description={ __( 'Add a custom prompt.', 'classifai' ) }
			>
				<PromptRepeater
					prompts={ featureSettings.prompt }
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
				{ Object.keys( postTypes || {} ).map( ( key ) => {
					return (
						<CheckboxControl
							id={ key }
							key={ key }
							checked={
								featureSettings.post_types?.[ key ] === key
							}
							label={ postTypes?.[ key ] }
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
				label={ __( 'Quick draft integration', 'classifai' ) }
				description={ __(
					'Adds a "Create Draft from Prompt" button to the Quick Draft widget on the admin dashboard.',
					'classifai'
				) }
			>
				<ToggleControl
					checked={ featureSettings.enable_quick_draft !== false }
					onChange={ ( value ) => {
						setFeatureSettings( {
							enable_quick_draft: value,
						} );
					} }
					__nextHasNoMarginBottom
				/>
			</SettingsRow>
		</>
	);
};
