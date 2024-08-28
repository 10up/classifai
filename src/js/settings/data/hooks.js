import { useContext } from '@wordpress/element';
import { useDispatch, useSelect } from '@wordpress/data';
import { STORE_NAME } from '../data/store';

import { FeatureContext } from '../components/feature-settings/context';

export const useFeatureSettings = () => {
	let { featureName } = useContext( FeatureContext );

	if ( ! featureName ) {
		featureName = null;
	}

	const { setFeatureSettings } = useDispatch( STORE_NAME );

	return {
		featureName,
		getFeatureSettings: ( key ) =>
			useSelect( ( select ) =>
				select( STORE_NAME ).getFeatureSettings( key, featureName )
			),
		setFeatureSettings: ( settings ) =>
			setFeatureSettings( settings, featureName ),
		getIsSaving: () =>
			useSelect( ( select ) => select( STORE_NAME ).getIsSaving() ),
	};
};
