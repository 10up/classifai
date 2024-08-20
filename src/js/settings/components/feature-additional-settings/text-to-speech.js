import { useSelect, useDispatch } from '@wordpress/data';
// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
import { CheckboxControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { SettingsRow } from '../settings-row';
import { STORE_NAME } from '../../data/store';
import { usePostTypes } from '../../utils/utils';

export const TextToSpeechSettings = () => {
	const featureSettings = useSelect( ( select ) =>
		select( STORE_NAME ).getFeatureSettings()
	);
	const { setFeatureSettings } = useDispatch( STORE_NAME );
	const { postTypesSelectOptions } = usePostTypes();

	return (
		<SettingsRow
			label={ __( 'Allowed post types', 'classifai' ) }
			description={ __(
				'Choose which post types support this feature.',
				'classifai'
			) }
		>
			{ postTypesSelectOptions.map( ( option ) => {
				const { value: key, label } = option;
				return (
					<CheckboxControl
						key={ key }
						checked={ featureSettings.post_types?.[ key ] === key }
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
	);
};
