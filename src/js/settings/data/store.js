/**
 * WordPress dependencies
 */
import { createReduxStore, register } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { reducer } from './reducer';
import * as selectors from './selectors';
import * as actions from './actions';

export const STORE_NAME = 'classifai-settings';

/**
 * Store for the settings data.
 */
export const store = createReduxStore( STORE_NAME, {
	reducer,
	selectors,
	actions,
} );

// Register the store.
register( store );
