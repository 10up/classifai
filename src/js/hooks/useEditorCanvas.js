import { useState, useEffect } from '@wordpress/element';

export const useEditorCanvas = () => {
	const [ iframeCanvas, setIframeCanvas ] = useState( null );

	useEffect( () => {
		let observer;

		/**
		 * Function to check for the editor canvas iframe.
		 *
		 * @return {boolean} True if the iframe is found, false otherwise.
		 */
		const checkForCanvas = () => {
			const __iframeCanvas =
				document.getElementsByName( 'editor-canvas' );
			if ( __iframeCanvas.length > 0 ) {
				setIframeCanvas( __iframeCanvas[ 0 ] );
				return true;
			}
			return false;
		};

		// Perform an initial check in case the iframe already exists.
		if ( ! checkForCanvas() ) {
			// Set up the observer to listen for DOM mutations.
			observer = new MutationObserver( () => {
				if ( checkForCanvas() ) {
					// Disconnect the observer once the iframe is found.
					observer.disconnect();
				}
			} );

			// Observe changes to the DOM body or its descendants.
			observer.observe( document.body, {
				childList: true,
				subtree: true,
			} );
		}

		// Cleanup observer on component unmount.
		return () => observer?.disconnect();
	}, [] );

	return iframeCanvas;
};
