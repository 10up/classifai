import { test, expect } from '../../fixtures/test';

test.describe( '[Language processing] Classify Content (Ollama) Tests', () => {
	test.beforeAll( async ( { browser, requestUtils } ) => {
		try {
			await requestUtils.deactivatePlugin( 'classic-editor' );
		} catch ( _ ) {
			// noop
		}

		const page = await browser.newPage();

		// Configure feature settings.
		await page.goto(
			'/wp-admin/tools.php?page=classifai#/language_processing/feature_classification'
		);
		await expect(
			page.locator( '.components-panel__header h2' ).first()
		).toBeVisible();
		// Enable feature.
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

		// Select provider.
		const editBtn = page.locator( '.classifai-settings-edit-provider' );
		if ( await editBtn.count() ) {
			await editBtn.first().click();
		}
		await page
			.locator( '.classifai-provider-select select' )
			.selectOption( 'ollama_embeddings' );
		// Save settings.
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

	test( 'Can save Ollama Embeddings "Language Processing" settings', async ( {
		classifaiUtils,
		page,
	} ) => {
		await classifaiUtils.visitFeatureSettings(
			'language_processing/feature_classification'
		);

		await classifaiUtils.selectProvider( 'ollama_embeddings' );

		await page
			.locator( '#ollama_embeddings_model' )
			.selectOption( 'nomic-embed-text:latest' );
		await page
			.locator(
				'.settings-allowed-post-statuses input#post_status_publish'
			)
			.check();
		await page.locator( '#category-enabled' ).check();
		await page.locator( '#category-threshold' ).fill( '100' );

		await classifaiUtils.saveFeatureSettings();
	} );

	test( 'Can create category and post and category will get auto-assigned', async ( {
		classifaiUtils,
		editor,
		page,
		requestUtils,
	} ) => {
		// Create test term.
		const terms = ( await requestUtils.rest( {
			path: '/wp/v2/categories',
			params: { per_page: 100 },
		} ) ) as Array< { id: number; name: string } >;
		for ( const term of terms ) {
			if ( term.name === 'Uncategorized' ) {
				continue;
			}
			try {
				await requestUtils.rest( {
					method: 'DELETE',
					path: `/wp/v2/categories/${ term.id }`,
					params: { force: true },
				} );
			} catch ( _ ) {
				// noop
			}
		}
		await requestUtils.rest( {
			method: 'POST',
			path: '/wp/v2/categories',
			data: { name: 'Test' },
		} );

		// Create test post.
		await classifaiUtils.createPost( {
			title: 'Test Ollama embeddings',
			content: 'Test embeddings content',
		} );

		// Close post publish panel.
		await classifaiUtils.closePublishPanel();

		// Open post settings sidebar.
		await editor.openDocumentSettingsSidebar();

		// Find and open the category panel.
		const panelButton = page
			.locator(
				'.components-panel__body .components-panel__body-title button:has-text("Categories")'
			)
			.first();
		await panelButton.waitFor();
		const panel = panelButton.locator(
			'xpath=ancestor::*[contains(concat(" ", normalize-space(@class), " "), " components-panel__body ")][1]'
		);
		const cls = ( await panel.getAttribute( 'class' ) ) || '';
		if ( ! cls.includes( 'is-opened' ) ) {
			await panelButton.click();
		}

		// Ensure our test category is checked.
		await expect(
			panel.locator(
				'.editor-post-taxonomies__hierarchical-terms-list .editor-post-taxonomies__hierarchical-terms-choice:first-child input'
			)
		).toBeChecked();
		await expect(
			panel
				.locator( '.editor-post-taxonomies__hierarchical-terms-list' )
				.locator( ':scope > *', { hasText: 'Test' } )
				.first()
		).toBeVisible();
	} );

	test( 'Can see the enable button in a post (Classic Editor)', async ( {
		classifaiUtils,
		page,
	} ) => {
		await classifaiUtils.enableClassicEditor();

		await classifaiUtils.visitFeatureSettings(
			'language_processing/feature_classification'
		);

		await classifaiUtils.enableFeature();
		await page.locator( '.settings-allowed-post-types input#post' ).check();
		await page
			.locator(
				'.settings-allowed-post-statuses input#post_status_publish'
			)
			.check();
		await page.locator( '#category-enabled' ).check();
		await classifaiUtils.saveFeatureSettings();

		// Classic create post.
		await page.goto( '/wp-admin/post-new.php?post_type=post' );
		await page.locator( '#title' ).fill( 'Ollama Embeddings test classic' );
		// Ensure Visual mode (default), since previous tests may have switched to Text.
		if ( await page.locator( '#content-tmce' ).count() ) {
			await page.locator( '#content-tmce' ).click();
		}
		const contentFrame = page.frameLocator( '#content_ifr' );
		await contentFrame
			.locator( 'body#tinymce' )
			.fill( "This feature uses Ollama's Embeddings capabilities." );
		await page.locator( '#publish' ).click();

		await expect(
			page.locator( '#classifai_language_processing_metabox' )
		).toBeAttached();
		await page.locator( '#classifai-process-content' ).check();

		await classifaiUtils.disableClassicEditor();
	} );
} );
