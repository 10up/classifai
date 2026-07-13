/**
 * WordPress dependencies
 */
import { useSelect, useDispatch } from '@wordpress/data';
import { CheckboxControl, RadioControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { SettingsRow } from '../settings-row';
import { STORE_NAME } from '../../data/store';

/**
 * Component for the Descriptive Text Generator Feature settings.
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
		<>
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
			<SettingsRow
				label={ __( 'Processing mode', 'classifai' ) }
				description={ __(
					'Choose how you want images to be processed. Images can be processed automatically when uploaded — either during the upload request, or in the background so uploads are not blocked — or triggered manually on each desired image. With either automatic mode you can still trigger processing manually on individual images.',
					'classifai'
				) }
			>
				<RadioControl
					className="processing-mode-radio-control"
					onChange={ ( value ) => {
						setFeatureSettings( {
							processing_mode: value,
						} );
					} }
					options={ [
						{
							label: __( 'Automatically on upload', 'classifai' ),
							value: 'automatic',
						},
						{
							label: __(
								'Automatically on upload (background)',
								'classifai'
							),
							value: 'automatic_async',
						},
						{
							label: __( 'Manually trigger', 'classifai' ),
							value: 'manual',
						},
					] }
					selected={ featureSettings.processing_mode }
				/>
			</SettingsRow>
		</>
	);
};
