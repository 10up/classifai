import { useSelect, useDispatch } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { CheckboxControl } from '@wordpress/components';
import { SettingsRow } from '../settings-row';
import { STORE_NAME } from '../../data/store';
import { PromptRepeater } from './prompt-repeater';
import { usePostTypes } from '../../utils/utils';

export const ExcerptGenerationSettings = () => {
	const featureSettings = useSelect( ( select ) =>
		select( STORE_NAME ).getFeatureSettings()
	);
	const { excerptPostTypesOptions } = usePostTypes();
	const { setFeatureSettings } = useDispatch( STORE_NAME );
	const setPromts = ( prompts ) => {
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
					setPromts={ setPromts }
				/>
			</SettingsRow>
			<SettingsRow
				label={ __( 'Allowed post types', 'classifai' ) }
				description={ __(
					'Choose which post types support this feature.',
					'classifai'
				) }
			>
				{ ( excerptPostTypesOptions || [] ).map( ( option ) => {
					const { value: key, label } = option;
					return (
						<CheckboxControl
							key={ key }
							checked={
								featureSettings.post_types?.[ key ] === key
							}
							label={ label }
							onChange={ ( value ) => {
								setFeatureSettings( {
									post_types: {
										...featureSettings.post_types,
										[ key ]: value ? key : '0',
									},
								} );
							} }
						/>
					);
				} ) }
			</SettingsRow>
		</>
	);
};
