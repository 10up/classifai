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
			.selectOption( 'openai_chatgpt' );

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

		await page.locator( '#openai_chatgpt_api_key' ).fill( 'abc123' );

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

	test( 'Can set multiple custom resize generation prompts, select one as the default and delete one.', async ( {
		classifaiUtils,
		editor,
		page,
	} ) => {
		await classifaiUtils.visitFeatureSettings(
			'language_processing/feature_content_resizing'
		);

		// Add three custom shrink prompts.
		for ( let i = 0; i < 3; i++ ) {
			await page
				.locator(
					'.settings-condense-text-prompt button.components-button.action__add_prompt'
				)
				.click();
		}
		await expect(
			page.locator(
				'.settings-condense-text-prompt .classifai-prompts div.classifai-field-type-prompt-setting'
			)
		).toHaveCount( 4 );

		// Add three custom grow prompts.
		for ( let i = 0; i < 3; i++ ) {
			await page
				.locator(
					'.settings-expand-text-prompt button.components-button.action__add_prompt'
				)
				.click();
		}
		await expect(
			page.locator(
				'.settings-expand-text-prompt .classifai-prompts div.classifai-field-type-prompt-setting'
			)
		).toHaveCount( 4 );

		// Set the data for each prompt.
		await page
			.locator(
				'.settings-condense-text-prompt #classifai-prompt-setting-1 .classifai-prompt-title input'
			)
			.fill( 'First custom prompt' );
		await page
			.locator(
				'.settings-condense-text-prompt #classifai-prompt-setting-1 .classifai-prompt-text textarea'
			)
			.fill( 'This is our first custom shrink prompt' );

		await page
			.locator(
				'.settings-condense-text-prompt #classifai-prompt-setting-2 .classifai-prompt-title input'
			)
			.fill( 'Second custom prompt' );
		await page
			.locator(
				'.settings-condense-text-prompt #classifai-prompt-setting-2 .classifai-prompt-text textarea'
			)
			.fill( 'This prompt should be deleted' );

		await page
			.locator(
				'.settings-condense-text-prompt #classifai-prompt-setting-3 .classifai-prompt-title input'
			)
			.fill( 'Third custom prompt' );
		await page
			.locator(
				'.settings-condense-text-prompt #classifai-prompt-setting-3 .classifai-prompt-text textarea'
			)
			.fill( 'This is a custom shrink prompt' );

		// Expand prompts.
		await page
			.locator(
				'.settings-expand-text-prompt #classifai-prompt-setting-1 .classifai-prompt-title input'
			)
			.fill( 'First custom prompt' );
		await page
			.locator(
				'.settings-expand-text-prompt #classifai-prompt-setting-1 .classifai-prompt-text textarea'
			)
			.fill( 'This is our first custom grow prompt' );

		await page
			.locator(
				'.settings-expand-text-prompt #classifai-prompt-setting-2 .classifai-prompt-title input'
			)
			.fill( 'Second custom prompt' );
		await page
			.locator(
				'.settings-expand-text-prompt #classifai-prompt-setting-2 .classifai-prompt-text textarea'
			)
			.fill( 'This prompt should be deleted' );
		await page
			.locator(
				'.settings-expand-text-prompt #classifai-prompt-setting-3 .classifai-prompt-title input'
			)
			.fill( 'Third custom prompt' );
		await page
			.locator(
				'.settings-expand-text-prompt #classifai-prompt-setting-3 .classifai-prompt-text textarea'
			)
			.fill( 'This is a custom grow prompt' );

		// Set the third prompt as our default.
		await page
			.locator(
				'.settings-condense-text-prompt #classifai-prompt-setting-3 .actions-rows button.action__set_default'
			)
			.click( { force: true } );

		await page
			.locator(
				'.settings-expand-text-prompt #classifai-prompt-setting-3 .actions-rows button.action__set_default'
			)
			.click( { force: true } );

		// Delete the second prompt.
		await page
			.locator(
				'.settings-condense-text-prompt #classifai-prompt-setting-2 .actions-rows button.action__remove_prompt'
			)
			.click( { force: true } );
		await page
			.locator( 'div.components-confirm-dialog button.is-primary' )
			.click();
		await expect(
			page.locator(
				'.settings-condense-text-prompt .classifai-prompts div.classifai-field-type-prompt-setting'
			)
		).toHaveCount( 3 );

		await page
			.locator(
				'.settings-expand-text-prompt #classifai-prompt-setting-2 .actions-rows button.action__remove_prompt'
			)
			.click( { force: true } );
		await page
			.locator( 'div.components-confirm-dialog button.is-primary' )
			.click();
		await expect(
			page.locator(
				'.settings-expand-text-prompt .classifai-prompts div.classifai-field-type-prompt-setting'
			)
		).toHaveCount( 3 );

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
			page.locator( '.classifai-content-resize__result-table tbody tr:first-child .classifai-content-resize__grow-stat', { hasText: '+6 words' } )
		).toBeVisible();
		await expect(
			page.locator( '.classifai-content-resize__result-table tbody tr:first-child .classifai-content-resize__grow-stat', { hasText: '+31 characters' } )
		).toBeVisible();
		await page
			.locator(
				'.classifai-content-resize__result-table tbody tr:first-child button'
			)
			.first()
			.click();
		await expect(
			editor.canvas.locator( '[data-type="core/paragraph"]' ).first()
		).toContainText( 'Start with the basic block of one narrative.' );

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
			page.locator( '.classifai-content-resize__result-table tbody tr:first-child .classifai-content-resize__shrink-stat', { hasText: '-7 words' } )
		).toBeVisible();
		await expect(
			page.locator( '.classifai-content-resize__result-table tbody tr:first-child .classifai-content-resize__shrink-stat', { hasText: '-45 characters' } )
		).toBeVisible();
		await page
			.locator(
				'.classifai-content-resize__result-table tbody tr:first-child button'
			)
			.first()
			.click();
		await expect(
			editor.canvas.locator( '[data-type="core/paragraph"]' ).first()
		).toContainText( 'Start with the basic block of one narrative.' );
	} );

	test( 'Can enable/disable resize content feature', async ( {
		classifaiUtils,
	} ) => {
		// Disable feature.
		await classifaiUtils.visitFeatureSettings(
			'language_processing/feature_content_resizing'
		);
		await classifaiUtils.disableFeature();
		await classifaiUtils.saveFeatureSettings();

		// Verify that the feature is not available.
		await classifaiUtils.verifyResizeContentEnabled( false );

		// Enable feature.
		await classifaiUtils.visitFeatureSettings(
			'language_processing/feature_content_resizing'
		);
		await classifaiUtils.enableFeature();
		await classifaiUtils.saveFeatureSettings();

		// Verify that the feature is available.
		await classifaiUtils.verifyResizeContentEnabled( true );
	} );

	test( 'Can enable/disable resize content feature by role', async ( {
		classifaiUtils,
	} ) => {
		// Disable admin role.
		await classifaiUtils.disableFeatureForRoles( 'feature_content_resizing', [
			'administrator',
		] );

		// Verify that the feature is not available.
		await classifaiUtils.verifyResizeContentEnabled( false );

		// Enable admin role.
		await classifaiUtils.enableFeatureForRoles( 'feature_content_resizing', [
			'administrator',
		] );

		// Verify that the feature is available.
		await classifaiUtils.verifyResizeContentEnabled( true );
	} );

	test( 'Can enable/disable resize content feature by user', async ( {
		classifaiUtils,
	} ) => {
		// Disable admin role.
		await classifaiUtils.disableFeatureForRoles( 'feature_content_resizing', [
			'administrator',
		] );

		// Verify that the feature is not available.
		await classifaiUtils.verifyResizeContentEnabled( false );

		// Enable feature for admin user.
		await classifaiUtils.enableFeatureForUsers( 'feature_content_resizing', [
			'admin',
		] );

		// Verify that the feature is available.
		await classifaiUtils.verifyResizeContentEnabled( true );
	} );

	test( 'User can opt-out resize content feature', async ( {
		classifaiUtils,
	} ) => {
		// Enable user based opt-out.
		await classifaiUtils.enableFeatureOptOut( 'feature_content_resizing' );

		// opt-out
		await classifaiUtils.optOutFeature( 'feature_content_resizing' );

		// Verify that the feature is not available.
		await classifaiUtils.verifyResizeContentEnabled( false );

		// opt-in
		await classifaiUtils.optInFeature( 'feature_content_resizing' );

		// Verify that the feature is available.
		await classifaiUtils.verifyResizeContentEnabled( true );
	} );
} );
