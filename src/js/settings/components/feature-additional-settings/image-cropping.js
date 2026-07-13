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
 * Component for the Image Cropping Feature settings.
 *
 * @return {React.ReactElement} ImageCroppingSettings component.
 */
export const ImageCroppingSettings = () => {
	const featureSettings = useSelect( ( select ) =>
		select( STORE_NAME ).getFeatureSettings()
	);
	const { setFeatureSettings } = useDispatch( STORE_NAME );

	return (
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
	);
};
