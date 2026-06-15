/**
 * External dependencies
 */
import type { CSSProperties } from 'react';
import React from 'react';

/**
 * WordPress dependencies
 */
import { TextareaControl, Button, Icon } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { keyboardReturn } from '@wordpress/icons';

// Define style objects outside of JSX
const containerStyles: CSSProperties = {
	position: 'relative',
};

const getTextareaStyles = ( isLoading: boolean ): CSSProperties => ( {
	width: '100%',
	height: '80px',
	maxHeight: '200px',
	minHeight: '100px',
	borderRadius: '4px',
	border: '1px solid #ccc',
	padding: '10px 75px 10px 10px',
	resize: 'none',
	opacity: isLoading ? 0.7 : 1,
} );

const buttonStyles: CSSProperties = {
	position: 'absolute',
	bottom: '8px',
	right: '8px',
	paddingInline: '8px',
	paddingInlineStart: '6px',
};

/**
 * Props for the ChatInput component
 */
export interface ChatInputProps {
	value: string;
	onChange: ( value: string ) => void;
	onKeyDown: ( event: React.KeyboardEvent< HTMLTextAreaElement > ) => void;
	isLoading: boolean;
	placeholderText: string;
	textareaRef: React.RefObject< HTMLTextAreaElement >;
}

/**
 * ChatInput component
 *
 * Input area for user to type messages to the AI
 *
 * @param {ChatInputProps} props Component props
 * @return {React.ReactElement} Chat input component
 */
export const ChatInput: React.FC< ChatInputProps > = ( {
	value,
	onChange,
	onKeyDown,
	isLoading,
	placeholderText,
	textareaRef,
} ) => {
	return (
		<div style={ containerStyles }>
			<TextareaControl
				__nextHasNoMarginBottom
				ref={ textareaRef }
				className="classifai-chat-input"
				placeholder={ placeholderText }
				value={ value }
				onChange={ onChange }
				onKeyDown={ onKeyDown }
				disabled={ isLoading }
				style={ getTextareaStyles( isLoading ) }
			/>
			<Button
				icon={
					<Icon
						icon={ keyboardReturn }
						viewBox="0 0 24 24"
						height={ 16 }
						width={ 16 }
					/>
				}
				iconPosition="right"
				type="submit"
				style={ buttonStyles }
				variant="primary"
				disabled={ isLoading || ! value }
				isBusy={ isLoading }
			>
				{ isLoading
					? __( 'Sending…', 'classifai' )
					: __( 'Send', 'classifai' ) }
			</Button>
		</div>
	);
};
