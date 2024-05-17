export const getSettings = ( state, feature ) =>
	feature ? state.setting?.[ feature ] || state.settings : state.settings;

export const getCurrentService = ( state ) => state.currentService;

export const getCurrentFeature = ( state ) => state.currentFeature;

export const getIsLoaded = ( state ) => state.isLoaded;

export const getIsSaving = ( state ) => state.isSaving;
