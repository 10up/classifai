/**
 * External dependencies
 */
import React, { useEffect } from 'react';

/**
 * WordPress dependencies
 */
import { createRoot } from '@wordpress/element';
import domReady from '@wordpress/dom-ready';
import { registerPlugin } from '@wordpress/plugins';

/**
 * Internal dependencies
 */
import { ChatUI } from './components/chat-ui';

/**
 * RenderChatUI component
 *
 * Renders the ChatUI component into the editor
 *
 * @return {React.ReactElement|null} Component that renders nothing directly
 */
export const RenderChatUI = () => {
	useEffect( () => {
		const editorIframe = document.querySelector( '.editor-visual-editor' );

		if ( ! editorIframe || ! editorIframe.parentNode ) {
			return;
		}

		const rootElement = document.createElement( 'div' );
		editorIframe.parentNode.appendChild( rootElement );

		const root = createRoot( rootElement );
		root.render( <ChatUI /> );

		return () => {
			root.unmount();
			if ( editorIframe.parentNode && rootElement.parentNode ) {
				editorIframe.parentNode.removeChild( rootElement );
			}
		};
	}, [] );

	return null;
};

// Initialize the plugin when the DOM is ready
domReady( () => {
	registerPlugin( 'classifai', {
		render: RenderChatUI,
	} );
} );
