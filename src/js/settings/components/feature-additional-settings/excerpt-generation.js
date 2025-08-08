/**
 * WordPress dependencies
 */
import { useSelect, useDispatch } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import {
	__experimentalInputControl as InputControl, // eslint-disable-line @wordpress/no-unsafe-wp-apis
	CheckboxControl,
	SelectControl,
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

	// Get available ACF fields if ACF is active.
	const acfFields = window.classifAISettings?.acfFields || [];

	return (
		<>
			<SettingsRow
				label={ __( 'Prompt', 'classifai' ) }
				description={ __(
					"Add a custom prompt. Note the following variables that can be used in the prompt and will be replaced with content: {{WORDS}} will be replaced with the desired excerpt length setting. {{TITLE}} will be replaced with the item's title. {{AUTHOR}} will be replaced with the post author's display name.",
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
					'How many words should the excerpt be? Note that the final result may not exactly match this, it often tends to exceed this number by 10–15 words.',
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
			<SettingsRow
				label={ __( 'Target field', 'classifai' ) }
				description={ __(
					'Choose where to save the generated excerpt. You can target the default excerpt field, custom meta fields, or ACF fields.',
					'classifai'
				) }
			>
				<SelectControl
					value={ featureSettings.target_field_type || 'post_excerpt' }
					options={ [
						{
							label: __( 'Default excerpt field', 'classifai' ),
							value: 'post_excerpt',
						},
						{
							label: __( 'Custom meta field', 'classifai' ),
							value: 'custom_meta',
						},
						...( acfFields.length > 0 ? [
							{
								label: __( 'ACF field', 'classifai' ),
								value: 'acf_field',
							},
						] : [] ),
					] }
					onChange={ ( value ) =>
						setFeatureSettings( { target_field_type: value } )
					}
				/>
				{ featureSettings.target_field_type === 'custom_meta' && (
					<InputControl
						label={ __( 'Meta key', 'classifai' ) }
						value={ featureSettings.target_custom_field || '' }
						onChange={ ( value ) =>
							setFeatureSettings( { target_custom_field: value } )
						}
						placeholder={ __( 'e.g., editorial_subtitle, custom_excerpt', 'classifai' ) }
					/>
				) }
				{ featureSettings.target_field_type === 'acf_field' && (
					<SelectControl
						label={ __( 'ACF field', 'classifai' ) }
						value={ featureSettings.target_acf_field || '' }
						options={ [
							{
								label: __( 'Select ACF field', 'classifai' ),
								value: '',
							},
							...acfFields.map( ( field ) => ( {
								label: field.label,
								value: field.key,
							} ) ),
						] }
						onChange={ ( value ) =>
							setFeatureSettings( { target_acf_field: value } )
						}
					/>
				) }
			</SettingsRow>
		</>
	);
};
