// Update URL based on the current tab and feature selected
export const updateUrl = ( key, value ) => {
	const urlParams = new URLSearchParams( window.location.search );
	urlParams.set( key, value );

	if ( window.history.pushState ) {
		const newUrl =
			window.location.protocol +
			'//' +
			window.location.host +
			window.location.pathname +
			'?' +
			urlParams.toString() +
			window.location.hash;

		window.history.replaceState( { path: newUrl }, '', newUrl );
	} else {
		window.location.search = urlParams.toString();
	}
};
