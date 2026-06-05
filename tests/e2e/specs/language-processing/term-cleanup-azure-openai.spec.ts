import { test, expect } from '../../fixtures/test';

test.describe( '[Language processing] Term Cleanup - Azure OpenAI Tests', () => {
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

	test( "ElasticPress option is hidden if the plugin isn't active", async ( {
		classifaiUtils,
		page,
	} ) => {
		await classifaiUtils.disableElasticPress();

		await classifaiUtils.visitFeatureSettings(
			'language_processing/feature_term_cleanup'
		);

		await expect( page.locator( '#use_ep' ) ).toBeDisabled();
	} );

	test( 'Can save Term Cleanup settings', async ( {
		classifaiUtils,
		page,
	} ) => {
		await classifaiUtils.enableElasticPress();

		await classifaiUtils.visitFeatureSettings(
			'language_processing/feature_term_cleanup'
		);

		// Enable Feature.
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
		await page.locator( '#use_ep' ).check();
		await page.locator( '#category-enabled' ).uncheck();
		await page.locator( '#category-threshold' ).fill( '80' );
		await page.locator( '#post_tag-enabled' ).check();
		await page.locator( '#post_tag-threshold' ).fill( '80' );

		// Save settings.
		await classifaiUtils.saveFeatureSettings();

		// Ensure settings page now exists.
		await page.goto(
			'/wp-admin/tools.php?page=classifai-term-cleanup&tax=post_tag'
		);

		await expect(
			page.locator( '.classifai-wrapper .submit-wrapper' )
		).toBeAttached();

		await classifaiUtils.disableElasticPress();
	} );
} );
