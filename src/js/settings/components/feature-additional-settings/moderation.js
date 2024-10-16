import { useSelect, useDispatch } from '@wordpress/data';
// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
import { CheckboxControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { SettingsRow } from '../settings-row';
import { STORE_NAME } from '../../data/store';

export const ModerationSettings = () => {
	const featureSettings = useSelect( ( select ) =>
		select( STORE_NAME ).getFeatureSettings()
	);
	const { setFeatureSettings } = useDispatch( STORE_NAME );
	const contentTypes = {
		comments: __( 'Comments', 'classifai' ),
	};

	return (
		<SettingsRow
			label={ __( 'Content to moderate', 'classifai' ) }
			description={ __(
				'Choose what type of content to moderate.',
				'classifai'
			) }
			className="settings-moderation-content-types"
		>
			{ Object.keys( contentTypes ).map( ( contentType ) => {
				return (
					<CheckboxControl
						id={ contentType }
						key={ contentType }
						checked={
							featureSettings.content_types?.[ contentType ] ===
							contentType
						}
						label={ contentTypes[ contentType ] }
						onChange={ ( value ) => {
							setFeatureSettings( {
								content_types: {
									...featureSettings.content_types,
									[ contentType ]: value ? contentType : '0',
								},
							} );
						} }
					/>
				);
			} ) }
		</SettingsRow>
	);
};
