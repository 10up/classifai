import { test, expect } from '../../fixtures/test';
import { getGeminiAPIData } from '../../fixtures/test-data';

test.describe( '[Language processing] Title Generation Tests', () => {
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

	test( 'Can save Google AI (Gemini API) "Language Processing" title settings', async ( {
		classifaiUtils,
		page,
	} ) => {
		await classifaiUtils.visitFeatureSettings(
			'language_processing/feature_title_generation'
		);
		await expect( page.locator( '#classifai-logo' ) ).toBeVisible();
		await classifaiUtils.selectProvider( 'googleai_gemini_api' );
		await page
			.locator( 'input#googleai_gemini_api_key' )
			.fill( 'password' );
		await classifaiUtils.enableFeature();
		await classifaiUtils.allowFeatureToAdmin();
		await classifaiUtils.saveFeatureSettings();
	} );

	test( 'Can see the generate titles button in a post', async ( {
		classifaiUtils,
		editor,
		page,
	} ) => {
		const data = getGeminiAPIData();

		await classifaiUtils.createPost( {
			title: 'Test ChatGPT generate titles',
			content: 'Test content',
		} );

		await classifaiUtils.closePublishPanel();
		await editor.openDocumentSettingsSidebar();

		await classifaiUtils.openLegacyPostStatusPanelIfPresent();

		const titleBtn = page
			.locator( '.classifai-post-status button.title' )
			.first();
		await expect( titleBtn ).toBeVisible();
		await titleBtn.click();

		await expect( page.locator( '.title-modal' ) ).toBeVisible();
		await expect(
			page
				.locator( '.title-modal .classifai-title' )
				.first()
				.locator( 'textarea' )
		).toHaveValue( data );

		await page
			.locator( '.title-modal .classifai-title' )
			.first()
			.locator( 'button' )
			.click();

		await expect( page.locator( '.title-modal' ) ).toHaveCount( 0 );
		await expect(
			editor.canvas.locator( '.editor-post-title__input' ).first()
		).toContainText( data );
	} );

	test( 'Can see the generate titles button in a post (Classic Editor)', async ( {
		classifaiUtils,
		page,
	} ) => {
		await classifaiUtils.enableClassicEditor();

		await classifaiUtils.visitFeatureSettings(
			'language_processing/feature_title_generation'
		);
		await classifaiUtils.enableFeature();
		await classifaiUtils.saveFeatureSettings();

		const data = getGeminiAPIData();

		await page.goto( '/wp-admin/post-new.php' );

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

		await classifaiUtils.disableClassicEditor();
	} );
} );
