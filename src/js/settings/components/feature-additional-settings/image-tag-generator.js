import { useSelect, useDispatch } from '@wordpress/data';
// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
import { SelectControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { SettingsRow } from '../settings-row';
import { STORE_NAME } from '../../data/store';

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
