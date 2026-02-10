import React, { useEffect, useState, useRef, useLayoutEffect } from 'react';
import apiFetch from '@wordpress/api-fetch';
import { select, dispatch } from '@wordpress/data';
import { pasteHandler, parse } from '@wordpress/blocks';
import { store as editorStore } from '@wordpress/editor';
import { store as blockEditorStore } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import { Fill } from '@wordpress/components';
import { decodeEntities } from '@wordpress/html-entities';
import { addFilter } from '@wordpress/hooks';

// Import our custom components
import { ChatHistory } from './chat-history';
import { ErrorMessage } from './error-message';
import { ChatInput } from './chat-input';
import { ConversationEntry } from './types';

const chatTabSlug = 'classifai-content-generation';

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
				} catch ( e ) {
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
				} catch ( e ) {
					// Cross-origin iframe access error - can't remove listener
					// Silently fail for cross-origin iframes
				}
			} );
		};
	}, [ isExpanded ] );

	// Handle quick action option selection
	// TODO: Look to fully support this in the future.
	// eslint-disable-next-line @typescript-eslint/no-unused-vars
	const handleOptionSelect = ( option: string ): void => {
		let prompt = '';
		const selectedContent = select( editorStore ).getEditedPostContent();

		switch ( option ) {
			case 'proofread':
				prompt = `Proofread the following content and correct any grammar, spelling, or punctuation errors:\n\n${ selectedContent }`;
				break;
			case 'rewrite':
				// If rewrite is clicked from the initial view, we handle it through the QuickActionOptions component
				// which will expand and show all options rather than starting a conversation
				return;
			case 'rewrite-execute':
				// This is when rewrite is clicked from the expanded options view
				prompt = `Rewrite the following content to improve clarity and flow:\n\n${ selectedContent }`;
				break;
			case 'tone-friendly':
				prompt = `Rewrite the following content using a friendly, conversational tone:\n\n${ selectedContent }`;
				break;
			case 'tone-professional':
				prompt = `Rewrite the following content using a professional, formal tone:\n\n${ selectedContent }`;
				break;
			case 'tone-concise':
				prompt = `Rewrite the following content to be more concise and direct:\n\n${ selectedContent }`;
				break;
			case 'summary':
				prompt = `Create a summary of the following content:\n\n${ selectedContent }`;
				break;
			case 'key-points':
				prompt = `Extract the key points from the following content:\n\n${ selectedContent }`;
				break;
			case 'table':
				prompt = `Convert the following content into a well-structured table:\n\n${ selectedContent }`;
				break;
			case 'list':
				prompt = `Convert the following content into a bulleted list:\n\n${ selectedContent }`;
				break;
			case 'compose':
				prompt = `Write a blog post about: `;
				setInputValue( prompt );
				return;
			case 'custom':
				// Just focus the input field
				return;
			default:
				return;
		}

		// Auto-submit the prompt
		setInputValue( '' );

		// Get post data
		const postId = select( editorStore ).getCurrentPostId();
		const title = select( editorStore ).getEditedPostAttribute( 'title' );

		// Update conversation immediately with user message
		const updatedConversation: ConversationEntry[] = [
			...conversation,
			{
				prompt,
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
				summary: prompt,
				title,
				conversation: updatedConversation.slice( 0, -1 ),
			},
		} ).then(
			( res: unknown ) => {
				// Update conversation with response
				setConversation( [
					...updatedConversation.slice( 0, -1 ),
					{
						prompt,
						completion: res as string,
					},
				] );
				setError( false );
				setIsLoading( false );
			},
			( err: { message?: string } ) => {
				setError( err?.message || 'An error occurred' );
				setIsLoading( false );
			}
		);
	};

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
				const contentWithEntities = decodeEntities( content );

				const containsBlockMarkup =
					contentWithEntities.includes( '<!-- wp:' );

				let blocks;
				if ( containsBlockMarkup ) {
					blocks = parse( contentWithEntities );
				} else {
					blocks = pasteHandler( {
						HTML: contentWithEntities,
						plainText: contentWithEntities,
						mode: 'BLOCKS',
					} );
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
			conversation[ conversation.length - 1 ].completion !== null;

		const currentContent = select( editorStore ).getEditedPostContent();
		// eslint-disable-next-line @typescript-eslint/no-unused-vars
		const hasContent = currentContent.length > 0;

		if ( hasActiveConversation ) {
			return __( 'Request changes to the content…', 'classifai' );
		}

		// TODO: Look to support modifying existing content in the future.
		// if ( hasContent ) {
		// 	return __( 'Request changes to the content…', 'classifai' );
		// }

		return __( 'Add a summary of your article', 'classifai' );
	};

	return (
		<Fill name={ chatTabSlug }>
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
					onChange={ ( value ) => setInputValue( value ) }
					onKeyDown={ handleKeyDown }
					isLoading={ isLoading }
					placeholderText={ getPlaceholderText() }
				/>
			</form>
		</Fill>
	);
};

addFilter( 'classifai.chatUI', 'classifai', ( args ) => {
	args.push( {
		name: chatTabSlug,
		title: __( 'Generate Content', 'classifai' ),
	} );
	return args;
} );
