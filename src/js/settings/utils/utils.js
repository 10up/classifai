/**
 * WordPress dependencies
 */
import { useSelect, useDispatch } from '@wordpress/data';
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

/**
 * Get the feature settings for the given feature name.
 *
 * @param {string} featureName The feature name.
 * @return {Object} The feature settings.
 */
export const getFeature = ( featureName ) => {
	const features = getFeatures();
	return features[ featureName ];
};

/**
 * Get the features object.
 * The features object is a combination of all the features from all the services.
 *
 * @return {Object} The features object.
 */
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

	const excerptPostTypes = useSelect( ( select ) => {
		const { getPostTypes } = select( coreStore );
		const filteredPostTypes = getPostTypes( { per_page: -1 } )?.filter(
			( { viewable, supports } ) => viewable && supports?.excerpt
		);
		return filteredPostTypes;
	}, [] );

	const excerptPostTypesOptions = useMemo( () => {
		return ( excerptPostTypes || [] ).map( ( { labels, slug } ) => ( {
			label: labels.singular_name,
			value: slug,
		} ) );
	}, [ excerptPostTypes ] );

	return { postTypesSelectOptions, postTypes, excerptPostTypesOptions };
};

/**
 * Post Statuses Hook.
 * Returns a helper object that contains:
 * An `options` object from the available post statuses.
 *
 * @return {Object} The helper object related to post statuses.
 */
export const useTaxonomies = () => {
	const taxonomyOptions = useSelect( ( select ) => {
		// Remove the NLUs taxonomies from the list of taxonomies
		const excludedTaxonomies = [
			'watson-category',
			'watson-keyword',
			'watson-concept',
			'watson-entity',
		];
		return select( 'core' )
			.getTaxonomies()
			?.filter( ( { slug } ) => ! excludedTaxonomies.includes( slug ) );
	}, [] );

	const taxonomies = useMemo( () => taxonomyOptions, [ taxonomyOptions ] );

	return { taxonomies };
};

/**
 * User Permissions Preferences Hook.
 *
 * Exports a hook that returns the user permissions preferences.
 * It uses the `core/preferences` store to manage the user permissions panel state.
 * @return {Object} The user permissions preferences.
 */
export const useUserPermissionsPreferences = () => {
	let cache;
	const { set, setPersistenceLayer } =
		useDispatch( 'core/preferences' ) || {};
	if ( setPersistenceLayer ) {
		setPersistenceLayer( {
			async get() {
				if ( cache ) {
					return cache;
				}

				const preferences = JSON.parse(
					window.localStorage.getItem(
						'CLASSIFAI_SETTINGS_PREFERENCES'
					)
				);
				if ( preferences ) {
					cache = preferences;
				} else {
					cache = {};
				}
				return cache;
			},
			set( preferences ) {
				cache = preferences;
				window.localStorage.setItem(
					'CLASSIFAI_SETTINGS_PREFERENCES',
					JSON.stringify( preferences )
				);
			},
		} );
	}

	const isOpen = useSelect( ( select ) => {
		const { get } = select( 'core/preferences' ) || {};
		if ( ! get ) {
			return false;
		}

		const open = get( 'classifai/settings', 'user-permissions-panel-open' );
		if ( open === undefined ) {
			return false;
		}
		return open;
	}, [] );

	const setIsOpen = ( value ) => {
		if ( ! set ) {
			return;
		}

		set( 'classifai/settings', 'user-permissions-panel-open', value );
	};

	return { isOpen, setIsOpen };
};

/**
 * Returns true if a feature is enabled and authenticated.
 *
 * @param {Object} feature The feature object.
 * @return {boolean} True if the feature is enabled and authenticated, false otherwise.
 */
export const isFeatureActive = ( feature ) => {
	const isEnabled = '1' === feature.status;
	const provider = feature?.provider;
	const authenticated = feature[ provider ].authenticated;

	return isEnabled && authenticated;
};

/**
 * Returns the onboarding steps.
 *
 * @return {Array} Array of onboarding steps.
 */
export const getOnboardingSteps = () => {
	return [
		'enable_features',
		'classifai_registration',
		'configure_features',
		'finish',
	];
};

/**
 * Get the next step in the onboarding process.
 *
 * @param {string} currentStep
 * @return {string} The next step in the onboarding process.
 */
export const getNextOnboardingStep = ( currentStep ) => {
	const steps = getOnboardingSteps();
	const currentIndex = steps.indexOf( currentStep );
	return steps[ currentIndex + 1 ];
};
