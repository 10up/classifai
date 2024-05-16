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

export const getFeature = ( featureName ) => {
	let features = {};
	for ( const key in window.classifAISettings?.features || {} ) {
		features = {
			...features,
			...( window.classifAISettings.features[ key ] || {} ),
		};
	}

	return features[ featureName ];
};
