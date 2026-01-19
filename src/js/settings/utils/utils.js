/**
 * WordPress dependencies
 */
import { useSelect, useDispatch } from '@wordpress/data';

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
 * Considers both Feature-level and global Provider configs. When providerProfiles
 * and providerConfigs are passed, falls back to the global config if the feature
 * block does not have authenticated (e.g. when using global credentials).
 *
 * @param {Object} featureSettings    The Feature settings (merged with global when from REST).
 * @param {Object} [providerProfiles] Optional. Provider profiles from Provider-configs.
 * @param {Object} [providerConfigs]  Optional. Global Provider configs.
 * @return {boolean} True if the provider is configured, false otherwise.
 */
export const isProviderConfigured = (
	featureSettings,
	providerProfiles = null,
	providerConfigs = null
) => {
	const selectedProvider = featureSettings?.provider;
	if ( ! selectedProvider ) {
		return false;
	}

	if ( featureSettings[ selectedProvider ]?.authenticated ) {
		return true;
	}

	if ( providerProfiles && providerConfigs ) {
		const profile = getProfileForProvider(
			selectedProvider,
			providerProfiles
		);
		if ( profile && providerConfigs[ profile.profileId ]?.authenticated ) {
			return true;
		}
	}

	return false;
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
	const authenticated = feature[ provider ]?.authenticated;

	return isEnabled && authenticated;
};

/**
 * Returns true if a provider configuration is needed.
 *
 * @param {Object} feature The feature object.
 * @return {boolean} True if the feature is enabled and provider configuration is needed, false otherwise.
 */
export const isProviderConfigurationNeeded = ( feature ) => {
	const isEnabled = '1' === feature.status;
	const provider = feature?.provider;
	const authenticated = feature[ provider ]?.authenticated;

	return isEnabled && ! authenticated;
};

/**
 * Find the profile that contains the given Provider ID.
 *
 * @param {string} providerId Provider ID (e.g. openai_chatgpt).
 * @param {Object} profiles   Provider profiles from Provider-configs { profileId: { provider_ids, credential_fields, label } }.
 * @return {{ profileId: string, credential_fields: string[] }|null} Profile info or null.
 */
export const getProfileForProvider = ( providerId, profiles ) => {
	if ( ! providerId || ! profiles || typeof profiles !== 'object' ) {
		return null;
	}
	for ( const [ id, p ] of Object.entries( profiles ) ) {
		if (
			Array.isArray( p?.provider_ids ) &&
			p.provider_ids.includes( providerId )
		) {
			return {
				profileId: id,
				credential_fields: p.credential_fields || [],
			};
		}
	}
	return null;
};

/**
 * Check if Feature has non-empty credential values for the Provider (Feature-level override).
 *
 * @param {Object}   featureSettings  Feature settings (Provider block).
 * @param {string[]} credentialFields Field names to check (excluding 'authenticated').
 * @return {boolean} True if any credential field is non-empty.
 */
export const hasFeatureLevelCredentials = (
	featureSettings,
	credentialFields
) => {
	if ( ! featureSettings || ! Array.isArray( credentialFields ) ) {
		return false;
	}
	return credentialFields.some(
		( f ) =>
			f !== 'authenticated' &&
			featureSettings[ f ] !== null &&
			featureSettings[ f ] !== undefined &&
			String( featureSettings[ f ] ).trim() !== ''
	);
};
