import { test, expect } from '../../fixtures/test';
import { getChatGPTData } from '../../fixtures/test-data';

test.describe( '[Language processing] Excerpt Generation Tests', () => {
	test.beforeAll( async ( { browser, requestUtils } ) => {
		try {
			await requestUtils.deactivatePlugin( 'classic-editor' );
		} catch ( _ ) {
			// noop
		}

		const page = await browser.newPage();

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

	test( 'Can save Azure OpenAI "Language Processing" settings', async ( {
		classifaiUtils,
		page,
	} ) => {
		await classifaiUtils.visitFeatureSettings(
			'language_processing/feature_excerpt_generation'
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
		await page.locator( '#excerpt_length' ).fill( '35' );
		await classifaiUtils.saveFeatureSettings();
	} );

	test( 'Can see the generate excerpt button in a post', async ( {
		classifaiUtils,
		page,
	} ) => {
		await page.goto( '/wp-admin/plugins.php' );
		await classifaiUtils.disableClassicEditor();

		const data = getChatGPTData();

		// Create test post.
		await classifaiUtils.createPost( {
			title: 'Test Azure OpenAI post',
			content: 'Test content',
		} );

		// Close post publish panel.
		await classifaiUtils.closePublishPanel();

		// Open post settings sidebar.
		await classifaiUtils.editor.openDocumentSettingsSidebar();

		// Find and open the excerpt panel.
		await page.locator( '.editor-post-excerpt__dropdown button' ).click();

		// Click on button and verify data loads in.
		await page.locator( '.classifai-excerpt-generation button' ).click();
		await expect(
			page.locator(
				'.editor-post-excerpt .editor-post-excerpt__textarea textarea'
			)
		).toHaveValue( data );
	} );

	test( 'Can see the generate excerpt button in a post (Classic Editor)', async ( {
		classifaiUtils,
		page,
	} ) => {
		await classifaiUtils.enableClassicEditor();

		await classifaiUtils.visitFeatureSettings(
			'language_processing/feature_excerpt_generation'
		);
		await classifaiUtils.enableFeature();
		await classifaiUtils.saveFeatureSettings();

		const data = getChatGPTData();

		await page.goto( '/wp-admin/post-new.php?post_type=post' );
		await page.locator( '#title' ).fill( 'Excerpt test classic' );
		// Ensure Visual mode (default), since previous tests may have switched to Text.
		if ( await page.locator( '#content-tmce' ).count() ) {
			await page.locator( '#content-tmce' ).click();
		}
		const contentFrame = page.frameLocator( '#content_ifr' );
		await contentFrame
			.locator( 'body#tinymce' )
			.fill( 'Test GPT content.' );

		// Ensure excerpt metabox is shown.
		await classifaiUtils.showClassicEditorExcerptMetabox();

		// Verify button exists.
		await expect(
			page.locator( '#classifai-excerpt-generation__excerpt-generate-btn' )
		).toBeAttached();

		// Click on button and verify data loads in.
		await page
			.locator( '#classifai-excerpt-generation__excerpt-generate-btn' )
			.click();
		await expect( page.locator( '#excerpt' ) ).toHaveValue( data );

		await classifaiUtils.disableClassicEditor();
	} );
} );
