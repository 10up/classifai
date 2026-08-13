import { test, expect } from '../../fixtures/test';

test.describe( 'Image Generation (OpenAI DALL·E) Tests', () => {
	test.beforeAll( async ( { browser, requestUtils } ) => {
		const page = await browser.newPage();
		await page.goto(
			'/wp-admin/tools.php?page=classifai#/image_processing/feature_image_generation'
		);
		const toggle = page.locator(
			'.classifai-enable-feature-toggle input[type="checkbox"]'
		);
		if ( ! ( await toggle.isChecked() ) ) {
			await page.evaluate( () => {
				window.localStorage.setItem(
					'classifai_dont_ask_credential_reuse',
					'true'
				);
			} );
			await toggle.check();
		}
		const editBtn = page.locator( '.classifai-settings-edit-provider' );
		if ( await editBtn.count() ) {
			await editBtn.first().click();
		}
		await page
			.locator( '.classifai-provider-select select' )
			.selectOption( 'openai_dalle' );
		const responsePromise = page.waitForResponse( ( res ) =>
			res.url().includes( '/wp-json/classifai/v1/settings/' )
		);
		await page
			.locator( '.classifai-settings-footer button.save-settings-button' )
			.click();
		await responsePromise;

		// Opt-in all features
		await page.goto( '/wp-admin/profile.php' );
		const optOuts = page.locator(
			'input[name="classifai_opted_out_features[]"]'
		);
		const total = await optOuts.count();
		let anyChecked = false;
		for ( let i = 0; i < total; i++ ) {
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

	test( 'Can save OpenAI "Image Processing" settings', async ( {
		classifaiUtils,
		page,
	} ) => {
		await classifaiUtils.visitFeatureSettings(
			'image_processing/feature_image_generation'
		);
		await classifaiUtils.selectProvider( 'openai_dalle' );
		await page.locator( '#openai_api_key' ).fill( 'password' );
		await page
			.locator( 'select#openai_dalle_number_of_images' )
			.selectOption( '2' );
		await page
			.locator( 'select#openai_dalle_quality' )
			.selectOption( 'low' );
		await page
			.locator( 'select#openai_dalle_image_size' )
			.selectOption( '1024x1536' );

		await classifaiUtils.allowFeatureToAdmin();

		await classifaiUtils.saveFeatureSettings();
	} );

	test( 'Can generate images in the media modal', async ( {
		classifaiUtils,
		editor,
		page,
	} ) => {
		await classifaiUtils.createPost( {
			title: 'Test DALL-E post',
			content: 'Test content',
		} );

		await classifaiUtils.closePublishPanel();
		await editor.openDocumentSettingsSidebar();
		await classifaiUtils.openFeaturedImageModal();

		await expect( page.locator( '#menu-item-generate' ) ).toBeVisible();
		await page.locator( '#menu-item-generate' ).click();
		await page
			.locator( '.prompt-view .prompt' )
			.fill( 'A sunset over the mountains' );
		await page.locator( '.prompt-view .button-generate' ).click();

		await expect(
			page.locator( '.generated-images ul li' )
		).toHaveCount( 2 );
	} );

	test( 'Can enable/disable image generation feature', async ( {
		classifaiUtils,
	} ) => {
		await classifaiUtils.visitFeatureSettings(
			'image_processing/feature_image_generation'
		);
		await classifaiUtils.disableFeature();
		await classifaiUtils.saveFeatureSettings();

		await classifaiUtils.verifyImageGenerationEnabled( false );

		await classifaiUtils.visitFeatureSettings(
			'image_processing/feature_image_generation'
		);
		await classifaiUtils.enableFeature();
		await classifaiUtils.saveFeatureSettings();

		await classifaiUtils.verifyImageGenerationEnabled( true );
	} );

	test( 'Can generate image directly in media library', async ( {
		classifaiUtils,
		page,
	} ) => {
		await classifaiUtils.visitFeatureSettings(
			'image_processing/feature_image_generation'
		);

		await classifaiUtils.enableFeature();
		await classifaiUtils.allowFeatureToAdmin();
		await classifaiUtils.saveFeatureSettings();

		await page.goto( '/wp-admin/upload.php' );
		await page
			.locator(
				'.wp-has-current-submenu.wp-menu-open li a:has-text("Generate Images")'
			)
			.click();

		await expect( page.locator( '#menu-item-generate' ) ).toBeVisible();

		await page.locator( '#menu-item-generate' ).click();
		await page
			.locator( '.prompt-view .prompt' )
			.fill( 'A sunset over the mountains' );
		await page.locator( '.prompt-view .button-generate' ).click();

		await expect(
			page.locator( '.generated-images ul li' )
		).toHaveCount( 2 );
	} );

	test( 'Can enable/disable image generation feature by role', async ( {
		classifaiUtils,
	} ) => {
		await classifaiUtils.visitFeatureSettings(
			'image_processing/feature_image_generation'
		);
		await classifaiUtils.enableFeature();
		await classifaiUtils.saveFeatureSettings();

		await classifaiUtils.disableFeatureForRoles( 'feature_image_generation', [
			'administrator',
		] );

		await classifaiUtils.verifyImageGenerationEnabled( false );

		await classifaiUtils.enableFeatureForRoles( 'feature_image_generation', [
			'administrator',
		] );

		await classifaiUtils.verifyImageGenerationEnabled( true );
	} );

	test( 'Can enable/disable image generation feature by user', async ( {
		classifaiUtils,
	} ) => {
		await classifaiUtils.disableFeatureForRoles( 'feature_image_generation', [
			'administrator',
		] );

		await classifaiUtils.verifyImageGenerationEnabled( false );

		await classifaiUtils.enableFeatureForUsers( 'feature_image_generation', [
			'admin',
		] );

		await classifaiUtils.verifyImageGenerationEnabled( true );
	} );

	test( 'User can opt-out image generation feature', async ( {
		classifaiUtils,
	} ) => {
		await classifaiUtils.enableFeatureOptOut( 'feature_image_generation' );

		await classifaiUtils.optOutFeature( 'feature_image_generation' );

		await classifaiUtils.verifyImageGenerationEnabled( false );

		await classifaiUtils.optInFeature( 'feature_image_generation' );

		await classifaiUtils.verifyImageGenerationEnabled( true );
	} );
} );
