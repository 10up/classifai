/**
 * WordPress dependencies
 */
import { deactivatePlugin, activatePlugin } from '@wordpress/e2e-test-utils';

describe( 'Plugin Activation Notice', () => {
	beforeEach( async () => {
		await deactivatePlugin( 'classifai' );
	} );
	it( 'Should successfully activate.', async () => {
		await activatePlugin( 'classifai' );
		await page.waitForSelector( '#message' );
		await expect( page ).toMatchElement( '#message', { text: 'Plugin activated.' } );
	} );
} );
