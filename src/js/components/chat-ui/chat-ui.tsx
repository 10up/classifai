import React, {
	useEffect,
	useState,
	useRef,
	useLayoutEffect,
	CSSProperties,
} from 'react';
import { motion, AnimatePresence } from 'motion/react';
import { __ } from '@wordpress/i18n';
import { SlotFillProvider, Slot, TabPanel } from '@wordpress/components';
import { PluginArea } from '@wordpress/plugins';
import { applyFilters } from '@wordpress/hooks';

// Import our custom components
import { SparkleIcon } from './sparkle-icon';

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

const featureTabs = applyFilters( 'classifai.chatUI', [] ) as {
	name: string;
	title: string;
}[];

/**
 * ChatUI component
 *
 * Main component for the ClassifAI chat interface
 *
 * @return {React.ReactElement} The complete chat UI
 */
export const ChatUI: React.FC = () => {
	const [ isExpanded, setIsExpanded ] = useState< boolean >( false );
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

	const toggleChatUI = (): void => {
		setIsExpanded( ! isExpanded );
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
							<SlotFillProvider>
								<PluginArea />
								<TabPanel
									tabs={ featureTabs }
									className="classifai-chat-tabs"
								>
									{ ( tab ) => (
										<div style={ { padding: '1rem 14px' } }>
											<Slot name={ tab.name } />
										</div>
									) }
								</TabPanel>
							</SlotFillProvider>
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
