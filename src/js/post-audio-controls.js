import '../scss/post-audio-controls.scss';

const audioControlEl = document.querySelector( '.class-post-audio-controls' );
const playBtn = document.querySelector( '.dashicons-controls-play' );
const pauseBtn = document.querySelector( '.dashicons-controls-pause' );

if ( audioControlEl ) {
	audioControlEl.addEventListener( 'click', function() {
		const audioEl = document.getElementById( 'classifai-post-audio-player' );

		if ( audioEl.paused ) {
			audioEl.play();
			pauseBtn.style.display = 'block';
			playBtn.style.display = 'none';
		} else {
			audioEl.pause();
			pauseBtn.style.display = 'none';
			playBtn.style.display = 'block';
		}
	} );
}
