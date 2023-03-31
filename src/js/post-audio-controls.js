import '../scss/post-audio-controls.scss';

const audioControlEl = document.querySelector( '.class-post-audio-controls' );
const playBtn = document.querySelector( '.dashicons-controls-play' );
const pauseBtn = document.querySelector( '.dashicons-controls-pause' );

if ( audioControlEl ) {
	const audioEl = document.getElementById( 'classifai-post-audio-player' );

	audioControlEl.addEventListener( 'click', function() {
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

	audioEl.addEventListener( 'ended', function() {
		audioEl.currentTime = 0;
		pauseBtn.style.display = 'none';
		playBtn.style.display = 'block';
	} );
}
