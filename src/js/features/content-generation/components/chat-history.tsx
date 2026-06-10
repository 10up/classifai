/**
 * External dependencies
 */
import type { CSSProperties } from 'react';
import React from 'react';

/**
 * Internal dependencies
 */
import { ConversationItem } from './conversation-item';
import type { ConversationEntry } from './types';

// Define style object outside of JSX
const historyContainerStyles: CSSProperties = {
	marginBottom: '10px',
	maxHeight: '400px',
	overflowY: 'auto',
	padding: '10px',
	borderRadius: '8px',
	border: '1px solid #ccc',
	flex: '1',
};

/**
 * Props for the ChatHistory component
 */
export interface ChatHistoryProps {
	conversation: ConversationEntry[];
	onStartOver: () => void;
	onInsertContent: ( content: string ) => void;
}

/**
 * ChatHistory component
 *
 * Displays the conversation history between the user and AI
 *
 * @param {ChatHistoryProps} props Component props
 * @return {React.ReactElement|null} Chat history container or null if no conversation
 */
export const ChatHistory: React.FC< ChatHistoryProps > = ( {
	conversation,
	onStartOver,
	onInsertContent,
} ) => {
	if ( conversation.length === 0 ) {
		return null;
	}

	return (
		<div
			className="classifai-chat-history"
			style={ historyContainerStyles }
		>
			{ conversation.map( ( entry, index ) => (
				<ConversationItem
					key={ index }
					entry={ entry }
					onStartOver={ onStartOver }
					onInsertContent={ onInsertContent }
				/>
			) ) }
		</div>
	);
};
