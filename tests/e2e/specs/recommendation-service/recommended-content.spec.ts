import { test } from '../../fixtures/test';

test.describe( '[Recommendation Service] Recommended Content Tests', () => {
	test.beforeAll( async ( { browser, requestUtils } ) => {
		try {
			await requestUtils.deactivatePlugin( 'classic-editor' );
		} catch ( _ ) {
			// noop
		}
		const page = await browser.newPage();
		await page.goto( '/wp-admin/profile.php' );
		const optOuts = page.locator(
			'input[name="classifai_opted_out_features[]"]'
		);
		const count = await optOuts.count();
		let anyChecked = false;
		for ( let i = 0; i < count; i++ ) {
			const cb = optOuts.nth( i );
			if ( await cb.isChecked() ) {
				await cb.uncheck();
				anyChecked = true;
			}
		}
		if ( anyChecked ) {
			await page.locator( '#submit' ).click();
		}
		await page.close();
	} );

	test( 'Can save OpenAI Embedding settings', async ( {
		classifaiUtils,
		page,
	} ) => {
		await classifaiUtils.visitFeatureSettings(
			'content_recommendation/feature_recommended_content'
		);
		await classifaiUtils.enableFeature();
		await classifaiUtils.selectProvider( 'openai_embeddings' );
		await page.locator( '#openai_api_key' ).fill( 'password' );
		await page.locator( '#embedding-threshold' ).fill( '50' );

		await classifaiUtils.allowFeatureToAdmin();
		await classifaiUtils.saveFeatureSettings();
	} );

	test.skip( 'Can add the Recommended Content block in a post', async () => {
		// Skipped in Cypress; preserved as skip in Playwright.
		// Original test inserted the 'core/query/classifai/recommended-content'
		// block via cy.insertBlockCustom and verified it rendered in the editor.
	} );
} );
