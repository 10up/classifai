/**
 * External dependencies
 */
import { createContext, useContext } from '@wordpress/element';

export const FeatureContext = createContext( {
	featureName: '',
} );

export const useFeatureContext = () => {
	return useContext( FeatureContext );
};
