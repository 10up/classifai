/**
 * External dependencies
 */
import type { CSSProperties } from 'react';
import React, { useEffect, useState, useRef, useLayoutEffect } from 'react';
import { motion, AnimatePresence } from 'motion/react';

/**
 * WordPress dependencies
 */
import apiFetch from '@wordpress/api-fetch';
import { select, dispatch } from '@wordpress/data';
import { pasteHandler, parse } from '@wordpress/blocks';
import { store as editorStore } from '@wordpress/editor';
import { store as blockEditorStore } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import { decodeEntities } from '@wordpress/html-entities';

/**
 * Internal dependencies
 */
import { SparkleIcon } from './sparkle-icon';
import { ChatHistory } from './chat-history';
import { ErrorMessage } from './error-message';
import { ChatInput } from './chat-input';
import type { ConversationEntry } from './types';
import { renderBlockTreeToMarkup } from '../utils/render-block-tree';

// Define style objects outside of JSX
const chatContainerStyles: CSSProperties = {
	position: 'absolute',
	bottom: '20px',
	right: '20px',
	zIndex: '1000',
};

const chatUIStyles: CSSProperties = {
	width: '500px',
	maxHeight: '700px',
	backgroundColor: 'white',
	padding: '14px',
	boxShadow:
		'0px 2px 3px 0px rgba(0, 0, 0, 0.05), 0px 4px 5px 0px rgba(0, 0, 0, 0.04), 0px 4px 5px 0px rgba(0, 0, 0, 0.03), 0px 16px 16px 0px rgba(0, 0, 0, 0.02)',
	borderRadius: '8px',
	border: '1px solid #e0e0e0',
	display: 'flex',
	flexDirection: 'column',
};

const chatContentStyles: CSSProperties = {
	display: 'flex',
	flexDirection: 'column',
	height: '100%',
	maxHeight: '700px',
	overflow: 'clip',
	padding: '2px',
};

const chatTitleStyles: CSSProperties = {
	marginBottom: '12px',
	fontWeight: 'bold',
	fontSize: '16px',
};

const chatButtonStyles: CSSProperties = {
	display: 'flex',
	justifyContent: 'center',
	alignItems: 'center',
	boxShadow: '0 0 10px rgba(0, 0, 0, 0.1)',
	padding: '0',
	width: '48px',
	height: '48px',
	borderRadius: '999px',
	minWidth: 'unset',
	minHeight: 'unset',
	color: 'white',
	border: 'none',
	cursor: 'pointer',
	backgroundColor:
		'var(--wp-components-color-accent-darker-10,var(--wp-admin-theme-color-darker-10,#2145e6))',
};

/**
 * ChatUI component
 *
 * Main component for the ClassifAI chat interface
 *
 * @return {React.ReactElement} The complete chat UI
 */
