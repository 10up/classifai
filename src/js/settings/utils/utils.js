import { useSelect } from '@wordpress/data';
import { useMemo } from '@wordpress/element';
import { store as coreStore } from '@wordpress/core-data';

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
	const features = getFeatures();
	return features[ featureName ];
};

export const getFeatures = () => {
	let features = {};
	for ( const key in window.classifAISettings?.features || {} ) {
		features = {
			...features,
			...( window.classifAISettings.features[ key ] || {} ),
		};
	}

	return features;
};

/**
 * Get the initial service based on the URL query.
 *
 * @return {string} The initial service based on the URL query.
 */
export const getInitialService = () => {
	const { services } = window.classifAISettings;
	const urlParams = new URLSearchParams( window.location.search );
	const requestedTab = urlParams.get( 'tab' );
	const initialService = Object.keys( services || {} ).includes(
		requestedTab
	)
		? requestedTab
		: 'language_processing';
	return initialService;
};

/**
 * Get the initial feature based on the URL query.
 *
 * @param {string} service The current service.
 * @return {string} The initial feature based on the URL query.
 */
export const getInitialFeature = ( service ) => {
	const { features } = window.classifAISettings;
	const urlParams = new URLSearchParams( window.location.search );
	const requestedFeature = urlParams.get( 'feature' );
	const serviceFeatures = features[ service ] || {};
	const initialFeature = Object.keys( serviceFeatures ).includes(
		requestedFeature
	)
		? requestedFeature
		: Object.keys( serviceFeatures )[ 0 ] || 'feature_classification';
	return initialFeature;
};

/**
 * Get the scope name for the given string.
 *
 * @param {string} name The name to convert to a valid scope name.
 * @return {string} returns the scope name
 */
export const getScope = ( name ) => {
	return ( name || '' ).replace( /_/g, '-' );
};

/**
 * Check if the provider is configured.
 *
 * @param {Object} featureSettings The feature settings.
 * @return {boolean} True if the provider is configured, false otherwise.
 */
export const isProviderConfigured = ( featureSettings ) => {
	const selectedProvider = featureSettings?.provider;
	if ( ! selectedProvider ) {
		return false;
	}

	return featureSettings[ selectedProvider ]?.authenticated || false;
};

/**
 * Returns a helper object that contains:
 * An `options` object from the available post types, to be passed to a `SelectControl`.
 *
 * @return {Object} The helper object related to post types.
 */
export const usePostTypes = () => {
	const postTypes = useSelect( ( select ) => {
		const { getPostTypes } = select( coreStore );
		const excludedPostTypes = [ 'attachment' ];
		const filteredPostTypes = getPostTypes( { per_page: -1 } )?.filter(
			( { viewable, slug } ) =>
				viewable && ! excludedPostTypes.includes( slug )
		);
		return filteredPostTypes;
	}, [] );

	const postTypesSelectOptions = useMemo(
		() =>
			( postTypes || [] ).map( ( { labels, slug } ) => ( {
				label: labels.singular_name,
				value: slug,
			} ) ),
		[ postTypes ]
	);
	return { postTypesSelectOptions, postTypes };
};
