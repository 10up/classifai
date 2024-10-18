/**
 * WordPress dependencies
 */
import { createContext, useContext } from '@wordpress/element';

/**
 * Context for the FeatureSettings.
 */
export const FeatureContext = createContext( {
	featureName: '',
} );

/**
 * Custom hook to access the FeatureContext.
 *
 * This hook provides a convenient way to access the FeatureContext.
 *
 * @return {Object} The current context value for FeatureContext.
 */
export const useFeatureContext = () => {
	return useContext( FeatureContext );
};
