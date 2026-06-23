/**
 * Internal dependencies
 */
import './index.scss';

const audioControlEl = document.querySelector( '.class-post-audio-controls' );

if ( audioControlEl ) {
	const playBtn = audioControlEl.querySelector( '.dashicons-controls-play' );
	const pauseBtn = audioControlEl.querySelector(
		'.dashicons-controls-pause'
	);
	const headingEl = document.querySelector( '.classifai-post-audio-heading' );
	const audioEl = document.getElementById( 'classifai-post-audio-player' );
	const defaultAria = audioControlEl.ariaLabel;
	const pauseAria = audioControlEl.dataset.ariaPauseAudio;

	let audioPromise = null;
	let isGenerating = false;

	/**
	 * Switches audio playback state.
	 */
	function switchState() {
		if ( audioEl.paused ) {
			audioPromise = audioEl.play();
			pauseBtn.style.display = 'block';
			playBtn.style.display = 'none';
			audioControlEl.ariaLabel = pauseAria;
		} else if ( audioPromise ) {
			audioPromise.then( () => {
				audioEl.pause();
				pauseBtn.style.display = 'none';
				playBtn.style.display = 'block';
				audioControlEl.ariaLabel = defaultAria;
			} );
		}
	}

	/**
	 * Generates the audio on demand (the first time a visitor listens), then
	 * plays it once ready.
	 */
	async function generateAndPlay() {
		if ( isGenerating ) {
			return;
		}

		isGenerating = true;
		audioControlEl.classList.remove( 'has-error' );
		audioControlEl.classList.add( 'is-generating' );

		const originalHeading = headingEl ? headingEl.textContent : '';

		if ( headingEl && audioControlEl.dataset.generatingLabel ) {
			headingEl.textContent = audioControlEl.dataset.generatingLabel;
		}

		try {
			const response = await fetch( audioControlEl.dataset.restUrl, {
				method: 'POST',
				headers: { 'Content-Type': 'application/json' },
				body: JSON.stringify( {
					nonce: audioControlEl.dataset.nonce,
				} ),
			} );
			const data = await response.json();

			if ( data && data.success && data.url ) {
				audioEl.src = data.url;
				audioControlEl.dataset.hasAudio = '1';

				if ( headingEl ) {
					headingEl.textContent = originalHeading;
				}

				switchState();
			} else {
				throw new Error(
					data && data.message ? data.message : 'generation_failed'
				);
			}
		} catch {
			audioControlEl.classList.add( 'has-error' );

			if ( headingEl ) {
				headingEl.textContent =
					audioControlEl.dataset.errorLabel || originalHeading;
			}
		} finally {
			isGenerating = false;
			audioControlEl.classList.remove( 'is-generating' );
		}
	}

	/**
	 * Handles activation of the control via click or keyboard.
	 */
	function handleActivate() {
		if ( isGenerating ) {
			return;
		}

		// Generate on demand the first time, otherwise toggle playback.
		if (
			'1' !== audioControlEl.dataset.hasAudio &&
			audioControlEl.dataset.restUrl
		) {
			generateAndPlay();
		} else {
			switchState();
		}
	}

	audioControlEl.addEventListener( 'click', handleActivate );
	audioControlEl.addEventListener( 'keypress', ( e ) => {
		if ( 'Space' === e.code || 'Enter' === e.code ) {
			e.preventDefault();
			handleActivate();
			audioControlEl.focus();
		}
	} );

	audioEl.addEventListener( 'ended', function () {
		audioEl.currentTime = 0;
		pauseBtn.style.display = 'none';
		playBtn.style.display = 'block';
		audioControlEl.ariaLabel = defaultAria;
	} );
}
