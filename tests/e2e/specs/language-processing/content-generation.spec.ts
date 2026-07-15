import { test, expect } from '../../fixtures/test';

test.describe( '[Language processing] Content Generation Tests', () => {
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
			'/wp-admin/tools.php?page=classifai#/language_processing/feature_content_generation'
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

		await page.close();
	} );

	test( 'Can save Azure OpenAI ChatGPT settings', async ( {
		classifaiUtils,
		page,
	} ) => {
		await classifaiUtils.visitFeatureSettings(
			'language_processing/feature_content_generation'
		);
		await expect( page.locator( '#classifai-logo' ) ).toBeVisible();
		await classifaiUtils.selectProvider( 'azure_openai' );
		await page
			.locator( 'input#azure_openai_endpoint_url' )
			.fill( 'https://e2e-test-azure-openai.test/' );
		await page.locator( 'input#azure_openai_api_key' ).fill( 'password' );
		await page.locator( 'input#azure_openai_deployment' ).fill( 'test' );

		await classifaiUtils.saveFeatureSettings();
	} );

	test( 'Can save Ollama settings', async ( { classifaiUtils, page } ) => {
		await classifaiUtils.visitFeatureSettings(
			'language_processing/feature_content_generation'
		);
		await expect( page.locator( '#classifai-logo' ) ).toBeVisible();
		await classifaiUtils.selectProvider( 'ollama' );

		await classifaiUtils.saveFeatureSettings();
		await page
			.locator( '#ollama_model' )
			.selectOption( 'deepseek-llm:latest' );
		await classifaiUtils.saveFeatureSettings();
	} );

	test( 'Can save OpenAI ChatGPT settings', async ( {
		classifaiUtils,
		page,
	} ) => {
		await classifaiUtils.visitFeatureSettings(
			'language_processing/feature_content_generation'
		);
		await expect( page.locator( '#classifai-logo' ) ).toBeVisible();
		await classifaiUtils.selectProvider( 'openai_chatgpt' );
		await page.locator( '#openai_chatgpt_api_key' ).fill( 'password' );

		await classifaiUtils.enableFeature();
		await classifaiUtils.allowFeatureToAdmin();
		await classifaiUtils.saveFeatureSettings();
	} );

	test( 'Can see the generate content button in a post', async ( {
		classifaiUtils,
		page,
	} ) => {
		await page.goto( '/wp-admin/plugins.php' );
		await classifaiUtils.disableClassicEditor();

		// Create test post.
		await classifaiUtils.createPost( {
			title: 'Test Content Generation post',
			content: '',
		} );

		// Close post publish panel.
		await classifaiUtils.closePublishPanel();

		// Open the chat UI.
		await page
			.locator( '.classifai-chat-button' )
			.first()
			.click( { force: true } );
		await expect( page.locator( '.classifai-chat-ui' ) ).toBeVisible();

		// Verify you can add an initial summary and content loads in.
		await page
			.locator( '.classifai-chat-input textarea' )
			.fill( '5 tips for using WordPress' );
		await page.locator( '.classifai-chat-ui button.is-primary' ).click();
		await expect( page.locator( '.classifai-chat-history' ) ).toContainText(
			'Hello there, how may I assist you today?'
		);

		// Verify you can start over.
		await page
			.locator( '.classifai-chat-history' )
			.locator( 'button.is-tertiary.is-destructive' )
			.click();
		await expect( page.locator( '.classifai-chat-history' ) ).toHaveCount(
			0
		);

		// Verify you can iterate on the response.
		await page
			.locator( '.classifai-chat-input textarea' )
			.fill( '5 tips for using WordPress' );
		await page.locator( '.classifai-chat-ui button.is-primary' ).click();
		await expect( page.locator( '.classifai-chat-history' ) ).toContainText(
			'Hello there, how may I assist you today?'
		);
		await page
			.locator( '.classifai-chat-input textarea' )
			.fill( 'Make it longer' );
		await page.locator( '.classifai-chat-ui button.is-primary' ).click();

		// Verify you can insert the content
		await page
			.locator( '.classifai-chat-history' )
			.locator( 'button.is-tertiary' )
			.last()
			.click();
		await expect( page.locator( '.classifai-chat-ui' ) ).toHaveCount( 0 );
	} );

	test( 'Can save Quick Draft integration settings', async ( {
		classifaiUtils,
		page,
	} ) => {
		await classifaiUtils.visitFeatureSettings(
			'language_processing/feature_content_generation'
		);
		await page.locator( '.settings-enable-quick-draft input' ).check();
		await classifaiUtils.saveFeatureSettings();
	} );

	test( 'Can see the generate content button in the Quick Draft widget', async ( {
		page,
	} ) => {
		await page.goto( '/wp-admin/index.php' );
		await expect( page.locator( '#dashboard_quick_press' ) ).toBeVisible();
		await page.locator( '#classifai-generate-content' ).click();

		// Should show error message.
		await expect(
			page.locator( '#dashboard_quick_press .notice' )
		).toContainText(
			'Please enter some content to generate a draft from.'
		);

		// Add title and content.
		await page
			.locator( '#dashboard_quick_press #title' )
			.fill( 'Test Content Generation post' );
		await page
			.locator( '#dashboard_quick_press #content' )
			.fill( '5 tips for using WordPress' );

		// Click the generate button.
		await page.locator( '#classifai-generate-content' ).click();

		// Should show success message.
		await expect(
			page.locator( '#dashboard_quick_press .notice' )
		).toContainText( 'Draft created successfully!' );

		// Refresh the page and verify the draft is created.
		await page.reload();
		await expect(
			page.locator(
				'#dashboard_quick_press .drafts li:first-child .draft-title'
			)
		).toContainText( 'Test Content Generation post' );
	} );

	test( 'Can set multiple custom prompts, select one as the default and delete one.', async ( {
		classifaiUtils,
		page,
	} ) => {
		await classifaiUtils.disableClassicEditor();

		await classifaiUtils.visitFeatureSettings(
			'language_processing/feature_content_generation'
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
			.locator(
				'#classifai-prompt-setting-1 .classifai-prompt-title input'
			)
			.fill( 'First custom prompt' );
		await page
			.locator(
				'#classifai-prompt-setting-1 .classifai-prompt-text textarea'
			)
			.fill( 'This is our first custom prompt' );

		await page
			.locator(
				'#classifai-prompt-setting-2 .classifai-prompt-title input'
			)
			.fill( 'Second custom prompt' );
		await page
			.locator(
				'#classifai-prompt-setting-2 .classifai-prompt-text textarea'
			)
			.fill( 'This prompt should be deleted' );
		await page
			.locator(
				'#classifai-prompt-setting-3 .classifai-prompt-title input'
			)
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

	test( 'Can enable/disable feature', async ( { classifaiUtils } ) => {
		// Disable features.
		await classifaiUtils.visitFeatureSettings(
			'language_processing/feature_content_generation'
		);
		await classifaiUtils.disableFeature();
		await classifaiUtils.saveFeatureSettings();

		// Verify that the feature is not available.
		await classifaiUtils.verifyContentGenerationEnabled( false );

		// Enable feature.
		await classifaiUtils.visitFeatureSettings(
			'language_processing/feature_content_generation'
		);
		await classifaiUtils.enableFeature();
		await classifaiUtils.saveFeatureSettings();

		// Verify that the feature is available.
		await classifaiUtils.verifyContentGenerationEnabled( true );
	} );

	test( 'Can enable/disable feature by role', async ( { classifaiUtils } ) => {
		await classifaiUtils.visitFeatureSettings(
			'language_processing/feature_content_generation'
		);
		await classifaiUtils.enableFeature();
		await classifaiUtils.saveFeatureSettings();

		// Disable admin role.
		await classifaiUtils.disableFeatureForRoles(
			'feature_content_generation',
			[ 'administrator' ]
		);

		// Verify that the feature is not available.
		await classifaiUtils.verifyContentGenerationEnabled( false );

		// enable admin role.
		await classifaiUtils.enableFeatureForRoles(
			'feature_content_generation',
			[ 'administrator' ]
		);

		// Verify that the feature is available.
		await classifaiUtils.verifyContentGenerationEnabled( true );
	} );

	test( 'Can enable/disable feature by user', async ( { classifaiUtils } ) => {
		// Disable admin role.
		await classifaiUtils.disableFeatureForRoles(
			'feature_content_generation',
			[ 'administrator' ]
		);

		await classifaiUtils.enableFeatureForUsers(
			'feature_content_generation',
			[]
		);

		// Verify that the feature is not available.
		await classifaiUtils.verifyContentGenerationEnabled( false );

		// Enable feature for admin user.
		await classifaiUtils.enableFeatureForUsers(
			'feature_content_generation',
			[ 'admin' ]
		);

		// Verify that the feature is available.
		await classifaiUtils.verifyContentGenerationEnabled( true );
	} );

	test( 'User can opt-out of feature', async ( { classifaiUtils } ) => {
		// Enable user based opt-out.
		await classifaiUtils.enableFeatureOptOut(
			'feature_content_generation'
		);

		// opt-out
		await classifaiUtils.optOutFeature( 'feature_content_generation' );

		// Verify that the feature is not available.
		await classifaiUtils.verifyContentGenerationEnabled( false );

		// opt-in
		await classifaiUtils.optInFeature( 'feature_content_generation' );

		// Verify that the feature is available.
		await classifaiUtils.verifyContentGenerationEnabled( true );
	} );
} );
