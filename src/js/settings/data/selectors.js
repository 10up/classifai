export const getSettings = ( state, feature ) => {
	if ( feature ) {
		return state.settings?.[ feature ] || state.settings;
	}
	return state.settings;
};

export const getFeatureSettings = ( state, key, feature ) => {
	if ( key ) {
		return state.settings?.[ feature || state.currentFeature ]?.[ key ];
	}
	return state.settings?.[ feature || state.currentFeature ] || {};
};

export const getCurrentService = ( state ) => state.currentService;

export const getCurrentFeature = ( state ) => state.currentFeature;

export const getIsLoaded = ( state ) => state.isLoaded;

export const getIsSaving = ( state ) => state.isSaving;

export const getError = ( state ) => state.error;

export const getProviderProfiles = ( state ) => state.providerProfiles;

export const getProviderConfigs = ( state ) => state.providerConfigs;

export const getProviderConfig = ( state, profileId ) =>
	state.providerConfigs?.[ profileId ] ?? null;
