import { test, expect } from '../../fixtures/test';
import { getChatGPTData } from '../../fixtures/test-data';

test.describe( '[Language processing] WooCommerce Product Excerpt Generation Tests', () => {
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

	test( 'Enable OpenAI ChatGPT "Language Processing" title settings', async ( {
		classifaiUtils,
		page,
	} ) => {
		await classifaiUtils.visitFeatureSettings(
			'language_processing/feature_title_generation'
		);
		await classifaiUtils.selectProvider( 'openai_chatgpt' );
		await page.locator( '#openai_chatgpt_api_key' ).fill( 'password' );
		await classifaiUtils.enableFeature();
		await classifaiUtils.allowFeatureToAdmin();
		await page
			.locator( '#openai_chatgpt_number_of_suggestions' )
			.fill( '1' );
		await classifaiUtils.saveFeatureSettings();
	} );

	test( 'Can generate and insert product title (Classic Editor)', async ( {
		classifaiUtils,
		page,
	} ) => {
		await classifaiUtils.activateWooCommerce();
		await classifaiUtils.enableClassicEditor();

		const data = getChatGPTData();

		await page.goto( '/wp-admin/post-new.php?post_type=product' );

		// Wait for the page to be fully loaded and initialized.
		await expect( page.locator( '#title' ) ).toBeVisible();
		if ( await page.locator( '#content-tmce' ).count() ) {
			await page.locator( '#content-tmce' ).click();
		}
		await expect( page.locator( '#content_ifr' ) ).toBeAttached();

		await page
			.locator( '#classifai-title-generation__title-generate-btn' )
			.click();
		await expect(
			page.locator( '#classifai-title-generation__modal' )
		).toBeVisible();
		await expect(
			page
				.locator( '.classifai-title-generation__result-item' )
				.first()
				.locator( 'textarea' )
		).toHaveValue( data );

		await page
			.locator( '.classifai-title-generation__select-title' )
			.first()
			.click();
		await expect(
			page.locator( '#classifai-title-generation__modal' )
		).toBeHidden();
		await expect( page.locator( '#title' ) ).toHaveValue( data );

		await classifaiUtils.deactivateWooCommerce();
		await classifaiUtils.disableClassicEditor();
	} );
} );
