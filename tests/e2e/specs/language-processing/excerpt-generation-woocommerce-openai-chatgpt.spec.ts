import { test, expect } from '../../fixtures/test';

test.describe( '[Language processing] WooCommerce Product Excerpt Generation Tests', () => {
	test.beforeAll( async ( { browser } ) => {
		const page = await browser.newPage();

		// Visit excerpt generation settings, enable feature and allow post type "post".
		await page.goto(
			'/wp-admin/tools.php?page=classifai#/language_processing/feature_excerpt_generation'
		);
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
		await page.locator( '.settings-allowed-post-types input#post' ).check();

		const responsePromise = page.waitForResponse(
			( res ) =>
				res.url().includes( '/wp-json/classifai/v1/settings/' ) &&
				res.request().method() === 'POST'
		);
		await page
			.locator( '.classifai-settings-footer button.save-settings-button' )
			.click();
		await responsePromise;

		// Opt in to all features.
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

	test( 'Enable OpenAI ChatGPT "Language Processing" excerpt settings', async ( {
		classifaiUtils,
		page,
	} ) => {
		await classifaiUtils.visitFeatureSettings(
			'language_processing/feature_excerpt_generation'
		);
		await classifaiUtils.selectProvider( 'openai_chatgpt' );
		await page.locator( '#openai_chatgpt_api_key' ).fill( 'password' );

		await classifaiUtils.enableFeature();
		await classifaiUtils.allowFeatureToAdmin();
		await page.locator( '#excerpt_length' ).fill( '35' );
		await classifaiUtils.saveFeatureSettings();
	} );

	test( 'Can generate and insert product short description (Classic Editor)', async ( {
		classifaiUtils,
		page,
	} ) => {
		await classifaiUtils.activateWooCommerce();
		await classifaiUtils.enableClassicEditor();

		const expectedResponse = 'Hello there, how may I assist you today?';

		// Create test product and wait for page load.
		await page.goto( '/wp-admin/post-new.php?post_type=product' );

		// Ensure excerpt metabox is shown.
		await classifaiUtils.showClassicEditorExcerptMetabox();

		// Verify button exists.
		await expect(
			page.locator( '#classifai-excerpt-generation__excerpt-generate-btn' )
		).toBeAttached();

		// Click on button and wait for excerpt to be populated.
		await page
			.locator( '#classifai-excerpt-generation__excerpt-generate-btn' )
			.click();

		// Check both TinyMCE and textarea with retries.
		const hasTinyMCE = await page.evaluate( () => {
			return !! (
				( window as any ).tinyMCE &&
				( window as any ).tinyMCE.get( 'excerpt' )
			);
		} );

		if ( hasTinyMCE ) {
			await expect
				.poll(
					async () => {
						return await page.evaluate( () => {
							const content = ( window as any ).tinyMCE
								.get( 'excerpt' )
								.getContent();
							return content.replace( /<\/?p>/g, '' );
						} );
					},
					{ timeout: 10000 }
				)
				.toBe( expectedResponse );
		} else {
			await expect( page.locator( '#excerpt' ) ).toHaveValue(
				expectedResponse,
				{ timeout: 10000 }
			);
		}

		await classifaiUtils.deactivateWooCommerce();
		await classifaiUtils.disableClassicEditor();
	} );
} );
