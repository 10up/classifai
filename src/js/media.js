( function( $ )  {

	/**
	 * 
	 * @param {Element} The button being clicked
	 * @param {string} Which endpoint to query
	 * @param {Function|bool} Optional callback to run after the request completes.
	 *  
	 */
	const handleClick = ( { button, endpoint, callback = false } ) => {
		const postID = button.getAttribute( 'data-id' );
		const [ spinner ] = button.getElementsByClassName( 'spinner' );
		
		button.setAttribute( 'disabled', 'disabled' );
		spinner.style.display = 'inline-block';
		spinner.classList.add( 'is-active' );

		const path = `${ endpoint }${ postID }`;
		wp.apiRequest( { path } ).then( () => {
			button.removeAttribute( 'disabled' );
			spinner.style.display = 'none';
			spinner.classList.remove( 'is-active' );
			callback && callback();
		} );
	}

	$( document ).ready( function() {
		wp.media.frame.on( 'edit:attachment', () => {
			
			const altTagsButton = document.getElementById( 'classifai-rescan-alt-tags' );
			const imageTagsButton = document.getElementById( 'classifai-rescan-image-tags' );
			altTagsButton.addEventListener( 'click', e => handleClick( { button: e.target, endpoint: '/classifai/v1/alt-tags/', callback: () => console.log( 'callback' ) } ) );
			imageTagsButton.addEventListener( 'click', e => handleClick( { button: e.target, endpoint: '/classifai/v1/image-tags/' } ) );
		} );
	} );
} )( jQuery ) ;