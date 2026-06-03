/**
 * WordPress dependencies
 */
import { registerPlugin } from '@wordpress/plugins';
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { registerCoreBlocks } from '@wordpress/block-library';
import { useSelect, useDispatch } from '@wordpress/data';
import { store as coreStore } from '@wordpress/blocks';
import { Fill, Flex, FlexItem, Icon } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { SettingsRow } from '../../settings/components/settings-row';

// To access core/query details.
registerCoreBlocks();

registerPlugin( 'classifai-plugin-recommended-content-feature-fields', {
	render: AdditionalFeatureFields,
	scope: 'feature-recommended-content',
} );

function AdditionalFeatureFields() {
	const featureSettings = useSelect( ( select ) =>
		select( 'classifai-settings' ).getFeatureSettings()
	);

	const { setFeatureSettings } = useDispatch( 'classifai-settings' );

	const defaultTemplate = featureSettings.default_template;
	const blockVariations = useSelect( ( select ) =>
		select( coreStore ).getBlockVariations( 'core/query' )
	);
	const [ focusedTemplate, setFocusedTemplate ] = useState(
		featureSettings.defaultTemplate
	);
	const [ isTemplateInFocus, setIsTemplateInFocus ] = useState( false );

	const iconWrapperStyle = {
		border: '1px solid #e0e0e0',
		width: '120px',
		borderRadius: '6px',
		cursor: 'pointer',
	};

	return (
		<Fill name="ClassifAIFeatureSettings">
			<SettingsRow label={ __( 'Default template', 'classifai' ) }>
				<Flex align="normal" justify="start" gap={ 2 }>
					{ blockVariations.map( ( variation, index ) => (
						<FlexItem
							key={ index }
							role="button"
							tabIndex="0"
							onFocus={ () => {
								setFocusedTemplate( variation.name );
								setIsTemplateInFocus( true );
							} }
							onBlur={ () => setIsTemplateInFocus( false ) }
							onKeyDown={ ( e ) => {
								if (
									'Space' === e.code ||
									'Enter' === e.code
								) {
									e.preventDefault();
									setFeatureSettings( {
										default_template: variation.name,
									} );
								}
							} }
							style={ {
								...iconWrapperStyle,
								borderColor:
									defaultTemplate === variation.name
										? 'var(--wp-admin-theme-color)'
										: '#e0e0e0',
								borderWidth:
									defaultTemplate === variation.name && '1px',
								backgroundColor:
									defaultTemplate === variation.name &&
									'color-mix(in srgb, var(--wp-admin-theme-color) 10%, transparent)',
								outline:
									isTemplateInFocus &&
									focusedTemplate === variation.name &&
									'2px solid var(--wp-admin-theme-color)',
							} }
							onClick={ () =>
								setFeatureSettings( {
									default_template: variation.name,
								} )
							}
						>
							<Icon
								icon={ variation.icon }
								size={ 80 }
								{ ...( defaultTemplate === variation.name && {
									fill: 'var(--wp-admin-theme-color)',
								} ) }
							/>
							<span
								style={ {
									display: 'block',
									padding: '0 10px 10px 10px',
								} }
							>
								{ variation.title }
							</span>
						</FlexItem>
					) ) }
				</Flex>
			</SettingsRow>
		</Fill>
	);
}
