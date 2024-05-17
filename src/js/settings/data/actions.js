export const setSettings = ( settings ) => ( {
	type: 'SET_SETTINGS',
	payload: settings,
} );

export const setFeatureSettings = ( feature, settings ) => ( {
	type: 'SET_FEATURE_SETTINGS',
	feature,
	payload: settings,
} );

export const setCurrentService = ( service ) => ( {
	type: 'SET_CURRENT_SERVICE',
	payload: service,
} );

export const setCurrentFeature = ( feature ) => ( {
	type: 'SET_CURRENT_FEATURE',
	payload: feature,
} );

export const setIsLoaded = ( isLoaded ) => ( {
	type: 'SET_IS_LOADED',
	payload: isLoaded,
} );

export const setIsSaving = ( isSaving ) => ( {
	type: 'SET_IS_SAVING',
	payload: isSaving,
} );
