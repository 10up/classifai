/**
 * WordPress dependencies
 */
import { deactivatePlugin, activatePlugin, visitAdminPage } from '@wordpress/e2e-test-utils';

describe( 'Plugin Settings Screen', () => {
	beforeEach( async () => {
		await activatePlugin( 'classifai' );
	} );
	afterEach( async () => {
		await deactivatePlugin( 'classifai' );
	} );
	it( 'Should show settings screen.', async () => {
		await visitAdminPage( 'admin.php', 'page=classifai_settings' );
		await expect( page ).toMatchElement( 'label[for="email"]', { text: /Registered Email/i } );
		await expect( page ).toMatchElement( 'label[for="license_key"]', { text: /Registration Key/i } );
	} );
} );
