/**
 * External dependencies
 */
import type { CSSProperties } from 'react';
import React, { useEffect, useRef } from 'react';
import { motion, AnimatePresence } from 'motion/react';

/**
 * Internal dependencies
 */
import { UserMessage } from './user-message';
import { AIResponse } from './ai-response';
import { LoadingResponse } from './loading-response';
import { ChatActionButtons } from './chat-action-buttons';
import type { ConversationEntry } from './types';

// Define style objects outside of JSX
const responseContainerStyles: CSSProperties = {
	display: 'flex',
	justifyContent: 'flex-start',
	marginBottom: '8px',
	alignItems: 'flex-start',
};

const responseContentStyles: CSSProperties = {
	display: 'flex',
	flexDirection: 'column',
	maxWidth: '95%',
};

/**
 * Props for the ConversationItem component
 */
export interface ConversationItemProps {
	entry: ConversationEntry;
	onStartOver: () => void;
	onInsertContent: ( content: string ) => void;
}

/**
 * ConversationItem component
 *
 * Displays a single conversation exchange (user prompt and AI response)
 *
 * @param {ConversationItemProps} props Component props
 * @return {React.ReactElement} A conversation exchange item
 */
export const ConversationItem: React.FC< ConversationItemProps > = ( {
	entry,
	onStartOver,
	onInsertContent,
} ) => {
	const hasCompletion = entry.completion !== null;
	const containerRef = useRef< HTMLDivElement >( null );

	useEffect( () => {
		if ( containerRef.current ) {
			containerRef.current.scrollIntoView( {
				behavior: 'smooth',
				block: 'nearest',
			} );
		}
	}, [] );

	return (
		<div>
			<AnimatePresence>
				<motion.div
					initial={ { opacity: 0 } }
					animate={ { opacity: 1 } }
					exit={ { opacity: 0 } }
				>
					<UserMessage message={ entry.prompt } />
				</motion.div>
				{ hasCompletion ? (
					<motion.div
						initial={ { opacity: 0 } }
						animate={ { opacity: 1 } }
						exit={ { opacity: 0 } }
						style={ responseContainerStyles }
					>
						<div style={ responseContentStyles }>
							<AIResponse content={ entry.completion || '' } />
							<ChatActionButtons
								onStartOver={ onStartOver }
								onInsertContent={ onInsertContent }
								content={ entry.completion || '' }
							/>
						</div>
					</motion.div>
				) : (
					<motion.div
						initial={ { opacity: 0 } }
						animate={ { opacity: 1 } }
						exit={ { opacity: 0 } }
						ref={ containerRef }
					>
						<LoadingResponse />
					</motion.div>
				) }
			</AnimatePresence>
		</div>
	);
};
