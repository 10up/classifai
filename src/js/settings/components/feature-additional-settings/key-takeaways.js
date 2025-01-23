/**
 * WordPress dependencies
 */
import { useSelect, useDispatch } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { CheckboxControl, RadioControl } from '@wordpress/components';

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
	const { postTypes } = window.classifAISettings;
	const { setFeatureSettings } = useDispatch( STORE_NAME );
	const setPrompts = ( prompts ) => {
		setFeatureSettings( {
			key_takeaways_prompt: prompts,
		} );
	};

	return (
		<>
			<SettingsRow
				label={ __( 'Prompt', 'classifai' ) }
				description={ __( 'Add a custom prompt.', 'classifai' ) }
			>
				<PromptRepeater
					prompts={ featureSettings.key_takeaways_prompt }
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
				label={ __( 'Render', 'classifai' ) }
				description={ __(
					'Choose how you want the Key Takeaways to render.',
					'classifai'
				) }
			>
				<RadioControl
					className="render-radio-control"
					onChange={ ( value ) => {
						setFeatureSettings( {
							render: value,
						} );
					} }
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
					selected={ featureSettings.render }
				/>
			</SettingsRow>
		</>
	);
};
