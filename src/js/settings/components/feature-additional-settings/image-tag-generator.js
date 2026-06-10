/**
 * WordPress dependencies
 */
import { useSelect, useDispatch } from '@wordpress/data';
import { SelectControl, RadioControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { SettingsRow } from '../settings-row';
import { STORE_NAME } from '../../data/store';
import { useFeatureContext } from '../feature-settings/context';
import { getFeature } from '../../utils/utils';

/**
 * Component for the Image Tag Generator feature settings.
 *
 * This component is used within the FeatureSettings component to allow users to configure the Image Tag Generator feature.
 *
 * @return {React.ReactElement} ImageTagGeneratorSettings component.
 */
export const ImageTagGeneratorSettings = () => {
	const { featureName } = useFeatureContext();
	const featureSettings = useSelect( ( select ) =>
		select( STORE_NAME ).getFeatureSettings()
	);
	const { setFeatureSettings } = useDispatch( STORE_NAME );
	const { taxonomies } = getFeature( featureName );

	const options = Object.keys( taxonomies || {} ).map( ( slug ) => {
		return {
			value: slug,
			label: taxonomies[ slug ],
		};
	} );
	return (
		<>
			<SettingsRow label={ __( 'Tag taxonomy', 'classifai' ) }>
				<SelectControl
					// eslint-disable-next-line no-restricted-syntax
					id="feature_image_tags_generator_tag_taxonomy"
					onChange={ ( value ) => {
						setFeatureSettings( {
							tag_taxonomy: value,
						} );
					} }
					value={
						featureSettings.tag_taxonomy || 'classifai-image-tags'
					}
					options={ options }
					__nextHasNoMarginBottom
					__next40pxDefaultSize
				/>
			</SettingsRow>
			<SettingsRow
				label={ __( 'Processing mode', 'classifai' ) }
				description={ __(
					'Choose how you want images to be processed. These can be processed automatically when each image is uploaded or can instead be triggered manually on each desired image. Note if set to automatic, you can still trigger the processing manually on individual images.',
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
		</>
	);
};
