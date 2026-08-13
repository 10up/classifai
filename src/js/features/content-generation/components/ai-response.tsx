/**
 * External dependencies
 */
import type { CSSProperties } from 'react';
import React from 'react';

/**
 * WordPress dependencies
 */
import { decodeEntities } from '@wordpress/html-entities';

/**
 * Props for the AIResponse component
 */
export interface AIResponseProps {
	content: string;
}

// Define style object outside of JSX
const contentStyles: CSSProperties = {
	padding: '10px 0px',
	color: '#333',
	wordBreak: 'break-word',
};

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
			style={ contentStyles }
			dangerouslySetInnerHTML={ { __html: decodeEntities( content ) } }
		/>
	);
};
