const { classifAISettings } = window;
const DEFAULT_STATE = {
	currentService: 'language_processing',
	currentFeature: 'classification',
	settings: classifAISettings || {},
	isLoaded: false,
	isSaving: false,
};

/**
 * Reducer for managing the settings data.
 *
 * @param {Object} state  Current state.
 * @param {Object} action Dispatched action.
 *
 * @return {Object} Updated state.
 */
export const reducer = ( state = DEFAULT_STATE, action ) => {
	switch ( action.type ) {
		case 'SET_SETTINGS':
			return {
				...state,
				settings: action.payload,
			};

		case 'SET_FEATURE_SETTINGS':
			return {
				...state,
				settings: {
					...state.settings,
					[ action.feature ]: action.payload,
				},
			};

		case 'SET_CURRENT_SERVICE':
			return {
				...state,
				currentService: action.payload,
			};

		case 'SET_CURRENT_FEATURE':
			return {
				...state,
				currentFeature: action.payload,
			};

		case 'SET_IS_LOADED':
			return {
				...state,
				isLoaded: action.payload,
			};

		case 'SET_IS_SAVING':
			return {
				...state,
				isSaving: action.payload,
			};

		default:
			return state;
	}
};
