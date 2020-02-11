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
		const [ spinner ] = button.parentNode.getElementsByClassName( 'spinner' );
		
		button.setAttribute( 'disabled', 'disabled' );
		spinner.style.display = 'inline-block';
		spinner.classList.add( 'is-active' );

		const path = `${ endpoint }${ postID }`;
		wp.apiRequest( { path } ).then( ( response ) => {
			button.removeAttribute( 'disabled' );
			spinner.style.display = 'none';
			spinner.classList.remove( 'is-active' );
			button.textContent = 'Rescan';
			callback && callback( response );
		} );
	};

	$( document ).ready( function() {
		if ( wp.media.frame ) {
			wp.media.frame.on( 'edit:attachment', () => {
				
				const altTagsButton = document.getElementById( 'classifai-rescan-alt-tags' );
				const imageTagsButton = document.getElementById( 'classifai-rescan-image-tags' );
				altTagsButton.addEventListener( 'click', e => handleClick(
					{ 
						button: e.target, 
						endpoint: '/classifai/v1/alt-tags/', 
						callback: resp => {
							if ( resp ) {
								const textField = document.getElementById( 'attachment-details-two-column-alt-text' );
								textField.value = resp;
							}
						}
					}
				) );
				
				imageTagsButton.addEventListener( 'click', e => handleClick( { button: e.target, endpoint: '/classifai/v1/image-tags/' } ) );
			} );
		}
	} );
} )( jQuery ) ;