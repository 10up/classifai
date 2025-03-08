import { registerPlugin } from '@wordpress/plugins';
import { createRoot, useEffect, useState, useRef } from '@wordpress/element';
import domReady from '@wordpress/dom-ready';
import {
	TextareaControl,
	Button,
	SVG,
	Path,
	Icon,
} from '@wordpress/components';
import { motion, AnimatePresence } from 'motion/react';
import apiFetch from '@wordpress/api-fetch';
import { select, dispatch } from '@wordpress/data';
import { autop } from '@wordpress/autop';
import { rawHandler } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import { backup, check, copySmall, keyboardReturn } from '@wordpress/icons';
import { useCopyToClipboard } from '@wordpress/compose';

function SparkleIcon() {
	return (
		<SVG
			xmlns="http://www.w3.org/2000/svg"
			version="1.1"
			viewBox="-5.0 -10.0 110.0 135.0"
			height="24px"
			width="24px"
		>
			<Path d="m7.9219 24.914c0.30469 0.09375 0.61719 0.14063 0.93359 0.19922 2.1914 0.41406 4.3477 0.95312 6.4766 1.6094 4.207 1.2969 6.8828 4.0312 8.1523 8.2461 0.66797 2.2266 1.207 4.4727 1.6445 6.75 0.11328 0.60156 0.19141 1.3008 1.0469 1.4375 0.75391-0.21484 0.82422-0.88672 0.93359-1.5 0.41016-2.332 0.99609-4.6133 1.6953-6.875 1.2891-4.1523 3.9961-6.8008 8.1523-8.0664 2.2227-0.67578 4.4688-1.2188 6.7461-1.6562 0.60547-0.11328 1.4141-0.15625 1.4531-0.96875 0.039062-0.85547-0.78906-0.86719-1.3867-1.0156-0.66016-0.16406-1.3398-0.24609-2.0078-0.39844-2.9727-0.67578-5.9648-1.3242-8.5586-3.0312-2.8516-1.8789-4.0625-4.8086-4.8906-7.9375-0.5-1.8867-0.86719-3.8086-1.3086-5.7109-0.11328-0.49609-0.28516-1.0195-0.92969-1-0.5625 0.015625-0.72656 0.50391-0.83594 0.94531-0.19531 0.79297-0.32812 1.6016-0.50391 2.4023-0.67578 3.0664-1.3477 6.1484-3.1602 8.7969-1.832 2.6797-4.6445 3.8125-7.6211 4.6758-1.9688 0.57031-3.9766 0.94922-5.9766 1.375-0.44922 0.097656-0.91406 0.27344-0.9375 0.83984-0.019531 0.57031 0.4375 0.75 0.88281 0.88281z" />
			<path d="m18.391 82.391c1.5312 0.28906 3.0352 0.66406 4.5273 1.125 2.9414 0.90625 4.8086 2.8164 5.6953 5.7656 0.46875 1.5547 0.84375 3.125 1.1484 4.7188 0.078125 0.41797 0.13281 0.90625 0.73047 1.0039 0.52734-0.14844 0.57812-0.62109 0.65234-1.0508 0.28516-1.6289 0.69531-3.2266 1.1836-4.8047 0.89844-2.9023 2.793-4.7539 5.6992-5.6367 1.5547-0.47266 3.1211-0.85156 4.7148-1.1562 0.42187-0.082031 0.98828-0.10938 1.0156-0.67578 0.027344-0.59766-0.55469-0.60547-0.96875-0.71094-0.46094-0.11328-0.9375-0.17188-1.4023-0.27734-2.0781-0.47266-4.168-0.92578-5.9805-2.1172-1.9922-1.3125-2.8398-3.3594-3.418-5.5469-0.34766-1.3203-0.60547-2.6602-0.91406-3.9922-0.078125-0.34766-0.19922-0.71094-0.64844-0.69922-0.39453 0.011718-0.50781 0.35156-0.58203 0.66016-0.13672 0.55469-0.23047 1.1211-0.35156 1.6797-0.47266 2.1445-0.94141 4.2969-2.207 6.1484-1.2812 1.8711-3.2461 2.6641-5.3281 3.2656-1.375 0.39844-2.7773 0.66406-4.1758 0.96094-0.31641 0.066407-0.64062 0.19141-0.65625 0.58594-0.015625 0.39844 0.30469 0.52344 0.61328 0.62109 0.21484 0.054688 0.43359 0.089844 0.65234 0.13281z" />
			<path d="m37.078 51.422c-0.035156 0.83984 0.63672 1.1016 1.2891 1.3008 0.44531 0.13672 0.90625 0.20703 1.3633 0.29297 3.2109 0.60547 6.3711 1.3945 9.4961 2.3594 6.168 1.9023 10.09 5.9102 11.949 12.094 0.98047 3.2617 1.7695 6.5547 2.4102 9.8945 0.16797 0.87891 0.28125 1.9062 1.5352 2.1094 1.1016-0.31641 1.2109-1.3008 1.3672-2.1992 0.60156-3.418 1.457-6.7656 2.4844-10.082 1.8867-6.0859 5.8594-9.9688 11.953-11.824 3.2578-0.99219 6.5508-1.7891 9.8906-2.4258 0.88672-0.16797 2.0742-0.23047 2.1289-1.4219 0.058594-1.2539-1.1602-1.2734-2.0312-1.4883-0.96875-0.23828-1.9648-0.36328-2.9414-0.58594-4.3555-0.99219-8.7422-1.9375-12.551-4.4453-4.1797-2.7539-5.957-7.0508-7.1719-11.637-0.73047-2.7656-1.2695-5.582-1.918-8.3711-0.16797-0.72656-0.42188-1.4922-1.3633-1.4648-0.82812 0.023437-1.0664 0.73828-1.2227 1.3828-0.28516 1.1641-0.48047 2.3516-0.73828 3.5195-0.98828 4.4961-1.9766 9.0156-4.6328 12.895-2.6875 3.9297-6.8125 5.5898-11.176 6.8516-2.8867 0.83594-5.8281 1.3945-8.7617 2.0156-0.64453 0.14453-1.3203 0.40625-1.3594 1.2305z" />
		</SVG>
	);
}

