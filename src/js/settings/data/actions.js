export const setFeatureSettings =
	( settings, feature = null ) =>
	( { select, dispatch } ) => {
		const currentFeature = feature || select.getCurrentFeature();
		const featureSettings = select.getFeatureSettings( null, feature );

		dispatch( {
			type: 'SET_FEATURE_SETTINGS',
			feature: currentFeature,
			payload: {
				...featureSettings,
				...settings,
			},
		} );
	};

export const setProviderSettings =
	( provider, settings ) =>
	( { select, dispatch } ) => {
		const currentFeature = select.getCurrentFeature();
		const featureSettings = select.getFeatureSettings();
		dispatch( {
			type: 'SET_FEATURE_SETTINGS',
			feature: currentFeature,
			payload: {
				...featureSettings,
				[ provider ]: {
					...( featureSettings[ provider ] || {} ),
					...settings,
				},
			},
		} );
	};

export const setSettings = ( settings ) => ( {
	type: 'SET_SETTINGS',
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

export const setError = ( error ) => ( {
	type: 'SET_ERROR',
	payload: error,
} );
