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

/**
 * FieldInfo Component
 *
 * This component displays a small helper icon button (e.g., an "info" icon) next to a field or label.
 * When clicked, it opens a modal displaying detailed information specific to the field.
 *
 * @summary Shows a helper icon button that opens a modal with detailed information.
 *
 * @param {Object} props                    Component props.
 * @param {Object} props.fieldInfo          Field information object.
 * @param {string} props.fieldInfo.type     Type of information to display (e.g., 'instruction').
 * @param {string} props.fieldInfo.provider Provider type to look up data.
 *
 * @example
 * <FieldInfo
 *   fieldInfo={{
 *     type: 'instruction',
 *     provider: 'gemini'
 *   }}
 * />
 *
 * @return {JSX.Element} The rendered FieldInfo button and modal.
 *
 * Notes:
 * - The modal content is loaded dynamically from feature context data.
 * - The icon can be customized based on info type.
 * - Uses `dangerouslySetInnerHTML` to render content.
 */
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
		feature.providers_data,
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
				label={ infoTitle }
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
