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
			.selectOption( 'ollama' );

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

		const responsePromise = page.waitForResponse(
			( res ) =>
				res.url().includes( '/wp-json/classifai/v1/settings/' ) &&
				res.request().method() === 'POST'
		);
		await page
			.locator( '.classifai-settings-footer button.save-settings-button' )
			.click();
		await responsePromise;

		await page
			.locator( '#ollama_model' )
			.selectOption( 'deepseek-llm:latest' );

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

		await classifaiUtils.allowFeatureToAdmin();
		await classifaiUtils.saveFeatureSettings();

		await classifaiUtils.createPost( {
			title: 'Resize Ollama content',
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
			title: 'Resize Ollama content',
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
