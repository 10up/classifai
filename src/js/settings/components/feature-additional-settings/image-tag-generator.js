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

/**
 * Component for the Image Tag Generator feature settings.
 *
 * This component is used within the FeatureSettings component to allow users to configure the Image Tag Generator feature.
 *
 * @return {React.ReactElement} ImageTagGeneratorSettings component.
 */
export const ImageTagGeneratorSettings = () => {
	const featureSettings = useSelect( ( select ) =>
		select( STORE_NAME ).getFeatureSettings()
	);
	const { setFeatureSettings } = useDispatch( STORE_NAME );

	const attachmentTaxonomies = useSelect( ( select ) => {
		const { getTaxonomies } = select( 'core' );
		return getTaxonomies( { type: 'attachment' } ) || [];
	}, [] );

	const options = attachmentTaxonomies.map( ( taxonomy ) => {
		return {
			value: taxonomy.slug,
			label: taxonomy.labels.name,
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
			/>
		</SettingsRow>
	);
};
