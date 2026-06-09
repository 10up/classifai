import { test, expect } from '../../fixtures/test';

test.describe( '[Language processing] Resize Content Tests', () => {
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

		await page.goto(
			'/wp-admin/tools.php?page=classifai#/language_processing/feature_content_resizing'
		);
		await expect(
			page.locator( '.components-panel__header h2' ).first()
		).toBeVisible();
		await expect( page.locator( '#classifai-logo' ) ).toBeVisible();
		await expect(
			page.locator( '.classifai-loading-settings' )
		).toHaveCount( 0 );

		const editBtn = page.locator( '.classifai-settings-edit-provider' );
		if ( await editBtn.count() ) {
			await editBtn.first().click();
		}
		await page
			.locator( '.classifai-provider-select select' )
			.selectOption( 'xai_grok' );

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

		await page.locator( '#xai_grok_api_key' ).fill( 'abc123' );

		// Set default condense prompt if needed.
		const condenseDefaultBtn = page.locator(
			'.settings-condense-text-prompt #classifai-prompt-setting-0 .actions-rows button.action__set_default'
		);
		if ( await condenseDefaultBtn.count() ) {
			const text = ( await condenseDefaultBtn.textContent() ) || '';
			if ( text === 'Set as default prompt' ) {
				await condenseDefaultBtn.click();
			}
		}

		// Set default expand prompt if needed.
		const expandDefaultBtn = page.locator(
			'.settings-expand-text-prompt #classifai-prompt-setting-0 .actions-rows button.action__set_default'
		);
		if ( await expandDefaultBtn.count() ) {
			const text = ( await expandDefaultBtn.textContent() ) || '';
			if ( text === 'Set as default prompt' ) {
				await expandDefaultBtn.click();
			}
		}

		const responsePromise = page.waitForResponse(
			( res ) =>
				res.url().includes( '/wp-json/classifai/v1/settings/' ) &&
				res.request().method() === 'POST'
		);
		await page
			.locator( '.classifai-settings-footer button.save-settings-button' )
			.click();
		await responsePromise;

		await page.close();
	} );

	test( 'Resize content feature can grow and shrink content', async ( {
		classifaiUtils,
		editor,
		page,
	} ) => {
		await classifaiUtils.visitFeatureSettings(
			'language_processing/feature_content_resizing'
		);

		await classifaiUtils.enableFeature();
		await classifaiUtils.allowFeatureToAdmin();
		await classifaiUtils.saveFeatureSettings();

		await classifaiUtils.createPost( {
			title: 'Resize content',
			content: 'Hello, world.',
		} );
		await classifaiUtils.focusFirstParagraph();
		await page.locator( '.classifai-resize-content-btn' ).click();
		await page
			.locator( '.components-button', { hasText: 'Expand this text' } )
			.click();
		await expect(
			page.locator( '.classifai-content-resize__result-table tbody tr:first-child .classifai-content-resize__grow-stat', { hasText: '+7 words' } )
		).toBeVisible();
		await expect(
			page.locator( '.classifai-content-resize__result-table tbody tr:first-child .classifai-content-resize__grow-stat', { hasText: '+40 characters' } )
		).toBeVisible();
		await page
			.locator(
				'.classifai-content-resize__result-table tbody tr:first-child button'
			)
			.first()
			.click();
		await expect(
			editor.canvas.locator( '[data-type="core/paragraph"]' ).first()
		).toContainText(
			'Start with the basic building block of one narrative.'
		);

		await classifaiUtils.createPost( {
			title: 'Resize content',
			content:
				'Start with the basic building block of one narrative to begin with the editorial process.',
		} );
		await classifaiUtils.focusFirstParagraph();
		await page.locator( '.classifai-resize-content-btn' ).click();
		await page
			.locator( '.components-button', { hasText: 'Condense this text' } )
			.click();
		await expect(
			page.locator( '.classifai-content-resize__result-table tbody tr:first-child .classifai-content-resize__shrink-stat', { hasText: '-6 words' } )
		).toBeVisible();
		await expect(
			page.locator( '.classifai-content-resize__result-table tbody tr:first-child .classifai-content-resize__shrink-stat', { hasText: '-36 characters' } )
		).toBeVisible();
		await page
			.locator(
				'.classifai-content-resize__result-table tbody tr:first-child button'
			)
			.first()
			.click();
		await expect(
			editor.canvas.locator( '[data-type="core/paragraph"]' ).first()
		).toContainText(
			'Start with the basic building block of one narrative.'
		);
	} );
} );
