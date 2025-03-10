import React from 'react';
import { ConversationItem } from './conversation-item';
import { ConversationEntry } from './types';

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
			style={ {
				marginBottom: '10px',
				maxHeight: '400px',
				overflowY: 'auto',
				paddingRight: '5px',
				flex: '1',
			} }
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
