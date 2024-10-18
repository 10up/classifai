/**
 * WordPress dependencies
 */
import domReady from '@wordpress/dom-ready';
import { createRoot, render } from '@wordpress/element';

/**
 * Internal dependencies
 */
import './data/store';
import '../../scss/settings.scss';
import { ClassifAISettings } from './components';

/**
 * Render the ClassifAI settings component.
 */
domReady( () => {
	const adminEl = document.getElementById( 'classifai-settings' );

	if ( adminEl ) {
		if ( createRoot ) {
			const settingsRoot = createRoot( adminEl );
			settingsRoot.render( <ClassifAISettings /> );
		} else {
			render( <ClassifAISettings />, adminEl );
		}
	}
} );
