import tippy from 'tippy.js';
import 'tippy.js/dist/tippy.css';
import 'tippy.js/themes/light.css';
import '../scss/onboarding.scss';

document.addEventListener( 'DOMContentLoaded', function() {
	const template = document.getElementById( 'help-menu-template' );
	const container = document.createElement( 'div' );
	container.appendChild( document.importNode( template.content, true ) );

	tippy( '.classifai-help', {
		allowHTML: true,
		content: container.innerHTML,
		trigger: 'click', // mouseenter, click, focus; manual.
		placement: 'bottom-end',
		arrow: true,
		// arrowType: 'round',
		animation: 'scale',
		duration: [ 250, 200 ],
		theme: 'light',
		distance: 12,
		interactive: true,
		showOnInit: true,
	} );
} );
