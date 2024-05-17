/**
 * WordPress dependencies
 */
import { useSelect, useDispatch } from '@wordpress/data';
import apiFetch from '@wordpress/api-fetch';
import { useEffect } from '@wordpress/element';

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
	const setFeatureSettings = ( feature, data ) =>
		dispatch( STORE_NAME ).setFeatureSettings( feature, data );
	const setIsSaving = ( saving ) =>
		dispatch( STORE_NAME ).setIsSaving( saving );
	const setIsLoaded = ( loaded ) =>
		dispatch( STORE_NAME ).setIsLoaded( loaded );

	// Load settings when the hook is called
	useEffect( () => {
		if ( ! loadSettings ) {
			return;
		}

		( async () => {
			const classifAISettings = await apiFetch( {
				path: '/classifai/v1/settings',
			} ); // TODO: handle error

			setSettings( classifAISettings );
			setIsLoaded( true );
		} )();
	}, [ loadSettings ] );

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
	};
};
