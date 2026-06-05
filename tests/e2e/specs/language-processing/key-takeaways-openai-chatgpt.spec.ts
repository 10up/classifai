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
		await classifaiUtils.selectProvider( 'openai_chatgpt' );
		await page.locator( '#openai_chatgpt_api_key' ).fill( 'password' );

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

	test( 'Block is visible on the front-end for logged in and logged out users', async ( {
		page,
		context,
	} ) => {
		await page.goto( '/test-key-takeaways-post/' );
		const items = page.locator(
			'.wp-block-classifai-key-takeaways .wp-block-classifai-key-takeaways__content ul li'
		);
		await expect( items ).toHaveCount( 4 );
		await expect( items.first() ).toContainText(
			'Spring symbolizes renewal and beauty, inspiring creativity and reflection.'
		);

		// Logout by clearing cookies, then visit again.
		await context.clearCookies();
		await page.goto( '/test-key-takeaways-post/' );
		const itemsLogged = page.locator(
			'.wp-block-classifai-key-takeaways .wp-block-classifai-key-takeaways__content ul li'
		);
		await expect( itemsLogged ).toHaveCount( 4 );
		await expect( itemsLogged.first() ).toContainText(
			'Spring symbolizes renewal and beauty, inspiring creativity and reflection.'
		);
	} );

	test( 'Can set multiple custom prompts, select one as the default and delete one.', async ( {
		classifaiUtils,
		page,
	} ) => {
		await classifaiUtils.visitFeatureSettings(
			'language_processing/feature_key_takeaways'
		);

		// Add three custom prompts.
		for ( let i = 0; i < 3; i++ ) {
			await page
				.locator( 'button.components-button.action__add_prompt' )
				.click();
		}
		await expect(
			page.locator(
				'.classifai-prompts div.classifai-field-type-prompt-setting'
			)
		).toHaveCount( 4 );

		// Set the data for each prompt.
		await page
			.locator( '#classifai-prompt-setting-1 .classifai-prompt-title input' )
			.fill( 'First custom prompt' );
		await page
			.locator(
				'#classifai-prompt-setting-1 .classifai-prompt-text textarea'
			)
			.fill( 'This is our first custom prompt' );

		await page
			.locator( '#classifai-prompt-setting-2 .classifai-prompt-title input' )
			.fill( 'Second custom prompt' );
		await page
			.locator(
				'#classifai-prompt-setting-2 .classifai-prompt-text textarea'
			)
			.fill( 'This prompt should be deleted' );
		await page
			.locator( '#classifai-prompt-setting-3 .classifai-prompt-title input' )
			.fill( 'Third custom prompt' );
		await page
			.locator(
				'#classifai-prompt-setting-3 .classifai-prompt-text textarea'
			)
			.fill( 'This is a custom prompt' );

		// Set the third prompt as our default.
		await page
			.locator(
				'#classifai-prompt-setting-3 .actions-rows button.action__set_default'
			)
			.click( { force: true } );

		// Delete the second prompt.
		await page
			.locator(
				'#classifai-prompt-setting-2 .actions-rows button.action__remove_prompt'
			)
			.click( { force: true } );
		await page
			.locator( 'div.components-confirm-dialog button.is-primary' )
			.click();
		await expect(
			page.locator(
				'.classifai-prompts div.classifai-field-type-prompt-setting'
			)
		).toHaveCount( 3 );

		await classifaiUtils.saveFeatureSettings();
	} );

	test( 'Can disable feature', async ( { classifaiUtils, editor } ) => {
		// Disable feature.
		await classifaiUtils.visitFeatureSettings(
			'language_processing/feature_key_takeaways'
		);
		await classifaiUtils.disableFeature();
		await classifaiUtils.saveFeatureSettings();

		// Verify that the feature is not available.
		await classifaiUtils.createPost( {
			title: 'Test Key Takeaways post disabled',
			content: 'Test GPT content',
			publish: false,
		} );
		await editor.insertBlock( { name: 'classifai/key-takeaways' } );
		await editor.publishPost();
		await expect(
			editor.canvas.locator( '.wp-block-classifai-key-takeaways' )
		).toHaveCount( 0 );
	} );

	test( 'Can disable feature by role', async ( {
		classifaiUtils,
		editor,
	} ) => {
		await classifaiUtils.visitFeatureSettings(
			'language_processing/feature_key_takeaways'
		);
		await classifaiUtils.enableFeature();
		await classifaiUtils.saveFeatureSettings();

		// Disable admin role.
		await classifaiUtils.disableFeatureForRoles( 'feature_key_takeaways', [
			'administrator',
		] );

		// Verify that the feature is not available.
		await classifaiUtils.createPost( {
			title: 'Test Key Takeaways post disabled user',
			content: 'Test GPT content',
			publish: false,
		} );
		await editor.insertBlock( { name: 'classifai/key-takeaways' } );
		await editor.publishPost();
		await expect(
			editor.canvas.locator(
				'.wp-block-classifai-key-takeaways .components-placeholder__fieldset'
			)
		).toContainText( 'Key takeaways not currently enabled' );
	} );

	test( 'Can disable feature by user', async ( {
		classifaiUtils,
		editor,
	} ) => {
		// Disable admin role.
		await classifaiUtils.disableFeatureForRoles( 'feature_key_takeaways', [
			'administrator',
		] );

		await classifaiUtils.enableFeatureForUsers( 'feature_key_takeaways', [] );

		// Verify that the feature is not available.
		await classifaiUtils.createPost( {
			title: 'Test Key Takeaways post disabled user',
			content: 'Test GPT content',
			publish: false,
		} );
		await editor.insertBlock( { name: 'classifai/key-takeaways' } );
		await editor.publishPost();
		await expect(
			editor.canvas.locator(
				'.wp-block-classifai-key-takeaways .components-placeholder__fieldset'
			)
		).toContainText( 'Key takeaways not currently enabled' );
	} );

	test( 'User can opt-out of feature', async ( {
		classifaiUtils,
		editor,
	} ) => {
		// Enable user based opt-out.
		await classifaiUtils.enableFeatureOptOut( 'feature_key_takeaways' );

		// opt-out
		await classifaiUtils.optOutFeature( 'feature_key_takeaways' );

		// Verify that the feature is not available.
		await classifaiUtils.createPost( {
			title: 'Test Key Takeaways post disabled',
			content: 'Test GPT content',
			publish: false,
		} );
		await editor.insertBlock( { name: 'classifai/key-takeaways' } );
		await editor.publishPost();
		await expect(
			editor.canvas.locator(
				'.wp-block-classifai-key-takeaways .components-placeholder__fieldset'
			)
		).toContainText( 'Key takeaways not currently enabled' );
	} );
} );
