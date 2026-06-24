/**
 * WordPress dependencies
 */
import { useSelect, useDispatch } from '@wordpress/data';
import {
	CheckboxControl,
	SelectControl,
	TextControl,
} from '@wordpress/components';
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
	const { postTypes } = window.classifAISettings;
	const setPrompts = ( prompts ) => {
		setFeatureSettings( {
			key_takeaways_prompt: prompts,
		} );
	};

	const generationTiming = featureSettings.generation_timing || 'manual';

	return (
		<>
			<SettingsRow
				label={ __( 'Processing mode', 'classifai' ) }
				description={ __(
					'"On demand" adds a button to the front-end of supported posts that generates key takeaways the first time a visitor requests them, then stores the result for reuse.',
					'classifai'
				) }
				className="settings-key-takeaways-generation-timing"
			>
				<SelectControl
					value={ generationTiming }
					options={ [
						{
							label: __(
								'Manual (add the Key Takeaways block to a post)',
								'classifai'
							),
							value: 'manual',
						},
						{
							label: __(
								'On demand (generate on first front-end request)',
								'classifai'
							),
							value: 'on_demand',
						},
					] }
					onChange={ ( value ) => {
						setFeatureSettings( {
							generation_timing: value,
						} );
					} }
					__nextHasNoMarginBottom
					__next40pxDefaultSize
				/>
			</SettingsRow>

			{ 'on_demand' === generationTiming && (
				<>
					<SettingsRow
						label={ __( 'Allowed post types', 'classifai' ) }
						description={ __(
							'Choose which post types show the front-end button.',
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
										featureSettings.post_types?.[ key ] ===
										key
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
						label={ __( 'Button label', 'classifai' ) }
						description={ __(
							'Text shown on the front-end button.',
							'classifai'
						) }
					>
						<TextControl
							value={ featureSettings.button_label || '' }
							placeholder={ __( 'Key Takeaways', 'classifai' ) }
							onChange={ ( value ) => {
								setFeatureSettings( {
									button_label: value,
								} );
							} }
							__nextHasNoMarginBottom
							__next40pxDefaultSize
						/>
					</SettingsRow>

					<SettingsRow
						label={ __( 'Display format', 'classifai' ) }
						description={ __(
							'How takeaways are rendered in the front-end panel.',
							'classifai'
						) }
					>
						<SelectControl
							value={ featureSettings.render || 'list' }
							options={ [
								{
									label: __( 'List', 'classifai' ),
									value: 'list',
								},
								{
									label: __( 'Paragraph', 'classifai' ),
									value: 'paragraph',
								},
							] }
							onChange={ ( value ) => {
								setFeatureSettings( {
									render: value,
								} );
							} }
							__nextHasNoMarginBottom
							__next40pxDefaultSize
						/>
					</SettingsRow>
				</>
			) }

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
		</>
	);
};
