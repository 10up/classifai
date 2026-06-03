/**
 * External dependencies
 */
import type { CSSProperties } from 'react';
import React, { useState } from 'react';

/**
 * WordPress dependencies
 */
import { Button, Icon } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { backup, check, copySmall } from '@wordpress/icons';
import { useCopyToClipboard } from '@wordpress/compose';

// Define style objects outside of JSX
const actionsContainerStyles: CSSProperties = {
	display: 'flex',
	justifyContent: 'flex-end',
	gap: '2px',
	marginTop: '0',
	marginBottom: '8px',
	flexWrap: 'wrap',
};

const buttonStyles: CSSProperties = {
	paddingInlineEnd: '4px',
};

/**
 * Props for the ChatActionButtons component
 */
export interface ChatActionButtonsProps {
	onStartOver: () => void;
	onInsertContent: ( content: string ) => void;
	content: string;
}

/**
 * ChatActionButtons component
 *
 * Action buttons for chat responses (Start Over, Copy, Insert)
 *
 * @param {ChatActionButtonsProps} props Component props
 * @return {React.ReactElement} Action buttons for conversation
 */
export const ChatActionButtons: React.FC< ChatActionButtonsProps > = ( {
	onStartOver,
	onInsertContent,
	content,
} ) => {
	const [ hasCopied, setHasCopied ] = useState< boolean >( false );

	const onSuccessfulCopy = (): void => {
		setHasCopied( true );
		setTimeout( () => {
			setHasCopied( false );
		}, 1500 );
	};
	const copyRef = useCopyToClipboard( content, onSuccessfulCopy );

	return (
		<div style={ actionsContainerStyles }>
			<Button
				variant="tertiary"
				isDestructive
				onClick={ onStartOver }
				icon={
					<Icon
						icon={ backup }
						viewBox="0 0 24 24"
						height={ 16 }
						width={ 16 }
					/>
				}
				iconPosition="right"
				style={ buttonStyles }
			>
				{ __( 'Start Over', 'classifai' ) }
			</Button>
			<Button
				ref={ copyRef }
				variant="tertiary"
				icon={
					hasCopied ? (
						<Icon
							icon={ check }
							viewBox="0 0 24 24"
							height={ 16 }
							width={ 16 }
						/>
					) : (
						<Icon
							icon={ copySmall }
							viewBox="0 0 24 24"
							height={ 16 }
							width={ 16 }
						/>
					)
				}
				disabled={ hasCopied }
				iconPosition="right"
				style={ buttonStyles }
			>
				{ hasCopied
					? __( 'Copied!', 'classifai' )
					: __( 'Copy', 'classifai' ) }
			</Button>
			<Button
				variant="tertiary"
				onClick={ () => onInsertContent( content ) }
				icon={
					<Icon
						icon={ check }
						viewBox="0 0 24 24"
						height={ 20 }
						width={ 20 }
					/>
				}
				iconPosition="right"
				style={ buttonStyles }
			>
				{ __( 'Insert', 'classifai' ) }
			</Button>
		</div>
	);
};
