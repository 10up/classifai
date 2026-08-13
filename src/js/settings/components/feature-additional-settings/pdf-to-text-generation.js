/**
 * WordPress dependencies
 */
import { useSelect, useDispatch } from '@wordpress/data';
import { RadioControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { SettingsRow } from '../settings-row';
import { STORE_NAME } from '../../data/store';

/**
 * Component for the PDF To Text Generation settings.
 *
 * @return {React.ReactElement} PDFToTextGenerationSettings component.
 */
export const PDFToTextGenerationSettings = () => {
	const featureSettings = useSelect( ( select ) =>
		select( STORE_NAME ).getFeatureSettings()
	);
	const { setFeatureSettings } = useDispatch( STORE_NAME );

	return (
		<SettingsRow
			label={ __( 'Processing mode', 'classifai' ) }
			description={ __(
				'Choose how you want PDFs to be processed. These can be processed automatically when each PDF is uploaded or can instead be triggered manually on each desired PDF. Note if set to automatic, you can still trigger the processing manually on individual PDFs.',
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
						label: __( 'Manually trigger', 'classifai' ),
						value: 'manual',
					},
				] }
				selected={ featureSettings.processing_mode }
			/>
		</SettingsRow>
	);
};
