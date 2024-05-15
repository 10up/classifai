import apiFetch from '@wordpress/api-fetch';
import { useEffect, useState } from '@wordpress/element';

export const useSettings = () => {
	const [ settings, setSettings ] = useState( {} ); // TODO: Set default settings here

	useEffect( () => {
		apiFetch( { path: '/classifai/v1/settings' } ).then( ( res ) => {
			setSettings( res );
		} );
	}, [] );

	const saveSettings = ( featureSettings ) => {
		apiFetch( {
			path: '/classifai/v1/settings',
			method: 'POST',
			data: featureSettings,
		} ).then( ( res ) => {
			setSettings( res );
		} );
	};

	return {
		settings,
		setSettings,
		saveSettings,
	};
};
