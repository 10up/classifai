import { useContext, useState, useEffect } from '@wordpress/element';
import { useDispatch, useSelect } from '@wordpress/data';
import { STORE_NAME } from '../data/store';

import { FeatureContext } from '../components/feature-settings/context';

export const useFeatureSettings = () => {
	const [ isSaving, setIsSaving ] = useState( null );
	let { featureName } = useContext( FeatureContext );

	if ( ! featureName ) {
		featureName = null;
	}

	const { setFeatureSettings } = useDispatch( STORE_NAME );
	const __isSaving = useSelect( select => select( STORE_NAME ).getIsSaving() );

	useEffect( () => {
		if ( __isSaving ) {
			setIsSaving( __isSaving );
		} else if ( false === __isSaving && null !== isSaving ) {
			setIsSaving( false );
			setTimeout( () => setIsSaving( null ), 0 );
		}
	}, [ __isSaving ] );

	return {
		isSaving,
		featureName,
		getFeatureSettings: ( key ) => useSelect( select => select( STORE_NAME ).getFeatureSettings( key, featureName ) ),
		getSettings: ( key, featureName ) => useSelect( select => select( STORE_NAME ).getSettings( key, featureName ) ),
		setFeatureSettings: ( settings ) => setFeatureSettings( settings, featureName ),
		getIsSaving: () => useSelect( select => select( STORE_NAME ).getIsSaving() ),
	}
};
