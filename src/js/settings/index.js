/**
 * WordPress dependencies
 */
import domReady from '@wordpress/dom-ready';
import { createRoot } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { Settings } from './components/settings';
import './data/store';
import '../../scss/settings.scss';
import './features'; // TODO: This is for testing purposes only and still in experimental phase.
import './providers'; // TODO: This is for testing purposes only and still in experimental phase.

domReady( () => {
	const root = createRoot( document.getElementById( 'classifai-settings' ) );

	root.render( <Settings /> );
} );
