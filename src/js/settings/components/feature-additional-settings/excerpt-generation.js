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
 * @param {Object} props                    Component props.
 * @param {boolean} [props.showPrompt=true] Whether to show the prompt settings row.
 * @param {boolean} [props.showPostTypes=true] Whether to show the post types settings row.
 * @param {boolean} [props.showLength=true] Whether to show the excerpt length settings row.
 * @return {React.ReactElement} ExcerptGenerationSettings component.
 */
export const ExcerptGenerationSettings = ( { 
	showPrompt = true, 
	showPostTypes = true, 
	showLength = true 
} = {} ) => {
	// determine which provider is currently loaded
	const provider = useSelect(
		( select ) =>
			select( STORE_NAME ).getFeatureSettings( 'provider' ) ||
			Object.keys( feature?.providers || {} )[ 0 ]
	);
	
	/**
	 * These fields don't apply to Azure Language. Azure has its own keyed fields. This parameter is
	 * filtered at Classifai\Providers\Azure\Language (classifai_azure_language_summary_length)
	 * 
	 * There is likely a better way to handle this where the individual provider configs can govern
	 * the fields that are shown. For now this is a quick fix.
	 */
	if ( provider === 'azure_language' ) {
		showPrompt = false;
		showLength = false;
	}
	
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
			{ showPrompt && (
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
			) }
			{ showPostTypes && (
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
			) }
			{ showLength && (
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
			) }
		</>
	);
};
