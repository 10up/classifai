/**
 * WordPress dependencies
 */
import domReady from '@wordpress/dom-ready';
import { createRoot } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { ClassifAISettings, ClassifAIOnboarding } from './components';
import './data/store';
import '../../scss/settings.scss';

domReady( () => {
	const onboardingEl = document.getElementById( 'classifai-onboarding' );

	if ( onboardingEl ) {
		const onboardingRoot = createRoot( onboardingEl );
		onboardingRoot.render( <ClassifAIOnboarding /> );
	}

	const settingsEl = document.getElementById( 'classifai-settings' );

	if ( settingsEl ) {
		const settingsRoot = createRoot( settingsEl );
		settingsRoot.render( <ClassifAISettings /> );
	}
} );
