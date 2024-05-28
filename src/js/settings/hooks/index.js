/**
 * WordPress dependencies
 */
import { useSelect, useDispatch } from '@wordpress/data';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import { STORE_NAME } from '../data/store';

export const useSettings = ( loadSettings = false ) => {
	const dispatch = useDispatch();

	const settings = useSelect( ( select ) =>
		select( STORE_NAME ).getSettings()
	);
	const isLoaded = useSelect( ( select ) =>
		select( STORE_NAME ).getIsLoaded()
	);
	const isSaving = useSelect( ( select ) =>
		select( STORE_NAME ).getIsSaving()
	);
	const currentService = useSelect( ( select ) =>
		select( STORE_NAME ).getCurrentService()
	);
	const currentFeature = useSelect( ( select ) =>
		select( STORE_NAME ).getCurrentFeature()
	);

	const getFeatureSettings = ( feature ) => settings[ feature ] || {};

	const setSettings = ( data ) => dispatch( STORE_NAME ).setSettings( data );
	const setFeatureSettings = ( data ) =>
		dispatch( STORE_NAME ).setFeatureSettings( data );
	const setIsSaving = ( saving ) =>
		dispatch( STORE_NAME ).setIsSaving( saving );
	const setIsLoaded = ( loaded ) =>
		dispatch( STORE_NAME ).setIsLoaded( loaded );
	const setCurrentService = ( service ) =>
		dispatch( STORE_NAME ).setCurrentService( service );
	const setCurrentFeature = ( feature ) =>
		dispatch( STORE_NAME ).setCurrentFeature( feature );

	/**
	 * Save settings for a feature.
	 *
	 * @param {string} featureName Feature name
	 */
	const saveSettings = ( featureName ) => {
		setIsSaving( true );
		apiFetch( {
			path: '/classifai/v1/settings/',
			method: 'POST',
			data: { [ featureName ]: settings[ featureName ] },
		} )
			.then( ( res ) => {
				setSettings( res );
				setIsSaving( false );
			} )
			.catch( ( error ) => {
				// eslint-disable-next-line no-console
				console.error( error ); // TODO: handle error and show a notice
				setIsSaving( false );
			} );
	};

	return {
		settings,
		isLoaded,
		isSaving,
		currentFeature,
		currentService,
		getFeatureSettings,
		setFeatureSettings,
		saveSettings,
		setCurrentService,
		setCurrentFeature,
	};
};