export const ChatUI: React.FC = () => {
	const [ inputValue, setInputValue ] = useState< string >( '' );
	const [ isExpanded, setIsExpanded ] = useState< boolean >( false );
	const [ isLoading, setIsLoading ] = useState< boolean >( false );
	const [ error, setError ] = useState< string | false >( false );
	const [ conversation, setConversation ] = useState< ConversationEntry[] >(
		[]
	);
	const chatContainerRef = useRef< HTMLDivElement >( null );
	const textareaRef = useRef< HTMLTextAreaElement >( null );

	// Function to handle clicks outside the chat UI
	const handleClickOutside = ( event: MouseEvent ): void => {
		if (
			chatContainerRef.current &&
			! chatContainerRef.current.contains( event.target as Node )
		) {
			setIsExpanded( false );
		}
	};

	useLayoutEffect( () => {
		if ( textareaRef.current && isExpanded ) {
			textareaRef.current.focus();
		}
	}, [ isExpanded ] );

	// Add event listeners for clicks outside
	useEffect( () => {
		// Only add event listeners when the chat UI is expanded
		if ( isExpanded ) {
			// Add event listener to main document
			document.addEventListener( 'mousedown', handleClickOutside );

			// Add event listeners to all iframes
			const iframes = document.querySelectorAll( 'iframe' );
			iframes.forEach( ( iframe ) => {
				try {
					if ( iframe.contentDocument ) {
						iframe.contentDocument.addEventListener(
							'mousedown',
							handleClickOutside
						);
					}
				} catch {
					// Cross-origin iframe access error - can't add listener
					// Silently fail for cross-origin iframes
				}
			} );
		}

		return () => {
			// Remove event listener from main document
			document.removeEventListener( 'mousedown', handleClickOutside );

			// Remove event listeners from all iframes
			const iframes = document.querySelectorAll( 'iframe' );
			iframes.forEach( ( iframe ) => {
				try {
					if ( iframe.contentDocument ) {
						iframe.contentDocument.removeEventListener(
							'mousedown',
							handleClickOutside
						);
					}
				} catch {
					// Cross-origin iframe access error - can't remove listener
					// Silently fail for cross-origin iframes
				}
			} );
		};
	}, [ isExpanded ] );

	const handleSubmit = ( event: React.FormEvent ): void => {
		event.preventDefault();
		if ( ! inputValue.trim() ) {
			return;
		}

		const userMessage = inputValue;
		setInputValue( '' );
		setError( false );

		// Get post data
		const postId = select( editorStore ).getCurrentPostId();
		const title = select( editorStore ).getEditedPostAttribute( 'title' );

		// Update conversation immediately with user message
		const updatedConversation: ConversationEntry[] = [
			...conversation,
			{
				prompt: userMessage,
				completion: null, // Will be filled in once API response is received
			},
		];
		setConversation( updatedConversation );

		// Call API
		setIsLoading( true );
		apiFetch( {
			path: '/classifai/v1/create-content',
			method: 'POST',
			data: {
				id: postId,
				summary: userMessage,
				title,
				conversation: updatedConversation.slice( 0, -1 ), // Exclude the message we just added
			},
		} ).then(
			( res: unknown ) => {
				// Update conversation with response
				setConversation( [
					...updatedConversation.slice( 0, -1 ),
					{
						prompt: userMessage,
						completion: res as string,
					},
				] );
				setError( false );
				setIsLoading( false );
			},
			( err: { message?: string } ) => {
				setError( err?.message || 'An error occurred' );
				setConversation( [] );
				setIsLoading( false );
			}
		);
	};

	const handleKeyDown = (
		event: React.KeyboardEvent< HTMLTextAreaElement >
	): void => {
		// Submit on Enter key, but not when Shift is pressed
		if ( event.key === 'Enter' && ! event.shiftKey ) {
			event.preventDefault();
			handleSubmit( event as unknown as React.FormEvent );
		}
		// Shift+Enter will add a new line by default (no action needed)
	};

	const toggleChatUI = (): void => {
		setIsExpanded( ! isExpanded );
	};

	const startOver = (): void => {
		setConversation( [] );
		setError( false );
	};

	const insertContent = ( content: string ): void => {
		dispatch( editorStore )
			.editPost( {
				content: '',
			} )
			.then( () => {
				// The response is a JSON BlockTree; render it to block markup.
				const markup = renderBlockTreeToMarkup( content );

				let blocks;
				if ( markup !== null ) {
					blocks = parse( markup );
				} else {
					// Fallback: treat the response as raw HTML/block markup.
					const contentWithEntities = decodeEntities( content );
					if ( contentWithEntities.includes( '<!-- wp:' ) ) {
						blocks = parse( contentWithEntities );
					} else {
						blocks = pasteHandler( {
							HTML: contentWithEntities,
							plainText: contentWithEntities,
							mode: 'BLOCKS',
						} );
					}
				}
				dispatch( blockEditorStore ).insertBlocks( blocks );

				// Close the chat UI after inserting content
				setIsExpanded( false );
				// Clear the conversation
				setConversation( [] );
			} );
	};

	// Determine placeholder text based on conversation state
	const getPlaceholderText = (): string => {
		const hasActiveConversation =
			conversation.length > 0 &&
			conversation[ conversation.length - 1 ]?.completion !== null;

		if ( hasActiveConversation ) {
			return __( 'Request changes to the content…', 'classifai' );
		}

		return __( 'Add a summary of your article', 'classifai' );
	};

	return (
		<div
			className="classifai-chat-container"
			style={ chatContainerStyles }
			ref={ chatContainerRef }
		>
			<AnimatePresence>
				{ isExpanded ? (
					<motion.div
						layoutId="chat-container"
						initial={ { opacity: 0.9 } }
						animate={ { opacity: 1 } }
						exit={ { opacity: 0 } }
						transition={ { duration: 0.3, type: 'spring' } }
						className="classifai-chat-ui"
						style={ chatUIStyles }
					>
						<motion.div
							initial={ { opacity: 0, y: 10 } }
							animate={ { opacity: 1, y: 0 } }
							transition={ { delay: 0.1 } }
							style={ chatContentStyles }
						>
							<div style={ chatTitleStyles }>
								{ __( 'Generate content', 'classifai' ) }
							</div>
							<form onSubmit={ handleSubmit }>
								{ ! error && (
									<ChatHistory
										conversation={ conversation }
										onStartOver={ startOver }
										onInsertContent={ insertContent }
									/>
								) }
								<ErrorMessage error={ error } />
								<ChatInput
									textareaRef={ textareaRef }
									value={ inputValue }
									onChange={ ( value ) =>
										setInputValue( value )
									}
									onKeyDown={ handleKeyDown }
									isLoading={ isLoading }
									placeholderText={ getPlaceholderText() }
								/>
							</form>
						</motion.div>
					</motion.div>
				) : (
					<motion.button
						onClick={ toggleChatUI }
						layoutId="chat-container"
						className="classifai-chat-button"
						initial={ { opacity: 0.9 } }
						animate={ { opacity: 1 } }
						exit={ { opacity: 0.0 } }
						transition={ { type: 'spring', duration: 0.3 } }
						style={ chatButtonStyles }
						aria-label={ __(
							'Open content generation assistant',
							'classifai'
						) }
						whileHover={ {
							backgroundColor:
								'var(--wp-components-color-accent-darker-10,var(--wp-admin-theme-color-darker-10,#2145e6))',
							scale: 1.05,
						} }
					>
						<SparkleIcon />
					</motion.button>
				) }
			</AnimatePresence>
		</div>
	);
};
