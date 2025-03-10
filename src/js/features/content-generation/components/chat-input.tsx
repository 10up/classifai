import React from 'react';
import { TextareaControl, Button, Icon } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { keyboardReturn } from '@wordpress/icons';

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
		<div style={ { position: 'relative' } }>
			<TextareaControl
				__nextHasNoMarginBottom
				ref={ textareaRef }
				className="classifai-chat-input"
				placeholder={ placeholderText }
				value={ value }
				onChange={ onChange }
				onKeyDown={ onKeyDown }
				disabled={ isLoading }
				style={ {
					width: '100%',
					height: '80px',
					maxHeight: '200px',
					minHeight: '80px',
					borderRadius: '4px',
					border: '1px solid #ccc',
					padding: '10px',
					paddingBottom: '40px',
					resize: 'none',
					opacity: isLoading ? 0.7 : 1,
				} }
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
				style={ {
					position: 'absolute',
					bottom: '8px',
					right: '8px',
					paddingInline: '4px',
					paddingInlineStart: '6px',
				} }
				variant="primary"
				size="small"
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
