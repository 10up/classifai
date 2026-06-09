import { test, expect } from '../../fixtures/test';

test.describe( '[Language processing] Key Takeaways Tests', () => {
	test.beforeAll( async ( { browser, requestUtils } ) => {
		try {
			await requestUtils.deactivatePlugin( 'classic-editor' );
		} catch ( _ ) {
			// noop
		}

		const page = await browser.newPage();

		// Configure feature settings.
		await page.goto(
			'/wp-admin/tools.php?page=classifai#/language_processing/feature_key_takeaways'
		);
		await expect(
			page.locator( '.components-panel__header h2' ).first()
		).toBeVisible();
		await expect( page.locator( '#classifai-logo' ) ).toBeVisible();
		await page.evaluate( () => {
			window.localStorage.setItem(
				'classifai_dont_ask_credential_reuse',
				'true'
			);
		} );
		const toggle = page.locator(
			'.classifai-enable-feature-toggle input[type="checkbox"]'
		);
		if ( ! ( await toggle.isChecked() ) ) {
			await toggle.check();
		}
		const savePromise = page.waitForResponse(
			( res ) =>
				res.url().includes( '/wp-json/classifai/v1/settings/' ) &&
				res.request().method() === 'POST'
		);
		await page
			.locator( '.classifai-settings-footer button.save-settings-button' )
			.click();
		await savePromise;

		// Opt in all features.
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

	test( 'Can save Feature settings', async ( { classifaiUtils, page } ) => {
		await classifaiUtils.visitFeatureSettings(
			'language_processing/feature_key_takeaways'
		);
		await expect( page.locator( '#classifai-logo' ) ).toBeVisible();
		await classifaiUtils.selectProvider( 'azure_openai' );
		await page
			.locator( 'input#azure_openai_endpoint_url' )
			.fill( 'https://e2e-test-azure-openai.test/' );
		await page.locator( 'input#azure_openai_api_key' ).fill( 'password' );
		await page.locator( 'input#azure_openai_deployment' ).fill( 'test' );

		await classifaiUtils.enableFeature();
		await classifaiUtils.allowFeatureToAdmin();
		await classifaiUtils.saveFeatureSettings();
	} );

	test( 'Can add the Key Takeaways block in a post', async ( {
		classifaiUtils,
		editor,
	} ) => {
		// Create test post and add our block.
		await classifaiUtils.createPost( {
			title: 'Test Key Takeaways post',
			content: 'Test GPT content',
			publish: false,
		} );
		await editor.insertBlock( { name: 'classifai/key-takeaways' } );
		await editor.publishPost();

		const items = editor.canvas.locator(
			'.wp-block-classifai-key-takeaways .wp-block-classifai-key-takeaways__content ul li'
		);
		await expect( items ).toHaveCount( 4 );
		await expect( items.first() ).toContainText(
			'Spring symbolizes renewal and beauty, inspiring creativity and reflection.'
		);
	} );
} );