// Loading Dots Component
function LoadingDots() {
	return (
		<div
			style={ {
				display: 'inline-flex',
				alignItems: 'center',
				fontSize: '20px',
			} }
		>
			<motion.span
				initial={ { opacity: 0.3 } }
				animate={ { opacity: 1 } }
				exit={ { opacity: 0.3 } }
				transition={ {
					duration: 0.5,
					repeat: Infinity,
					repeatType: 'reverse',
				} }
				style={ { marginRight: '4px' } }
			>
				.
			</motion.span>
			<motion.span
				initial={ { opacity: 0.3 } }
				animate={ { opacity: 1 } }
				exit={ { opacity: 0.3 } }
				transition={ {
					duration: 0.5,
					repeat: Infinity,
					repeatType: 'reverse',
					delay: 0.2,
				} }
				style={ { marginRight: '4px' } }
			>
				.
			</motion.span>
			<motion.span
				initial={ { opacity: 0.3 } }
				animate={ { opacity: 1 } }
				exit={ { opacity: 0.3 } }
				transition={ {
					duration: 0.5,
					repeat: Infinity,
					repeatType: 'reverse',
					delay: 0.4,
				} }
			>
				.
			</motion.span>
			<span className="screen-reader-text">
				{ __( 'Loading', 'classifai' ) }
			</span>
		</div>
	);
}

// User Message Bubble Component
function UserMessage( { message } ) {
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
				{ message }
			</div>
		</div>
	);
}

// Chat Action Buttons Component
function ChatActionButtons( { onStartOver, onInsertContent, content } ) {
	const [ hasCopied, setHasCopied ] = useState( false );

	const onSuccessfullCopy = () => {
		setHasCopied( true );
		setTimeout( () => {
			setHasCopied( false );
		}, 1500 );
	};
	const copyRef = useCopyToClipboard( content, onSuccessfullCopy );

	return (
		<div
			style={ {
				display: 'flex',
				justifyContent: 'flex-end',
				gap: '2px',
				marginTop: '0',
				marginBottom: '8px',
				flexWrap: 'wrap',
			} }
		>
			<Button
				variant="tertiary"
				isDestructive
				onClick={ onStartOver }
				size="small"
				icon={
					<Icon
						icon={ backup }
						viewBox="0 0 24 24"
						height={ 16 }
						width={ 16 }
					/>
				}
				iconPosition="right"
				style={ { paddingInlineEnd: '4px' } }
			>
				{ __( 'Start Over', 'classifai' ) }
			</Button>
			<Button
				ref={ copyRef }
				variant="tertiary"
				size="small"
				icon={
					hasCopied ? (
						<Icon
							icon={ check }
							viewBox="0 0 24 24"
							height={ 16 }
							width={ 16 }
						/>
					) : (
						<Icon
							icon={ copySmall }
							viewBox="0 0 24 24"
							height={ 16 }
							width={ 16 }
						/>
					)
				}
				disabled={ hasCopied }
				iconPosition="right"
				style={ { paddingInlineEnd: '4px' } }
			>
				{ hasCopied
					? __( 'Copied!', 'classifai' )
					: __( 'Copy', 'classifai' ) }
			</Button>
			<Button
				variant="tertiary"
				onClick={ () => onInsertContent( content ) }
				size="small"
				icon={
					<Icon
						icon={ check }
						viewBox="0 0 24 24"
						height={ 20 }
						width={ 20 }
					/>
				}
				iconPosition="right"
				style={ { paddingInlineEnd: '4px' } }
			>
				{ __( 'Insert', 'classifai' ) }
			</Button>
		</div>
	);
}

