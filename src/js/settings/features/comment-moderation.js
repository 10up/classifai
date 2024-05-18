/**
 * WordPress dependencies
 */
import { CheckboxControl, Fill } from '@wordpress/components';
import { registerPlugin } from '@wordpress/plugins';
import { __ } from '@wordpress/i18n';
import { useSelect, useDispatch } from '@wordpress/data';

const ModerationSettings = () => {
	const featureSettings = useSelect( ( select ) =>
		select( 'classifai-settings' ).getFeatureSettings()
	);
	const { setFeatureSettings } = useDispatch( 'classifai-settings' );
	const contentTypes = {
		comments: __( 'Comments', 'classifai' ),
	};
	return (
		<>
			<Fill name="ClassifAIFeatureSettings">
				<div className="classifai-settings-input-control">
					<div className="settings-label">
						{ __( 'Content to moderate', 'classifai' ) }
					</div>
					{ Object.keys( contentTypes ).map( ( contentType ) => {
						return (
							<CheckboxControl
								key={ contentType }
								checked={
									featureSettings.content_types?.[
										contentType
									] === contentType
								}
								label={ contentTypes[ contentType ] }
								onChange={ ( value ) => {
									setFeatureSettings( {
										content_types: {
											...featureSettings.content_types,
											[ contentType ]: value
												? contentType
												: '0',
										},
									} );
								} }
							/>
						);
					} ) }
					<span className="description classifai-input-description">
						{ __(
							'Choose what type of content to moderate.',
							'classifai'
						) }
					</span>
				</div>
			</Fill>
		</>
	);
};

registerPlugin( 'classifai-feature-title-generation', {
	scope: 'feature-moderation',
	render: ModerationSettings,
} );
