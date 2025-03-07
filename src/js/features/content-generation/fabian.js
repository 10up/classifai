import { registerPlugin } from '@wordpress/plugins';
import { createRoot, useEffect, useState, useRef } from '@wordpress/element';
import domReady from '@wordpress/dom-ready';
import { TextareaControl, Button, SVG, Path } from '@wordpress/components';
import { motion, AnimatePresence } from 'motion/react';

function ReturnIcon() {
	return (
		<SVG height="16px" viewBox="0 -960 960 960" width="16px">
			<Path d="M360-240 120-480l240-240 56 56-144 144h488v-160h80v240H272l144 144-56 56Z" />
		</SVG>
	);
}

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

function ChatUI() {
	const [ inputValue, setInputValue ] = useState( '' );
	const [ isExpanded, setIsExpanded ] = useState( false );
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

	// Set up and clean up click outside listener
	useEffect( () => {
		if ( isExpanded ) {
			// Add event listener to main document
			document.addEventListener( 'mousedown', handleClickOutside );

			// Add event listeners to all iframes in the document
			const iframes = document.querySelectorAll( 'iframe' );
			iframes.forEach( ( iframe ) => {
				// Try-catch to handle cross-origin iframe issues
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
		// Handle form submission logic here
		// TODO: Process the user's message and generate a response
		setInputValue( '' );
	};

	const toggleChatUI = () => {
		setIsExpanded( ! isExpanded );
	};

	return (
		<div
			className="classifai-fabian-chat-container"
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
						className="classifai-fabian-chat-ui"
						style={ {
							width: '300px',
							backgroundColor: 'white',
							padding: '12px',
							boxShadow: '0 0 10px rgba(0, 0, 0, 0.1)',
							borderRadius: '8px',
							overflow: 'hidden',
						} }
					>
						<motion.div
							initial={ { opacity: 0, y: 10 } }
							animate={ { opacity: 1, y: 0 } }
							transition={ { delay: 0.1 } }
						>
							<form onSubmit={ handleSubmit }>
								<div style={ { position: 'relative' } }>
									<TextareaControl
										__nextHasNoMarginBottom
										className="classifai-fabian-chat-input"
										placeholder="Ask Fabian anything..."
										value={ inputValue }
										onChange={ ( value ) =>
											setInputValue( value )
										}
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
										} }
									/>
									<Button
										icon={ ReturnIcon }
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
									>
										Send
									</Button>
								</div>
							</form>
						</motion.div>
					</motion.div>
				) : (
					<motion.div
						layoutId="chat-container"
						initial={ { opacity: 0.9 } }
						animate={ { opacity: 1 } }
						exit={ { opacity: 0 } }
						transition={ { type: 'spring', duration: 0.3 } }
						style={ {
							display: 'flex',
							justifyContent: 'center',
							alignItems: 'center',
							backgroundColor: 'white',
							borderRadius: '50%',
							boxShadow: '0 0 10px rgba(0, 0, 0, 0.1)',
							padding: '0',
							width: '48px',
							height: '48px',
						} }
					>
						<motion.div
							layoutId="chat-header"
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
								aria-label="Open Fabian AI assistant"
								className="classifai-fabian-chat-button"
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
	registerPlugin( 'classifai-fabian', {
		render: RenderChatUI,
	} );
} );