// AI Response Bubble Component
function AIResponse( { content } ) {
	return (
		<div
			style={ {
				backgroundColor: '#f0f0f0',
				padding: '10px 14px',
				borderRadius: '18px 18px 18px 0',
				boxShadow: '0 1px 2px rgba(0,0,0,0.1)',
				marginBottom: '8px',
				color: '#333',
				whiteSpace: 'pre-wrap',
				wordBreak: 'break-word',
			} }
		>
			{ content }
		</div>
	);
}

// Loading Response Component
function LoadingResponse() {
	return (
		<div
			style={ {
				display: 'flex',
				justifyContent: 'flex-start',
				marginBottom: '8px',
			} }
		>
			<div
				style={ {
					backgroundColor: '#f0f0f0',
					padding: '10px 14px',
					borderRadius: '18px 18px 18px 0',
					color: '#666',
					fontStyle: 'italic',
					display: 'flex',
					gap: '4px',
					alignItems: 'flex-end',
				} }
			>
				{ __( 'Waiting for response', 'classifai' ) }
				<LoadingDots />
			</div>
		</div>
	);
}

// Conversation Item Component
function ConversationItem( { entry, onStartOver, onInsertContent } ) {
	const hasCompletion = entry.completion !== null;

	return (
		<div style={ { marginBottom: '20px' } }>
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
						style={ {
							display: 'flex',
							justifyContent: 'flex-start',
							marginBottom: '8px',
							alignItems: 'flex-start',
						} }
					>
						<div
							style={ {
								display: 'flex',
								flexDirection: 'column',
								maxWidth: '85%',
							} }
						>
							<AIResponse content={ entry.completion } />
							<ChatActionButtons
								onStartOver={ onStartOver }
								onInsertContent={ onInsertContent }
								content={ entry.completion }
							/>
						</div>
					</motion.div>
				) : (
					<motion.div
						initial={ { opacity: 0 } }
						animate={ { opacity: 1 } }
						exit={ { opacity: 0 } }
					>
						<LoadingResponse />
					</motion.div>
				) }
			</AnimatePresence>
		</div>
	);
}

// Chat History Component
function ChatHistory( { conversation, onStartOver, onInsertContent } ) {
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
}

// Chat Input Component
function ChatInput( {
	value,
	onChange,
	onKeyDown,
	isLoading,
	placeholderText,
} ) {
	return (
		<div style={ { position: 'relative' } }>
			<TextareaControl
				__nextHasNoMarginBottom
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
				disabled={ isLoading }
				isBusy={ isLoading }
			>
				{ isLoading
					? __( 'Sending…', 'classifai' )
					: __( 'Send', 'classifai' ) }
			</Button>
		</div>
	);
}

// Error Message Component
function ErrorMessage( { error } ) {
	if ( ! error ) {
		return null;
	}

	return (
		<div
			style={ {
				color: '#cc1818',
				marginBottom: '10px',
			} }
		>
			{ __( 'Error', 'classifai' ) }: { error }
		</div>
	);
}

