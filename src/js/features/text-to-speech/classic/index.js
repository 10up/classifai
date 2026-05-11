// Poll admin-ajax to update the Classic Editor TTS meta box once audio generation completes.
const init = () => {
	const status = document.querySelector( '.classifai-tts-status' );

	if ( ! status ) {
		return;
	}

	const postId = parseInt( status.dataset.postId, 10 );
	const nonce = status.dataset.nonce;

	if ( ! postId || ! nonce ) {
		return;
	}

	const POLL_INTERVAL_MS = 10000;

	const renderComplete = ( { error, html } ) => {
		if ( error ) {
			status.textContent = error;
			return;
		}

		if ( html ) {
			status.outerHTML = html;
		}
	};

	const timer = setInterval( async () => {
		try {
			const body = new URLSearchParams( {
				action: 'classifai_get_tts_status',
				nonce,
				post_id: postId,
			} );

			const response = await fetch( window.ajaxurl, {
				method: 'POST',
				credentials: 'same-origin',
				body,
			} );

			if ( ! response.ok ) {
				return;
			}

			const json = await response.json();

			if ( ! json || ! json.success || ! json.data ) {
				return;
			}

			if ( json.data.inProgress ) {
				return;
			}

			clearInterval( timer );
			renderComplete( json.data );
		} catch ( e ) {
			// Swallow transient fetch errors and try again next tick.
		}
	}, POLL_INTERVAL_MS );
};

if ( document.readyState !== 'loading' ) {
	init();
} else {
	document.addEventListener( 'DOMContentLoaded', init );
}
