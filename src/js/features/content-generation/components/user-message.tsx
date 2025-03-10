import React from 'react';
import { decodeEntities } from '@wordpress/html-entities';

/**
 * Props for the UserMessage component
 */
export interface UserMessageProps {
	message: string;
}

/**
 * UserMessage component
 *
 * Displays a message from the user in the chat UI
 *
 * @param {UserMessageProps} props Component props
 * @return {React.ReactElement} Message bubble for user text
 */
export const UserMessage: React.FC< UserMessageProps > = ( { message } ) => {
	return (
		<div
			style={ {
				display: 'flex',
				justifyContent: 'flex-end',
				marginBottom: '8px',
			} }
		>
			<div
				style={ {
					backgroundColor: '#e0f2ff',
					padding: '10px 14px',
					borderRadius: '18px 18px 0 18px',
					maxWidth: '85%',
					boxShadow: '0 1px 2px rgba(0,0,0,0.1)',
					position: 'relative',
					color: '#333',
					fontWeight: '400',
					whiteSpace: 'pre-wrap',
					wordBreak: 'break-word',
				} }
			>
				{ decodeEntities( message ) }
			</div>
		</div>
	);
};