// Main Chat UI Component
function ChatUI() {
	const [ inputValue, setInputValue ] = useState( '' );
	const [ isExpanded, setIsExpanded ] = useState( false );
	const [ isLoading, setIsLoading ] = useState( false );
	const [ error, setError ] = useState( false );
	const [ conversation, setConversation ] = useState( [] );
	const chatContainerRef = useRef( null );

	// Function to handle clicks outside the chat UI
	const handleClickOutside = ( event ) => {
		if (
			chatContainerRef.current &&
			! chatContainerRef.current.contains( event.target )
		) {
			setIsExpanded( false );
		}
	};

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

	const handleSubmit = ( event ) => {
		event.preventDefault();
		if ( ! inputValue.trim() ) {
			return;
		}

		const userMessage = inputValue;
		setInputValue( '' );

		// Get post data
		const postId = select( 'core/editor' ).getCurrentPostId();
		const title = select( 'core/editor' ).getEditedPostAttribute( 'title' );

		// Update conversation immediately with user message
		const updatedConversation = [
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
			( res ) => {
				// Update conversation with response
				setConversation( [
					...updatedConversation.slice( 0, -1 ),
					{
						prompt: userMessage,
						completion: res,
					},
				] );
				setError( false );
				setIsLoading( false );
			},
			( err ) => {
				setError( err?.message || 'An error occurred' );
				setIsLoading( false );
			}
		);
	};

	const handleKeyDown = ( event ) => {
		// Submit on Enter key, but not when Shift is pressed
		if ( event.key === 'Enter' && ! event.shiftKey ) {
			event.preventDefault();
			handleSubmit( event );
		}
		// Shift+Enter will add a new line by default (no action needed)
	};

	const toggleChatUI = () => {
		setIsExpanded( ! isExpanded );
	};

	const startOver = () => {
		setConversation( [] );
		setError( false );
	};

	const insertContent = ( content ) => {
		dispatch( 'core/editor' )
			.editPost( {
				content: '',
			} )
			.then( () => {
				dispatch( 'core/block-editor' ).insertBlocks(
					rawHandler( {
						HTML: autop( content ),
					} )
				);
				// Close the chat UI after inserting content
				setIsExpanded( false );
				// Clear the conversation
				setConversation( [] );
			} );
	};

	// Determine placeholder text based on conversation state
	const getPlaceholderText = () => {
		const hasActiveConversation =
			conversation.length > 0 &&
			conversation[ conversation.length - 1 ].completion !== null;

		if ( hasActiveConversation ) {
			return __(
				'Ask a follow-up or request changes to the content…',
				'classifai'
			);
		}

		return __( 'What do you want to write about?', 'classifai' );
	};

	return (
		<div
			className="classifai-chat-container"
			style={ {
				position: 'absolute',
				bottom: '20px',
				right: '20px',
				zIndex: '1000',
			} }
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
						style={ {
							width: '400px',
							maxHeight: '600px',
							backgroundColor: 'white',
							padding: '16px',
							boxShadow:
								'0px 2px 3px 0px rgba(0, 0, 0, 0.05), 0px 4px 5px 0px rgba(0, 0, 0, 0.04), 0px 4px 5px 0px rgba(0, 0, 0, 0.03), 0px 16px 16px 0px rgba(0, 0, 0, 0.02)',
							borderRadius: '8px',
							border: '1px solid #e0e0e0',
							overflow: 'hidden',
							display: 'flex',
							flexDirection: 'column',
						} }
					>
						<motion.div
							initial={ { opacity: 0, y: 10 } }
							animate={ { opacity: 1, y: 0 } }
							transition={ { delay: 0.1 } }
							style={ {
								display: 'flex',
								flexDirection: 'column',
								height: '100%',
								maxHeight: '600px',
								overflow: 'hidden',
							} }
						>
							<div
								style={ {
									marginBottom: '12px',
									fontWeight: 'bold',
									fontSize: '16px',
								} }
							>
								{ __( 'ClassifAI Assistant', 'classifai' ) }
							</div>
							<form onSubmit={ handleSubmit }>
								<ChatHistory
									conversation={ conversation }
									onStartOver={ startOver }
									onInsertContent={ insertContent }
								/>
								<ErrorMessage error={ error } />
								<ChatInput
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
					<motion.div
						layoutId="chat-container"
						initial={ { opacity: 0.9 } }
						animate={ { opacity: 1 } }
						exit={ { opacity: 0.0 } }
						transition={ { type: 'spring', duration: 0.3 } }
						style={ {
							display: 'flex',
							justifyContent: 'center',
							alignItems: 'center',
							backgroundColor: 'white',
							boxShadow: '0 0 10px rgba(0, 0, 0, 0.1)',
							borderRadius: '50%',
							padding: '0',
							width: '48px',
							height: '48px',
						} }
					>
						<motion.div
							style={ {
								display: 'flex',
								justifyContent: 'center',
								alignItems: 'center',
								width: '100%',
								height: '100%',
							} }
						>
							<Button
								icon={ SparkleIcon }
								onClick={ toggleChatUI }
								variant="primary"
								size="small"
								aria-label={ __(
									'Open ClassifAI assistant',
									'classifai'
								) }
								className="classifai-chat-button"
								style={ {
									width: '100%',
									height: '100%',
									borderRadius: '50%',
									display: 'flex',
									justifyContent: 'center',
									alignItems: 'center',
									minWidth: 'unset',
									minHeight: 'unset',
								} }
							/>
						</motion.div>
					</motion.div>
				) }
			</AnimatePresence>
		</div>
	);
}

function RenderChatUI() {
	useEffect( () => {
		const editorIframe = document.querySelector(
			'.editor-visual-editor.is-iframed'
		);
		const rootElement = document.createElement( 'div' );

		editorIframe.parentNode.appendChild( rootElement );

		const root = createRoot( rootElement );
		root.render( <ChatUI /> );

		return () => {
			root.unmount();
			editorIframe.parentNode.removeChild( rootElement );
		};
	}, [] );

	return null;
}

domReady( () => {
	registerPlugin( 'classifai', {
		render: RenderChatUI,
	} );
} );
