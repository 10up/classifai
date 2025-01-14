/**
 * WordPress dependencies
 */
import { useSelect, useDispatch } from '@wordpress/data';
// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
import { CheckboxControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { SettingsRow } from '../settings-row';
import { STORE_NAME } from '../../data/store';

/**
 * Component for Descriptive Text Generator feature settings.
 *
 * This component is used within the FeatureSettings component to allow users to configure the Descriptive Text Generator feature.
 *
 * @return {React.ReactElement} DescriptiveTextGeneratorSettings component.
 */
export const DescriptiveTextGeneratorSettings = () => {
	const featureSettings = useSelect( ( select ) =>
		select( STORE_NAME ).getFeatureSettings()
	);
	const { setFeatureSettings } = useDispatch( STORE_NAME );

	const options = {
		alt: __( 'Alt text', 'classifai' ),
		caption: __( 'Image caption', 'classifai' ),
		description: __( 'Image description', 'classifai' ),
	};
	return (
		<SettingsRow
			label={ __( 'Descriptive text fields', 'classifai' ) }
			description={ __(
				'Choose image fields where the generated text should be applied.',
				'classifai'
			) }
			className="classifai-descriptive-text-fields"
		>
			{ Object.keys( options ).map( ( option ) => {
				return (
					<CheckboxControl
						id={ option }
						key={ option }
						checked={
							featureSettings.descriptive_text_fields?.[
								option
							] === option
						}
						label={ options[ option ] }
						onChange={ ( value ) => {
							setFeatureSettings( {
								descriptive_text_fields: {
									...featureSettings.descriptive_text_fields,
									[ option ]: value ? option : '0',
								},
							} );
						} }
						__nextHasNoMarginBottom
					/>
				);
			} ) }
		</SettingsRow>
	);
};
