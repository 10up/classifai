/**
 * WordPress dependencies
 */
import { useContext } from '@wordpress/element';
import { useDispatch, useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_NAME } from '../data/store';
import { FeatureContext } from '../components/feature-settings/context';

/**
 * Custom hook to get and set feature settings.
 *
 * @return {Object} The feature settings.
 */
export const useFeatureSettings = () => {
	let { featureName } = useContext( FeatureContext );

	if ( ! featureName ) {
		featureName = null;
	}

	const { setFeatureSettings } = useDispatch( STORE_NAME );

	const getFeatureSettings = useSelect( ( select ) => {
		const store = select( STORE_NAME );

		return ( key ) => store.getFeatureSettings( key, featureName );
	} );

	return {
		featureName,
		getFeatureSettings,
		setFeatureSettings: ( settings ) =>
			setFeatureSettings( settings, featureName ),
	};
};
