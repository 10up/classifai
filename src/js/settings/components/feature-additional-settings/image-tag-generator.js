/**
 * WordPress dependencies
 */
import { useSelect, useDispatch } from '@wordpress/data';
// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
import { SelectControl } from '@wordpress/components';
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
		<SettingsRow label={ __( 'Tag taxonomy', 'classifai' ) }>
			<SelectControl
				id="feature_image_tags_generator_tag_taxonomy"
				onChange={ ( value ) => {
					setFeatureSettings( {
						tag_taxonomy: value,
					} );
				} }
				value={ featureSettings.tag_taxonomy || 'classifai-image-tags' }
				options={ options }
				__nextHasNoMarginBottom
			/>
		</SettingsRow>
	);
};
