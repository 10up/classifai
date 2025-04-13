import domReady from '@wordpress/dom-ready';
import { registerPlugin } from '@wordpress/plugins';
import { ChatUI } from './components/chat-ui';

// Initialize the plugin when the DOM is ready
domReady( () => {
	registerPlugin( 'classifai-content-generation', {
		render: ChatUI,
	} );
} );
