export const getSettings = ( state, feature ) => {
	if ( feature ) {
		return state.settings?.[ feature ] || state.settings;
	}
	return state.settings;
};

export const getFeatureSettings = ( state, key ) => {
	if ( key ) {
		return state.settings?.[ state.currentFeature ]?.[ key ];
	}
	return state.settings?.[ state.currentFeature ] || {};
};

export const getCurrentService = ( state ) => state.currentService;

export const getCurrentFeature = ( state ) => state.currentFeature;

export const getIsLoaded = ( state ) => state.isLoaded;

export const getIsSaving = ( state ) => state.isSaving;
