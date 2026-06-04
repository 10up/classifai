import { test, expect } from '../../fixtures/test';

test.describe( '[Language processing] Smart 404 - Azure OpenAI Tests', () => {
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
		await classifaiUtils.selectProvider( 'azure_openai_embeddings' );
		await page
			.locator( 'input#azure_openai_embeddings_endpoint_url' )
			.fill( 'https://e2e-test-azure-openai.test/' );
		await page
			.locator( 'input#azure_openai_embeddings_api_key' )
			.fill( 'password' );
		await page
			.locator( 'input#azure_openai_embeddings_deployment' )
			.fill( 'test' );

		// Change all settings.
		await page.locator( '#feature_smart_404_num' ).fill( '4' );
		await page.locator( '#feature_smart_404_num_search' ).fill( '7000' );
		await page.locator( '#feature_smart_404_threshold' ).fill( '3.25' );
		await page.locator( '#feature_smart_404_rescore' ).uncheck();
		await page.locator( '#feature_smart_404_fallback' ).check();
		await page
			.locator( '#feature_smart_404_score_function' )
			.selectOption( 'l1_norm' );
		await classifaiUtils.allowFeatureToAdmin();

		// Save settings.
		await classifaiUtils.saveFeatureSettings();

		await classifaiUtils.disableElasticPress();
	} );
} );
