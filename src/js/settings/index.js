/**
 * WordPress dependencies
 */
import domReady from '@wordpress/dom-ready';
import { createRoot } from '@wordpress/element';

/**
 * Internal dependencies
 */
import './data/store';
import '../../scss/settings.scss';
import { ClassifAISettings } from './components';

domReady( () => {
	const adminEl = document.getElementById( 'classifai-settings' );

	if ( adminEl ) {
		const settingsRoot = createRoot( adminEl );
		settingsRoot.render( <ClassifAISettings /> );
	}
} );
