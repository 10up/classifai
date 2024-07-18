/**
 * WordPress dependencies
 */
import domReady from '@wordpress/dom-ready';
import { createRoot } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { ClassifAISettings } from './components';
import './data/store';
import '../../scss/settings.scss';

domReady( () => {
	const root = createRoot( document.getElementById( 'classifai-settings' ) );

	root.render( <ClassifAISettings /> );
} );
