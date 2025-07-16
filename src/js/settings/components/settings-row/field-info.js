/**
 * WordPress dependencies
 */
import { Button, Modal } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { useFeatureContext } from '../feature-settings/context';

/**
 * External Dependencies
 */
import { marked } from 'marked';
import { useEffect } from 'react';
import { getFeature } from '../../utils/utils';

export const FieldInfo = ( props ) => {
	const { featureName } = useFeatureContext();
	const [ isOpen, setOpen ] = useState( false );
	const [ icon, setIcon ] = useState( 'info' );
	const [ fieldInfoContent, setFieldInfoContent ] = useState( '' );
	const [ infoTitle, setInfoTitle ] = useState( '' );
	const openModal = () => setOpen( true );
	const closeModal = () => setOpen( false );
	const feature = getFeature( featureName );

	useEffect( () => {
		switch ( props.fieldInfo.type ) {
			case 'instruction':
				setFieldInfoContent(
					feature.providers_data[ props.fieldInfo.type ][
						props.fieldInfo.provider
					] || __( 'No info found.', 'classifai' )
				);
				setInfoTitle(
					`${
						feature.label +
						' - ' +
						feature.providers[ props.fieldInfo.provider ]
					}` || ''
				);
				setIcon( 'editor-help' );
				break;

			default:
				break;
		}
	}, [
		props.fieldInfo,
		feature.providers,
		feature.providers_data.instructions,
		feature.label,
	] );

	return (
		<>
			<Button
				className="settings-info-button"
				showTooltip
				variant="tertiary"
				onClick={ openModal }
				size="small"
				icon={ icon }
				label={ __( 'AI Configuration Instruction', 'classifai' ) }
			></Button>
			{ isOpen && (
				<Modal
					size="large"
					className="settings-info-modal"
					title={ infoTitle }
					onRequestClose={ closeModal }
					isDismissible={ true }
				>
					<div
						dangerouslySetInnerHTML={ {
							__html: marked.parse( fieldInfoContent ),
						} }
					/>
				</Modal>
			) }
		</>
	);
};
