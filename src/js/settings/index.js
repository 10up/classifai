/**
 * WordPress dependencies
 */
import domReady from '@wordpress/dom-ready';
import { createRoot } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { Settings } from './components/settings';

domReady( () => {
	const root = createRoot( document.getElementById( 'classifai-settings' ) );

	root.render( <Settings /> );
} );
