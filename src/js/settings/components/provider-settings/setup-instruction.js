/* global ClassifAI */
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

export const SetupInstruction = ( { provider } ) => {
	const { featureName } = useFeatureContext();
	const [ isOpen, setOpen ] = useState( false );
	const [ readmeContent, setReadmeContent ] = useState( '' );
	const [ instructionTitle, setInstructionTitle ] = useState( '' );
	const openModal = () => setOpen( true );
	const closeModal = () => setOpen( false );
	const feature = getFeature( featureName );

	useEffect( () => {
		setReadmeContent(
			ClassifAI.instruction[ featureName ][ provider ] ||
				'No Instruction found'
		);
		setInstructionTitle( feature.providers[ provider ] || '' );
	}, [ provider, featureName, feature.providers ] );

	const instruction = marked.parse( readmeContent );

	return (
		<>
			<Button
				className="settings-info-button"
				showTooltip
				variant="tertiary"
				onClick={ openModal }
				size="small"
				icon={ 'editor-help' }
				label={ __( 'AI Configuration Instruction', 'classifai' ) }
			></Button>
			{ isOpen && (
				<Modal
					size="large"
					className="settings-instruction-modal"
					title={ instructionTitle }
					onRequestClose={ closeModal }
					isDismissible={ true }
				>
					<div dangerouslySetInnerHTML={ { __html: instruction } } />
				</Modal>
			) }
		</>
	);
};
