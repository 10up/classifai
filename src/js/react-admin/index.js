import { render } from 'react-dom';
import { AdminApp } from './app';

document.addEventListener( 'DOMContentLoaded', () => {
	const adminSettingsContainer = document.getElementById( 'classifai-container-root' );
	const globalData = window.classifaiReactAdmin || {};
	
	if ( adminSettingsContainer ) {
		render( <AdminApp data={ globalData } />, adminSettingsContainer );
	}
} );
