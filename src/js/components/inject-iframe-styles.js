import { useEffect, useRef, createPortal, render } from '@wordpress/element';

import { useEditorCanvas } from '../hooks';

export const InjectIframeStyles = ( { children, title } ) => {
	const iframeCanvas = useEditorCanvas();

	// Reference to the iframe in which we show blocks for preview.
	const iframeRef = useRef( null );

	useEffect( () => {
		if ( ! iframeCanvas || ! iframeRef.current ) {
			return;
		}

		// Get the newly created iframe's document.
		const iframeDocument =
			iframeRef.current.contentDocument ||
			iframeRef.current.contentWindow.document;

		// Copy the styles from the existing iframe (editor canvas).
		const editorIframeDocument =
			iframeCanvas.contentDocument || iframeCanvas.contentWindow.document;
		const iframeStyles = editorIframeDocument.querySelectorAll(
			'link[rel="stylesheet"], style'
		);

		// Append styles (external & internal) to the new iframe's body.
		iframeStyles.forEach( ( style ) => {
			if ( style.tagName === 'LINK' ) {
				iframeDocument.head.appendChild( style.cloneNode( true ) );
			} else if ( style.tagName === 'STYLE' ) {
				const clonedStyle = document.createElement( 'style' );
				clonedStyle.textContent = style.textContent;
				iframeDocument.head.appendChild( clonedStyle );
			}
		} );

		const intervalId = setInterval( () => {
			if ( ! iframeDocument.body ) {
				return;
			}

			iframeDocument.body.classList.add(
				'block-editor-iframe__body',
				'editor-styles-wrapper',
				'post-type-post',
				'admin-color-fresh',
				'wp-embed-responsive'
			);
			iframeDocument.body
				.querySelector( '.is-root-container' )
				.classList.add(
					'is-desktop-preview',
					'is-layout-constrained',
					'wp-block-post-content-is-layout-constrained',
					'has-global-padding',
					'alignfull',
					'wp-block-post-content',
					'block-editor-block-list__layout'
				);

			clearInterval( intervalId );
		}, 100 );

		// Use React Portal to render the children into the iframe container.
		// TODO: Might need to replace with `createPortal` due to React 18.
		const portal = createPortal( children, iframeDocument.body );
		render( portal, iframeDocument.body );
	}, [ iframeCanvas ] );

	if ( ! iframeCanvas ) {
		return null;
	}

	return (
		<div style={ { height: '100vh' } }>
			<iframe
				ref={ iframeRef }
				style={ { width: '100%', height: '100%', border: 'none' } }
				title={ title }
			></iframe>
		</div>
	);
};
