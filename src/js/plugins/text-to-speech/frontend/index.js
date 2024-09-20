import './index.scss';

const audioControlEl = document.querySelector( '.class-post-audio-controls' );
const playBtn = document.querySelector( '.dashicons-controls-play' );
const pauseBtn = document.querySelector( '.dashicons-controls-pause' );
const defaultAria = audioControlEl.ariaLabel;
const pauseAria = audioControlEl.dataset.ariaPauseAudio;

if ( audioControlEl ) {
	const audioEl = document.getElementById( 'classifai-post-audio-player' );
	let audioPromise = null;

	/**
	 * Switches audio playback state.
	 */
	function switchState() {
		if ( audioEl.paused ) {
			audioPromise = audioEl.play();
			pauseBtn.style.display = 'block';
			playBtn.style.display = 'none';
			audioControlEl.ariaLabel = pauseAria;
		} else {
			audioPromise.then( () => {
				audioEl.pause();
				pauseBtn.style.display = 'none';
				playBtn.style.display = 'block';
				audioControlEl.ariaLabel = defaultAria;
			} );
		}
	}

	audioControlEl.addEventListener( 'click', switchState );
	audioControlEl.addEventListener( 'keypress', ( e ) => {
		if ( 'Space' === e.code || 'Enter' === e.code ) {
			e.preventDefault();
			switchState();
			audioControlEl.focus();
		}
	} );

	audioEl.addEventListener( 'ended', function () {
		audioEl.currentTime = 0;
		pauseBtn.style.display = 'none';
		playBtn.style.display = 'block';
	} );
}
