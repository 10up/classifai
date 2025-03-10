import React from 'react';
import { decodeEntities } from '@wordpress/html-entities';

/**
 * Props for the AIResponse component
 */
export interface AIResponseProps {
	content: string;
}

/**
 * AIResponse component
 *
 * Displays the AI's response in the chat UI
 *
 * @param {AIResponseProps} props Component props
 * @return {React.ReactElement} AI response container
 */
export const AIResponse: React.FC< AIResponseProps > = ( { content } ) => {
	return (
		<div
			style={ {
				padding: '10px 0px',
				color: '#333',
				wordBreak: 'break-word',
			} }
			dangerouslySetInnerHTML={ { __html: decodeEntities( content ) } }
		/>
	);
};
