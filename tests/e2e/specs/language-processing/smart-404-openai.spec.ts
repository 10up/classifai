import { test, expect } from '../../fixtures/test';

test.describe( '[Language processing] Smart 404 - OpenAI Tests', () => {
	test.beforeAll( async ( { browser } ) => {
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

	test( "See error message if ElasticPress isn't activate", async ( {
		classifaiUtils,
		page,
	} ) => {
		await classifaiUtils.disableElasticPress();

		await classifaiUtils.visitFeatureSettings(
			'language_processing/feature_smart_404'
		);

		await expect(
			page.locator( '.elasticpress-required-notice.components-notice' )
		).toBeAttached();
	} );

	test( 'Can save Smart 404 settings', async ( { classifaiUtils, page } ) => {
		await classifaiUtils.enableElasticPress();

		await classifaiUtils.visitFeatureSettings(
			'language_processing/feature_smart_404'
		);

		// Enabled Feature.
		await classifaiUtils.enableFeature();

		// Setup Provider.
		await classifaiUtils.selectProvider( 'openai_embeddings' );
		await page.locator( '#openai_api_key' ).fill( 'password' );

		// Change all settings.
		await page.locator( '#feature_smart_404_num' ).fill( '5' );
		await page.locator( '#feature_smart_404_num_search' ).fill( '8000' );
		await page.locator( '#feature_smart_404_threshold' ).fill( '2.55' );
		await page.locator( '#feature_smart_404_rescore' ).check();
		await page.locator( '#feature_smart_404_fallback' ).uncheck();
		await page
			.locator( '#feature_smart_404_score_function' )
			.selectOption( 'dot_product' );
		await classifaiUtils.allowFeatureToAdmin();

		// Save settings.
		await classifaiUtils.saveFeatureSettings();

		await classifaiUtils.disableElasticPress();
	} );
} );
